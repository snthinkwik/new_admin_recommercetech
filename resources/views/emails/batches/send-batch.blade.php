@extends('email')

@section('content')

    <p>{!! $body !!}</p>

@endsection

@section('regards')
    Regards,<br/><strong>Recomm Sales</strong>
@endsection

{{--
@if(isset($user) && $user && $user->invoice_api_id)
@section('customer-id')
<div style="background-color:#f4f4f4;margin:0px auto;max-width:960px;">
    <div style="background-color:#FFFFFF;margin:0px auto;max-width:720px;">
        <table align="center" border="0" cellpadding="0" cellspacing="0" style="background-color:#FFFFFF;width:100%;">
            <tbody>
                <tr>
                    <td style="direction:ltr;font-size:0;text-align:center;">
                        <div style="text-align:left;direction:ltr;font-size:0;display:inline-block;width:100%;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="left" style="padding:0 25px 15px;word-break:break-word;">
                                        <div style="font-size:16px;text-align:left;color:#000000;">

                                            <p style="font-size:11px; margin:0">
                                                RCT ref: {{ $user->invoice_api_id }}
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
@endif--}}
