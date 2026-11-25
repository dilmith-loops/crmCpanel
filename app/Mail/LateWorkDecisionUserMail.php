<?php

namespace App\Mail;

use App\Models\LateWorkRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LateWorkDecisionUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public $lateWorkRequest;
    public $isApproved;

    public function __construct(LateWorkRequest $lateWorkRequest)
    {
        $this->lateWorkRequest = $lateWorkRequest;
        $this->isApproved = $lateWorkRequest->status === 'approved';
    }


    public function build()
    {
        $req = $this->lateWorkRequest;
        $req->loadMissing('user', 'hod', 'decidedBy', 'department');

        $isApproved = $req->status === 'approved';
        $statusLabel = $isApproved ? 'Approved' : 'Declined';
        $fromAddress = env('LATE_WORK_MAIL_FROM_ADDRESS', config('mail.from.address'));
        $fromName = env('LATE_WORK_MAIL_FROM_NAME', 'Loops HR - Late Work');

        $subject = "{$statusLabel}: Late & Weekend Work Request - {$req->job_number}";

        return $this->from($fromAddress, $fromName)
            ->subject($subject)
            ->view('emails.late-work.user-decision')
            ->with([
                'req' => $req,
                'isApproved' => $isApproved,
                'staffName' => $req->user->name ?? 'Staff member',
                'decidedByName' => $req->decidedBy->name ?? ($req->hod->name ?? 'HOD'),
                'viewUrl' => route('late-work.index', ['tab' => 'mine']),
            ]);
    }
}
