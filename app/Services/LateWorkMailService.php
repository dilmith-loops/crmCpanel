<?php

namespace App\Services;

use App\Mail\LateWorkApprovedHRNotificationMail;
use App\Mail\LateWorkDecisionUserMail;
use App\Mail\LateWorkSubmittedHODMail;
use App\Mail\LateWorkSubmittedUserConfirmationMail;
use App\Models\LateWorkRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LateWorkMailService
{
    /**
     * Resolve the mailer to use:
     * If dedicated LATE_WORK_MAIL_USERNAME is configured, use 'late_work_smtp'.
     * Otherwise fallback to default mailer.
     */
    public static function getMailer()
    {
        if (config('mail.default') === 'log' || env('MAIL_MAILER') === 'log' || env('LATE_WORK_MAIL_MAILER') === 'log') {
            return Mail::mailer('log');
        }
        $customUser = config('mail.mailers.late_work_smtp.username');
        if (!empty($customUser)) {
            return Mail::mailer('late_work_smtp');
        }
        return Mail::mailer();
    }

    /**
     * Send email notifications on new request submission:
     * 1. Send confirmation to Requester.
     * 2. Send approval request to the relevant HOD(s).
     */
    public static function sendSubmissionEmails(LateWorkRequest $request): void
    {
        try {
            $mailer = self::getMailer();
            $request->loadMissing('user', 'hod', 'department');

            // 1. Send confirmation to Requester
            if ($request->user && !empty($request->user->email) && filter_var($request->user->email, FILTER_VALIDATE_EMAIL)) {
                $mailer->to($request->user->email)->send(new LateWorkSubmittedUserConfirmationMail($request));
            }

            // 2. Determine HOD recipients
            $hodRecipients = collect();

            if ($request->hod && !empty($request->hod->email)) {
                $hodRecipients->push($request->hod);
            } elseif ($request->user && $request->user->associated_hod) {
                $hodRecipients->push($request->user->associated_hod);
            } elseif ($request->user && $request->user->supervisor) {
                $hodRecipients->push($request->user->supervisor);
            } elseif ($request->department) {
                // If no specific HOD assigned, notify assigned HODs for the department
                foreach ($request->department->hods as $dHod) {
                    $hodRecipients->push($dHod);
                }
            }

            // Unique by email
            $hodRecipients = $hodRecipients->unique('email')->filter(function ($u) {
                return !empty($u->email) && filter_var($u->email, FILTER_VALIDATE_EMAIL);
            });

            foreach ($hodRecipients as $hodUser) {
                $reqCopy = clone $request;
                $reqCopy->setRelation('hod', $hodUser);
                $mailer->to($hodUser->email)->send(new LateWorkSubmittedHODMail($reqCopy));
            }
        } catch (\Throwable $e) {
            Log::error('LateWorkMailService::sendSubmissionEmails failed: ' . $e->getMessage(), [
                'request_id' => $request->id,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Send email notifications when request is approved or declined:
     * 1. Send decision notice to staff member.
     * 2. If approved, send notification to HR Admin.
     */
    public static function sendDecisionEmails(LateWorkRequest $request): void
    {
        try {
            $mailer = self::getMailer();
            $request->loadMissing('user', 'hod', 'decidedBy', 'department');

            // 1. Send decision to Requester
            if ($request->user && !empty($request->user->email) && filter_var($request->user->email, FILTER_VALIDATE_EMAIL)) {
                $mailer->to($request->user->email)->send(new LateWorkDecisionUserMail($request));
            }

            // 2. If approved, notify HR Admin users
            if ($request->status === 'approved') {
                $hrEmails = LateWorkRequest::getHRAdminEmails();
                foreach ($hrEmails as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $mailer->to($email)->send(new LateWorkApprovedHRNotificationMail($request));
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('LateWorkMailService::sendDecisionEmails failed: ' . $e->getMessage(), [
                'request_id' => $request->id,
                'status' => $request->status,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
