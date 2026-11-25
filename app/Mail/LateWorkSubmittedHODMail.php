<?php

namespace App\Mail;

use App\Models\LateWorkRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LateWorkSubmittedHODMail extends Mailable
{
    use Queueable, SerializesModels;

    public $lateWorkRequest;

    public function __construct(LateWorkRequest $lateWorkRequest)
    {
        $this->lateWorkRequest = $lateWorkRequest;
    }

    public function build()
    {
        $req = $this->lateWorkRequest;
        $req->loadMissing('user', 'hod', 'department');

        $staffName = $req->user->name ?? 'Staff member';
        $fromAddress = env('LATE_WORK_MAIL_FROM_ADDRESS', config('mail.from.address'));
        $fromName = env('LATE_WORK_MAIL_FROM_NAME', 'Loops HR - Late Work');

        $isReappeal = ($req->reappeal_count > 0 || !empty($req->reappeal_notes));
        $prefix = $isReappeal ? '[Re-appeal] ' : '';
        $subject = "{$prefix}[Action Required] Late & Weekend Work Request - {$staffName} ({$req->job_number})";

        return $this->from($fromAddress, $fromName)
            ->subject($subject)
            ->view('emails.late-work.hod-approval-request')
            ->with([
                'req' => $req,
                'staffName' => $staffName,
                'hodName' => $req->hod->name ?? 'HOD',
                'approveUrl' => route('late-work.index', ['tab' => 'approve']),
            ]);
    }
}
