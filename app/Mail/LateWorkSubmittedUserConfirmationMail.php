<?php

namespace App\Mail;

use App\Models\LateWorkRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LateWorkSubmittedUserConfirmationMail extends Mailable
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

        $fromAddress = env('LATE_WORK_MAIL_FROM_ADDRESS', config('mail.from.address'));
        $fromName = env('LATE_WORK_MAIL_FROM_NAME', 'Loops HR - Late Work');

        $subject = "Late & Weekend Work Request Submitted - {$req->job_number}";

        return $this->from($fromAddress, $fromName)
            ->subject($subject)
            ->view('emails.late-work.user-confirmation')
            ->with([
                'req' => $req,
                'staffName' => $req->user->name ?? 'Staff member',
                'hodName' => $req->hod->name ?? 'Department HOD',
                'viewUrl' => route('late-work.index', ['tab' => 'mine']),
            ]);
    }
}
