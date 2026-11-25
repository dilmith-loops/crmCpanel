<?php

namespace App\Mail;

use App\Models\LateWorkRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LateWorkApprovedHRNotificationMail extends Mailable
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
        $req->loadMissing('user', 'hod', 'decidedBy', 'department');

        $staffName = $req->user->name ?? 'Staff member';
        $dateFormatted = $req->date ? $req->date->format('d M Y') : date('d M Y');
        $fromAddress = env('LATE_WORK_MAIL_FROM_ADDRESS', config('mail.from.address'));
        $fromName = env('LATE_WORK_MAIL_FROM_NAME', 'Loops HR - Late Work');

        $subject = "[HR Notice] Approved Late & Weekend Work - {$staffName} ({$dateFormatted})";

        return $this->from($fromAddress, $fromName)
            ->subject($subject)
            ->view('emails.late-work.hr-approved-notice')
            ->with([
                'req' => $req,
                'staffName' => $staffName,
                'decidedByName' => $req->decidedBy->name ?? ($req->hod->name ?? 'HOD'),
                'logUrl' => route('late-work.index', ['tab' => 'log']),
            ]);
    }
}
