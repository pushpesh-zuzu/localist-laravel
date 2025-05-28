<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; padding: 40px; color: #333; }
        .header, .footer { margin-bottom: 30px; }
        .paid { border: 2px solid #00c896; color: #00c896; padding: 5px 10px; display: inline-block; font-weight: bold; }
        table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        .total-box { margin-top: 40px; float: right; }
        .total-box td { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h2>bark</h2>
        <p>{{ $date }}<br>
        VAT Invoice {{ $invoice_number }}<br>
        {{ $company_name }}</p>
    </div>

    @if($paid)
        <div class="paid">✓ PAID</div>
    @endif

    <h3>Total: £{{ number_format($total, 2) }}</h3>

    <table>
        <thead>
            <tr><th>DETAILS</th><th>PERIOD</th><th>PRICE</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>Purchase of {{ $credits }} credits</td>
                <td>One off charge</td>
                <td>£{{ number_format($sub_total, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="total-box">
        <tr><td>Sub Total</td><td>£{{ number_format($sub_total, 2) }}</td></tr>
        <tr><td>VAT (20%)</td><td>£{{ number_format($vat, 2) }}</td></tr>
        <tr><td><strong>Total</strong></td><td><strong>£{{ number_format($total, 2) }}</strong></td></tr>
    </table>

    <div class="footer" style="margin-top: 100px;">
        <p>Bark.com Global Limited<br>
        9th Floor, 3 Sheldon Square<br>
        London, W2 6HY<br>
        020 3697 0237</p>
    </div>
</body>
</html>
