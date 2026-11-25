<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LateWorkRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number',
        'user_id',
        'department_id',
        'department_name',
        'hod_id',
        'type',
        'date',
        'is_holiday',
        'is_wfh',
        'job_number',
        'job_label',
        'reason',
        'start_time',
        'finish_time',
        'meal',
        'taxi',
        'lieu',
        'next_day',
        'status',
        'decided_by',
        'decided_at',
        'hod_note',
        'reappeal_notes',
        'reappealed_at',
        'reappeal_count',
        'is_after_3pm',
    ];

    protected $casts = [
        'date' => 'date',
        'is_holiday' => 'boolean',
        'is_wfh' => 'boolean',
        'meal' => 'boolean',
        'taxi' => 'boolean',
        'is_after_3pm' => 'boolean',
        'decided_at' => 'datetime',
        'reappealed_at' => 'datetime',
        'reappeal_count' => 'integer',
    ];

    /**
     * Get a human-readable label for lieu leave entitlement.
     */
    public function getLieuLabelAttribute(): ?string
    {
        if (!$this->lieu) {
            return null;
        }
        return match ($this->lieu) {
            'half' => 'Half day (0.5)',
            'full' => 'Full day (1.0)',
            'one_and_half' => '1.5 days',
            default => ucfirst($this->lieu),
        };
    }

    /**
     * Get numeric days earned for lieu leave.
     */
    public function getLieuDaysAttribute(): float
    {
        return match ($this->lieu) {
            'half' => 0.5,
            'full' => 1.0,
            'one_and_half' => 1.5,
            default => 0.0,
        };
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function hod()
    {
        return $this->belongsTo(User::class, 'hod_id');
    }

    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function pettyCashRequest()
    {
        return $this->hasOne(PettyCashRequest::class, 'late_work_request_id');
    }

    /**
     * Get an array of individual job numbers when multiple are stored.
     */
    public function getJobNumbersAttribute(): array
    {
        if (empty($this->job_number)) {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $this->job_number))));
    }

    /**
     * Get an array of individual job labels when multiple are stored.
     */
    public function getJobLabelsAttribute(): array
    {
        if (empty($this->job_label)) {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(';', $this->job_label))));
    }

    /**
     * Get an array of individual job items: each having 'number' and 'label'.
     * Pairs job number at index i with job label at index i so each job is displayed one by one down.
     *
     * @return array<int, array{number: string, label: string|null}>
     */
    public function getJobItemsAttribute(): array
    {
        $numbers = $this->job_numbers;
        $labels = $this->job_labels;

        if (empty($numbers)) {
            if (!empty($this->job_label)) {
                return [['number' => '', 'label' => trim($this->job_label)]];
            }
            return [];
        }

        if (count($numbers) === 1 && !empty($this->job_label)) {
            return [[
                'number' => $numbers[0],
                'label' => trim($this->job_label),
            ]];
        }

        $items = [];
        foreach ($numbers as $i => $no) {
            $items[] = [
                'number' => $no,
                'label' => isset($labels[$i]) && trim($labels[$i]) !== '' ? trim($labels[$i]) : null,
            ];
        }
        return $items;
    }

    /**
     * Generate reference number like LW-2026-0001
     */
    public static function generateReferenceNumber(): string
    {
        $year = date('Y');
        $last = self::whereYear('created_at', $year)->orderBy('id', 'desc')->first();
        $sequence = $last ? (((int) substr($last->reference_number, -4)) + 1) : 1;
        return 'LW-' . $year . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Weekday Late Work Policy: Time to report to work based on sign out time.
     *
     * Official Policy Table:
     * - Between 9.00 p.m. - 10.30 p.m.   => 10.00 a.m.
     * - Between 10.31 p.m. – 12.00 p.m.  => 11.00 a.m. (12.00 midnight / 00:00 / 24:00)
     * - Between 12.01 – 3.00 a.m.        => Half day off
     * - Beyond 3.00 a.m.                 => Full day off
     * - Before 9.00 p.m.                 => 9.00 a.m. (regular reporting time)
     */
    public static function calculateNextDayReport(?string $finish): array
    {
        if (!$finish) {
            return ['key' => 'normal', 'label' => '9:00 AM', 'policy' => 'Prior to 9.00 p.m.'];
        }
        $parts = explode(':', $finish);
        $h = (int)($parts[0] ?? 0);
        $m = (int)($parts[1] ?? 0);

        // If time was entered as 12-hour format without PM (09:00 to 11:59):
        if ($h >= 9 && $h <= 11) {
            $h += 12; // 09:00 -> 21:00, 10:00 -> 22:00, 11:00 -> 23:00
        }

        $totalMinutes = $h * 60 + $m;

        // Morning hours after midnight up to 8:00 AM (00:00 to 08:00)
        if ($h <= 8) {
            if ($totalMinutes === 0) {
                // 12.00 Midnight (00:00)
                return ['key' => '11am', 'label' => '11:00 AM', 'policy' => 'Between 10.31 p.m. – 12.00 p.m.'];
            }
            if ($totalMinutes <= 180) { // Up to 03:00 AM (00:01 - 03:00)
                return ['key' => 'half', 'label' => 'Half day off', 'policy' => 'Between 12.01 – 3.00 a.m.'];
            }
            // Beyond 03:00 AM up to 08:00 AM
            return ['key' => 'full', 'label' => 'Full day off', 'policy' => 'Beyond 3.00 a.m.'];
        }

        // Before 9:00 PM (20:00 to 20:59 or earlier)
        if ($totalMinutes < 21 * 60) {
            return ['key' => 'normal', 'label' => '9:00 AM', 'policy' => 'Prior to 9.00 p.m.'];
        }

        // Between 9:00 PM and 10:30 PM (21:00 to 22:30)
        if ($totalMinutes <= 22 * 60 + 30) {
            return ['key' => '10am', 'label' => '10:00 AM', 'policy' => 'Between 9.00 p.m. - 10.30 p.m.'];
        }

        // Between 10:31 PM and 12:00 Midnight (22:31 to 23:59 or 24:00)
        return ['key' => '11am', 'label' => '11:00 AM', 'policy' => 'Between 10.31 p.m. – 12.00 p.m.'];
    }

    /**
     * Compute meal submission deadline:
     * - Weekday: 3:00 PM same day
     * - Weekend/Holiday: 3:00 PM on previous working day
     */
    public static function mealDeadline(string $type, $date): Carbon
    {
        $d = Carbon::parse($date)->startOfDay();
        if ($type === 'weekend') {
            do {
                $d->subDay();
            } while ($d->isWeekend());
        }
        return $d->setHour(15)->setMinute(0)->setSecond(0);
    }

    /**
     * Check if a request was submitted after 3 PM deadline.
     */
    public static function isPastMealDeadline(string $type, $date, $submissionTime = null): bool
    {
        $now = $submissionTime ? Carbon::parse($submissionTime) : Carbon::now();
        $deadline = self::mealDeadline($type, $date);
        return $now->greaterThan($deadline);
    }

    /**
     * Check if current user is authorized to approve/decline this request.
     */
    public function canApprove(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        // HR Admin, Management, IT Admin, Finance Admin, Super Admin can approve & decline
        if ($user->isHRAdmin() || $user->isManagement() || $user->hasAdminPrivileges()) {
            return true;
        }

        // Specifically assigned HOD
        if ($this->hod_id && (int)$this->hod_id === (int)$user->id) {
            return true;
        }

        // Requester's direct supervisor/HOD
        if ($this->user && (int)$this->user->supervisor_id === (int)$user->id) {
            return true;
        }

        // Department HOD
        if ($this->department && $this->department->isHod($user)) {
            return true;
        }

        // Fallback: User is role 'HOD' in same department
        if ($user->role === 'HOD' && $this->department_name && strcasecmp($user->department, $this->department_name) === 0) {
            return true;
        }

        return false;
    }

    /**
     * Check if staff member can withdraw their request.
     */
    public function canWithdraw(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        return $this->status === 'pending' && (int)$this->user_id === (int)$user->id;
    }

    /**
     * Check if user can re-appeal this request.
     */
    public function canReappeal(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        return $this->status === 'declined' && ((int)$this->user_id === (int)$user->id || $user->hasAdminPrivileges());
    }

    /**
     * Check if user is authorized to edit this request.
     * Strictly permitted for IT Admin and Finance Admin (including Super Admin).
     */
    public function canEdit(?User $user): bool
    {
        return $user && $user->canEditDeleteLateWork();
    }

    /**
     * Check if user is authorized to delete this request.
     * Strictly permitted for IT Admin and Finance Admin (including Super Admin).
     */
    public function canDelete(?User $user): bool
    {
        return $user && $user->canEditDeleteLateWork();
    }

    /**
     * Data array prepared for Edit Modal.
     */
    public function toEditArray(): array
    {
        return [
            'id' => $this->id,
            'ref' => $this->reference_number,
            'user_id' => $this->user_id,
            'user_name' => $this->user ? $this->user->name : 'Staff',
            'department_id' => $this->department_id,
            'department_name' => $this->department_name,
            'hod_id' => $this->hod_id,
            'type' => $this->type,
            'date' => $this->date ? $this->date->format('Y-m-d') : '',
            'date_display' => $this->date ? $this->date->format('D, d M Y') : '',
            'is_holiday' => (bool)$this->is_holiday,
            'is_wfh' => (bool)$this->is_wfh,
            'job_numbers' => $this->job_numbers,
            'job_number' => $this->job_number ?? '',
            'job_label' => $this->job_label ?? '',
            'start_time' => $this->start_time ? substr($this->start_time, 0, 5) : '',
            'finish_time' => $this->finish_time ? substr($this->finish_time, 0, 5) : '',
            'meal' => (bool)$this->meal,
            'taxi' => (bool)$this->taxi,
            'lieu' => $this->lieu ?? '',
            'next_day' => $this->next_day ?? '',
            'reason' => $this->reason ?? '',
            'status' => $this->status,
            'hod_note' => $this->hod_note ?? '',
        ];
    }

    /**
     * Count pending requests waiting for a user to approve.
     */
    public static function pendingForUser(?User $user): int
    {
        if (!$user) {
            return 0;
        }

        $query = self::where('status', 'pending');

        if ($user->hasAdminPrivileges() || $user->isHRAdmin() || $user->isManagement()) {
            return $query->count();
        }

        return $query->get()->filter(fn($req) => $req->canApprove($user))->count();
    }

    /**
     * Get list of HR Admin recipient emails and users.
     *
     * @return array<string> list of emails
     */
    public static function getHRAdminEmails(): array
    {
        $emails = [];

        // 1. All users with HR Admin role
        $hrUsers = User::where('role', 'HR Admin')->get();
        foreach ($hrUsers as $u) {
            if (!empty($u->email) && filter_var($u->email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = strtolower(trim($u->email));
            }
        }

        // 2. Settings table override or addition
        $settingEmails = Setting::get('hr_admin_notification_emails');
        if (!empty($settingEmails)) {
            $parts = preg_split('/[\r\n,;]+/', (string)$settingEmails);
            foreach ($parts as $p) {
                $p = trim($p);
                if (filter_var($p, FILTER_VALIDATE_EMAIL)) {
                    $emails[] = strtolower($p);
                }
            }
        }

        // 3. Environment variable fallback
        $envEmails = env('HR_ADMIN_NOTIFICATION_EMAILS');
        if (!empty($envEmails)) {
            $parts = preg_split('/[\r\n,;]+/', (string)$envEmails);
            foreach ($parts as $p) {
                $p = trim($p);
                if (filter_var($p, FILTER_VALIDATE_EMAIL)) {
                    $emails[] = strtolower($p);
                }
            }
        }

        return array_values(array_unique($emails));
    }
}
