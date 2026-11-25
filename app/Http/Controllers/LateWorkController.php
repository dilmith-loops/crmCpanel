<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\HolidayType;
use App\Models\ExpenseCategory;
use App\Models\LateWorkRequest;
use App\Models\PettyCashItem;
use App\Models\PettyCashProof;
use App\Models\PettyCashRequest;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\PettyCashNotification;
use App\Services\LateWorkMailService;
use App\Traits\LogsActivity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LateWorkController extends Controller
{
    use LogsActivity;

    /**
     * Display the Late & Weekend Work dashboard.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $defaultTab = ($user->isManagement() || $user->isHRAdmin()) ? 'log' : 'new';
        $tab = $request->input('tab', $defaultTab);


        // Available active departments
        $departments = Department::where('status', 'active')->orderBy('name')->get();

        // Jobs from CRM deals and custom jobs
        $dealJobs = Deal::whereNotNull('job_number')
            ->where('job_number', '!=', '')
            ->orderBy('job_number', 'desc')
            ->get(['job_number', 'title', 'customer_name'])
            ->map(function ($deal) {
                $label = $deal->title ?: '';
                if ($deal->customer_name) {
                    $label = $label ? "{$label} ({$deal->customer_name})" : $deal->customer_name;
                }
                return [
                    'no' => $deal->job_number,
                    'label' => $label,
                ];
            })
            ->toArray();

        // Custom jobs stored in Setting
        $customJobsSetting = Setting::get('late_work_custom_jobs', '[]');
        $customJobs = json_decode($customJobsSetting, true);
        if (!is_array($customJobs)) {
            $customJobs = [];
        }

        // Combine unique jobs by 'no'
        $allJobsMap = [];
        foreach ($dealJobs as $dj) {
            $allJobsMap[$dj['no']] = $dj;
        }
        foreach ($customJobs as $cj) {
            if (!empty($cj['no'])) {
                $allJobsMap[$cj['no']] = $cj;
            }
        }
        $jobs = array_values($allJobsMap);

        // 1. My requests
        $myRequests = LateWorkRequest::where('user_id', $user->id)
            ->with(['hod', 'decidedBy', 'department', 'pettyCashRequest'])
            ->orderBy('created_at', 'desc')
            ->get();

        // 2. Approvals
        $canApproveAny = $user->hasAdminPrivileges() || $user->isHRAdmin() || $user->isManagement() || $user->role === 'HOD';
        $pendingApprovals = collect();
        $recentDecided = collect();

        if ($canApproveAny) {
            $allPending = LateWorkRequest::where('status', 'pending')
                ->with(['user', 'hod', 'department', 'pettyCashRequest'])
                ->orderBy('created_at', 'desc')
                ->get();

            $pendingApprovals = $allPending->filter(fn($r) => $r->canApprove($user))->values();

            $allDecided = LateWorkRequest::whereIn('status', ['approved', 'declined'])
                ->with(['user', 'hod', 'decidedBy', 'department', 'pettyCashRequest'])
                ->orderBy('decided_at', 'desc')
                ->limit(30)
                ->get();

            $recentDecided = $allDecided->filter(fn($r) => $r->canApprove($user))->values();
        }

        // 3. Log tab (for HR Admin, Management, Admins)
        $canViewLog = $user->canViewLateWorkLog();
        $isHodOnly = $user->isHodOnly();

        // If HOD accesses log tab directly, route them to team's requests
        if ($isHodOnly && in_array($tab, ['log', 'team'])) {
            $tab = 'team';
        }

        $logRequests = collect();
        $stats = [
            'total' => 0,
            'approved' => 0,
            'meals' => 0,
            'mealTotal' => 0,
            'taxis' => 0,
            'weekendDays' => 0,
            'lieuDays' => 0,
        ];

        $filterMonth = $request->input('month', date('Y-m'));
        $filterDept = $request->input('dept', '');
        $filterStatus = $request->input('status', '');
        $filterType = $request->input('type', '');

        if ($canViewLog) {
            $logQuery = LateWorkRequest::with(['user', 'hod', 'decidedBy', 'department', 'pettyCashRequest'])
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc');

            if (!empty($filterMonth)) {
                $logQuery->where('date', 'like', $filterMonth . '%');
            }
            if (!empty($filterDept)) {
                $logQuery->where('department_id', $filterDept);
            }
            if (!empty($filterStatus)) {
                $logQuery->where('status', $filterStatus);
            }
            if (!empty($filterType)) {
                $logQuery->where('type', $filterType);
            }

            $logRequests = $logQuery->get();

            // Compute statistics
            $approvedLog = $logRequests->where('status', 'approved');
            $stats['total'] = $logRequests->count();
            $stats['approved'] = $approvedLog->count();
            $stats['meals'] = $approvedLog->where('meal', true)->count();
            $stats['mealTotal'] = $stats['meals'] * 1000;
            $stats['taxis'] = $approvedLog->where('taxi', true)->count();
            $stats['weekendDays'] = $approvedLog->where('type', 'weekend')->count();
            $stats['lieuDays'] = $approvedLog->reduce(function ($carry, $r) {
                if ($r->lieu === 'one_and_half') return $carry + 1.5;
                if ($r->lieu === 'full') return $carry + 1.0;
                if ($r->lieu === 'half') return $carry + 0.5;
                return $carry;
            }, 0.0);
        }

        // 3b. Team's requests tab (for HODs)
        $teamRequests = collect();
        $teamStats = [
            'total' => 0,
            'pending' => 0,
            'approved' => 0,
            'declined' => 0,
        ];
        $teamMonth = $request->input('month', date('Y-m'));
        $teamStatus = $request->input('status', '');
        $teamType = $request->input('type', '');

        if ($isHodOnly) {
            $hodDeptIds = Department::whereJsonContains('hod_ids', $user->id)
                ->orWhereJsonContains('hod_ids', (string)$user->id)
                ->orWhere('name', $user->department)
                ->pluck('id')
                ->toArray();

            $teamQuery = LateWorkRequest::with(['user', 'hod', 'decidedBy', 'department', 'pettyCashRequest'])
                ->where(function ($q) use ($user, $hodDeptIds) {
                    $q->where('hod_id', $user->id)
                      ->orWhere('department_name', $user->department)
                      ->orWhereHas('user', function ($u) use ($user) {
                          $u->where('supervisor_id', $user->id)
                            ->orWhere('department', $user->department);
                      });
                    if (!empty($hodDeptIds)) {
                        $q->orWhereIn('department_id', $hodDeptIds);
                    }
                })
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc');

            if (!empty($teamMonth)) {
                $teamQuery->where('date', 'like', $teamMonth . '%');
            }
            if (!empty($teamStatus)) {
                $teamQuery->where('status', $teamStatus);
            }
            if (!empty($teamType)) {
                $teamQuery->where('type', $teamType);
            }

            $teamRequests = $teamQuery->get();

            $teamStats['total'] = $teamRequests->count();
            $teamStats['pending'] = $teamRequests->where('status', 'pending')->count();
            $teamStats['approved'] = $teamRequests->where('status', 'approved')->count();
            $teamStats['declined'] = $teamRequests->where('status', 'declined')->count();
        }

        // 4. Setup tab (Admins, HR Admin)
        $canManageSetup = $user->canManageLateWorkSetup();
        $allUsers = User::orderBy('name')->get();

        // Departments with pre-loaded HOD user objects
        $departmentsWithHods = $departments->map(function ($dept) {
            return [
                'id' => $dept->id,
                'name' => $dept->name,
                'hod_ids' => is_array($dept->hod_ids) ? array_map('intval', $dept->hod_ids) : [],
                'hods' => $dept->hods->map(fn($h) => ['id' => $h->id, 'name' => $h->name, 'email' => $h->email])->toArray(),
            ];
        });

        // Resolve user's assigned HOD from the users list (supervisor_id / associated_hod)
        $userAssignedHod = $user->associated_hod 
            ?? $user->supervisor 
            ?? ($user->supervisor_id ? User::find($user->supervisor_id) : null)
            ?? User::where('role', 'HOD')->where('department', $user->department)->first();

        // All HOD users from the users list
        $allHods = User::where('role', 'HOD')
            ->orWhereIn('id', function ($q) {
                $q->select('supervisor_id')->from('users')->whereNotNull('supervisor_id');
            })
            ->orderBy('name')
            ->get();

        // Holiday Calendar settings
        $canManageHolidays = $user->canManageHolidays();
        $holidaysMap = Holiday::getHolidaysMap();
        $holidayTypes = HolidayType::getAllTypes();
        // Expense Categories for Petty Cash allowances (Meal allowance & PickMe/Travel)
        $expenseCategories = ExpenseCategory::where('status', 'active')
            ->where('name', '!=', 'IOU')
            ->orderBy('name')
            ->get();

        $mealCategory = $expenseCategories->first(fn($c) => stripos($c->name, 'Meal') !== false) ?? $expenseCategories->first();
        $travelCategory = $expenseCategories->first(fn($c) => stripos($c->name, 'Travel') !== false || stripos($c->name, 'PickMe') !== false || stripos($c->name, 'Transport') !== false) ?? $expenseCategories->skip(1)->first();

        return view('late-work.index', compact(
            'user',
            'tab',
            'departments',
            'departmentsWithHods',
            'userAssignedHod',
            'allHods',
            'jobs',
            'customJobs',
            'myRequests',
            'pendingApprovals',
            'recentDecided',
            'canApproveAny',
            'canViewLog',
            'isHodOnly',
            'teamRequests',
            'teamStats',
            'teamMonth',
            'teamStatus',
            'teamType',
            'canManageSetup',
            'canManageHolidays',
            'holidaysMap',
            'holidayTypes',
            'expenseCategories',
            'mealCategory',
            'travelCategory',
            'logRequests',
            'stats',
            'filterMonth',
            'filterDept',
            'filterStatus',
            'filterType',
            'allUsers'
        ));
    }

    /**
     * Store a newly created Late & Weekend Work request.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'type' => 'required|in:late,weekend',
            'dept_id' => 'required|exists:departments,id',
            'date' => 'required|date',
            'is_holiday' => 'nullable|boolean',
            'job_number' => 'nullable|string',
            'job_numbers' => 'nullable|array',
            'job_numbers.*' => 'string',
            'job_label' => 'nullable|string',
            'reason' => 'nullable|string|max:600',
            'start_time' => 'nullable|string|max:10',
            'finish_time' => 'required|string|max:10',
            'is_wfh' => 'nullable|boolean',
            'meal' => 'nullable|boolean',
            'taxi' => 'nullable|boolean',
            'lieu' => 'nullable|in:half,full,one_and_half',
            'hod_id' => 'nullable|exists:users,id',
            'petty_cash_items' => 'nullable|array',
            'petty_cash_items.*.expense_category_id' => 'nullable|exists:expense_categories,id',
            'petty_cash_items.*.amount' => 'nullable|numeric|min:0',
            'petty_cash_items.*.description' => 'nullable|string|max:255',
            'petty_cash_items.*.attendees' => 'nullable|array|max:5',
            'petty_cash_items.*.attendees.*' => 'nullable|string|max:100',
            'petty_cash_extra_notes' => 'nullable|string|max:1000',
            'petty_cash_proofs' => 'nullable|array',
            'petty_cash_proofs.*' => 'file|mimes:jpeg,png,jpg,pdf,doc,docx|max:10240',
            'proofs' => 'nullable|array',
            'proofs.*' => 'file|mimes:jpeg,png,jpg,pdf,doc,docx|max:10240',
        ]);

        // Combine job_numbers or fallback to job_number
        $jobNumber = '';
        if (!empty($validated['job_numbers']) && is_array($validated['job_numbers'])) {
            $cleanJobs = array_values(array_filter(array_map('trim', $validated['job_numbers'])));
            $jobNumber = implode(', ', $cleanJobs);
        }
        if (empty($jobNumber) && !empty($validated['job_number'])) {
            $jobNumber = trim($validated['job_number']);
        }

        if (empty($jobNumber)) {
            return back()->withInput()->with('error', 'Please select at least one job number.');
        }

        $type = $validated['type'];
        $date = Carbon::parse($validated['date']);
        $isHoliday = !empty($validated['is_holiday']);

        // Weekend validation rules
        if ($type === 'weekend') {
            if (!$date->isWeekend() && !$isHoliday) {
                return back()->withInput()->with('error', 'Weekend requests must fall on a Saturday or Sunday, or be marked as a public holiday.');
            }
            if (empty($validated['start_time'])) {
                return back()->withInput()->with('error', 'Start time is required for weekend or public holiday requests.');
            }
            if ($validated['start_time'] >= $validated['finish_time']) {
                return back()->withInput()->with('error', 'Finish time must be later than start time.');
            }
        } else {
            if ($date->isWeekend()) {
                return back()->withInput()->with('error', 'Weekday requests cannot fall on a weekend. Please select Weekend or Public Holiday instead.');
            }

            // Weekday Late Work: expected finish time must be between 8:00 PM and 8:00 AM
            $parts = explode(':', $validated['finish_time']);
            $finishHour = (int) ($parts[0] ?? 0);
            $finishMin = (int) ($parts[1] ?? 0);

            // Normalize 12-hour clock entries without PM (09:00 to 11:59) -> (21:00 to 23:59)
            if ($finishHour >= 9 && $finishHour <= 11) {
                $finishHour += 12;
                $validated['finish_time'] = sprintf('%02d:%02d', $finishHour, $finishMin);
            }

            $isWithinLateWindow = ($finishHour >= 20 || $finishHour < 8 || ($finishHour === 8 && $finishMin === 0));
            if (!$isWithinLateWindow) {
                return back()->withInput()->with('error', 'Expected finish time for weekday late work must be between 8:00 PM and 8:00 AM.');
            }
        }

        $dept = Department::findOrFail($validated['dept_id']);

        // Eligibility for meal and taxi:
        // Weekend is eligible. Weekday requires finish time between 8:00 PM (20:00) and 8:00 AM (08:00).
        $finishParts = explode(':', $validated['finish_time']);
        $finishHour = (int) ($finishParts[0] ?? 0);
        $finishMin = (int) ($finishParts[1] ?? 0);
        $isLateNight = ($finishHour >= 20 || $finishHour < 8 || ($finishHour === 8 && $finishMin === 0));
        $eligible = ($type === 'weekend' || $isLateNight);

        $isWfh = ($type === 'weekend') && !empty($validated['is_wfh']);

        // When working from home (WFH) on weekend/holiday, meal allowance and PickMe are excluded
        $meal = !$isWfh && $eligible && !empty($validated['meal']);
        $taxi = !$isWfh && $eligible && !empty($validated['taxi']);

        // Weekend/Holiday lieu leave policy:
        // < 4 hrs: no leave (not saved as leave)
        // 4 - 7.9 hrs: half day (0.5)
        // 8 - 11.9 hrs: full day (1.0)
        // >= 12 hrs: 1 day & half day (1.5 days)
        $lieu = null;
        if ($type === 'weekend') {
            $startParts = explode(':', $validated['start_time']);
            $startMins = ((int) ($startParts[0] ?? 0)) * 60 + ((int) ($startParts[1] ?? 0));
            $finishMins = $finishHour * 60 + $finishMin;
            $diffMins = $finishMins - $startMins;
            if ($diffMins < 0) {
                $diffMins += 24 * 60;
            }
            $diffHours = round($diffMins / 60, 2);

            if ($diffHours < 4.0) {
                $lieu = null;
            } elseif ($diffHours < 8.0) {
                $lieu = 'half';
            } elseif ($diffHours < 12.0) {
                $lieu = 'full';
            } else {
                $lieu = 'one_and_half';
            }
        }

        // Next day report calculation for weekdays
        $nextDayReport = ($type === 'late')
            ? LateWorkRequest::calculateNextDayReport($validated['finish_time'])['key']
            : null;

        // Deadline check (After 3 PM cutoff notice)
        $isAfter3pm = LateWorkRequest::isPastMealDeadline($type, $date);

        // Resolve HOD strictly prioritizing the user's assigned HOD from the users list:
        $resolvedHodId = $validated['hod_id'] ?? null;
        if (!$resolvedHodId) {
            if ($user->associated_hod) {
                $resolvedHodId = $user->associated_hod->id;
            } elseif ($user->supervisor_id) {
                $resolvedHodId = $user->supervisor_id;
            } elseif ($user->supervisor) {
                $resolvedHodId = $user->supervisor->id;
            } elseif (!empty($user->department)) {
                $deptHod = User::where('role', 'HOD')->where('department', $user->department)->first();
                if ($deptHod) {
                    $resolvedHodId = $deptHod->id;
                }
            }
            if (!$resolvedHodId && $dept->hods->isNotEmpty()) {
                $resolvedHodId = $dept->hods->first()->id;
            }
        }

        if ($meal || $taxi) {
            $activeUnsettledIou = PettyCashRequest::where('user_id', $user->id)
                ->where('is_iou', true)
                ->whereIn('status', ['approved', 'iou_issued', 'pending_settlement', 'pending_settlement_hod'])
                ->first();

            if ($activeUnsettledIou) {
                return back()->withInput()->with('error', 'Request Blocked: You cannot request allowances (Petty Cash) because you have an active unsettled IOU (' . $activeUnsettledIou->reference_number . '). Please settle your existing IOU first.');
            }
        }

        $record = LateWorkRequest::create([
            'reference_number' => LateWorkRequest::generateReferenceNumber(),
            'user_id' => $user->id,
            'department_id' => $dept->id,
            'department_name' => $dept->name,
            'hod_id' => $resolvedHodId,
            'type' => $type,
            'date' => $date->toDateString(),
            'is_holiday' => $isHoliday,
            'job_number' => $jobNumber,
            'job_label' => trim($validated['job_label'] ?? ''),
            'reason' => !empty($validated['reason']) ? trim($validated['reason']) : null,
            'start_time' => $type === 'weekend' ? $validated['start_time'] : null,
            'finish_time' => $validated['finish_time'],
            'is_wfh' => $isWfh,
            'meal' => $meal,
            'taxi' => $taxi,
            'lieu' => $lieu,
            'next_day' => $nextDayReport,
            'status' => 'pending',
            'is_after_3pm' => $isAfter3pm,
        ]);

        // Automatically create and link Petty Cash Request if Meal or PickMe allowances selected
        if ($meal || $taxi) {
            $mealCategory = ExpenseCategory::where('name', 'LIKE', '%Meal%')->first()
                ?? ExpenseCategory::firstOrCreate(['name' => 'Meal allowance', 'status' => 'active']);
            $travelCategory = ExpenseCategory::where('name', 'LIKE', '%Travel%')
                ->orWhere('name', 'LIKE', '%PickMe%')
                ->orWhere('name', 'LIKE', '%Transport%')
                ->first()
                ?? ExpenseCategory::firstOrCreate(['name' => 'Travel', 'status' => 'active']);

            $submittedItems = $request->input('petty_cash_items', []);
            $itemsToCreate = [];

            if (!empty($submittedItems) && is_array($submittedItems)) {
                foreach ($submittedItems as $sItem) {
                    $catId = !empty($sItem['expense_category_id']) ? (int)$sItem['expense_category_id'] : null;
                    if (!$catId) continue;
                    $amt = (float)($sItem['amount'] ?? 0);
                    $attendees = null;
                    if (!empty($sItem['attendees']) && is_array($sItem['attendees'])) {
                        $attendees = array_values(array_slice(array_filter(array_map('trim', $sItem['attendees']), fn($n) => $n !== ''), 0, 5));
                    }
                    $itemsToCreate[] = [
                        'expense_category_id' => $catId,
                        'amount' => max(0, $amt),
                        'description' => !empty($sItem['description']) ? trim($sItem['description']) : 'Work Tracker Allowance',
                        'attendees' => !empty($attendees) ? $attendees : null,
                    ];
                }
            }

            if ($meal) {
                $hasMeal = false;
                foreach ($itemsToCreate as $it) {
                    if ($mealCategory && $it['expense_category_id'] == $mealCategory->id) {
                        $hasMeal = true;
                        break;
                    }
                }
                if (!$hasMeal && $mealCategory) {
                    $itemsToCreate[] = [
                        'expense_category_id' => $mealCategory->id,
                        'amount' => 1000.00,
                        'description' => 'Meal allowance - ' . ($type === 'weekend' ? 'Weekend shift' : 'Late shift') . ' on ' . $date->toDateString(),
                        'attendees' => null,
                    ];
                }
            }

            if ($taxi) {
                $hasTravel = false;
                foreach ($itemsToCreate as $it) {
                    if ($travelCategory && $it['expense_category_id'] == $travelCategory->id) {
                        $hasTravel = true;
                        break;
                    }
                }
                if (!$hasTravel && $travelCategory) {
                    $taxiAmt = (float) $request->input('petty_cash_taxi_amount', 0);
                    $itemsToCreate[] = [
                        'expense_category_id' => $travelCategory->id,
                        'amount' => $taxiAmt > 0 ? $taxiAmt : 0.00,
                        'description' => 'PickMe ride home - ' . ($type === 'weekend' ? 'Weekend shift' : 'Late shift') . ' on ' . $date->toDateString(),
                        'attendees' => null,
                    ];
                }
            }

            $totalPettyCash = array_sum(array_column($itemsToCreate, 'amount'));

            $pcStatus = 'pending_hod';
            $isRequesterHod = ($user->role === 'HOD' || $user->hasRole('HOD'));
            if ($user->role === 'HR Admin' && !$user->associated_hod && !$resolvedHodId) {
                $isRequesterHod = true;
            }
            if ($isRequesterHod && !$resolvedHodId) {
                $pcStatus = 'pending_super_admin';
            }

            $pettyCash = PettyCashRequest::create([
                'reference_number' => PettyCashRequest::generateReferenceNumber(),
                'user_id' => $user->id,
                'hod_id' => $resolvedHodId,
                'department' => $dept->name,
                'job_number' => $jobNumber,
                'extra_notes' => $request->input('petty_cash_extra_notes') ?: (!empty($validated['reason']) ? ('Work Tracker: ' . trim($validated['reason'])) : 'Work Tracker'),
                'total_amount' => $totalPettyCash,
                'approved_amount' => $totalPettyCash,
                'is_iou' => false,
                'status' => $pcStatus,
                'source' => 'work_tracker',
                'late_work_request_id' => $record->id,
            ]);

            foreach ($itemsToCreate as $it) {
                PettyCashItem::create([
                    'petty_cash_request_id' => $pettyCash->id,
                    'expense_category_id' => $it['expense_category_id'],
                    'amount' => $it['amount'],
                    'description' => $it['description'],
                    'attendees' => $it['attendees'],
                ]);
            }

            // Save proofs if provided
            $proofFiles = $request->file('petty_cash_proofs') ?? $request->file('proofs') ?? [];
            if (!empty($proofFiles) && is_array($proofFiles)) {
                foreach ($proofFiles as $file) {
                    if ($file && $file->isValid()) {
                        PettyCashProof::createFromFile($file, $pettyCash->id);
                    }
                }
            }

            $hasProofs = $pettyCash->proofs()->count() > 0;
            $pettyCash->update([
                'is_draft' => true,
                'pending_proofs' => !$hasProofs,
            ]);

            $this->logAction("Created linked Petty Cash Request {$pettyCash->reference_number} via Work Tracker", $record);
        }

        $this->logAction("Submitted Late & Weekend Work request: {$record->reference_number}", $record);

        // Trigger emails (HOD approval + User confirmation)
        LateWorkMailService::sendSubmissionEmails($record);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Request submitted and sent to your HOD for approval.',
                'request' => $record,
            ]);
        }

        return redirect()->route('late-work.index', ['tab' => 'mine'])->with('success', 'Your request has been sent to your HOD for approval.');
    }

    /**
     * Approve a late work request.
     */
    public function approve(Request $request, LateWorkRequest $lateWorkRequest)
    {
        $user = auth()->user();

        if (!$lateWorkRequest->canApprove($user)) {
            return back()->with('error', 'You are not authorized to approve this request.');
        }

        if ($lateWorkRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been decided.');
        }

        $request->validate([
            'note' => 'nullable|string|max:255',
        ]);

        $lateWorkRequest->update([
            'status' => 'approved',
            'decided_by' => $user->id,
            'decided_at' => now(),
            'hod_note' => trim($request->input('note', '')),
        ]);

        $this->logAction("Approved Late & Weekend Work request: {$lateWorkRequest->reference_number}", $lateWorkRequest);

        // Save draft of Petty Cash request with HOD approved, pending proof attachments, routed to Finance
        $linkedPettyCash = PettyCashRequest::where('late_work_request_id', $lateWorkRequest->id)->first();
        if (!$linkedPettyCash && ($lateWorkRequest->meal || $lateWorkRequest->taxi)) {
            $mealCategory = ExpenseCategory::where('name', 'LIKE', '%Meal%')->first()
                ?? ExpenseCategory::firstOrCreate(['name' => 'Meal allowance', 'status' => 'active']);
            $travelCategory = ExpenseCategory::where('name', 'LIKE', '%Travel%')
                ->orWhere('name', 'LIKE', '%PickMe%')
                ->orWhere('name', 'LIKE', '%Transport%')
                ->first()
                ?? ExpenseCategory::firstOrCreate(['name' => 'Travel', 'status' => 'active']);

            $itemsToCreate = [];
            if ($lateWorkRequest->meal) {
                $itemsToCreate[] = [
                    'expense_category_id' => $mealCategory->id,
                    'amount' => 1000.00,
                    'description' => 'Meal allowance - ' . ($lateWorkRequest->type === 'weekend' ? 'Weekend shift' : 'Late shift') . ' on ' . ($lateWorkRequest->date ? $lateWorkRequest->date->toDateString() : date('Y-m-d')),
                    'attendees' => null,
                ];
            }
            if ($lateWorkRequest->taxi) {
                $itemsToCreate[] = [
                    'expense_category_id' => $travelCategory->id,
                    'amount' => 0.00,
                    'description' => 'PickMe ride home - ' . ($lateWorkRequest->type === 'weekend' ? 'Weekend shift' : 'Late shift') . ' on ' . ($lateWorkRequest->date ? $lateWorkRequest->date->toDateString() : date('Y-m-d')),
                    'attendees' => null,
                ];
            }
            $totalAmount = array_sum(array_column($itemsToCreate, 'amount'));

            $linkedPettyCash = PettyCashRequest::create([
                'reference_number' => PettyCashRequest::generateReferenceNumber(),
                'user_id' => $lateWorkRequest->user_id,
                'hod_id' => $user->id,
                'department' => $lateWorkRequest->department_name ?: ($lateWorkRequest->department->name ?? 'General'),
                'job_number' => $lateWorkRequest->job_number,
                'extra_notes' => 'Work Tracker: ' . ($lateWorkRequest->reason ?? 'Late work allowances'),
                'total_amount' => $totalAmount,
                'approved_amount' => $totalAmount,
                'is_iou' => false,
                'status' => 'pending_super_admin',
                'source' => 'work_tracker',
                'late_work_request_id' => $lateWorkRequest->id,
                'is_draft' => true,
                'pending_proofs' => true,
                'hod_approved_at' => now(),
            ]);

            foreach ($itemsToCreate as $it) {
                PettyCashItem::create([
                    'petty_cash_request_id' => $linkedPettyCash->id,
                    'expense_category_id' => $it['expense_category_id'],
                    'amount' => $it['amount'],
                    'description' => $it['description'],
                    'attendees' => $it['attendees'],
                ]);
            }
        } elseif ($linkedPettyCash && in_array($linkedPettyCash->status, ['pending_hod', 'draft'])) {
            $hasProofs = $linkedPettyCash->proofs()->count() > 0;
            $linkedPettyCash->update([
                'status' => 'pending_super_admin',
                'hod_id' => $user->id,
                'hod_approved_at' => now(),
                'is_draft' => true,
                'pending_proofs' => !$hasProofs,
            ]);
        }

        if ($linkedPettyCash) {
            try {
                $superAdmins = PettyCashNotification::getSuperAdminRecipients($linkedPettyCash->user_id);
                if ($superAdmins->isNotEmpty()) {
                    \Illuminate\Support\Facades\Notification::send($superAdmins, new PettyCashNotification($linkedPettyCash, 'hod_approved', $user));
                }

                $requestedUser = User::find($linkedPettyCash->user_id);
                if ($requestedUser) {
                    $requestedUser->notify(new PettyCashNotification($linkedPettyCash, 'hod_approved', $user));
                }
            } catch (\Throwable $ne) {
                \Illuminate\Support\Facades\Log::warning('Petty cash auto-approval notification warning: ' . $ne->getMessage());
            }

            $this->logAction("Saved draft Petty Cash {$linkedPettyCash->reference_number} with HOD approval (pending proofs, routed to Finance) upon Work Tracker approval", $linkedPettyCash);
        }

        // Trigger emails (User decision email + HR Admin notification)
        LateWorkMailService::sendDecisionEmails($lateWorkRequest);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Request approved successfully.',
                'request' => $lateWorkRequest,
            ]);
        }

        return back()->with('success', "Request {$lateWorkRequest->reference_number} has been approved.");
    }

    /**
     * Decline a late work request.
     */
    public function decline(Request $request, LateWorkRequest $lateWorkRequest)
    {
        $user = auth()->user();

        if (!$lateWorkRequest->canApprove($user)) {
            return back()->with('error', 'You are not authorized to decline this request.');
        }

        if ($lateWorkRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been decided.');
        }

        $request->validate([
            'note' => 'required|string|min:3|max:500',
        ], [
            'note.required' => 'Please provide a note / reason for declining this request so the user is informed.',
        ]);

        $lateWorkRequest->update([
            'status' => 'declined',
            'decided_by' => $user->id,
            'decided_at' => now(),
            'hod_note' => trim($request->input('note', '')),
        ]);

        $this->logAction("Declined Late & Weekend Work request: {$lateWorkRequest->reference_number}", $lateWorkRequest);

        // Automatically decline linked Petty Cash Request
        $linkedPettyCash = PettyCashRequest::where('late_work_request_id', $lateWorkRequest->id)->first();
        if ($linkedPettyCash && $linkedPettyCash->status === 'pending_hod') {
            $declineNote = 'Work Tracker shift declined: ' . trim($request->input('note', ''));
            $linkedPettyCash->update([
                'status' => 'rejected_by_hod',
                'hod_rejection_note' => $declineNote,
            ]);

            try {
                $requestedUser = User::find($linkedPettyCash->user_id);
                if ($requestedUser) {
                    $requestedUser->notify(new PettyCashNotification($linkedPettyCash, 'hod_rejected', $user, $declineNote));
                }
            } catch (\Throwable $ne) {
                \Illuminate\Support\Facades\Log::warning('Petty cash auto-rejection notification warning: ' . $ne->getMessage());
            }

            $this->logAction("Automatically declined linked Petty Cash: {$linkedPettyCash->reference_number} upon Work Tracker decline", $linkedPettyCash);
        }

        // Trigger decision email to user
        LateWorkMailService::sendDecisionEmails($lateWorkRequest);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Request declined.',
                'request' => $lateWorkRequest,
            ]);
        }

        return back()->with('success', "Request {$lateWorkRequest->reference_number} has been declined.");
    }

    /**
     * Re-appeal a declined Late & Weekend Work request.
     */
    public function reappeal(Request $request, LateWorkRequest $lateWorkRequest)
    {
        $user = auth()->user();

        if (!$lateWorkRequest->canReappeal($user)) {
            return back()->with('error', 'You are not authorized to re-appeal this request.');
        }

        $validated = $request->validate([
            'reappeal_notes' => 'required|string|min:5|max:1000',
            'reason' => 'nullable|string|max:600',
            'start_time' => 'nullable|string|max:10',
            'finish_time' => 'nullable|string|max:10',
            'is_wfh' => 'nullable|boolean',
            'meal' => 'nullable|boolean',
            'taxi' => 'nullable|boolean',
            'lieu' => 'nullable|in:half,full,one_and_half',
        ], [
            'reappeal_notes.required' => 'Please provide an explanation or justification for your re-appeal.',
        ]);

        $updateData = [
            'status' => 'pending',
            'reappeal_notes' => trim($validated['reappeal_notes']),
            'reappealed_at' => now(),
            'reappeal_count' => ($lateWorkRequest->reappeal_count ?? 0) + 1,
            'decided_by' => null,
            'decided_at' => null,
        ];

        if (!empty($validated['reason'])) {
            $updateData['reason'] = trim($validated['reason']);
        }
        if (!empty($validated['finish_time'])) {
            $fTime = $validated['finish_time'];
            if ($lateWorkRequest->type === 'late') {
                $fParts = explode(':', $fTime);
                $fh = (int) ($fParts[0] ?? 0);
                $fm = (int) ($fParts[1] ?? 0);
                if ($fh >= 9 && $fh <= 11) {
                    $fh += 12;
                    $fTime = sprintf('%02d:%02d', $fh, $fm);
                }
                $isWithinLate = ($fh >= 20 || $fh < 8 || ($fh === 8 && $fm === 0));
                if (!$isWithinLate) {
                    return back()->withInput()->with('error', 'Expected finish time for weekday late work must be between 8:00 PM and 8:00 AM.');
                }
            }
            $updateData['finish_time'] = $fTime;
            if ($lateWorkRequest->type === 'late') {
                $updateData['next_day'] = LateWorkRequest::calculateNextDayReport($fTime)['key'];
            }
        }
        if ($lateWorkRequest->type === 'weekend' && !empty($validated['start_time'])) {
            $updateData['start_time'] = $validated['start_time'];
        }

        $isWfh = $lateWorkRequest->is_wfh;
        if ($lateWorkRequest->type === 'weekend' && $request->has('is_wfh')) {
            $isWfh = $request->boolean('is_wfh');
            $updateData['is_wfh'] = $isWfh;
        }

        if ($isWfh && $lateWorkRequest->type === 'weekend') {
            $updateData['meal'] = false;
            $updateData['taxi'] = false;
        } else {
            if ($request->has('meal')) {
                $updateData['meal'] = $request->boolean('meal');
            }
            if ($request->has('taxi')) {
                $updateData['taxi'] = $request->boolean('taxi');
            }
        }

        if ($lateWorkRequest->type === 'weekend') {
            $startTime = $validated['start_time'] ?? $lateWorkRequest->start_time;
            $finishTime = $fTime ?? $lateWorkRequest->finish_time;
            if ($startTime && $finishTime) {
                $startParts = explode(':', $startTime);
                $finishParts = explode(':', $finishTime);
                $startMins = ((int)($startParts[0] ?? 0)) * 60 + ((int)($startParts[1] ?? 0));
                $finishMins = ((int)($finishParts[0] ?? 0)) * 60 + ((int)($finishParts[1] ?? 0));
                $diffMins = $finishMins - $startMins;
                if ($diffMins < 0) {
                    $diffMins += 24 * 60;
                }
                $diffHours = round($diffMins / 60, 2);

                if ($diffHours < 4.0) {
                    $updateData['lieu'] = null;
                } elseif ($diffHours < 8.0) {
                    $updateData['lieu'] = 'half';
                } elseif ($diffHours < 12.0) {
                    $updateData['lieu'] = 'full';
                } else {
                    $updateData['lieu'] = 'one_and_half';
                }
            } elseif (!empty($validated['lieu'])) {
                $updateData['lieu'] = $validated['lieu'];
            }
        }

        $lateWorkRequest->update($updateData);

        $this->logAction("Re-appealed Late & Weekend Work request: {$lateWorkRequest->reference_number}", $lateWorkRequest);

        // Reset linked Petty Cash to pending_hod on re-appeal
        $linkedPettyCash = PettyCashRequest::where('late_work_request_id', $lateWorkRequest->id)->first();
        if ($linkedPettyCash && $linkedPettyCash->status === 'rejected_by_hod') {
            $linkedPettyCash->update([
                'status' => 'pending_hod',
                'reappeal_count' => ($linkedPettyCash->reappeal_count ?? 0) + 1,
            ]);
            $this->logAction("Reset linked Petty Cash {$linkedPettyCash->reference_number} to pending_hod on Work Tracker reappeal", $linkedPettyCash);
        }

        // Notify HOD/Supervisor about the re-appeal and send confirmation to user
        LateWorkMailService::sendSubmissionEmails($lateWorkRequest);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Request {$lateWorkRequest->reference_number} has been re-appealed and resubmitted to your HOD for review.",
                'request' => $lateWorkRequest,
            ]);
        }

        return redirect()->route('late-work.index', ['tab' => 'mine'])
            ->with('success', "Request {$lateWorkRequest->reference_number} has been re-appealed and resubmitted to your HOD for review.");
    }

    /**
     * Withdraw a pending request.
     */
    public function withdraw(Request $request, LateWorkRequest $lateWorkRequest)
    {
        $user = auth()->user();

        if (!$lateWorkRequest->canWithdraw($user)) {
            return back()->with('error', 'You cannot withdraw this request.');
        }

        $lateWorkRequest->update([
            'status' => 'withdrawn',
            'decided_at' => now(),
            'decided_by' => $user->id,
        ]);

        $this->logAction("Withdrew Late & Weekend Work request: {$lateWorkRequest->reference_number}", $lateWorkRequest);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Request withdrawn.',
                'request' => $lateWorkRequest,
            ]);
        }

        return back()->with('success', 'Your request has been withdrawn.');
    }

    /**
     * Update a Late & Weekend Work request (Restricted to IT Admin and Finance Admin).
     */
    public function update(Request $request, LateWorkRequest $lateWorkRequest)
    {
        $user = auth()->user();

        if (!$user || !$user->canEditDeleteLateWork()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthorized action. Only IT Admin and Finance Admin can edit late work requests.'], 403);
            }
            return back()->with('error', 'Unauthorized action. Only IT Admin and Finance Admin can edit late work requests.');
        }

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'hod_id' => 'nullable|exists:users,id',
            'type' => 'required|in:late,weekend',
            'date' => 'required|date',
            'is_holiday' => 'nullable|boolean',
            'is_wfh' => 'nullable|boolean',
            'job_numbers' => 'nullable|array',
            'job_numbers.*' => 'nullable|string',
            'job_number' => 'nullable|string',
            'job_label' => 'nullable|string',
            'start_time' => 'nullable|string|max:10',
            'finish_time' => 'required|string|max:10',
            'meal' => 'nullable|boolean',
            'taxi' => 'nullable|boolean',
            'lieu' => 'nullable|in:half,full,one_and_half',
            'next_day' => 'nullable|string|max:50',
            'reason' => 'nullable|string|max:1000',
            'status' => 'required|in:pending,approved,declined,withdrawn',
            'hod_note' => 'nullable|string|max:1000',
        ]);

        // Job numbers
        $jobNumber = '';
        if (!empty($validated['job_numbers']) && is_array($validated['job_numbers'])) {
            $cleanJobs = array_values(array_filter(array_map('trim', $validated['job_numbers'])));
            $jobNumber = implode(', ', $cleanJobs);
        }
        if (empty($jobNumber) && !empty($validated['job_number'])) {
            $jobNumber = trim($validated['job_number']);
        }
        if (empty($jobNumber)) {
            $jobNumber = $lateWorkRequest->job_number;
        }

        $targetUserId = !empty($validated['user_id']) ? (int)$validated['user_id'] : $lateWorkRequest->user_id;
        $deptId = !empty($validated['department_id']) ? (int)$validated['department_id'] : $lateWorkRequest->department_id;
        $deptName = $lateWorkRequest->department_name;
        if ($deptId) {
            $dept = Department::find($deptId);
            if ($dept) {
                $deptName = $dept->name;
            }
        }

        $targetHodId = array_key_exists('hod_id', $validated) ? ($validated['hod_id'] ? (int)$validated['hod_id'] : null) : $lateWorkRequest->hod_id;
        $type = $validated['type'];
        $date = Carbon::parse($validated['date']);
        $isHoliday = !empty($validated['is_holiday']);
        $isWfh = ($type === 'weekend') && !empty($validated['is_wfh']);

        // Time normalization
        $finishParts = explode(':', $validated['finish_time']);
        $finishHour = (int) ($finishParts[0] ?? 0);
        $finishMin = (int) ($finishParts[1] ?? 0);
        if ($type === 'late' && $finishHour >= 9 && $finishHour <= 11) {
            $finishHour += 12;
            $validated['finish_time'] = sprintf('%02d:%02d', $finishHour, $finishMin);
        }

        // Allowances: WFH on weekend excludes meal & taxi
        $meal = !$isWfh && !empty($validated['meal']);
        $taxi = !$isWfh && !empty($validated['taxi']);

        // Lieu leave
        $lieu = null;
        if ($type === 'weekend') {
            if (!empty($validated['lieu'])) {
                $lieu = $validated['lieu'];
            } elseif (!empty($validated['start_time'])) {
                $startParts = explode(':', $validated['start_time']);
                $startMins = ((int) ($startParts[0] ?? 0)) * 60 + ((int) ($startParts[1] ?? 0));
                $finishMins = $finishHour * 60 + $finishMin;
                $diffMins = $finishMins - $startMins;
                if ($diffMins < 0) {
                    $diffMins += 24 * 60;
                }
                $diffHours = round($diffMins / 60, 2);
                if ($diffHours < 4.0) {
                    $lieu = null;
                } elseif ($diffHours < 8.0) {
                    $lieu = 'half';
                } elseif ($diffHours < 12.0) {
                    $lieu = 'full';
                } else {
                    $lieu = 'one_and_half';
                }
            }
        }

        // Next day reporting time
        $nextDay = null;
        if ($type === 'late') {
            $nextDay = !empty($validated['next_day'])
                ? $validated['next_day']
                : LateWorkRequest::calculateNextDayReport($validated['finish_time'])['key'];
        }

        $newStatus = $validated['status'];
        $decidedBy = $lateWorkRequest->decided_by;
        $decidedAt = $lateWorkRequest->decided_at;

        if (in_array($newStatus, ['approved', 'declined'])) {
            if (!$decidedBy || $newStatus !== $lateWorkRequest->status) {
                $decidedBy = $user->id;
                $decidedAt = now();
            }
        } elseif ($newStatus === 'pending') {
            $decidedBy = null;
            $decidedAt = null;
        }

        $lateWorkRequest->update([
            'user_id' => $targetUserId,
            'department_id' => $deptId,
            'department_name' => $deptName,
            'hod_id' => $targetHodId,
            'type' => $type,
            'date' => $date->toDateString(),
            'is_holiday' => $isHoliday,
            'is_wfh' => $isWfh,
            'job_number' => $jobNumber,
            'job_label' => trim($validated['job_label'] ?? $lateWorkRequest->job_label ?? ''),
            'reason' => !empty($validated['reason']) ? trim($validated['reason']) : null,
            'start_time' => $type === 'weekend' ? ($validated['start_time'] ?? null) : null,
            'finish_time' => $validated['finish_time'],
            'meal' => $meal,
            'taxi' => $taxi,
            'lieu' => $lieu,
            'next_day' => $nextDay,
            'status' => $newStatus,
            'decided_by' => $decidedBy,
            'decided_at' => $decidedAt,
            'hod_note' => $validated['hod_note'] ?? null,
        ]);

        // Sync linked Petty Cash Request if exists
        $linkedPettyCash = $lateWorkRequest->pettyCashRequest;
        if ($linkedPettyCash) {
            $linkedPettyCash->update([
                'user_id' => $targetUserId,
                'hod_id' => $targetHodId,
                'department' => $deptName,
                'job_number' => $jobNumber,
            ]);

            if ($newStatus === 'declined' && $linkedPettyCash->status === 'pending_hod') {
                $linkedPettyCash->update([
                    'status' => 'rejected_by_hod',
                    'hod_rejection_note' => 'Shift declined by Admin: ' . ($validated['hod_note'] ?? ''),
                ]);
            } elseif ($newStatus === 'approved' && in_array($linkedPettyCash->status, ['pending_hod', 'rejected_by_hod'])) {
                $linkedPettyCash->update([
                    'status' => 'approved',
                    'hod_approved_at' => now(),
                ]);
            }

            if (!$meal && !$taxi) {
                $linkedPettyCash->items()->delete();
                $linkedPettyCash->update(['total_amount' => 0, 'approved_amount' => 0]);
            } else {
                $mealCat = ExpenseCategory::where('name', 'LIKE', '%Meal%')->first();
                $travelCat = ExpenseCategory::where('name', 'LIKE', '%Travel%')->orWhere('name', 'LIKE', '%PickMe%')->first();

                if ($meal && $mealCat) {
                    $hasMeal = $linkedPettyCash->items()->where('expense_category_id', $mealCat->id)->exists();
                    if (!$hasMeal) {
                        PettyCashItem::create([
                            'petty_cash_request_id' => $linkedPettyCash->id,
                            'expense_category_id' => $mealCat->id,
                            'amount' => 1000.00,
                            'description' => 'Meal allowance',
                        ]);
                    }
                } elseif (!$meal && $mealCat) {
                    $linkedPettyCash->items()->where('expense_category_id', $mealCat->id)->delete();
                }

                if (!$taxi && $travelCat) {
                    $linkedPettyCash->items()->where('expense_category_id', $travelCat->id)->delete();
                }

                $tot = $linkedPettyCash->items()->sum('amount');
                $linkedPettyCash->update(['total_amount' => $tot, 'approved_amount' => $tot]);
            }
        } elseif ($meal || $taxi) {
            $mealCategory = ExpenseCategory::where('name', 'LIKE', '%Meal%')->first()
                ?? ExpenseCategory::firstOrCreate(['name' => 'Meal allowance', 'status' => 'active']);
            $travelCategory = ExpenseCategory::where('name', 'LIKE', '%Travel%')
                ->orWhere('name', 'LIKE', '%PickMe%')
                ->first()
                ?? ExpenseCategory::firstOrCreate(['name' => 'Travel', 'status' => 'active']);

            $newPc = PettyCashRequest::create([
                'reference_number' => PettyCashRequest::generateReferenceNumber(),
                'user_id' => $targetUserId,
                'hod_id' => $targetHodId,
                'department' => $deptName,
                'job_number' => $jobNumber,
                'extra_notes' => !empty($validated['reason']) ? ('Work Tracker: ' . trim($validated['reason'])) : 'Work Tracker',
                'total_amount' => $meal ? 1000 : 0,
                'approved_amount' => $meal ? 1000 : 0,
                'is_iou' => false,
                'status' => $newStatus === 'approved' ? 'approved' : 'pending_hod',
                'source' => 'work_tracker',
                'late_work_request_id' => $lateWorkRequest->id,
            ]);

            if ($meal) {
                PettyCashItem::create([
                    'petty_cash_request_id' => $newPc->id,
                    'expense_category_id' => $mealCategory->id,
                    'amount' => 1000.00,
                    'description' => 'Meal allowance',
                ]);
            }
        }

        $this->logAction("Updated Late & Weekend Work request: {$lateWorkRequest->reference_number}", $lateWorkRequest);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Request {$lateWorkRequest->reference_number} updated successfully.",
                'request' => $lateWorkRequest->fresh(),
            ]);
        }

        return back()->with('success', "Request {$lateWorkRequest->reference_number} updated successfully.");
    }

    /**
     * Delete a Late & Weekend Work request (Restricted to IT Admin and Finance Admin).
     */
    public function destroy(LateWorkRequest $lateWorkRequest, Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->canEditDeleteLateWork()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthorized action. Only IT Admin and Finance Admin can delete late work requests.'], 403);
            }
            return back()->with('error', 'Unauthorized action. Only IT Admin and Finance Admin can delete late work requests.');
        }

        $ref = $lateWorkRequest->reference_number;

        // Clean up linked petty cash request if one exists
        if ($lateWorkRequest->pettyCashRequest) {
            $pettyCash = $lateWorkRequest->pettyCashRequest;

            foreach ($pettyCash->proofs as $proof) {
                $fullPath = public_path($proof->file_path);
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }
            if ($pettyCash->signature_path) {
                $sigPath = public_path($pettyCash->signature_path);
                if (file_exists($sigPath)) {
                    @unlink($sigPath);
                }
            }
            if ($pettyCash->settlement_signature_path) {
                $setSigPath = public_path($pettyCash->settlement_signature_path);
                if (file_exists($setSigPath)) {
                    @unlink($setSigPath);
                }
            }

            $pettyCash->items()->delete();
            $pettyCash->proofs()->delete();
            $pettyCash->delete();
        }

        $lateWorkRequest->delete();

        $this->logAction("Deleted Late & Weekend Work request: {$ref}", $lateWorkRequest);

        $msg = "Late & Weekend Work request {$ref} was deleted successfully.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Export requests log to CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $user = auth()->user();
        if (!$user->canViewLateWorkLog()) {
            abort(403, 'Unauthorized');
        }

        $month = $request->input('month', date('Y-m'));
        $dept = $request->input('dept');
        $status = $request->input('status');
        $type = $request->input('type');

        $query = LateWorkRequest::with(['user', 'hod', 'decidedBy', 'department'])
            ->orderBy('date', 'desc');

        if (!empty($month)) {
            $query->where('date', 'like', $month . '%');
        }
        if (!empty($dept)) {
            $query->where('department_id', $dept);
        }
        if (!empty($status)) {
            $query->where('status', $status);
        }
        if (!empty($type)) {
            $query->where('type', $type);
        }

        if ($user->role === 'HOD' && !$user->hasAdminPrivileges() && !$user->isManagement() && !$user->isHRAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('hod_id', $user->id)
                  ->orWhere('department_name', $user->department)
                  ->orWhereHas('user', function ($u) use ($user) {
                      $u->where('supervisor_id', $user->id);
                  });
            });
        }

        $requests = $query->get();

        $filename = "after-hours-" . ($month ?: 'all') . ".csv";

        return response()->streamDownload(function () use ($requests) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, [
                'Reference',
                'Type',
                'Work Mode',
                'Date',
                'Employee',
                'Department',
                'Job No',
                'Job Label',
                'Reason',
                'Start',
                'Finish',
                'Meal Allowance',
                'PickMe Ride',
                'Lieu Leave',
                'Next-day Report',
                'Status',
                'Approved By',
                'Approved Time',
                'Approver Note',
                'Submitted At',
                'After 3 PM',
            ]);

            foreach ($requests as $r) {
                $nd = $r->finish_time ? LateWorkRequest::calculateNextDayReport($r->finish_time)['label'] : '';
                $lieuDisplay = '';
                if ($r->lieu === 'one_and_half') {
                    $lieuDisplay = '1.5 days';
                } elseif ($r->lieu === 'full') {
                    $lieuDisplay = 'Full day';
                } elseif ($r->lieu === 'half') {
                    $lieuDisplay = 'Half day';
                }

                fputcsv($handle, [
                    $r->reference_number,
                    $r->type === 'weekend' ? ($r->is_holiday ? 'Public holiday' : 'Weekend') : 'Late night',
                    $r->is_wfh ? 'WFH' : 'Office',
                    $r->date ? $r->date->format('Y-m-d') : '',
                    $r->user->name ?? '',
                    $r->department_name,
                    $r->job_number,
                    $r->job_label,
                    $r->reason,
                    $r->start_time ?? '',
                    $r->finish_time,
                    $r->meal ? 'Yes' : 'No',
                    $r->taxi ? 'Yes' : 'No',
                    $lieuDisplay,
                    $r->type === 'weekend' ? '' : $nd,
                    ucfirst($r->status),
                    $r->decidedBy->name ?? '',
                    $r->decided_at ? $r->decided_at->format('Y-m-d H:i') : '',
                    $r->hod_note ?? '',
                    $r->created_at ? $r->created_at->format('Y-m-d H:i') : '',
                    $r->is_after_3pm ? 'Yes' : 'No',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Save Setup configuration: Departments HOD assignments and custom job numbers.
     */
    public function saveSetup(Request $request)
    {
        $user = auth()->user();
        if (!$user->canManageLateWorkSetup()) {
            abort(403, 'Unauthorized');
        }

        // 1. Departments HOD mapping
        $deptData = $request->input('departments', []);
        if (is_array($deptData)) {
            foreach ($deptData as $deptId => $data) {
                $dept = Department::find($deptId);
                if ($dept) {
                    $hodIds = isset($data['hod_ids']) ? array_filter(array_map('intval', (array)$data['hod_ids'])) : [];
                    $dept->update([
                        'hod_ids' => array_values(array_unique($hodIds)),
                    ]);
                }
            }
        }

        // 2. Custom jobs text parsing
        $jobsText = $request->input('jobs_text', '');
        $lines = preg_split('/[\r\n]+/', (string)$jobsText);
        $parsedJobs = [];
        $seen = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            $parts = explode('|', $line, 2);
            $no = trim($parts[0]);
            $label = isset($parts[1]) ? trim($parts[1]) : '';
            if (!empty($no) && !isset($seen[$no])) {
                $seen[$no] = true;
                $parsedJobs[] = [
                    'no' => $no,
                    'label' => $label,
                ];
            }
        }

        Setting::set('late_work_custom_jobs', json_encode($parsedJobs));

        // 3. HR Admin Notification Emails
        if ($request->has('hr_admin_emails')) {
            Setting::set('hr_admin_notification_emails', trim($request->input('hr_admin_emails', '')));
        }

        $this->logAction('Updated Late & Weekend Work setup settings');

        return redirect()->route('late-work.index')->with('success', 'Late Work configuration saved successfully.');
    }

    /**
     * Save/Update a holiday (HR Admin & IT Admin).
     */
    public function saveHoliday(Request $request)
    {
        $user = auth()->user();
        if (!$user->canManageHolidays()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized. Only HR Admin and IT Admin can manage holidays.'], 403);
            }
            return back()->with('error', 'Unauthorized. Only HR Admin and IT Admin can manage holidays.');
        }

        $validCodes = HolidayType::pluck('code')->toArray();
        if (empty($validCodes)) {
            HolidayType::ensureDefaults();
            $validCodes = HolidayType::pluck('code')->toArray();
        }

        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'name' => 'nullable|string|max:255',
            'type' => ['nullable', 'string', Rule::in($validCodes)],
            'description' => 'nullable|string|max:500',
        ]);

        $holiday = Holiday::updateOrCreate(
            ['date' => $validated['date']],
            [
                'name' => trim($validated['name'] ?? '') ?: 'Public Holiday',
                'type' => $validated['type'] ?? 'public',
                'description' => $validated['description'] ?? null,
                'created_by' => $user->id,
            ]
        );

        $dateFormatted = Carbon::parse($holiday->date)->format('d M Y');
        $this->logAction("Added/updated holiday: {$dateFormatted} ({$holiday->name})");

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Holiday for {$dateFormatted} saved successfully.",
                'holiday' => $holiday,
                'holidaysMap' => Holiday::getHolidaysMap(),
            ]);
        }

        return redirect()->route('late-work.index', ['tab' => 'calendar'])->with('success', "Holiday for {$dateFormatted} saved successfully.");
    }

    /**
     * Toggle holiday on a date (quick toggle from the calendar grid).
     */
    public function toggleHoliday(Request $request)
    {
        $user = auth()->user();
        if (!$user->canManageHolidays()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validCodes = HolidayType::pluck('code')->toArray();
        if (empty($validCodes)) {
            HolidayType::ensureDefaults();
            $validCodes = HolidayType::pluck('code')->toArray();
        }

        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'name' => 'nullable|string|max:255',
            'type' => ['nullable', 'string', Rule::in($validCodes)],
        ]);

        $existing = Holiday::where('date', $validated['date'])->first();
        if ($existing) {
            $existing->delete();
            $action = 'removed';
            $this->logAction("Removed holiday on {$validated['date']}");
        } else {
            $holiday = Holiday::create([
                'date' => $validated['date'],
                'name' => trim($validated['name'] ?? '') ?: 'Public Holiday',
                'type' => $validated['type'] ?? 'public',
                'created_by' => $user->id,
            ]);
            $action = 'added';
            $this->logAction("Added holiday on {$validated['date']} ({$holiday->name})");
        }

        return response()->json([
            'success' => true,
            'action' => $action,
            'holidaysMap' => Holiday::getHolidaysMap(),
        ]);
    }

    /**
     * Delete a holiday.
     */
    public function deleteHoliday(Holiday $holiday, Request $request)
    {
        $user = auth()->user();
        if (!$user->canManageHolidays()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
            }
            return back()->with('error', 'Unauthorized.');
        }

        $dateFormatted = Carbon::parse($holiday->date)->format('d M Y');
        $holiday->delete();

        $this->logAction("Removed holiday: {$dateFormatted}");

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Holiday on {$dateFormatted} removed.",
                'holidaysMap' => Holiday::getHolidaysMap(),
            ]);
        }

        return redirect()->route('late-work.index', ['tab' => 'calendar'])->with('success', "Holiday on {$dateFormatted} removed.");
    }

    /**
     * Save/Create a new holiday type (HR Admin & IT Admin).
     */
    public function saveHolidayType(Request $request)
    {
        $user = auth()->user();
        if (!$user->canManageHolidays()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Only HR Admin and IT Admin can manage holiday types.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|in:amber,indigo,emerald,purple,rose,sky,orange,teal',
        ]);

        $name = trim($validated['name']);
        $color = $validated['color'] ?? 'amber';
        $code = HolidayType::generateUniqueCode($name);

        $holidayType = HolidayType::create([
            'name' => $name,
            'code' => $code,
            'color' => $color,
            'is_system' => false,
            'created_by' => $user->id,
        ]);

        $this->logAction("Added holiday type: {$holidayType->name} ({$holidayType->code})");

        return response()->json([
            'success' => true,
            'message' => "Holiday type '{$holidayType->name}' added successfully.",
            'holidayType' => $holidayType,
            'holidayTypes' => HolidayType::getAllTypes(),
        ]);
    }

    /**
     * Delete a custom holiday type (HR Admin & IT Admin).
     */
    public function deleteHolidayType(HolidayType $holidayType, Request $request)
    {
        $user = auth()->user();
        if (!$user->canManageHolidays()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Only HR Admin and IT Admin can manage holiday types.'], 403);
        }

        if ($holidayType->is_system) {
            return response()->json(['success' => false, 'message' => 'System default holiday types cannot be deleted.'], 422);
        }

        $typeName = $holidayType->name;
        $typeCode = $holidayType->code;

        // Reassign any holidays currently set to this type to 'public'
        Holiday::where('type', $typeCode)->update(['type' => 'public']);

        $holidayType->delete();

        $this->logAction("Removed holiday type: {$typeName} ({$typeCode})");

        return response()->json([
            'success' => true,
            'message' => "Holiday type '{$typeName}' deleted successfully.",
            'holidayTypes' => HolidayType::getAllTypes(),
            'holidaysMap' => Holiday::getHolidaysMap(),
        ]);
    }
}
