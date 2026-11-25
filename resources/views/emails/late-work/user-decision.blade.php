<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Late & Weekend Work Request Decision</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f5f8; margin: 0; padding: 20px; color: #1a1e2c;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 8px 24px rgba(0,0,0,0.06); border: 1px solid #d9dce5;">
        <!-- Header -->
        <tr>
            <td style="background: {{ $isApproved ? 'linear-gradient(135deg, #173528 0%, #1f7a4d 100%)' : 'linear-gradient(135deg, #3d1d1b 0%, #b3261e 100%)' }}; padding: 28px 24px; text-align: left;">
                <span style="display: inline-block; background-color: {{ $isApproved ? '#ddf1e6' : '#f9dedc' }}; color: {{ $isApproved ? '#1f7a4d' : '#b3261e' }}; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; padding: 4px 10px; border-radius: 6px; margin-bottom: 8px;">
                    {{ $isApproved ? 'Request Approved' : 'Request Declined' }}
                </span>
                <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 700;">
                    {{ $isApproved ? 'Your Request Has Been Approved' : 'Your Request Has Been Declined' }}
                </h1>
                <p style="color: rgba(255,255,255,0.85); margin: 4px 0 0 0; font-size: 13px;">
                    Decided by <strong>{{ $decidedByName }}</strong> · Ref: {{ $req->reference_number }}
                </p>
            </td>
        </tr>

        <!-- Body -->
        <tr>
            <td style="padding: 28px 24px;">
                <p style="font-size: 15px; font-weight: 600; color: #1a1e2c; margin-top: 0;">
                    Hi {{ $staffName }},
                </p>
                <p style="font-size: 14px; color: #5d6375; line-height: 1.6; margin-bottom: 20px;">
                    @if($isApproved)
                        Your Late &amp; Weekend Work request for <strong>{{ $req->date ? $req->date->format('D, d M Y') : '' }}</strong> has been approved by <strong>{{ $decidedByName }}</strong>.
                    @else
                        Your Late &amp; Weekend Work request for <strong>{{ $req->date ? $req->date->format('D, d M Y') : '' }}</strong> has been declined by <strong>{{ $decidedByName }}</strong>.
                    @endif
                </p>

                @if(!empty($req->hod_note))
                <div style="background: {{ $isApproved ? '#ddf1e6' : '#fdf2f2' }}; border-left: 4px solid {{ $isApproved ? '#1f7a4d' : '#b3261e' }}; padding: 14px 16px; margin-bottom: 20px; border-radius: 6px;">
                    <span style="display: block; font-size: 11px; font-weight: 700; color: {{ $isApproved ? '#1f7a4d' : '#b3261e' }}; text-transform: uppercase; margin-bottom: 4px;">
                        {{ $isApproved ? 'Approver Note' : 'Reason for Rejection / Note from Approver' }}
                    </span>
                    <p style="margin: 0; font-size: 14px; color: #1a1e2c; font-style: italic;">"{{ $req->hod_note }}"</p>
                </div>
                @endif

                @if(!$isApproved)
                <p style="font-size: 13px; color: #5d6375; line-height: 1.6; margin-bottom: 20px;">
                    If you would like to provide additional justification or adjust your working hours/allowances, you can <strong>re-appeal</strong> this request in your dashboard.
                </p>
                @endif

                <!-- Request Summary Card -->
                <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #eceef3; border-radius: 10px; padding: 18px; margin-bottom: 22px; border: 1px solid #d9dce5;">
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase; width: 38%;">Status</td>
                        <td style="padding: 6px 0; font-size: 14px; font-weight: 700; color: {{ $isApproved ? '#1f7a4d' : '#b3261e' }};">
                            {{ ucfirst($req->status) }}
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
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase;">Date &amp; Time</td>
                        <td style="padding: 6px 0; font-size: 14px; color: #1a1e2c;">
                            {{ $req->date ? $req->date->format('D, d M Y') : '' }}
                            ({{ $req->type === 'weekend' ? (($req->start_time ? date('g:i A', strtotime($req->start_time)) : '') . ' – ' . ($req->finish_time ? date('g:i A', strtotime($req->finish_time)) : '')) : ('Until ' . ($req->finish_time ? date('g:i A', strtotime($req->finish_time)) : '')) }})
                        </td>
                    </tr>
                    @if($isApproved)
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase;">Approved Allowances</td>
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
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase;">Clock-in Next Day</td>
                        <td style="padding: 6px 0; font-size: 13px; color: #1a1e2c;">
                            @php
                                $nd = \App\Models\LateWorkRequest::calculateNextDayReport($req->finish_time);
                            @endphp
                            <strong>{{ $nd['label'] }}</strong> (ensure both fingerprint in and out are logged)
                        </td>
                    </tr>
                    @endif
                    @endif
                </table>

                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 20px;">
                    <tr>
                        <td align="center">
                            <a href="{{ $viewUrl }}" style="display: inline-block; background-color: #1a1e2c; color: #ffffff; font-size: 14px; font-weight: 700; text-decoration: none; padding: 12px 28px; border-radius: 8px;">
                                {{ $isApproved ? 'View in My Requests' : 'View & Re-appeal in Dashboard' }}
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
