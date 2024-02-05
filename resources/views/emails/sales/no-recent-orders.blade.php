@extends('email')

@section('content')


    <p>Hi {{ $customer->first_name }},</p>
    <p style="margin-top: 0;">I hope you are well?</p>
    <p style="margin-top: 0;">I’ve just checked your account and I can see you haven’t placed an order with us in a while.</p>
    <p style="margin-top: 0;">How was your last order from us? Can we do anything to improve?</p>
    <p style="margin-top: 0;">RCT are committed to improving our services and we always look to take on board our clients feedback.</p>
    <p style="margin-top: 0;">Just so you know, RCT also offer the following service:<br />
        Wholesale of Mobile Phone Parts<br />
        Unlocking Services
    </p>
    <p style="margin-top: 0;">If any services are of interest please feel free to
        drop me an email on chris@recomm.co.uk or alternatively give me a call on
        07535 239003.</p>
    <p style="margin: 0;">Thank you and I look forward to hearing from you soon.</p>

@endsection

@section('regards')
    Regards,<br/><strong>Recomm</strong>
@endsection