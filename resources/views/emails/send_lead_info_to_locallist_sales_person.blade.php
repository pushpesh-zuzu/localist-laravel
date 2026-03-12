<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>New Lead – Sales Notification</title>
</head>

<body style="margin:0;padding:0;background:#f4f8fb;font-family:Arial,Helvetica,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:30px 10px;">

                <table width="700" cellpadding="0" cellspacing="0"
                    style="background:#ffffff;border-radius:12px;overflow:hidden;">

                    <!-- HEADER -->
                    <tr>
                        <td style="background:#00AFE3;padding:18px 24px;color:#ffffff;">
                            <h2 style="margin:0;font-size:20px;">
                                New Lead Matched – Sales Information
                            </h2>
                        </td>
                    </tr>

                    <!-- LEAD DETAILS -->
                    <tr>
                        <td style="padding:20px 24px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="font-size:14px;color:#555;padding-bottom:8px;">
                                        <strong>Service Name:</strong>
                                    </td>
                                    <td style="font-size:14px;color:#222;padding-bottom:8px;">
                                        {{ $serviceName ?? 'N/A' }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="font-size:14px;color:#555;padding-bottom:8px;">
                                        <strong>Quote Customer Name:</strong>
                                    </td>
                                    <td style="font-size:14px;color:#222;padding-bottom:8px;">
                                        {{ ucfirst($customerName) ?? 'N/A' }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="font-size:14px;color:#555;padding-bottom:8px;">
                                        <strong>Postcode:</strong>
                                    </td>
                                    <td style="font-size:14px;color:#222;padding-bottom:8px;">
                                        {{ $postCode ?? 'N/A' }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="font-size:14px;color:#555;padding-bottom:8px;">
                                        <strong>Credit Value:</strong>
                                    </td>
                                    <td style="font-size:14px;color:#222;padding-bottom:8px;">
                                        {{ $CreditValue ?? 0 }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="font-size:14px;color:#555;">
                                        <strong>Account Manager:</strong>
                                    </td>

                                    <td style="font-size:14px;color:#222;">
                                        {{ $quoteOwnerName ?? '-' }}
                                    </td>
                                </tr>


                            </table>
                        </td>
                    </tr>

                    <!-- SELLERS TABLE -->
                    <tr>
                        <td style="padding:0 24px 24px;">
                            <h3 style="margin:0 0 10px;font-size:16px;color:#253238;">
                                Matched Lead Buyers ({{ count($sellers) }})
                            </h3>

                            <table width="100%" cellpadding="8" cellspacing="0"
                                style="border-collapse:collapse;border:1px solid #e5e7eb;">
                                <thead>
                                    <tr style="background:#f1f5f9;">
                                        <th align="left" style="border:1px solid #e5e7eb;font-size:13px;">
                                            Lead Buyer Name
                                        </th>
                                        <th align="left" style="border:1px solid #e5e7eb;font-size:13px;">
                                            Email
                                        </th>
                                        <th align="left" style="border:1px solid #e5e7eb;font-size:13px;">
                                            Phone Number
                                        </th>
                                        <th align="left" style="border:1px solid #e5e7eb;font-size:13px;">
                                            Postcode
                                        </th>
                                       

                                        <th align="center" style="border:1px solid #e5e7eb;font-size:13px;">
                                            Total Credit
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse($sellers as $seller)
                                    <tr>
                                        <td style="border:1px solid #e5e7eb;font-size:13px;">
                                            {{ $seller->name ?? 'N/A' }}
                                        </td>
                                        <td style="border:1px solid #e5e7eb;font-size:13px;">
                                            {{ $seller->email ?? 'N/A' }}
                                        </td>
                                        <td style="border:1px solid #e5e7eb;font-size:13px;">
                                            {{ $seller->phone ?? 'N/A' }}
                                        </td>

                                        <td style="border:1px solid #e5e7eb;font-size:13px;">
                                            {{ $seller->postcode ?? 'N/A' }}
                                        </td>

                                      

                                        <td align="center" style="border:1px solid #e5e7eb;font-size:13px;">
                                            {{ $seller->total_credit ?? 0 }}
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" align="center"
                                            style="border:1px solid #e5e7eb;font-size:13px;color:#999;">
                                            No suppliers matched for this lead.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    <!-- FOOTER -->

                </table>

            </td>
        </tr>
    </table>
</body>

</html>