<!DOCTYPE html>
<html>
<head>
  <title>VAT Invoice</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f9fcfd; padding: 40px; color: #333;">
  <div style="max-width: 700px; margin: auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
    
    <!-- Logo and header -->
    <div style="display: flex; justify-content: space-between; align-items: center;">
      <span><img src="{{public_path('images/localist_logo.svg')}}" width="150"/></span>
      <div style="text-align: right; font-size: 14px; color: #777; margin-top:-40px">
        <strong>Localist Global Limited</strong><br>
        9th Floor, 3 Sheldon Square<br>
        London, W2 6HY<br>
        020 3697 0237
        
      </div>
    </div>

    <!-- Invoice info -->
    <div style="">
		<div style="width:50%; ">
		  <div style="color: #6de1a7; font-size: 18px;">{{date('d/m/Y',strtotime($created_at))}}</div>
		  <div style="font-weight: bold; font-size: 22px;">VAT Invoice {{$invoice_number}}</div>
		  <hr>
		  <div style="margin-top: 10px;font-size: 20px;">{{$name}}</div>
		</div>
		<div style="margin-top:-50px;">
		  <div style="text-align: right;">
			<div style="border: 2px solid #6de1a7; color: #6de1a7; padding: 10px 20px; border-radius: 6px; display: inline-block; font-weight: bold;">
			  PAID
			</div>
			<div style="margin-top: 20px; font-size: 16px;">TOTAL</div>
            <div style="font-size: 28px; font-weight: bold; color: #6de1a7;">&pound;{{number_format($total_amount, 2)}}</div>
		  </div>
		</div>
    </div>

    <!-- Paid box and total -->
    

    <!-- Invoice details table -->
    <table style="width: 100%; margin-top: 40px; border-collapse: collapse; font-size: 16px;">
      <thead>
        <tr style="text-align: center; border-bottom: 2px solid #ddd;">
          <th style="padding: 10px 0;">DETAILS</th>
          <th style="padding: 10px 0;">PERIOD</th>
          <th style="padding: 10px 0; text-align: right;">PRICE</th>
        </tr>
      </thead>
      <tbody>
        <tr style="border-bottom: 1px solid #eee;">
          <td style="padding: 10px 0;">{{$details}}</td>
          <td style="padding: 10px 0;">One off charge</td>
          <td style="padding: 10px 0; text-align: right;">&pound;{{number_format($amount, 2)}}</td>
        </tr>
      </tbody>
    </table>

    <!-- Summary -->
    <div style="margin-top: 30px; font-size: 18px;">
        <div style="display: flex; justify-content: space-between;">
            <span style="margin-left:53%">Sub Total</span>
            <span style="float: right;">&pound;{{number_format($amount, 2)}}</span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 5px;">
            <span style="margin-left:53%">VAT(20%)</span>
            <span style="float: right;">&pound;{{number_format($vat, 2)}}</span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 5px; font-weight: bold;">
            <span style="margin-left:53%">Total</span>
            <span style="float: right;">&pound;{{number_format($total_amount, 2)}}</span>
        </div>
    </div>

  </div>
</body>
</html>
