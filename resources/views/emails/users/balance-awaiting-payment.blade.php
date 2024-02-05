@extends('email')

@section('content')

    <p>Hi {{ $user->first_name }},</p>
                                      
    <p>Thank you for your recent order from Recomm.</p>
    <p>There is currently a balance of {{ money_format(config('app.money_format'), $balance) }} on your account.</p>
    <p>Please can you advise when you can make payment?</p>
                                    
    <p>Payment method is preferred by Bank Transfer:</p>
                        
    <p>
        Account Name: Recommerce Ltd<br/>
        Account no: 49869160<br/>
        Sort Code: 30-98-97<br/>
        Bank: Lloyds
    </p>
                        

{{--    <p>--}}
{{--        <a href="{{ config('services.trg_uk.url') }}/pay-order?userhash={{ $user->hash }}">Click here to login to your account and pay online.</a>--}}
{{--    </p>--}}


@endsection

@section('regards')
    Regards,<br/><strong>Recomm</strong>
@endsection