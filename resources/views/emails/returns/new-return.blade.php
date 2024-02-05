@extends('email')

@section('content')

	<p>Hi {{ $user->first_name }},</p>

	<p>Sorry that you need to return an item to Recomm. Please click on the link below to start your return request.</p>

	<p><a href="{{ $stockReturn->recomm_url }}">Click here to view RMA Request Form</a></p>

	<p>Please populate with the items you wish to return using the IMEI or serial number of the device.</p>

	<p>This is a pre-request, please wait for confirmation before returning goods.</p>

@endsection

@section('regards')
	<div>Regards,</div>
	<div><b>Victoria Penhale</b></div>
@endsection