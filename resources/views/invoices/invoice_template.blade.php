<!DOCTYPE html>
<html>
<head>
  <title>VAT Invoice</title>

  <style>
    @page {
  margin: 20px 20px 5px 20px; /* bottom sirf 5px */
}

body {
  margin: 0;
  padding: 0;
  font-family: Arial, Helvetica, sans-serif;
  background-color: #f9fcfd;
  color: #333;
}

.invoice-wrapper {
  max-width: 700px;
  margin: auto;
  background: #ffffff;

  /* 👇 MOST IMPORTANT CHANGE */
  padding: 40px 40px 10px 40px; /* bottom padding kam */

  /* 👇 PDF ke liye better */
  border-radius: 0;
}

table {
  width: 100%;
  border-collapse: collapse;
}
  </style>
</head>

<body>

  <div class="invoice-wrapper">

    <!-- Header -->
    <table>
      <tr>
        <td>
          <img src="{{ public_path('images/localist_logo.png') }}" width="150">
        </td>
        <td align="right" style="font-size:14px;color:#777;">
          <strong>Internet Media Group Ltd</strong><br>
          Honeycomb South<br>
          Chester Business Park<br>
          Chester, CH4 9QJ<br>
          VAT Number: <strong>486847329</strong>
        </td>
      </tr>
    </table>

    <!-- Invoice Info -->
    <table style="margin-top:30px;">
      <tr>
        <td>
          <div style="color:#6de1a7;font-size:18px;">
            {{ date('d/m/Y', strtotime($created_at)) }}
          </div>

          <div style="font-weight:bold;font-size:22px;">
            VAT Invoice {{ $invoice_number }}
          </div>

          <hr style="margin:10px 0;">

          <div style="font-size:20px;">
            {{ $name }}
          </div>
        </td>

        <td align="right">
          <div style="border:2px solid #6de1a7;color:#6de1a7;padding:10px 20px;
                      border-radius:6px;font-weight:bold;display:inline-block;">
            PAID
          </div>

          <div style="margin-top:20px;font-size:16px;">TOTAL</div>
          <div style="font-size:28px;font-weight:bold;color:#6de1a7;">
            &pound;{{ $total_amount }}
          </div>
        </td>
      </tr>
    </table>

    <!-- Details Table -->
    <table style="margin-top:40px;font-size:16px;">
      <thead>
        <tr style="border-bottom:2px solid #ddd;">
          <th align="left" style="padding:10px 0;">DETAILS</th>
          <th align="left" style="padding:10px 0;">PERIOD</th>
          <th align="right" style="padding:10px 0;">PRICE</th>
        </tr>
      </thead>

      <tbody>
        <tr style="border-bottom:1px solid #eee;">
          <td style="padding:10px 0;">{{ $details }}</td>
          <td style="padding:10px 0;">One off charge</td>
          <td align="right" style="padding:10px 0;">&pound;{{ $amount }}</td>
        </tr>
      </tbody>
    </table>

    <!-- Summary -->
    <table style="margin-top:30px;font-size:18px;">
      <tr>
        <td align="right" width="80%">Sub Total</td>
        <td align="right">&pound;{{ $amount }}</td>
      </tr>

      <tr>
        <td align="right">VAT (20%)</td>
        <td align="right">&pound;{{ $vat }}</td>
      </tr>

      <tr style="font-weight:bold;">
        <td align="right">Total</td>
        <td align="right">&pound;{{ $total_amount }}</td>
      </tr>
    </table>

  </div>

</body>
</html>
