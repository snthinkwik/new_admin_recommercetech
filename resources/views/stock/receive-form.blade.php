{!! BsForm::open(['route' => 'stock.receive'], ['id-and-prefix' => 'stock-receive']) !!}
	{!!
		BsForm::groupText(
			'ref',
			null,
			['placeholder' => '3rd party ref or IMEI'],
			['label' => 'Receive Physical Stock', 'help-block' => 'Upon receiving stock the system will automatically iCloud check the item.']
		)
	!!}
	{!! BsForm::groupCheckbox('skip_check', 1, null, ['id' => 'skip-check'], ['label' => 'Skip iCloud check']) !!}
	<div id="stock-receive-loading" class="hide mb15"><img src="{{ asset('img/ajax-loader.gif') }}" alt="Loading..."></div>
	<div id="stock-receive-message"></div>
	{!! BsForm::groupSubmit('Receive') !!}
{!! BsForm::close() !!}