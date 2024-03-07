<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Delivery Notes</title>
    <style type="text/css"> * {
            margin: 0;
          padding-top: 5px;
            text-indent: 0;
        }
        .container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        h1 {
            color: black;
            font-family: Arial, sans-serif;
            font-style: normal;
            font-weight: bold;
            text-decoration: none;
            font-size: 9pt;
        }

        .s1 {
            color: black;
            font-family: Arial, sans-serif;
            font-style: normal;
            font-weight: normal;
            text-decoration: none;
            font-size: 6pt;
        }

        .s2 {
            color: black;
            font-family: Arial, sans-serif;
            font-style: normal;
            font-weight: normal;
            text-decoration: none;
            font-size: 6pt;
        }

        .s3 {
            color: black;
            font-family: Arial, sans-serif;
            font-style: normal;
            font-weight: normal;
            text-decoration: none;
            font-size: 6pt;
        }

        .s4 {
            color: black;
            font-family: Arial, sans-serif;
            font-style: normal;
            font-weight: normal;
            text-decoration: none;
            font-size: 11pt;
        }

        .s5 {
            color: #8C8F95;
            font-family: Arial, sans-serif;
            font-style: normal;
            font-weight: normal;
            text-decoration: none;
            font-size: 7.5pt;
        }

        .s6 {
            color: black;
            font-family: Arial, sans-serif;
            font-style: normal;
            font-weight: normal;
            text-decoration: none;
            font-size: 7.5pt;
        }

        .s7 {
            color: black;
            font-family: Arial, sans-serif;
            font-style: normal;
            font-weight: normal;
            text-decoration: none;
            font-size: 8pt;
        }

        p {
            color: #8C8F95;
            font-family: Arial, sans-serif;
            font-style: normal;
            font-weight: normal;
            text-decoration: none;
            font-size: 8pt;
            margin: 0pt;
        }

        .a {
            color: #8C8F95;
            font-family: Arial, sans-serif;
            font-style: normal;
            font-weight: normal;
            text-decoration: none;
            font-size: 8pt;
        }

        table, tbody {
            vertical-align: top;
            overflow: visible;
        }
        .float{
            text-indent: 0pt;
            float: right
        }
        .footer {
            position: fixed;
            left: 0px;
            right: 0px;
            padding: 0px;
            min-height: auto;
            overflow: visible;

        }
    </style>
</head>
<body>
<?php

$billingAddress=json_decode($delivery->billing_address);

$shippingAddress=json_decode($delivery->shipping_address);

if($sales->platform===\App\Models\Stock::PLATFROM_MOBILE_ADVANTAGE){
    $logoPath=public_path('img/mobile_advantage_.jpg');
    $width="180";
    $height="50" ;
    $contactNumber="0203 0112270";
    $contactEmail="help@mobileadvantage.co.uk";
}else{
    $width="180";
    $height="40" ;
    $logoPath=public_path('img/logo.png');
    $contactNumber="0203 0111040";
    $contactEmail="help@recomm.co.uk";

}



if(count(json_decode($delivery->item_list))>1){
    $botton="bottom:120px";
}else{

    $botton="bottom:80px";

}





?>



<p class="float"><span>
        <table border="0" cellspacing="0" cellpadding="0" style="width: 100%; margin-left: -212px !important;">
            <tr>

                <td style="text-align:right" >
                    <img width="{{$width}}" height="{{$height}}"
                            src="{{$logoPath}}"/>
                </td>
            </tr>
        </table>
    </span>
</p>

<h1 style="padding-top: 3pt;padding-left: 40pt;text-indent: 0pt;text-align: left;">Recommerce Ltd</h1>
<p class="s1" style="padding-top: 5pt;padding-left: 40pt;text-indent: 0pt;text-align: left;">1st Floor,
    Atlantic House,Gomm Road
</p>
<p class="s1" style=" padding-top: 5pt;padding-left: 40pt;text-indent: 0pt;text-align: left;">High Wycombe</p>
<p class="s1" style="padding-top: 5pt;padding-left: 40pt;text-indent: 0pt;text-align: left;">Buckinghamshire</p>
<p class="s1" style="padding-top: 5pt;padding-left: 40pt;text-indent: 0pt;text-align: left;">HP13 7DJ</p>
<p class="s1" style="padding-left: 40pt;text-indent: 0pt;text-align: left; line-height: 60%; margin-top: -1px">{{$contactNumber}}</p>
<p style="padding-top: 3pt;text-indent: 0pt;text-align: left;line-height: 75%;">
    <a href="mailto:{{$contactEmail}}" class="s2" style="padding-left: 40pt">{{$contactEmail}}</a></p>
<p class="s2" style="padding-left: 40pt;padding-top: 3pt;text-indent: 0pt;text-align: left;margin-top:-3px">
   VAT Registration No.: GB270411831
</p>








<table style="border-collapse:collapse;margin-left:6pt" cellspacing="0" >

    <tr style="height:33pt">
        <td style="width:165pt"><p style="text-indent: 0pt;text-align: left;"><br/></p>
            <p class="s4" style="text-indent: 0pt;text-align: left;padding-left: 40pt;">Delivery Note</p></td>
    </tr>
    <tr style="height:15pt">
        <td style="width:165pt"><p class="s5" style="padding-top: 4pt;text-indent: 0pt;text-align: left;padding-left: 40pt">INVOICE TO</p>
        </td>
        <td style="width:218pt"><p class="s5" style="padding-top: 4pt;text-indent: 0pt;text-align: left;">SHIP TO</p></td>
        <td style="width:86pt">
            <p class="s5" style="padding-top: 4pt;padding-left: 40pt;text-indent: 0pt;text-align: left;font-weight: bolder;color: #000000">
                DATE
                <br>

                INVOICE
            <br>
                ORDER REF
              <br>
                DISPATCH DATE
            </p>
        </td>
        <td style="width:90pt"><p class="s6"
                                  style="padding-top: 4pt;padding-left: 14pt;text-indent: 0pt;text-align: left;">
                {{$delivery->date}}<br>
                {{$delivery->invoice_number}}<br>

                {{$delivery->order_ref}}<br>

                @if(!is_null($sales->dispatch_date)) {{  date('d/m/Y', strtotime($sales->dispatch_date))}} @endif

            </p></td>
    </tr>
    <tr style="height:60pt">
        <td style="width:165pt"><p class="s6" style="text-align: left;margin-top: -44px;line-height: 88%;padding-left: 40pt">



                {{$buyer_name}}<br>
{{--                {{$delivery->company_name}}<br>--}}
                {{$billingAddress->line1}}<br>
               @if(!is_null($billingAddress->line2)) @if($billingAddress->line2 !==""){{trim($billingAddress->line2)}}<br>@endif @endif
                {{$billingAddress->city}}<br>
                {{$billingAddress->postcode}}<br>
                {{$billingAddress->country}}


            </p></td>
        <td style="width:218pt"><p class="s6"style="text-align: left;margin-top: -44px;line-height: 88%">
                {{$shipping_name}}<br>
{{--                {{$delivery->company_name}}<br>--}}
                {{$shippingAddress->line1}}<br>
                @if(!is_null($shippingAddress->line2))@if($shippingAddress->line2 !==""){{trim($shippingAddress->line2)}}<br> @endif  @endif
                {{$shippingAddress->city}}<br>
                {{$shippingAddress->postcode}}<br>
                {{$shippingAddress->country}}


            </p>

        </td>


    </tr>
    <tr>
        <td></td>
    </tr>

    <tr style="height:19pt">

        <td style="width:165pt" bgcolor="#CCCCCC"><p class="s6"
                                                     style="padding-top: 4pt;padding-left: 40pt;text-indent: 0pt;text-align: left;font-size: x-small">
                DESCRIPTION</p></td>

        <td style="width:86pt" bgcolor="#CCCCCC"><p style="text-indent: 0pt;text-align: left;"><br/></p></td>
        <td style="width:90pt" bgcolor="#CCCCCC"><p style="text-indent: 0pt;text-align: left;color:#000000">QTY <br/></p></td>
    </tr>


    @foreach(json_decode($delivery->item_list) as $item)

        <tr style="height:22pt">
            <td style="width:200pt">

{{--                <p class="s7" style="padding-left: 3pt;text-indent: 0pt;text-align: left;font-weight: bolder">{{$item->service_name}}</p>--}}
<p class="s7" style="padding-left: 40pt;text-indent: 0pt;text-align: left;">
    {{$item->description}}</p>


            </td>
            <td style="width:90pt"><p style="text-indent: 0pt;text-align: left;"><br/></p></td>
            <td style="width:90pt"><p style="text-indent: 0pt;text-align: left;color: #000000">{{$item->qty}} <br/></p></td>
        </tr>
    @endforeach


</table>
<p><br></p>
<p style="text-indent: 0pt;text-align: justify;width:550px;margin-left: 30pt"> Many thanks for your order.If you have any queries or issues please contact us as soon as possible by either emailing {{$contactEmail}} or calling us on {{$contactNumber}}. Many thanks the team at Recomm</p>
<p style="padding-left: 6pt;text-indent: 0pt;line-height: 1pt;text-align: left;"/>


<p><br></p>
<p><br></p>



<div  class="footer" style="<?php echo $botton; ?>">
<p style="text-indent: 0pt;text-align: justify;width:550px;margin-left: 30pt;  ">{{html_entity_decode($note)}}</p>

{{--@foreach($atArray as $at )--}}
{{--    <p style="text-indent: 0pt;text-align: justify;width:550px;margin-left: 30pt">{{$at}}</p>--}}
{{--@endforeach--}}

@foreach(json_decode($delivery->item_list) as $item)
    <p style="text-indent: 0pt;text-align: justify;width:550px;margin-left: 30pt">{{$item->description .' '. $condition}}</p>
    @endforeach


</div>
</body>
</html>
