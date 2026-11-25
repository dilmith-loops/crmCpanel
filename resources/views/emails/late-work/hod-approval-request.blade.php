<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Late & Weekend Work Approval Request</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f5f8; margin: 0; padding: 20px; color: #1a1e2c;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 8px 24px rgba(0,0,0,0.06); border: 1px solid #d9dce5;">
        <!-- Header -->
        <tr>
            <td style="background: linear-gradient(135deg, #1a1e2c 0%, #2f3446 60%, #b86a00 100%); padding: 28px 24px; text-align: left;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <span style="display: inline-block; background-color: #fbebd2; color: #b86a00; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; padding: 4px 10px; border-radius: 6px; margin-bottom: 8px;">
                                Action Required · Approval Needed
                            </span>
                            <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 700;">
                                Late &amp; Weekend Work Request
                            </h1>
                            <p style="color: #d9dce5; margin: 4px 0 0 0; font-size: 13px;">
                                Reference: <strong>{{ $req->reference_number }}</strong>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Body -->
        <tr>
            <td style="padding: 28px 24px;">
                <p style="font-size: 15px; font-weight: 600; color: #1a1e2c; margin-top: 0;">
                    Hello {{ $hodName }},
                </p>
                <p style="font-size: 14px; color: #5d6375; line-height: 1.6; margin-bottom: 20px;">
                    <strong>{{ $staffName }}</strong> from the <strong>{{ $req->department_name ?? 'your department' }}</strong> team has {{ !empty($req->reappeal_notes) ? 're-appealed a previously declined request' : 'submitted a request' }} for Late &amp; Weekend Work. Please review the details below and grant or decline approval in the CRM.
                </p>

                @if(!empty($req->reappeal_notes))
                <div style="background: #fff8e6; border-left: 4px solid #b86a00; padding: 14px 16px; margin-bottom: 20px; border-radius: 6px;">
                    <span style="font-size: 11px; font-weight: 700; color: #b86a00; text-transform: uppercase; display: block; margin-bottom: 4px;">
                        Re-appeal Justification from Requester (Attempt #{{ $req->reappeal_count }})
                    </span>
                    <p style="margin: 0; font-size: 13px; color: #1a1e2c; font-style: italic;">"{{ $req->reappeal_notes }}"</p>
                    @if(!empty($req->hod_note))
                        <p style="margin: 6px 0 0 0; font-size: 11px; color: #8c5300;">Previously declined with note: "{{ $req->hod_note }}"</p>
                    @endif
                </div>
                @endif

                <!-- Request Summary Card -->
                <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #eceef3; border-radius: 10px; padding: 18px; margin-bottom: 22px; border: 1px solid #d9dce5;">
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase; width: 38%;">Staff Member</td>
                        <td style="padding: 6px 0; font-size: 14px; font-weight: 600; color: #1a1e2c;">{{ $staffName }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase;">Department</td>
                        <td style="padding: 6px 0; font-size: 14px; color: #1a1e2c;">{{ $req->department_name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase;">Request Type</td>
                        <td style="padding: 6px 0; font-size: 14px; font-weight: 600; color: #b86a00;">
                            {{ $req->type === 'weekend' ? ($req->is_holiday ? 'Public Holiday Work' : 'Weekend Work') : 'Weekday Work (Past 8:00 PM)' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase;">Date of Work</td>
                        <td style="padding: 6px 0; font-size: 14px; font-weight: 600; color: #1a1e2c;">
                            {{ $req->date ? $req->date->format('D, d M Y') : 'N/A' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase;">Working Hours</td>
                        <td style="padding: 6px 0; font-size: 14px; color: #1a1e2c;">
                            @if($req->type === 'weekend')
                                {{ $req->start_time ? date('g:i A', strtotime($req->start_time)) : '' }} – {{ $req->finish_time ? date('g:i A', strtotime($req->finish_time)) : '' }}
                            @else
                                Until {{ $req->finish_time ? date('g:i A', strtotime($req->finish_time)) : '' }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase; vertical-align: top;">Job</td>
                        <td style="padding: 6px 0; font-size: 13px; color: #1a1e2c; vertical-align: top;">
                            @php $jobItems = $req->job_items; @endphp
                            @if(!empty($jobItems))
                                @foreach($jobItems as $idx => $item)
                                    <div style="{{ $idx > 0 ? 'margin-top: 6px; padding-top: 6px; border-top: 1px dashed #e2e8f0;' : '' }}">
                                        <span style="font-family: monospace; font-weight: 700; color: #1a1e2c; font-size: 13px;">{{ $item['number'] }}</span>
                                        @if(!empty($item['label']))
                                            <span style="color: #5d6375; font-size: 13px;"> · {{ $item['label'] }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <span style="font-family: monospace; font-weight: 700; color: #1a1e2c; font-size: 13px;">{{ $req->job_number ?: 'N/A' }}</span>
                                @if($req->job_label)
                                    <span style="color: #5d6375; font-size: 13px;"> · {{ $req->job_label }}</span>
                                @endif
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase;">Allowances Claimed</td>
                        <td style="padding: 6px 0; font-size: 13px; color: #1a1e2c;">
                            @php $allowances = []; @endphp
                            @if($req->is_wfh) @php $allowances[] = 'Work from Home (WFH)'; @endphp @endif
                            @if($req->meal) @php $allowances[] = 'Meal Allowance (Rs. 1,000)'; @endphp @endif
                            @if($req->taxi) @php $allowances[] = 'PickMe Ride Home'; @endphp @endif
                            @if($req->lieu) @php $allowances[] = 'Lieu Leave (' . $req->lieu_label . ')'; @endphp @endif
                            {{ !empty($allowances) ? implode(' · ', $allowances) : 'None' }}
                        </td>
                    </tr>
                    @if($req->type !== 'weekend' && $req->next_day)
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase;">Next-Day Clock-in</td>
                        <td style="padding: 6px 0; font-size: 13px; color: #1a1e2c;">
                            @php
                                $nd = \App\Models\LateWorkRequest::calculateNextDayReport($req->finish_time);
                            @endphp
                            <strong>{{ $nd['label'] }}</strong> (Policy 3.11)
                        </td>
                    </tr>
                    @endif
                    @if($req->is_after_3pm)
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #b3261e; text-transform: uppercase;">Notice</td>
                        <td style="padding: 6px 0; font-size: 13px; color: #b3261e; font-weight: 600;">
                            Submitted past the 3:00 PM deadline (marked for Finance).
                        </td>
                    </tr>
                    @endif
                </table>

                <!-- Reason Box -->
                @if($req->reason)
                <div style="background: #ffffff; border-left: 4px solid #b86a00; padding: 12px 16px; margin-bottom: 24px; border-radius: 4px; border-top: 1px solid #d9dce5; border-right: 1px solid #d9dce5; border-bottom: 1px solid #d9dce5;">
                    <span style="display: block; font-size: 11px; font-weight: 700; color: #5d6375; text-transform: uppercase; margin-bottom: 4px;">Reason Given</span>
                    <p style="margin: 0; font-size: 14px; color: #1a1e2c; line-height: 1.5; white-space: pre-wrap;">{{ $req->reason }}</p>
                </div>
                @endif

                <!-- Call to Action Button -->
                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 24px;">
                    <tr>
                        <td align="center">
                            <a href="{{ $approveUrl }}" style="display: inline-block; background-color: #b86a00; color: #ffffff; font-size: 14px; font-weight: 700; text-decoration: none; padding: 13px 32px; border-radius: 8px; box-shadow: 0 4px 12px rgba(184, 106, 0, 0.3);">
                                Review &amp; Approve in CRM
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background-color: #eceef3; padding: 18px 24px; text-align: center; border-top: 1px solid #d9dce5; font-size: 12px; color: #5d6375;">
                &copy; {{ date('Y') }} Loops Integrated · Late &amp; Weekend Work System
            </td>
        </tr>
    </table>
</body>
</html>
