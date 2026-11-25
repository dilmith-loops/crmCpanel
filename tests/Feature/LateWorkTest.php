<?php

namespace Tests\Feature;

use App\Mail\LateWorkApprovedHRNotificationMail;
use App\Mail\LateWorkDecisionUserMail;
use App\Mail\LateWorkSubmittedHODMail;
use App\Mail\LateWorkSubmittedUserConfirmationMail;
use App\Models\Department;
use App\Models\ExpenseCategory;
use App\Models\Holiday;
use App\Models\HolidayType;
use App\Models\LateWorkRequest;
use App\Models\PettyCashItem;
use App\Models\PettyCashProof;
use App\Models\PettyCashRequest;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LateWorkTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $hod;
    private User $hrAdmin;
    private User $management;
    private User $admin;
    private Department $dept;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dept = Department::firstOrCreate(
            ['name' => 'Creative'],
            ['group' => 'SBU', 'status' => 'active']
        );


        $this->admin = User::factory()->create([
            'name' => 'Finance Chief',
            'email' => 'admin@test.com',
            'role' => 'Finance Admin',
        ]);

        $this->management = User::factory()->create([
            'name' => 'Management Director',
            'email' => 'mgmt@test.com',
            'role' => 'Management',
        ]);

        $this->hrAdmin = User::factory()->create([
            'name' => 'HR Administrator',
            'email' => 'hr@test.com',
            'role' => 'HR Admin',
        ]);

        $this->hod = User::factory()->create([
            'name' => 'Creative Head',
            'email' => 'hod_creative@test.com',
            'role' => 'HOD',
            'department' => 'Creative',
        ]);

        // Assign HOD to department
        $this->dept->update(['hod_ids' => [$this->hod->id]]);

        $this->staff = User::factory()->create([
            'name' => 'Creative Writer',
            'email' => 'writer@test.com',
            'role' => 'Staff',
            'department' => 'Creative',
            'supervisor_id' => $this->hod->id,
        ]);
    }

    public function test_sidebar_displays_late_work_tab_for_every_user_role()
    {
        foreach ([$this->staff, $this->hod, $this->hrAdmin, $this->management, $this->admin] as $user) {
            $response = $this->actingAs($user)->get(route('dashboard'));
            $response->assertStatus(200);
            $response->assertSee('Late &amp; Weekend Work', false);
            $response->assertSee(route('late-work.index'));
        }
    }

    public function test_staff_can_view_late_work_index()
    {
        $response = $this->actingAs($this->staff)->get(route('late-work.index'));
        $response->assertStatus(200);
        $response->assertSee('Late &amp; Weekend Work', false);
        $response->assertSee('New request');
        $response->assertSee('My requests');
        $response->assertSee('Instructions');
        $response->assertSee('3.11 After Office Hours');
        $response->assertSee('instructionsModalOpen');
        $response->assertSee('Between 9.00 p.m. - 10.30 p.m.');
        $response->assertSee('Between 10.31 p.m. – 12.00 p.m.');
        $response->assertSee('Between 12.01 – 3.00 a.m.');
        $response->assertSee('Beyond 3.00 a.m.');
    }

    public function test_staff_submits_late_work_request_sends_email_to_hod_and_confirmation_to_user()
    {
        Mail::fake();

        // Target next Wednesday (weekday)
        $nextWednesday = now()->next(\Carbon\Carbon::WEDNESDAY)->toDateString();

        $payload = [
            'type' => 'late',
            'dept_id' => $this->dept->id,
            'date' => $nextWednesday,
            'job_number' => 'LI-2615',
            'job_label' => 'Sample Foods pitch deck',
            'reason' => 'Pitch presentation is tomorrow 9 AM. Need to finish copy scripts.',
            'finish_time' => '22:00',
            'meal' => '1',
            'taxi' => '1',
            'hod_id' => $this->hod->id,
        ];

        $response = $this->actingAs($this->staff)->post(route('late-work.store'), $payload);

        $response->assertRedirect(route('late-work.index', ['tab' => 'mine']));
        $this->assertDatabaseHas('late_work_requests', [
            'user_id' => $this->staff->id,
            'job_number' => 'LI-2615',
            'status' => 'pending',
            'meal' => 1,
            'taxi' => 1,
            'finish_time' => '22:00',
        ]);

        $created = LateWorkRequest::where('job_number', 'LI-2615')->first();
        $this->assertNotNull($created);
        $this->assertEquals('10am', $created->next_day);

        // 1. Assert email sent to HOD for approval
        Mail::assertSent(LateWorkSubmittedHODMail::class, function ($mail) {
            return $mail->hasTo('hod_creative@test.com') &&
                   str_contains($mail->build()->subject, 'Action Required') &&
                   $mail->lateWorkRequest->job_number === 'LI-2615';
        });

        // 2. Assert confirmation email sent to Staff member
        Mail::assertSent(LateWorkSubmittedUserConfirmationMail::class, function ($mail) {
            return $mail->hasTo('writer@test.com') &&
                   str_contains($mail->build()->subject, 'Submitted') &&
                   $mail->lateWorkRequest->job_number === 'LI-2615';
        });
    }

    public function test_hod_can_approve_request_and_emails_are_sent_to_user_and_hr_admin()
    {
        Mail::fake();

        $request = LateWorkRequest::create([
            'reference_number' => 'LW-2026-0001',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'late',
            'date' => now()->next(\Carbon\Carbon::WEDNESDAY)->toDateString(),
            'job_number' => 'LI-2615',
            'job_label' => 'Sample Foods pitch deck',
            'reason' => 'Pitch scripts adaptation',
            'finish_time' => '22:30',
            'meal' => true,
            'taxi' => true,
            'status' => 'pending',
        ]);

        // HOD views approvals tab
        $response = $this->actingAs($this->hod)->get(route('late-work.index', ['tab' => 'approve']));
        $response->assertStatus(200);
        $response->assertSee('LI-2615');

        // HOD approves request with a note
        $approveResponse = $this->actingAs($this->hod)->post(route('late-work.approve', $request), [
            'note' => 'Approved. Please share the final slides before leaving.',
        ]);

        $approveResponse->assertSessionHas('success');
        $request->refresh();

        $this->assertEquals('approved', $request->status);
        $this->assertEquals($this->hod->id, $request->decided_by);
        $this->assertEquals('Approved. Please share the final slides before leaving.', $request->hod_note);
        $this->assertNotNull($request->decided_at);

        // 1. Email sent to user with approval decision
        Mail::assertSent(LateWorkDecisionUserMail::class, function ($mail) {
            return $mail->hasTo('writer@test.com') &&
                   $mail->isApproved === true &&
                   str_contains($mail->build()->subject, 'Approved');
        });

        // 2. Email sent to HR Admin
        Mail::assertSent(LateWorkApprovedHRNotificationMail::class, function ($mail) {
            return $mail->hasTo('hr@test.com') &&
                   str_contains($mail->build()->subject, '[HR Notice] Approved');
        });
    }

    public function test_hod_can_decline_request_and_email_is_sent_to_user()
    {
        Mail::fake();

        $request = LateWorkRequest::create([
            'reference_number' => 'LW-2026-0002',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'late',
            'date' => now()->next(\Carbon\Carbon::WEDNESDAY)->toDateString(),
            'job_number' => 'LI-2607',
            'reason' => 'Audio recording edit',
            'finish_time' => '19:30',
            'meal' => false,
            'taxi' => false,
            'status' => 'pending',
        ]);

        $declineResponse = $this->actingAs($this->hod)->post(route('late-work.decline', $request), [
            'note' => 'Can be completed tomorrow morning.',
        ]);

        $declineResponse->assertSessionHas('success');
        $request->refresh();

        $this->assertEquals('declined', $request->status);
        $this->assertEquals('Can be completed tomorrow morning.', $request->hod_note);

        // Decision email sent to user
        Mail::assertSent(LateWorkDecisionUserMail::class, function ($mail) {
            return $mail->hasTo('writer@test.com') &&
                   $mail->isApproved === false &&
                   str_contains($mail->build()->subject, 'Declined');
        });

        // No HR Admin email sent for declined requests
        Mail::assertNotSent(LateWorkApprovedHRNotificationMail::class);
    }

    public function test_staff_can_withdraw_pending_request()
    {
        $request = LateWorkRequest::create([
            'reference_number' => 'LW-2026-0003',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'late',
            'date' => now()->next(\Carbon\Carbon::THURSDAY)->toDateString(),
            'job_number' => 'LI-2615',
            'reason' => 'Meeting cancelled',
            'finish_time' => '21:00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->staff)->post(route('late-work.withdraw', $request));
        $response->assertSessionHas('success');

        $request->refresh();
        $this->assertEquals('withdrawn', $request->status);
    }

    public function test_hr_admin_and_management_can_view_approved_requests_in_log()
    {
        $request = LateWorkRequest::create([
            'reference_number' => 'LW-2026-0004',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'late',
            'date' => now()->toDateString(),
            'job_number' => 'LI-2612',
            'reason' => 'Billboard print release',
            'finish_time' => '21:30',
            'meal' => true,
            'taxi' => true,
            'status' => 'approved',
            'decided_by' => $this->hod->id,
            'decided_at' => now(),
        ]);

        // 1. HR Admin sees approved request in Log
        $hrResponse = $this->actingAs($this->hrAdmin)->get(route('late-work.index', ['tab' => 'log']));
        $hrResponse->assertStatus(200);
        $hrResponse->assertSee('LI-2612');
        $hrResponse->assertSee('Approved');
        $hrResponse->assertSee('Rs. 1,000');

        // 2. Management user sees approved request in Log directly
        $mgmtResponse = $this->actingAs($this->management)->get(route('late-work.index'));
        $mgmtResponse->assertStatus(200);
        $mgmtResponse->assertSee('Logs &amp; Reports', false);
        $mgmtResponse->assertSee('LI-2612');
        $mgmtResponse->assertSee('Approved');
    }


    public function test_csv_export_generates_valid_csv()
    {
        LateWorkRequest::create([
            'reference_number' => 'LW-2026-0005',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'weekend',
            'date' => now()->next(\Carbon\Carbon::SATURDAY)->toDateString(),
            'start_time' => '10:00',
            'finish_time' => '16:00',
            'job_number' => 'LI-2601',
            'job_label' => 'Social calendar coverage',
            'reason' => 'Weekend client event coverage',
            'meal' => true,
            'taxi' => false,
            'lieu' => 'full',
            'status' => 'approved',
            'decided_by' => $this->hod->id,
            'decided_at' => now(),
        ]);

        $response = $this->actingAs($this->hrAdmin)->get(route('late-work.export-csv'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $content = $response->streamedContent();

        $this->assertStringContainsString('Reference', $content);
        $this->assertStringContainsString('Approved By', $content);
        $this->assertStringContainsString('Approved Time', $content);
        $this->assertStringNotContainsString('Decided By', $content);
        $this->assertStringNotContainsString('Decided At', $content);
        $this->assertStringContainsString('LW-2026-0005', $content);
        $this->assertStringContainsString('LI-2601', $content);
        $this->assertStringContainsString('Creative Writer', $content);
    }

    public function test_admin_can_save_setup_configuration()
    {
        $payload = [
            'departments' => [
                $this->dept->id => [
                    'hod_ids' => [$this->hod->id, $this->admin->id],
                ],
            ],
            'jobs_text' => "LI-2615 | Pitch Deck\nLI-2620 | Credentials",
            'hr_admin_emails' => 'hr_alert@test.com',
        ];

        $response = $this->actingAs($this->admin)->post(route('late-work.save-setup'), $payload);
        $response->assertRedirect(route('late-work.index'));

        $this->dept->refresh();
        $this->assertContains($this->hod->id, $this->dept->hod_ids);
        $this->assertContains($this->admin->id, $this->dept->hod_ids);

        $customJobs = json_decode(Setting::get('late_work_custom_jobs'), true);
        $this->assertCount(2, $customJobs);
        $this->assertEquals('LI-2615', $customJobs[0]['no']);
        $this->assertEquals('hr_alert@test.com', Setting::get('hr_admin_notification_emails'));
    }

    public function test_staff_can_submit_request_with_multiple_job_numbers()
    {
        Mail::fake();

        $nextWednesday = now()->next(\Carbon\Carbon::WEDNESDAY)->toDateString();

        $payload = [
            'type' => 'late',
            'dept_id' => $this->dept->id,
            'date' => $nextWednesday,
            'job_numbers' => ['LOOPS/2026/0469', 'LOOPS/2026/0470'],
            'job_label' => 'Dove Campaign; Sunsilk Promo',
            'reason' => 'Multiple campaign deadlines tonight',
            'finish_time' => '21:30',
            'meal' => 1,
            'taxi' => 0,
        ];

        $response = $this->actingAs($this->staff)->post(route('late-work.store'), $payload);
        $response->assertRedirect(route('late-work.index', ['tab' => 'mine']));

        $record = LateWorkRequest::where('user_id', $this->staff->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals('LOOPS/2026/0469, LOOPS/2026/0470', $record->job_number);
        $this->assertEquals(['LOOPS/2026/0469', 'LOOPS/2026/0470'], $record->job_numbers);
        $this->assertTrue($record->meal);
        $this->assertFalse($record->taxi);
    }

    public function test_allowances_can_be_manually_omitted_without_auto_pick()
    {
        Mail::fake();

        $nextWednesday = now()->next(\Carbon\Carbon::WEDNESDAY)->toDateString();

        $payload = [
            'type' => 'late',
            'dept_id' => $this->dept->id,
            'date' => $nextWednesday,
            'job_number' => 'LOOPS/2026/0469',
            'reason' => 'Finishing presentation tonight, no food or ride requested',
            'finish_time' => '22:00',
            'meal' => 0,
            'taxi' => 0,
        ];

        $response = $this->actingAs($this->staff)->post(route('late-work.store'), $payload);
        $response->assertRedirect(route('late-work.index', ['tab' => 'mine']));

        $record = LateWorkRequest::where('user_id', $this->staff->id)->first();
        $this->assertNotNull($record);
        $this->assertFalse($record->meal);
        $this->assertFalse($record->taxi);
    }

    public function test_hod_sees_teams_requests_and_not_logs_and_reports()
    {
        // Create a team request by staff in HOD's department
        $req = LateWorkRequest::create([
            'reference_number' => 'LW-2026-0099',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'late',
            'date' => now()->toDateString(),
            'job_number' => 'LOOPS/2026/0888',
            'reason' => 'Quarterly report delivery',
            'finish_time' => '22:00',
            'meal' => true,
            'taxi' => true,
            'status' => 'pending',
        ]);

        // 1. HOD views index
        $hodResponse = $this->actingAs($this->hod)->get(route('late-work.index', ['tab' => 'team']));
        $hodResponse->assertStatus(200);
        $hodResponse->assertSee("Team's requests", false);
        $hodResponse->assertSee("Team's Requests", false);
        $hodResponse->assertSee('LOOPS/2026/0888');
        $hodResponse->assertSee('Quarterly report delivery');
        // HOD must NOT see Logs & Reports
        $hodResponse->assertDontSee('Logs &amp; Reports', false);
        $hodResponse->assertDontSee('Export CSV');

        // 2. HR Admin views index
        $hrResponse = $this->actingAs($this->hrAdmin)->get(route('late-work.index'));
        $hrResponse->assertStatus(200);
        $hrResponse->assertSee('Logs &amp; Reports', false);
        $hrResponse->assertDontSee("Team's requests", false);

        // 3. Management views index
        $mgmtResponse = $this->actingAs($this->management)->get(route('late-work.index'));
        $mgmtResponse->assertStatus(200);
        $mgmtResponse->assertSee('Logs &amp; Reports', false);
        $mgmtResponse->assertDontSee("Team's requests", false);
    }

    public function test_user_hod_is_retrieved_from_users_list()
    {
        Mail::fake();

        // Create an HOD in users list
        $specialHod = User::factory()->create([
            'name' => 'Damith Tech Lead',
            'email' => 'damith@test.com',
            'role' => 'HOD',
            'department' => 'Tech',
        ]);

        // Create department without explicit hod_ids
        $techDept = Department::firstOrCreate(
            ['name' => 'Tech'],
            ['group' => 'SBU', 'status' => 'active', 'hod_ids' => []]
        );

        // Create staff user whose supervisor_id in users list is $specialHod
        $developer = User::factory()->create([
            'name' => 'Dilmith Dev',
            'email' => 'dilmith@test.com',
            'role' => 'Staff',
            'department' => 'Tech',
            'supervisor_id' => $specialHod->id,
        ]);

        // 1. Verify view renders assigned HOD from users list
        $indexResponse = $this->actingAs($developer)->get(route('late-work.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Damith Tech Lead');
        $indexResponse->assertSee('name="hod_id"', false);
        $indexResponse->assertSee('value="' . $specialHod->id . '"', false);

        // 2. Submit request without explicit hod_id payload
        $nextWednesday = now()->next(\Carbon\Carbon::WEDNESDAY)->toDateString();
        $payload = [
            'type' => 'late',
            'dept_id' => $techDept->id,
            'date' => $nextWednesday,
            'job_numbers' => ['TECH/2026/001'],
            'reason' => 'Server upgrade tonight',
            'finish_time' => '21:00',
            'meal' => 1,
            'taxi' => 0,
        ];

        $response = $this->actingAs($developer)->post(route('late-work.store'), $payload);
        $response->assertRedirect(route('late-work.index', ['tab' => 'mine']));

        // Verify request in database was assigned to $specialHod from users list
        $request = LateWorkRequest::where('user_id', $developer->id)->first();
        $this->assertNotNull($request);
        $this->assertEquals($specialHod->id, $request->hod_id);

        // Verify email sent to the HOD from users list
        Mail::assertSent(LateWorkSubmittedHODMail::class, function ($mail) use ($specialHod) {
            return $mail->hasTo($specialHod->email);
        });
    }

    public function test_multiple_jobs_are_rendered_paired_and_one_by_one_down_in_emails_and_views()
    {
        $request = LateWorkRequest::create([
            'reference_number' => 'LW-2026-0500',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'late',
            'date' => now()->toDateString(),
            'job_number' => 'LOOPS/2026/0469, LOOPS/2026/0466',
            'job_label' => 'Dove (Duplicated) (Unilever Sri Lankan Limited (USLL)); wonder trade visibilty - Katugasthota and Rathnapura (The Colombo Ice Company PLC)',
            'reason' => 'Finishing dual campaign deliverables',
            'finish_time' => '22:30',
            'meal' => true,
            'taxi' => true,
            'status' => 'pending',
        ]);

        // 1. Verify job_items accessor pairs each job number with its label
        $items = $request->job_items;
        $this->assertCount(2, $items);
        $this->assertEquals('LOOPS/2026/0469', $items[0]['number']);
        $this->assertEquals('Dove (Duplicated) (Unilever Sri Lankan Limited (USLL))', $items[0]['label']);
        $this->assertEquals('LOOPS/2026/0466', $items[1]['number']);
        $this->assertEquals('wonder trade visibilty - Katugasthota and Rathnapura (The Colombo Ice Company PLC)', $items[1]['label']);

        // 2. Verify rendered email contains each job and its description paired, not lumped together
        $mail = new LateWorkSubmittedUserConfirmationMail($request);
        $renderedMail = $mail->render();
        $this->assertStringContainsString('LOOPS/2026/0469', $renderedMail);
        $this->assertStringContainsString('Dove (Duplicated) (Unilever Sri Lankan Limited (USLL))', $renderedMail);
        $this->assertStringContainsString('LOOPS/2026/0466', $renderedMail);
        $this->assertStringContainsString('wonder trade visibilty - Katugasthota and Rathnapura (The Colombo Ice Company PLC)', $renderedMail);
        $this->assertStringNotContainsString('LOOPS/2026/0469, LOOPS/2026/0466 · Dove', $renderedMail);

        // 3. Verify index view displays the items
        $response = $this->actingAs($this->staff)->get(route('late-work.index', ['tab' => 'mine']));
        $response->assertStatus(200);
        $response->assertSee('LOOPS/2026/0469');
        $response->assertSee('LOOPS/2026/0466');
    }

    public function test_decline_requires_a_note_and_sends_note_to_user_in_email()
    {
        Mail::fake();

        $request = LateWorkRequest::create([
            'reference_number' => 'LW-2026-0601',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'late',
            'date' => now()->next(\Carbon\Carbon::WEDNESDAY)->toDateString(),
            'job_number' => 'LOOPS/2026/0991',
            'reason' => 'Video render',
            'finish_time' => '21:00',
            'status' => 'pending',
        ]);

        // 1. Decline without note fails validation
        $failResponse = $this->actingAs($this->hod)->post(route('late-work.decline', $request), [
            'note' => '',
        ]);
        $failResponse->assertSessionHasErrors('note');
        $this->assertEquals('pending', $request->fresh()->status);

        // 2. Decline with note succeeds
        $successResponse = $this->actingAs($this->hod)->post(route('late-work.decline', $request), [
            'note' => 'Please complete during standard hours tomorrow morning.',
        ]);
        $successResponse->assertSessionHas('success');
        $request->refresh();

        $this->assertEquals('declined', $request->status);
        $this->assertEquals('Please complete during standard hours tomorrow morning.', $request->hod_note);

        // 3. Email sent to user contains the decline note
        Mail::assertSent(LateWorkDecisionUserMail::class, function ($mail) use ($request) {
            $rendered = $mail->render();
            return $mail->hasTo('writer@test.com') &&
                   $mail->isApproved === false &&
                   str_contains($rendered, 'Please complete during standard hours tomorrow morning.') &&
                   str_contains($rendered, 're-appeal');
        });
    }

    public function test_staff_can_reappeal_declined_request_and_routes_back_to_hod()
    {
        Mail::fake();

        $request = LateWorkRequest::create([
            'reference_number' => 'LW-2026-0602',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'late',
            'date' => now()->next(\Carbon\Carbon::WEDNESDAY)->toDateString(),
            'job_number' => 'LOOPS/2026/0992',
            'reason' => 'Pitch deck design',
            'finish_time' => '22:00',
            'status' => 'declined',
            'hod_note' => 'Budget limit for night work exceeded.',
            'decided_by' => $this->hod->id,
            'decided_at' => now(),
        ]);

        // 1. Unauthorized user cannot re-appeal
        $otherStaff = User::factory()->create(['role' => 'Staff']);
        $unauthResponse = $this->actingAs($otherStaff)->post(route('late-work.reappeal', $request), [
            'reappeal_notes' => 'I want to reappeal this',
        ]);
        $unauthResponse->assertSessionHas('error');
        $this->assertEquals('declined', $request->fresh()->status);

        // 2. Requester re-appeals with justification
        $reappealResponse = $this->actingAs($this->staff)->post(route('late-work.reappeal', $request), [
            'reappeal_notes' => 'Client just confirmed presentation time moved to 8:30 AM tomorrow. Critical to finalize tonight.',
            'finish_time' => '21:30',
            'meal' => 1,
            'taxi' => 0,
        ]);

        $reappealResponse->assertRedirect(route('late-work.index', ['tab' => 'mine']));
        $request->refresh();

        $this->assertEquals('pending', $request->status);
        $this->assertEquals('Client just confirmed presentation time moved to 8:30 AM tomorrow. Critical to finalize tonight.', $request->reappeal_notes);
        $this->assertEquals(1, $request->reappeal_count);
        $this->assertNull($request->decided_by);
        $this->assertNull($request->decided_at);
        $this->assertEquals('21:30', $request->finish_time);

        // 3. Email sent to HOD contains re-appeal justification
        Mail::assertSent(LateWorkSubmittedHODMail::class, function ($mail) use ($request) {
            $rendered = $mail->render();
            return $mail->hasTo('hod_creative@test.com') &&
                   str_contains($mail->build()->subject, '[Re-appeal]') &&
                   str_contains($rendered, 'Client just confirmed presentation time moved to 8:30 AM tomorrow') &&
                   str_contains($rendered, 'Budget limit for night work exceeded.');
        });

        // 4. Confirmation email sent to staff
        Mail::assertSent(LateWorkSubmittedUserConfirmationMail::class, function ($mail) {
            return $mail->hasTo('writer@test.com');
        });

        // 5. HOD can see re-appeal and approve it
        $approveResponse = $this->actingAs($this->hod)->post(route('late-work.approve', $request), [
            'note' => 'Approved on re-appeal due to client morning deadline.',
        ]);
        $approveResponse->assertSessionHas('success');
        $this->assertEquals('approved', $request->fresh()->status);
    }

    public function test_calculate_next_day_report_matches_official_weekday_late_work_policy_table()
    {
        // 1. Between 9.00 p.m. - 10.30 p.m. => 10.00 a.m.
        $r1 = LateWorkRequest::calculateNextDayReport('21:00');
        $this->assertEquals('10am', $r1['key']);
        $this->assertEquals('10:00 AM', $r1['label']);

        $r2 = LateWorkRequest::calculateNextDayReport('22:30');
        $this->assertEquals('10am', $r2['key']);
        $this->assertEquals('10:00 AM', $r2['label']);

        // 2. Between 10.31 p.m. – 12.00 p.m. => 11.00 a.m.
        $r3 = LateWorkRequest::calculateNextDayReport('22:31');
        $this->assertEquals('11am', $r3['key']);
        $this->assertEquals('11:00 AM', $r3['label']);

        $r4 = LateWorkRequest::calculateNextDayReport('23:59');
        $this->assertEquals('11am', $r4['key']);
        $this->assertEquals('11:00 AM', $r4['label']);

        $r5 = LateWorkRequest::calculateNextDayReport('00:00');
        $this->assertEquals('11am', $r5['key']);
        $this->assertEquals('11:00 AM', $r5['label']);

        $r6 = LateWorkRequest::calculateNextDayReport('24:00');
        $this->assertEquals('11am', $r6['key']);
        $this->assertEquals('11:00 AM', $r6['label']);

        // 3. Between 12.01 – 3.00 a.m. => Half day off
        $r7 = LateWorkRequest::calculateNextDayReport('00:01');
        $this->assertEquals('half', $r7['key']);
        $this->assertEquals('Half day off', $r7['label']);

        $r8 = LateWorkRequest::calculateNextDayReport('02:00');
        $this->assertEquals('half', $r8['key']);
        $this->assertEquals('Half day off', $r8['label']);

        $r9 = LateWorkRequest::calculateNextDayReport('03:00');
        $this->assertEquals('half', $r9['key']);
        $this->assertEquals('Half day off', $r9['label']);

        // 4. Beyond 3.00 a.m. => Full day off
        $r10 = LateWorkRequest::calculateNextDayReport('03:01');
        $this->assertEquals('full', $r10['key']);
        $this->assertEquals('Full day off', $r10['label']);

        $r11 = LateWorkRequest::calculateNextDayReport('05:30');
        $this->assertEquals('full', $r11['key']);
        $this->assertEquals('Full day off', $r11['label']);

        $r12 = LateWorkRequest::calculateNextDayReport('06:00');
        $this->assertEquals('full', $r12['key']);
        $this->assertEquals('Full day off', $r12['label']);

        // 5. Prior to 9.00 p.m. => 9.00 a.m. (regular start time)
        $r13 = LateWorkRequest::calculateNextDayReport('20:30');
        $this->assertEquals('normal', $r13['key']);
        $this->assertEquals('9:00 AM', $r13['label']);

        // 6. 12-hour clock normalization: '11:00' entered without PM is treated as 23:00 (11:00 PM) -> 11:00 AM
        $r14 = LateWorkRequest::calculateNextDayReport('11:00');
        $this->assertEquals('11am', $r14['key']);
        $this->assertEquals('11:00 AM', $r14['label']);
    }

    public function test_weekday_late_work_expected_time_enforces_8pm_to_8am_window_and_normalizes_12h_entries()
    {
        Mail::fake();
        $nextWednesday = now()->next(\Carbon\Carbon::WEDNESDAY)->toDateString();

        // 1. Entering 11:00 automatically normalizes to 23:00 (11:00 PM) and succeeds
        $resp1 = $this->actingAs($this->staff)->post(route('late-work.store'), [
            'type' => 'late',
            'dept_id' => $this->dept->id,
            'date' => $nextWednesday,
            'job_number' => 'LI-NORM-11',
            'reason' => 'Late night release deployment script testing',
            'finish_time' => '11:00',
            'meal' => '1',
            'taxi' => '1',
            'hod_id' => $this->hod->id,
        ]);
        $resp1->assertRedirect(route('late-work.index', ['tab' => 'mine']));
        $this->assertDatabaseHas('late_work_requests', [
            'job_number' => 'LI-NORM-11',
            'finish_time' => '23:00',
            'next_day' => '11am',
        ]);

        // 2. Entering a daytime hour (e.g. 14:00 / 2:00 PM) is rejected
        $resp2 = $this->actingAs($this->staff)->post(route('late-work.store'), [
            'type' => 'late',
            'dept_id' => $this->dept->id,
            'date' => $nextWednesday,
            'job_number' => 'LI-INVALID-DAY',
            'reason' => 'This is daytime work not after office hours',
            'finish_time' => '14:00',
            'hod_id' => $this->hod->id,
        ]);
        $resp2->assertSessionHas('error', 'Expected finish time for weekday late work must be between 8:00 PM and 8:00 AM.');

        // 3. Entering 08:00 (8:00 AM next morning) succeeds within window
        $resp3 = $this->actingAs($this->staff)->post(route('late-work.store'), [
            'type' => 'late',
            'dept_id' => $this->dept->id,
            'date' => $nextWednesday,
            'job_number' => 'LI-WINDOW-8AM',
            'reason' => 'All-night emergency server maintenance till morning',
            'finish_time' => '08:00',
            'meal' => '1',
            'taxi' => '1',
            'hod_id' => $this->hod->id,
        ]);
        $resp3->assertRedirect(route('late-work.index', ['tab' => 'mine']));
        $this->assertDatabaseHas('late_work_requests', [
            'job_number' => 'LI-WINDOW-8AM',
            'finish_time' => '08:00',
            'next_day' => 'full',
        ]);
    }

    public function test_hr_admin_and_it_admin_can_view_calendar_settings_tab()
    {
        $itAdmin = User::factory()->create([
            'name' => 'IT Admin User',
            'email' => 'it@test.com',
            'role' => 'IT Admin',
        ]);

        // HR Admin can see Calendar Settings
        $hrResp = $this->actingAs($this->hrAdmin)->get(route('late-work.index', ['tab' => 'calendar']));
        $hrResp->assertStatus(200);
        $hrResp->assertSee('Calendar Settings');
        $hrResp->assertSee('Holiday Calendar Settings');

        // IT Admin can see Calendar Settings
        $itResp = $this->actingAs($itAdmin)->get(route('late-work.index', ['tab' => 'calendar']));
        $itResp->assertStatus(200);
        $itResp->assertSee('Calendar Settings');
        $itResp->assertSee('Holiday Calendar Settings');

        // Regular staff CANNOT see Calendar Settings
        $staffResp = $this->actingAs($this->staff)->get(route('late-work.index'));
        $staffResp->assertStatus(200);
        $staffResp->assertDontSee('Holiday Calendar Settings');
        $staffResp->assertDontSee('Calendar Settings');
    }

    public function test_hr_admin_and_it_admin_can_save_and_delete_holidays()
    {
        $itAdmin = User::factory()->create([
            'name' => 'IT Admin User',
            'email' => 'it_admin@test.com',
            'role' => 'IT Admin',
        ]);

        // HR Admin adds a holiday
        $postResp = $this->actingAs($this->hrAdmin)->postJson(route('late-work.holidays.save'), [
            'date' => '2026-12-25',
            'name' => 'Christmas Day',
            'type' => 'public',
            'description' => 'Christmas Day celebration',
        ]);

        $postResp->assertOk();
        $postResp->assertJson(['success' => true]);
        $this->assertDatabaseHas('holidays', [
            'date' => '2026-12-25',
            'name' => 'Christmas Day',
            'type' => 'public',
        ]);

        // IT Admin toggles a holiday
        $toggleResp = $this->actingAs($itAdmin)->postJson(route('late-work.holidays.toggle'), [
            'date' => '2026-10-31',
            'name' => 'Halloween Day',
            'type' => 'company',
        ]);

        $toggleResp->assertOk();
        $toggleResp->assertJson(['success' => true, 'action' => 'added']);
        $this->assertDatabaseHas('holidays', [
            'date' => '2026-10-31',
            'name' => 'Halloween Day',
        ]);

        // Toggle again to remove
        $toggleResp2 = $this->actingAs($itAdmin)->postJson(route('late-work.holidays.toggle'), [
            'date' => '2026-10-31',
        ]);
        $toggleResp2->assertOk();
        $toggleResp2->assertJson(['success' => true, 'action' => 'removed']);
        $this->assertDatabaseMissing('holidays', [
            'date' => '2026-10-31',
        ]);

        // Delete holiday
        $holiday = Holiday::where('date', '2026-12-25')->first();
        $delResp = $this->actingAs($this->hrAdmin)->deleteJson(route('late-work.holidays.delete', $holiday));
        $delResp->assertOk();
        $delResp->assertJson(['success' => true]);
        $this->assertDatabaseMissing('holidays', [
            'date' => '2026-12-25',
        ]);
    }

    public function test_unauthorized_users_cannot_manage_holidays()
    {
        $saveResp = $this->actingAs($this->staff)->postJson(route('late-work.holidays.save'), [
            'date' => '2026-05-01',
            'name' => 'May Day',
            'type' => 'public',
        ]);
        $saveResp->assertStatus(403);

        $holiday = Holiday::create([
            'date' => '2026-05-01',
            'name' => 'May Day',
            'type' => 'public',
            'created_by' => $this->hrAdmin->id,
        ]);

        $delResp = $this->actingAs($this->staff)->deleteJson(route('late-work.holidays.delete', $holiday));
        $delResp->assertStatus(403);
    }

    public function test_hr_admin_and_it_admin_can_add_and_delete_holiday_types()
    {
        $itAdmin = User::factory()->create([
            'name' => 'IT Admin User',
            'email' => 'it_types@test.com',
            'role' => 'IT Admin',
        ]);

        // HR Admin adds a custom holiday type
        $addResp = $this->actingAs($this->hrAdmin)->postJson(route('late-work.holiday-types.save'), [
            'name' => 'Poya Day Holiday',
            'color' => 'orange',
        ]);

        $addResp->assertOk();
        $addResp->assertJson(['success' => true]);
        $this->assertDatabaseHas('holiday_types', [
            'name' => 'Poya Day Holiday',
            'code' => 'poya_day_holiday',
            'color' => 'orange',
            'is_system' => false,
        ]);

        $poyaType = HolidayType::where('code', 'poya_day_holiday')->first();

        // Save a holiday with this custom type
        $saveHolResp = $this->actingAs($itAdmin)->postJson(route('late-work.holidays.save'), [
            'date' => '2026-11-23',
            'name' => 'Ill Full Moon Poya Day',
            'type' => 'poya_day_holiday',
        ]);

        $saveHolResp->assertOk();
        $this->assertDatabaseHas('holidays', [
            'date' => '2026-11-23',
            'name' => 'Ill Full Moon Poya Day',
            'type' => 'poya_day_holiday',
        ]);

        // IT Admin deletes custom holiday type -> any holiday using it reverts to public
        $delResp = $this->actingAs($itAdmin)->deleteJson(route('late-work.holiday-types.delete', $poyaType));
        $delResp->assertOk();
        $delResp->assertJson(['success' => true]);
        $this->assertDatabaseMissing('holiday_types', [
            'id' => $poyaType->id,
        ]);
        $this->assertDatabaseHas('holidays', [
            'date' => '2026-11-23',
            'type' => 'public',
        ]);
    }

    public function test_system_default_holiday_types_cannot_be_deleted()
    {
        HolidayType::ensureDefaults();
        $systemType = HolidayType::where('code', 'public')->first();

        $delResp = $this->actingAs($this->hrAdmin)->deleteJson(route('late-work.holiday-types.delete', $systemType));
        $delResp->assertStatus(422);
        $delResp->assertJson(['success' => false]);
        $this->assertDatabaseHas('holiday_types', ['code' => 'public']);
    }

    public function test_unauthorized_user_cannot_manage_holiday_types()
    {
        $addResp = $this->actingAs($this->staff)->postJson(route('late-work.holiday-types.save'), [
            'name' => 'Staff Special Leave',
            'color' => 'rose',
        ]);
        $addResp->assertStatus(403);

        $customType = HolidayType::create([
            'name' => 'Special Day',
            'code' => 'special_day',
            'color' => 'rose',
            'is_system' => false,
            'created_by' => $this->hrAdmin->id,
        ]);

        $delResp = $this->actingAs($this->staff)->deleteJson(route('late-work.holiday-types.delete', $customType));
        $delResp->assertStatus(403);
    }

    public function test_hr_admin_and_management_can_approve_late_work_requests()
    {
        Mail::fake();

        $req1 = LateWorkRequest::create([
            'reference_number' => 'LW-2026-HR-01',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'late',
            'date' => now()->next(\Carbon\Carbon::WEDNESDAY)->toDateString(),
            'job_number' => 'LI-HR-APP',
            'reason' => 'Emergency sprint for client release',
            'finish_time' => '23:00',
            'status' => 'pending',
        ]);

        $req2 = LateWorkRequest::create([
            'reference_number' => 'LW-2026-MGMT-01',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'late',
            'date' => now()->next(\Carbon\Carbon::THURSDAY)->toDateString(),
            'job_number' => 'LI-MGMT-APP',
            'reason' => 'Quarterly investor deck finalization',
            'finish_time' => '22:00',
            'status' => 'pending',
        ]);

        // HR Admin can view and approve req1
        $hrResp = $this->actingAs($this->hrAdmin)->get(route('late-work.index', ['tab' => 'approve']));
        $hrResp->assertStatus(200);
        $hrResp->assertSee('LI-HR-APP');

        $hrApprove = $this->actingAs($this->hrAdmin)->post(route('late-work.approve', $req1), [
            'note' => 'Approved by HR Admin',
        ]);
        $hrApprove->assertSessionHas('success');
        $req1->refresh();
        $this->assertEquals('approved', $req1->status);
        $this->assertEquals($this->hrAdmin->id, $req1->decided_by);
        $this->assertEquals('Approved by HR Admin', $req1->hod_note);

        // Management can view and approve req2
        $mgmtResp = $this->actingAs($this->management)->get(route('late-work.index', ['tab' => 'approve']));
        $mgmtResp->assertStatus(200);
        $mgmtResp->assertSee('LI-MGMT-APP');

        $mgmtApprove = $this->actingAs($this->management)->post(route('late-work.approve', $req2), [
            'note' => 'Approved by Management Director',
        ]);
        $mgmtApprove->assertSessionHas('success');
        $req2->refresh();
        $this->assertEquals('approved', $req2->status);
        $this->assertEquals($this->management->id, $req2->decided_by);
        $this->assertEquals('Approved by Management Director', $req2->hod_note);
    }

    public function test_hr_admin_and_management_can_decline_late_work_requests()
    {
        Mail::fake();

        $req1 = LateWorkRequest::create([
            'reference_number' => 'LW-2026-HR-DEC',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'late',
            'date' => now()->next(\Carbon\Carbon::WEDNESDAY)->toDateString(),
            'job_number' => 'LI-HR-DEC',
            'reason' => 'Late shift test',
            'finish_time' => '22:00',
            'status' => 'pending',
        ]);

        $req2 = LateWorkRequest::create([
            'reference_number' => 'LW-2026-MGMT-DEC',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'late',
            'date' => now()->next(\Carbon\Carbon::THURSDAY)->toDateString(),
            'job_number' => 'LI-MGMT-DEC',
            'reason' => 'Late shift test 2',
            'finish_time' => '22:00',
            'status' => 'pending',
        ]);

        // HR Admin declines req1 with note
        $hrDecline = $this->actingAs($this->hrAdmin)->post(route('late-work.decline', $req1), [
            'note' => 'Declined by HR: Please reschedule during standard work hours.',
        ]);
        $hrDecline->assertSessionHas('success');
        $req1->refresh();
        $this->assertEquals('declined', $req1->status);
        $this->assertEquals($this->hrAdmin->id, $req1->decided_by);
        $this->assertEquals('Declined by HR: Please reschedule during standard work hours.', $req1->hod_note);

        // Management declines req2 with note
        $mgmtDecline = $this->actingAs($this->management)->post(route('late-work.decline', $req2), [
            'note' => 'Declined by Management: Overtime budget exceeded for this week.',
        ]);
        $mgmtDecline->assertSessionHas('success');
        $req2->refresh();
        $this->assertEquals('declined', $req2->status);
        $this->assertEquals($this->management->id, $req2->decided_by);
        $this->assertEquals('Declined by Management: Overtime budget exceeded for this week.', $req2->hod_note);
    }

    public function test_weekend_work_duration_less_than_4_hours_yields_no_lieu_leave()
    {
        Mail::fake();
        $sat = now()->next(\Carbon\Carbon::SATURDAY)->toDateString();
        $response = $this->actingAs($this->staff)->post(route('late-work.store'), [
            'type' => 'weekend',
            'dept_id' => $this->dept->id,
            'date' => $sat,
            'job_number' => 'TEST-001',
            'reason' => 'Short weekend shift under 4 hours',
            'start_time' => '10:00',
            'finish_time' => '13:30', // 3.5 hours
            'meal' => false,
            'taxi' => false,
        ]);

        $response->assertSessionHas('success');
        $req = LateWorkRequest::where('user_id', $this->staff->id)->latest()->first();
        $this->assertNotNull($req);
        $this->assertNull($req->lieu);
        $this->assertNull($req->lieu_label);
        $this->assertEquals(0.0, $req->lieu_days);
    }

    public function test_weekend_work_duration_4_to_8_hours_yields_half_day_leave()
    {
        Mail::fake();
        $sat = now()->next(\Carbon\Carbon::SATURDAY)->toDateString();
        $response = $this->actingAs($this->staff)->post(route('late-work.store'), [
            'type' => 'weekend',
            'dept_id' => $this->dept->id,
            'date' => $sat,
            'job_number' => 'TEST-002',
            'reason' => 'Weekend shift 4.5 hours',
            'start_time' => '09:00',
            'finish_time' => '13:30', // 4.5 hours
            'meal' => true,
            'taxi' => false,
        ]);

        $response->assertSessionHas('success');
        $req = LateWorkRequest::where('user_id', $this->staff->id)->latest()->first();
        $this->assertNotNull($req);
        $this->assertEquals('half', $req->lieu);
        $this->assertEquals('Half day (0.5)', $req->lieu_label);
        $this->assertEquals(0.5, $req->lieu_days);
    }

    public function test_weekend_work_duration_8_to_12_hours_yields_full_day_leave()
    {
        Mail::fake();
        $sat = now()->next(\Carbon\Carbon::SATURDAY)->toDateString();
        $response = $this->actingAs($this->staff)->post(route('late-work.store'), [
            'type' => 'weekend',
            'dept_id' => $this->dept->id,
            'date' => $sat,
            'job_number' => 'TEST-003',
            'reason' => 'Weekend shift 8.5 hours',
            'start_time' => '09:00',
            'finish_time' => '17:30', // 8.5 hours
            'meal' => true,
            'taxi' => false,
        ]);

        $response->assertSessionHas('success');
        $req = LateWorkRequest::where('user_id', $this->staff->id)->latest()->first();
        $this->assertNotNull($req);
        $this->assertEquals('full', $req->lieu);
        $this->assertEquals('Full day (1.0)', $req->lieu_label);
        $this->assertEquals(1.0, $req->lieu_days);
    }

    public function test_weekend_work_duration_12_hours_or_more_yields_one_and_half_day_leave()
    {
        Mail::fake();
        $sat = now()->next(\Carbon\Carbon::SATURDAY)->toDateString();
        $response = $this->actingAs($this->staff)->post(route('late-work.store'), [
            'type' => 'weekend',
            'dept_id' => $this->dept->id,
            'date' => $sat,
            'job_number' => 'TEST-004',
            'reason' => 'Long weekend shift 12 hours',
            'start_time' => '08:00',
            'finish_time' => '20:00', // 12 hours
            'meal' => true,
            'taxi' => true,
        ]);

        $response->assertSessionHas('success');
        $req = LateWorkRequest::where('user_id', $this->staff->id)->latest()->first();
        $this->assertNotNull($req);
        $this->assertEquals('one_and_half', $req->lieu);
        $this->assertEquals('1.5 days', $req->lieu_label);
        $this->assertEquals(1.5, $req->lieu_days);
    }

    public function test_weekend_work_with_wfh_excludes_meal_and_taxi()
    {
        Mail::fake();
        $sat = now()->next(\Carbon\Carbon::SATURDAY)->toDateString();
        $response = $this->actingAs($this->staff)->post(route('late-work.store'), [
            'type' => 'weekend',
            'dept_id' => $this->dept->id,
            'date' => $sat,
            'job_number' => 'TEST-005',
            'reason' => 'Working from home on weekend',
            'start_time' => '09:00',
            'finish_time' => '17:00',
            'is_wfh' => '1',
            'meal' => '1', // Even if submitted, WFH must exclude meal & taxi
            'taxi' => '1',
        ]);

        $response->assertSessionHas('success');
        $req = LateWorkRequest::where('user_id', $this->staff->id)->latest()->first();
        $this->assertNotNull($req);
        $this->assertTrue($req->is_wfh);
        $this->assertFalse($req->meal);
        $this->assertFalse($req->taxi);
        $this->assertEquals('full', $req->lieu);
    }

    public function test_reappeal_with_wfh_and_duration_updates_lieu_and_disables_allowances()
    {
        Mail::fake();
        $sat = now()->next(\Carbon\Carbon::SATURDAY)->toDateString();
        $req = LateWorkRequest::create([
            'reference_number' => 'LW-2026-9999',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'weekend',
            'date' => $sat,
            'start_time' => '09:00',
            'finish_time' => '12:00', // 3 hours (no leave)
            'job_number' => 'TEST-006',
            'reason' => 'Initial request',
            'status' => 'declined',
            'decided_by' => $this->hod->id,
            'decided_at' => now(),
            'hod_note' => 'Please adjust hours and specify if WFH',
        ]);

        $response = $this->actingAs($this->staff)->post(route('late-work.reappeal', $req), [
            'reappeal_notes' => 'Adjusted to 12 hours shift working from home.',
            'start_time' => '08:00',
            'finish_time' => '20:00', // 12 hours
            'is_wfh' => '1',
            'meal' => '1',
            'taxi' => '1',
        ]);

        $response->assertSessionHas('success');
        $req->refresh();
        $this->assertEquals('pending', $req->status);
        $this->assertTrue($req->is_wfh);
        $this->assertFalse($req->meal);
        $this->assertFalse($req->taxi);
        $this->assertEquals('one_and_half', $req->lieu);
        $this->assertEquals(1.5, $req->lieu_days);
    }

    public function test_submitting_late_work_with_allowances_creates_linked_petty_cash_request_marked_work_tracker()
    {
        Mail::fake();
        Storage::fake('public');

        $mealCat = ExpenseCategory::firstOrCreate(['name' => 'Meal allowance'], ['status' => 'active']);
        $travelCat = ExpenseCategory::firstOrCreate(['name' => 'Travel'], ['status' => 'active']);

        $file = UploadedFile::fake()->create('dinner_receipt.pdf', 150, 'application/pdf');

        $sat = now()->next(\Carbon\Carbon::SATURDAY)->toDateString();
        $response = $this->actingAs($this->staff)->post(route('late-work.store'), [
            'type' => 'weekend',
            'dept_id' => $this->dept->id,
            'date' => $sat,
            'job_number' => 'JOB-WT-01',
            'reason' => 'Shooting client campaign overtime',
            'start_time' => '09:00',
            'finish_time' => '21:00',
            'meal' => '1',
            'taxi' => '1',
            'petty_cash_items' => [
                [
                    'expense_category_id' => $mealCat->id,
                    'amount' => 1000.00,
                    'description' => 'Dinner meal allowance',
                    'attendees' => ['Alice', 'Bob'],
                ],
                [
                    'expense_category_id' => $travelCat->id,
                    'amount' => 1450.00,
                    'description' => 'PickMe ride home from Colombo 07',
                    'attendees' => [],
                ]
            ],
            'petty_cash_extra_notes' => 'Urgent campaign delivery shift',
            'petty_cash_proofs' => [$file],
        ]);

        $response->assertSessionHas('success');

        $lateWork = LateWorkRequest::where('user_id', $this->staff->id)->latest()->first();
        $this->assertNotNull($lateWork);
        $this->assertTrue($lateWork->meal);
        $this->assertTrue($lateWork->taxi);

        // Check linked Petty Cash Request
        $pettyCash = PettyCashRequest::where('late_work_request_id', $lateWork->id)->first();
        $this->assertNotNull($pettyCash);
        $this->assertEquals('work_tracker', $pettyCash->source);
        $this->assertTrue($pettyCash->isFromWorkTracker());
        $this->assertEquals('pending_hod', $pettyCash->status);
        $this->assertEquals(2450.00, $pettyCash->total_amount);
        $this->assertEquals('JOB-WT-01', $pettyCash->job_number);

        // Check line items
        $this->assertCount(2, $pettyCash->items);
        $mealItem = $pettyCash->items->firstWhere('expense_category_id', $mealCat->id);
        $this->assertNotNull($mealItem);
        $this->assertEquals(1000.00, $mealItem->amount);
        $this->assertEquals(['Alice', 'Bob'], $mealItem->attendees);

        $travelItem = $pettyCash->items->firstWhere('expense_category_id', $travelCat->id);
        $this->assertNotNull($travelItem);
        $this->assertEquals(1450.00, $travelItem->amount);

        // Check proof attachment
        $this->assertCount(1, $pettyCash->proofs);
        $this->assertEquals('dinner_receipt.pdf', $pettyCash->proofs->first()->file_name);
    }

    public function test_submitting_late_work_with_travel_without_amount_succeeds_as_draft(): void
    {
        Mail::fake();

        $mealCat = ExpenseCategory::firstOrCreate(['name' => 'Meal allowance'], ['status' => 'active']);
        $travelCat = ExpenseCategory::firstOrCreate(['name' => 'Travel'], ['status' => 'active']);
        $file = UploadedFile::fake()->create('ride.png', 100, 'image/png');

        $sat = now()->next(\Carbon\Carbon::SATURDAY)->toDateString();
        $payload = [
            'type' => 'weekend',
            'dept_id' => $this->dept->id,
            'date' => $sat,
            'job_number' => 'JOB-PICKME-NO-AMT',
            'start_time' => '09:00',
            'finish_time' => '17:30',
            'meal' => '1',
            'taxi' => '1',
            'petty_cash_items' => [
                [
                    'expense_category_id' => $mealCat->id,
                    'amount' => 1000.00,
                    'description' => 'Meal allowance',
                ],
                [
                    'expense_category_id' => $travelCat->id,
                    'amount' => '', // blank amount
                    'description' => 'PickMe ride home',
                ],
            ],
            'petty_cash_proofs' => [$file],
            'hod_id' => $this->hod->id,
        ];

        $response = $this->actingAs($this->staff)->post(route('late-work.store'), $payload);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('late-work.index', ['tab' => 'mine']));
        $this->assertDatabaseHas('late_work_requests', ['job_number' => 'JOB-PICKME-NO-AMT']);

        $lateWork = LateWorkRequest::where('job_number', 'JOB-PICKME-NO-AMT')->first();
        $pettyCash = PettyCashRequest::where('late_work_request_id', $lateWork->id)->first();
        $this->assertNotNull($pettyCash);
        $this->assertTrue((bool)$pettyCash->is_draft);
    }

    public function test_submitting_late_work_with_travel_without_attachment_succeeds_as_draft_pending_proofs(): void
    {
        Mail::fake();

        $travelCat = ExpenseCategory::firstOrCreate(['name' => 'Travel'], ['status' => 'active']);

        $sat = now()->next(\Carbon\Carbon::SATURDAY)->toDateString();
        $payload = [
            'type' => 'weekend',
            'dept_id' => $this->dept->id,
            'date' => $sat,
            'job_number' => 'JOB-PICKME-NO-FILE',
            'start_time' => '09:00',
            'finish_time' => '17:30',
            'taxi' => '1',
            'petty_cash_items' => [
                [
                    'expense_category_id' => $travelCat->id,
                    'amount' => 1200.00,
                    'description' => 'PickMe ride home',
                ],
            ],
            'hod_id' => $this->hod->id,
        ];

        $response = $this->actingAs($this->staff)->post(route('late-work.store'), $payload);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('late-work.index', ['tab' => 'mine']));
        $this->assertDatabaseHas('late_work_requests', ['job_number' => 'JOB-PICKME-NO-FILE']);

        $lateWork = LateWorkRequest::where('job_number', 'JOB-PICKME-NO-FILE')->first();
        $pettyCash = PettyCashRequest::where('late_work_request_id', $lateWork->id)->first();
        $this->assertNotNull($pettyCash);
        $this->assertTrue((bool)$pettyCash->is_draft);
        $this->assertTrue((bool)$pettyCash->pending_proofs);
    }

    public function test_hod_approval_of_late_work_automatically_approves_hod_approval_of_linked_petty_cash()
    {
        Mail::fake();
        Notification::fake();

        $mealCat = ExpenseCategory::firstOrCreate(['name' => 'Meal allowance'], ['status' => 'active']);

        $sat = now()->next(\Carbon\Carbon::SATURDAY)->toDateString();
        $lateWork = LateWorkRequest::create([
            'reference_number' => 'LW-2026-AUTOPC',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'weekend',
            'date' => $sat,
            'start_time' => '09:00',
            'finish_time' => '18:00',
            'job_number' => 'JOB-AUTO-01',
            'reason' => 'Design Sprint Overtime',
            'meal' => true,
            'taxi' => false,
            'status' => 'pending',
        ]);

        $pettyCash = PettyCashRequest::create([
            'reference_number' => 'PC-2026-AUTO1',
            'user_id' => $this->staff->id,
            'hod_id' => $this->hod->id,
            'department' => $this->dept->name,
            'job_number' => 'JOB-AUTO-01',
            'total_amount' => 1000.00,
            'approved_amount' => 1000.00,
            'status' => 'pending_hod',
            'source' => 'work_tracker',
            'late_work_request_id' => $lateWork->id,
        ]);

        PettyCashItem::create([
            'petty_cash_request_id' => $pettyCash->id,
            'expense_category_id' => $mealCat->id,
            'amount' => 1000.00,
            'description' => 'Meal allowance',
        ]);

        // HOD approves the late work request
        $response = $this->actingAs($this->hod)->post(route('late-work.approve', $lateWork), [
            'note' => 'Approved by HOD'
        ]);

        $response->assertSessionHas('success');

        $lateWork->refresh();
        $this->assertEquals('approved', $lateWork->status);

        // Linked Petty Cash MUST automatically advance to pending_super_admin (HOD approval done)
        $pettyCash->refresh();
        $this->assertEquals('pending_super_admin', $pettyCash->status);
    }

    public function test_hod_decline_of_late_work_automatically_declines_linked_petty_cash()
    {
        Mail::fake();
        Notification::fake();

        $sat = now()->next(\Carbon\Carbon::SATURDAY)->toDateString();
        $lateWork = LateWorkRequest::create([
            'reference_number' => 'LW-2026-DECL',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'weekend',
            'date' => $sat,
            'start_time' => '09:00',
            'finish_time' => '18:00',
            'job_number' => 'JOB-DECL-01',
            'reason' => 'Overtime Work',
            'meal' => true,
            'status' => 'pending',
        ]);

        $pettyCash = PettyCashRequest::create([
            'reference_number' => 'PC-2026-DECL1',
            'user_id' => $this->staff->id,
            'hod_id' => $this->hod->id,
            'department' => $this->dept->name,
            'job_number' => 'JOB-DECL-01',
            'total_amount' => 1000.00,
            'approved_amount' => 1000.00,
            'status' => 'pending_hod',
            'source' => 'work_tracker',
            'late_work_request_id' => $lateWork->id,
        ]);

        // HOD declines the late work request
        $response = $this->actingAs($this->hod)->post(route('late-work.decline', $lateWork), [
            'note' => 'Project postponed to next week'
        ]);

        $response->assertSessionHas('success');

        $lateWork->refresh();
        $this->assertEquals('declined', $lateWork->status);

        // Linked Petty Cash MUST be marked as rejected_by_hod
        $pettyCash->refresh();
        $this->assertEquals('rejected_by_hod', $pettyCash->status);
        $this->assertStringContainsString('Project postponed', $pettyCash->hod_rejection_note);
    }

    public function test_active_unsettled_iou_blocks_allowance_request_in_work_tracker()
    {
        Mail::fake();

        // Create active unsettled IOU for the staff member
        PettyCashRequest::create([
            'reference_number' => 'IOU-2026-0099',
            'user_id' => $this->staff->id,
            'department' => $this->dept->name,
            'total_amount' => 5000.00,
            'approved_amount' => 5000.00,
            'is_iou' => true,
            'status' => 'iou_issued',
        ]);

        $sat = now()->next(\Carbon\Carbon::SATURDAY)->toDateString();
        $response = $this->actingAs($this->staff)->post(route('late-work.store'), [
            'type' => 'weekend',
            'dept_id' => $this->dept->id,
            'date' => $sat,
            'job_number' => 'JOB-IOU-BLOCK',
            'reason' => 'Weekend Shift',
            'start_time' => '09:00',
            'finish_time' => '18:00',
            'meal' => '1',
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('active unsettled IOU', session('error'));

        // No new shift request created
        $this->assertDatabaseMissing('late_work_requests', [
            'job_number' => 'JOB-IOU-BLOCK',
        ]);
    }

    public function test_it_admin_and_finance_admin_can_edit_late_work_request()
    {
        $itAdmin = User::factory()->create([
            'name' => 'IT Admin User',
            'email' => 'itadmin@test.com',
            'role' => 'IT Admin',
        ]);

        $lateReq = LateWorkRequest::create([
            'reference_number' => 'LW-2026-9001',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'late',
            'date' => '2026-10-21',
            'job_number' => 'LOOPS/2026/0469',
            'reason' => 'Original Reason',
            'finish_time' => '21:00',
            'status' => 'approved',
            'decided_by' => $this->hod->id,
            'decided_at' => now(),
        ]);

        // IT Admin updates the request
        $response = $this->actingAs($itAdmin)->put(route('late-work.update', $lateReq), [
            'type' => 'late',
            'date' => '2026-10-21',
            'finish_time' => '22:30',
            'job_number' => 'LOOPS/2026/0469, LOOPS/2026/0466',
            'reason' => 'Updated Reason by IT Admin',
            'status' => 'approved',
            'meal' => '1',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('late_work_requests', [
            'id' => $lateReq->id,
            'reason' => 'Updated Reason by IT Admin',
            'job_number' => 'LOOPS/2026/0469, LOOPS/2026/0466',
            'meal' => 1,
        ]);

        // Finance Admin updates the request
        $responseFinance = $this->actingAs($this->admin)->put(route('late-work.update', $lateReq), [
            'type' => 'late',
            'date' => '2026-10-21',
            'finish_time' => '23:00',
            'job_number' => 'LOOPS/2026/0469',
            'reason' => 'Updated Reason by Finance Admin',
            'status' => 'approved',
        ]);

        $responseFinance->assertSessionHas('success');
        $this->assertDatabaseHas('late_work_requests', [
            'id' => $lateReq->id,
            'reason' => 'Updated Reason by Finance Admin',
        ]);
    }

    public function test_unauthorized_users_cannot_edit_late_work_request()
    {
        $lateReq = LateWorkRequest::create([
            'reference_number' => 'LW-2026-9002',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'late',
            'date' => '2026-10-21',
            'job_number' => 'LOOPS/2026/0469',
            'reason' => 'Original Reason',
            'finish_time' => '21:00',
            'status' => 'approved',
        ]);

        // Staff tries to edit
        $responseStaff = $this->actingAs($this->staff)->put(route('late-work.update', $lateReq), [
            'type' => 'late',
            'date' => '2026-10-21',
            'finish_time' => '22:00',
            'job_number' => 'LOOPS/2026/0469',
            'reason' => 'Hacked reason',
            'status' => 'approved',
        ]);
        $responseStaff->assertSessionHas('error');

        // HOD tries to edit
        $responseHod = $this->actingAs($this->hod)->put(route('late-work.update', $lateReq), [
            'type' => 'late',
            'date' => '2026-10-21',
            'finish_time' => '22:00',
            'job_number' => 'LOOPS/2026/0469',
            'reason' => 'Hacked reason',
            'status' => 'approved',
        ]);
        $responseHod->assertSessionHas('error');

        // HR Admin tries to edit
        $responseHr = $this->actingAs($this->hrAdmin)->put(route('late-work.update', $lateReq), [
            'type' => 'late',
            'date' => '2026-10-21',
            'finish_time' => '22:00',
            'job_number' => 'LOOPS/2026/0469',
            'reason' => 'Hacked reason',
            'status' => 'approved',
        ]);
        $responseHr->assertSessionHas('error');

        // Management tries to edit
        $responseMgmt = $this->actingAs($this->management)->put(route('late-work.update', $lateReq), [
            'type' => 'late',
            'date' => '2026-10-21',
            'finish_time' => '22:00',
            'job_number' => 'LOOPS/2026/0469',
            'reason' => 'Hacked reason',
            'status' => 'approved',
        ]);
        $responseMgmt->assertSessionHas('error');

        // Original reason remains intact
        $this->assertDatabaseHas('late_work_requests', [
            'id' => $lateReq->id,
            'reason' => 'Original Reason',
        ]);
    }

    public function test_it_admin_and_finance_admin_can_delete_late_work_request()
    {
        $itAdmin = User::factory()->create([
            'name' => 'IT Admin User',
            'email' => 'itadmin_del@test.com',
            'role' => 'IT Admin',
        ]);

        $lateReq1 = LateWorkRequest::create([
            'reference_number' => 'LW-2026-9003',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'late',
            'date' => '2026-10-21',
            'job_number' => 'LOOPS/2026/0469',
            'reason' => 'To be deleted by IT Admin',
            'finish_time' => '21:00',
            'status' => 'approved',
        ]);

        $lateReq2 = LateWorkRequest::create([
            'reference_number' => 'LW-2026-9004',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'late',
            'date' => '2026-10-21',
            'job_number' => 'LOOPS/2026/0469',
            'reason' => 'To be deleted by Finance Admin',
            'finish_time' => '21:00',
            'status' => 'approved',
        ]);

        // IT Admin deletes request 1
        $response1 = $this->actingAs($itAdmin)->delete(route('late-work.destroy', $lateReq1));
        $response1->assertSessionHas('success');
        $this->assertDatabaseMissing('late_work_requests', ['id' => $lateReq1->id]);

        // Finance Admin deletes request 2
        $response2 = $this->actingAs($this->admin)->delete(route('late-work.destroy', $lateReq2));
        $response2->assertSessionHas('success');
        $this->assertDatabaseMissing('late_work_requests', ['id' => $lateReq2->id]);
    }

    public function test_unauthorized_users_cannot_delete_late_work_request()
    {
        $lateReq = LateWorkRequest::create([
            'reference_number' => 'LW-2026-9005',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'late',
            'date' => '2026-10-21',
            'job_number' => 'LOOPS/2026/0469',
            'reason' => 'Must not be deleted',
            'finish_time' => '21:00',
            'status' => 'approved',
        ]);

        // Staff tries to delete
        $responseStaff = $this->actingAs($this->staff)->delete(route('late-work.destroy', $lateReq));
        $responseStaff->assertSessionHas('error');

        // HOD tries to delete
        $responseHod = $this->actingAs($this->hod)->delete(route('late-work.destroy', $lateReq));
        $responseHod->assertSessionHas('error');

        // HR Admin tries to delete
        $responseHr = $this->actingAs($this->hrAdmin)->delete(route('late-work.destroy', $lateReq));
        $responseHr->assertSessionHas('error');

        // Request still exists
        $this->assertDatabaseHas('late_work_requests', ['id' => $lateReq->id]);
    }

    public function test_edit_and_delete_buttons_rendered_for_it_admin_and_finance_admin()
    {
        $itAdmin = User::factory()->create([
            'name' => 'IT Admin Loops',
            'email' => 'loops_it@test.com',
            'role' => 'IT Admin',
        ]);

        $lateReq = LateWorkRequest::create([
            'reference_number' => 'LW-2026-0001',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'late',
            'date' => '2026-10-21',
            'job_number' => 'LOOPS/2026/0469',
            'reason' => 'Client urgent work',
            'finish_time' => '21:00',
            'status' => 'approved',
            'decided_by' => $this->hod->id,
            'decided_at' => now(),
        ]);

        // IT Admin sees Edit and Delete
        $itResp = $this->actingAs($itAdmin)->get(route('late-work.index', ['tab' => 'log']));
        $itResp->assertStatus(200);
        $itResp->assertSee('Edit Request');
        $itResp->assertSee(route('late-work.destroy', $lateReq));

        // Finance Admin sees Edit and Delete
        $finResp = $this->actingAs($this->admin)->get(route('late-work.index', ['tab' => 'log']));
        $finResp->assertStatus(200);
        $finResp->assertSee('Edit Request');
        $finResp->assertSee(route('late-work.destroy', $lateReq));

        // Staff does NOT see Edit and Delete
        $staffResp = $this->actingAs($this->staff)->get(route('late-work.index', ['tab' => 'mine']));
        $staffResp->assertStatus(200);
        $staffResp->assertDontSee('Edit Request');
    }

    public function test_staff_can_submit_late_work_request_without_reason(): void
    {
        Mail::fake();

        $payload = [
            'type' => 'late',
            'dept_id' => $this->dept->id,
            'date' => Carbon::now()->next(Carbon::TUESDAY)->toDateString(),
            'job_number' => 'LI-9999',
            'job_label' => 'Optional Reason Project',
            'reason' => '',
            'finish_time' => '22:00',
            'hod_id' => $this->hod->id,
        ];

        $response = $this->actingAs($this->staff)->post(route('late-work.store'), $payload);

        $response->assertRedirect(route('late-work.index', ['tab' => 'mine']));
        $this->assertDatabaseHas('late_work_requests', [
            'user_id' => $this->staff->id,
            'job_number' => 'LI-9999',
            'reason' => null,
            'status' => 'pending',
        ]);
    }

    public function test_it_admin_can_update_late_work_request_with_empty_reason(): void
    {
        $itAdmin = User::create([
            'name' => 'IT Admin Reason',
            'email' => 'it_reason@test.com',
            'password' => bcrypt('password'),
            'role' => 'it_admin',
            'department' => 'Technology',
        ]);

        $lateReq = LateWorkRequest::create([
            'reference_number' => 'LWR-OPT-REASON',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'late',
            'date' => Carbon::now()->next(Carbon::WEDNESDAY)->toDateString(),
            'job_number' => 'LI-2001',
            'job_label' => 'Test',
            'reason' => 'Existing reason',
            'finish_time' => '22:00',
            'meal' => 0,
            'taxi' => 0,
            'status' => 'pending',
        ]);

        $updatePayload = [
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'type' => 'late',
            'date' => Carbon::now()->next(Carbon::WEDNESDAY)->toDateString(),
            'job_number' => 'LI-2001',
            'job_label' => 'Updated Test',
            'finish_time' => '22:30',
            'reason' => '',
            'status' => 'pending',
        ];

        $resp = $this->actingAs($itAdmin)->put(route('late-work.update', $lateReq), $updatePayload);
        $resp->assertRedirect();

        $lateReq->refresh();
        $this->assertNull($lateReq->reason);
        $this->assertEquals('22:30', $lateReq->finish_time);
    }

    public function test_hod_approval_of_late_work_saves_draft_petty_cash_with_hod_approved_and_pending_proofs_routed_to_finance()
    {
        Mail::fake();
        Notification::fake();

        $mealCat = ExpenseCategory::firstOrCreate(['name' => 'Meal allowance'], ['status' => 'active']);

        $sat = now()->next(\Carbon\Carbon::SATURDAY)->toDateString();
        $lateWork = LateWorkRequest::create([
            'reference_number' => 'LW-2026-DRAFT-PROOF',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'weekend',
            'date' => $sat,
            'start_time' => '09:00',
            'finish_time' => '18:00',
            'job_number' => 'JOB-DRAFT-01',
            'reason' => 'Design Sprint Overtime',
            'meal' => true,
            'taxi' => false,
            'status' => 'pending',
        ]);

        $pettyCash = PettyCashRequest::create([
            'reference_number' => 'PC-2026-DRAFT01',
            'user_id' => $this->staff->id,
            'hod_id' => $this->hod->id,
            'department' => $this->dept->name,
            'job_number' => 'JOB-DRAFT-01',
            'total_amount' => 1000.00,
            'approved_amount' => 1000.00,
            'status' => 'pending_hod',
            'source' => 'work_tracker',
            'late_work_request_id' => $lateWork->id,
            'is_draft' => true,
            'pending_proofs' => true,
        ]);

        PettyCashItem::create([
            'petty_cash_request_id' => $pettyCash->id,
            'expense_category_id' => $mealCat->id,
            'amount' => 1000.00,
            'description' => 'Meal allowance',
        ]);

        // HOD approves the late work request
        $response = $this->actingAs($this->hod)->post(route('late-work.approve', $lateWork), [
            'note' => 'Approved by HOD'
        ]);

        $response->assertSessionHas('success');

        $lateWork->refresh();
        $this->assertEquals('approved', $lateWork->status);

        // Linked Petty Cash MUST be saved as draft with HOD approved, pending proofs, and routed to Finance
        $pettyCash->refresh();
        $this->assertEquals('pending_super_admin', $pettyCash->status);
        $this->assertTrue($pettyCash->is_draft);
        $this->assertTrue($pettyCash->pending_proofs);
        $this->assertNotNull($pettyCash->hod_approved_at);
        $this->assertEquals($this->hod->id, $pettyCash->hod_id);
    }

    public function test_finance_admin_can_set_petty_cash_to_hod_approval_state_before_finance_approval()
    {
        Notification::fake();

        $mealCat = ExpenseCategory::firstOrCreate(['name' => 'Meal allowance'], ['status' => 'active']);

        $pettyCash = PettyCashRequest::create([
            'reference_number' => 'PC-2026-HOD-STATE',
            'user_id' => $this->staff->id,
            'hod_id' => $this->hod->id,
            'department' => $this->dept->name,
            'job_number' => 'JOB-FINANCE-01',
            'total_amount' => 1000.00,
            'approved_amount' => 1000.00,
            'status' => 'pending_super_admin',
            'source' => 'work_tracker',
            'is_draft' => true,
            'pending_proofs' => true,
        ]);

        PettyCashItem::create([
            'petty_cash_request_id' => $pettyCash->id,
            'expense_category_id' => $mealCat->id,
            'amount' => 1000.00,
            'description' => 'Meal allowance',
        ]);

        // Finance Admin sets the request to HOD approval state before approving
        $response = $this->actingAs($this->admin)->post(route('petty-cash.sendToHod', $pettyCash), [
            'hod_id' => $this->hod->id,
            'finance_note' => 'Please verify receipts once attached by staff before final payment approval.',
        ]);

        $response->assertSessionHas('success');

        $pettyCash->refresh();
        $this->assertEquals('pending_hod', $pettyCash->status);
        $this->assertEquals($this->hod->id, $pettyCash->hod_id);
        $this->assertEquals('Please verify receipts once attached by staff before final payment approval.', $pettyCash->finance_note);

        // Now HOD can accept it back to Finance
        $hodResp = $this->actingAs($this->hod)->post(route('petty-cash.hodApprove', $pettyCash));
        $hodResp->assertSessionHas('success');

        $pettyCash->refresh();
        $this->assertEquals('pending_super_admin', $pettyCash->status);

        // Finally, Finance Admin can approve it
        $adminApproveResp = $this->actingAs($this->admin)->post(route('petty-cash.adminApprove', $pettyCash));
        $adminApproveResp->assertSessionHas('success');

        $pettyCash->refresh();
        $this->assertEquals('approved', $pettyCash->status);
    }

    public function test_non_admin_cannot_set_petty_cash_to_hod_approval_state()
    {
        $pettyCash = PettyCashRequest::create([
            'reference_number' => 'PC-2026-UNAUTH',
            'user_id' => $this->staff->id,
            'hod_id' => $this->hod->id,
            'department' => $this->dept->name,
            'total_amount' => 1000.00,
            'status' => 'pending_super_admin',
        ]);

        $response = $this->actingAs($this->staff)->post(route('petty-cash.sendToHod', $pettyCash), [
            'finance_note' => 'Unauthorized attempt',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals('pending_super_admin', $pettyCash->fresh()->status);
    }

    public function test_staff_can_upload_proof_attachments_to_pending_petty_cash_request()
    {
        Storage::fake('public');

        $pettyCash = PettyCashRequest::create([
            'reference_number' => 'PC-2026-UPLOAD-PROOF',
            'user_id' => $this->staff->id,
            'hod_id' => $this->hod->id,
            'department' => $this->dept->name,
            'total_amount' => 1000.00,
            'status' => 'pending_super_admin',
            'pending_proofs' => true,
        ]);

        $file = UploadedFile::fake()->create('dinner_receipt.pdf', 200, 'application/pdf');

        $response = $this->actingAs($this->staff)->post(route('petty-cash.uploadProofs', $pettyCash), [
            'proofs' => [$file],
        ]);

        $response->assertSessionHas('success');

        $pettyCash->refresh();
        $this->assertFalse($pettyCash->pending_proofs);
        $this->assertCount(1, $pettyCash->proofs);
        $this->assertStringContainsString('dinner_receipt.pdf', $pettyCash->proofs->first()->file_name);
    }

    public function test_staff_requester_can_edit_worktracker_petty_cash_request_before_finance_approval()
    {
        Storage::fake('public');

        $mealCat = ExpenseCategory::firstOrCreate(['name' => 'Meal allowance'], ['status' => 'active']);
        $travelCat = ExpenseCategory::firstOrCreate(['name' => 'Travel'], ['status' => 'active']);

        $lateWork = LateWorkRequest::create([
            'reference_number' => 'LW-2026-EDIT-TEST',
            'user_id' => $this->staff->id,
            'department_id' => $this->dept->id,
            'department_name' => $this->dept->name,
            'hod_id' => $this->hod->id,
            'type' => 'late',
            'date' => now()->toDateString(),
            'job_number' => 'JOB-EDIT-01',
            'finish_time' => '22:00',
            'meal' => true,
            'taxi' => true,
            'status' => 'approved',
        ]);

        $pettyCash = PettyCashRequest::create([
            'reference_number' => 'PC-2026-EDIT-TEST',
            'user_id' => $this->staff->id,
            'hod_id' => $this->hod->id,
            'department' => $this->dept->name,
            'job_number' => 'JOB-EDIT-01',
            'total_amount' => 1000.00,
            'approved_amount' => 1000.00,
            'status' => 'pending_super_admin',
            'source' => 'work_tracker',
            'late_work_request_id' => $lateWork->id,
            'is_draft' => true,
            'pending_proofs' => true,
        ]);

        PettyCashItem::create([
            'petty_cash_request_id' => $pettyCash->id,
            'expense_category_id' => $mealCat->id,
            'amount' => 1000.00,
            'description' => 'Meal allowance',
        ]);

        PettyCashItem::create([
            'petty_cash_request_id' => $pettyCash->id,
            'expense_category_id' => $travelCat->id,
            'amount' => 0.00,
            'description' => 'PickMe ride home',
        ]);

        $file = UploadedFile::fake()->create('pickme_receipt.png', 150, 'image/png');

        $updatePayload = [
            'hod_id' => $this->hod->id,
            'job_numbers' => ['JOB-EDIT-01'],
            'extra_notes' => 'Updated PickMe amount with receipt',
            'items' => [
                [
                    'expense_category_id' => $mealCat->id,
                    'amount' => 1000.00,
                    'description' => 'Dinner with team',
                    'attendees' => ['Alice', 'Bob'],
                ],
                [
                    'expense_category_id' => $travelCat->id,
                    'amount' => 1450.00,
                    'description' => 'PickMe ride home receipt attached',
                ],
            ],
            'proofs' => [$file],
        ];

        $response = $this->actingAs($this->staff)->put(route('petty-cash.update', $pettyCash), $updatePayload);
        $response->assertSessionHas('success');

        $pettyCash->refresh();
        $this->assertEquals(2450.00, $pettyCash->total_amount);
        $this->assertEquals(2450.00, $pettyCash->approved_amount);
        $this->assertFalse((bool)$pettyCash->pending_proofs);
        $this->assertEquals('Updated PickMe amount with receipt', $pettyCash->extra_notes);
        $this->assertCount(1, $pettyCash->proofs);

        $travelItem = $pettyCash->items()->where('expense_category_id', $travelCat->id)->first();
        $this->assertNotNull($travelItem);
        $this->assertEquals(1450.00, $travelItem->amount);

        $mealItem = $pettyCash->items()->where('expense_category_id', $mealCat->id)->first();
        $this->assertNotNull($mealItem);
        $this->assertEquals(['Alice', 'Bob'], $mealItem->attendees);
    }

    public function test_staff_requester_cannot_edit_worktracker_petty_cash_request_after_finance_approval()
    {
        $pettyCash = PettyCashRequest::create([
            'reference_number' => 'PC-2026-APPROVED-WT',
            'user_id' => $this->staff->id,
            'hod_id' => $this->hod->id,
            'department' => $this->dept->name,
            'total_amount' => 1000.00,
            'status' => 'approved',
            'source' => 'work_tracker',
        ]);

        $mealCat = ExpenseCategory::firstOrCreate(['name' => 'Meal allowance'], ['status' => 'active']);

        $response = $this->actingAs($this->staff)->put(route('petty-cash.update', $pettyCash), [
            'items' => [
                [
                    'expense_category_id' => $mealCat->id,
                    'amount' => 1500.00,
                    'description' => 'Unauthorized increase',
                ],
            ],
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(1000.00, $pettyCash->fresh()->total_amount);
    }

    public function test_unauthorized_user_cannot_edit_other_users_worktracker_petty_cash_request()
    {
        $otherUser = User::factory()->create([
            'name' => 'Other Staff',
            'role' => 'Staff',
            'department' => $this->dept->name,
        ]);

        $pettyCash = PettyCashRequest::create([
            'reference_number' => 'PC-2026-OTHER-WT',
            'user_id' => $this->staff->id,
            'hod_id' => $this->hod->id,
            'department' => $this->dept->name,
            'total_amount' => 1000.00,
            'status' => 'pending_super_admin',
            'source' => 'work_tracker',
        ]);

        $mealCat = ExpenseCategory::firstOrCreate(['name' => 'Meal allowance'], ['status' => 'active']);

        $response = $this->actingAs($otherUser)->put(route('petty-cash.update', $pettyCash), [
            'items' => [
                [
                    'expense_category_id' => $mealCat->id,
                    'amount' => 1500.00,
                    'description' => 'Tampering attempt',
                ],
            ],
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(1000.00, $pettyCash->fresh()->total_amount);
    }

    public function test_edit_button_rendered_for_requester_on_pending_worktracker_petty_cash_request()
    {
        $pettyCash = PettyCashRequest::create([
            'reference_number' => 'PC-2026-BUTTON-WT',
            'user_id' => $this->staff->id,
            'hod_id' => $this->hod->id,
            'department' => $this->dept->name,
            'total_amount' => 1000.00,
            'status' => 'pending_super_admin',
            'source' => 'work_tracker',
        ]);

        $response = $this->actingAs($this->staff)->get(route('petty-cash.index'));
        $response->assertStatus(200);
        $response->assertSee('PC-2026-BUTTON-WT');
        $response->assertSee("openEditPettyCashModal({$pettyCash->id})", false);
    }
}




