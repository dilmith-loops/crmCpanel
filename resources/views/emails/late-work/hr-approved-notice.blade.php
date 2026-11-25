<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Notification - Approved Late & Weekend Work</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f5f8; margin: 0; padding: 20px; color: #1a1e2c;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 8px 24px rgba(0,0,0,0.06); border: 1px solid #d9dce5;">
        <!-- Header -->
        <tr>
            <td style="background: linear-gradient(135deg, #0d3b66 0%, #1f7a4d 100%); padding: 28px 24px; text-align: left;">
                <span style="display: inline-block; background-color: #ddf1e6; color: #1f7a4d; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; padding: 4px 10px; border-radius: 6px; margin-bottom: 8px;">
                    HR Admin Notice · Request Approved
                </span>
                <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 700;">
                    Approved Late &amp; Weekend Work Request
                </h1>
                <p style="color: rgba(255,255,255,0.85); margin: 4px 0 0 0; font-size: 13px;">
                    Staff: <strong>{{ $staffName }}</strong> · Approved by: <strong>{{ $decidedByName }}</strong>
                </p>
            </td>
        </tr>

        <!-- Body -->
        <tr>
            <td style="padding: 28px 24px;">
                <p style="font-size: 15px; font-weight: 600; color: #1a1e2c; margin-top: 0;">
                    Hello HR Team,
                </p>
                <p style="font-size: 14px; color: #5d6375; line-height: 1.6; margin-bottom: 20px;">
                    A Late &amp; Weekend Work request for <strong>{{ $staffName }}</strong> ({{ $req->department_name ?? 'N/A' }}) has been approved by <strong>{{ $decidedByName }}</strong>. Please find the details below for attendance, payroll/meal allowance, and lieu leave tracking.
                </p>

                <!-- Details Card -->
                <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #eceef3; border-radius: 10px; padding: 18px; margin-bottom: 22px; border: 1px solid #d9dce5;">
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase; width: 38%;">Reference</td>
                        <td style="padding: 6px 0; font-size: 14px; font-weight: 700; color: #1a1e2c;">{{ $req->reference_number }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase;">Employee</td>
                        <td style="padding: 6px 0; font-size: 14px; font-weight: 600; color: #1a1e2c;">{{ $staffName }} ({{ $req->user->email ?? '' }})</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase;">Department</td>
                        <td style="padding: 6px 0; font-size: 14px; color: #1a1e2c;">{{ $req->department_name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase;">Type &amp; Date</td>
                        <td style="padding: 6px 0; font-size: 14px; font-weight: 600; color: #1a1e2c;">
                            {{ $req->type === 'weekend' ? ($req->is_holiday ? 'Public Holiday' : 'Weekend') : 'Weekday (Past 8 PM)' }}
                            · {{ $req->date ? $req->date->format('D, d M Y') : '' }}
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
                    @if($req->is_wfh)
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase;">Work Mode</td>
                        <td style="padding: 6px 0; font-size: 14px; font-weight: 600; color: #4338ca;">Working from Home (WFH)</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase;">Meal Allowance</td>
                        <td style="padding: 6px 0; font-size: 14px; font-weight: 600; color: {{ $req->meal ? '#1f7a4d' : '#5d6375' }};">
                            {{ $req->meal ? 'Yes (Rs. 1,000)' : 'No' }}
                            @if($req->is_after_3pm)
                                <span style="font-size: 11px; color: #b3261e; font-weight: 700;">[Submitted After 3 PM]</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase;">PickMe Ride Home</td>
                        <td style="padding: 6px 0; font-size: 14px; color: #1a1e2c;">{{ $req->taxi ? 'Yes' : 'No' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase;">Lieu Leave</td>
                        <td style="padding: 6px 0; font-size: 14px; font-weight: 600; color: #1a1e2c;">
                            {{ $req->lieu ? $req->lieu_label : 'None' }}
                        </td>
                    </tr>
                    @if($req->type !== 'weekend' && $req->next_day)
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase;">Next-Day Clock-in</td>
                        <td style="padding: 6px 0; font-size: 14px; font-weight: 600; color: #1a1e2c;">
                            @php
                                $nd = \App\Models\LateWorkRequest::calculateNextDayReport($req->finish_time);
                            @endphp
                            {{ $nd['label'] }} (Duty Leave entry needed)
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase;">HOD Decision</td>
                        <td style="padding: 6px 0; font-size: 14px; color: #1f7a4d; font-weight: 600;">
                            Approved by {{ $decidedByName }} on {{ $req->decided_at ? $req->decided_at->format('d M Y, h:i A') : '' }}
                            @if(!empty($req->hod_note))
                                <br><span style="font-size: 13px; color: #5d6375; font-style: italic; font-weight: normal;">"{{ $req->hod_note }}"</span>
                            @endif
                        </td>
                    </tr>
                </table>

                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 20px;">
                    <tr>
                        <td align="center">
                            <a href="{{ $logUrl }}" style="display: inline-block; background-color: #0d3b66; color: #ffffff; font-size: 14px; font-weight: 700; text-decoration: none; padding: 12px 28px; border-radius: 8px;">
                                View Late Work Log in CRM
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
