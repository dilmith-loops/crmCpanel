<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Late & Weekend Work Request Confirmation</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f5f8; margin: 0; padding: 20px; color: #1a1e2c;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 8px 24px rgba(0,0,0,0.06); border: 1px solid #d9dce5;">
        <!-- Header -->
        <tr>
            <td style="background: linear-gradient(135deg, #1a1e2c 0%, #2f3446 60%, #b86a00 100%); padding: 28px 24px; text-align: left;">
                <span style="display: inline-block; background-color: #f5edc2; color: #6b5a00; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; padding: 4px 10px; border-radius: 6px; margin-bottom: 8px;">
                    Request Submitted · Pending Approval
                </span>
                <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 700;">
                    Your Request Has Been Sent to HOD
                </h1>
                <p style="color: #d9dce5; margin: 4px 0 0 0; font-size: 13px;">
                    Reference: <strong>{{ $req->reference_number }}</strong>
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
                    Your Late &amp; Weekend Work request has been received and routed to <strong>{{ $hodName }}</strong> for approval. You will receive an email once your HOD reviews your request.
                </p>

                <!-- Request Summary Card -->
                <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #eceef3; border-radius: 10px; padding: 18px; margin-bottom: 22px; border: 1px solid #d9dce5;">
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase; width: 38%;">Request Type</td>
                        <td style="padding: 6px 0; font-size: 14px; font-weight: 600; color: #b86a00;">
                            {{ $req->type === 'weekend' ? ($req->is_holiday ? 'Public Holiday Work' : 'Weekend Work') : 'Weekday Work (Past 8:00 PM)' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase;">Date</td>
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
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase;">Allowances</td>
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
                        <td style="padding: 6px 0; font-size: 12px; font-weight: 700; color: #5d6375; text-transform: uppercase;">Expected Next Clock-in</td>
                        <td style="padding: 6px 0; font-size: 13px; color: #1a1e2c;">
                            @php
                                $nd = \App\Models\LateWorkRequest::calculateNextDayReport($req->finish_time);
                            @endphp
                            <strong>{{ $nd['label'] }}</strong> (Policy 3.11)
                        </td>
                    </tr>
                    @endif
                </table>

                <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 20px;">
                    <tr>
                        <td align="center">
                            <a href="{{ $viewUrl }}" style="display: inline-block; background-color: #1a1e2c; color: #ffffff; font-size: 14px; font-weight: 700; text-decoration: none; padding: 12px 28px; border-radius: 8px;">
                                View in My Requests
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
