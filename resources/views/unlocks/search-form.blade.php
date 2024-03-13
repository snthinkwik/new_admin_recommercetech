<?php
use App\Models\Unlock;
$statuses = ['' => 'All'] + Unlock::getAvailableStatusesWithKeys();
use App\Models\Stock;
$networks = ['' => 'All', 'Vodafone Special' => 'Vodafone Special'] + array_combine(Stock::getAdminUnlockableNetworks(), Stock::getAdminUnlockableNetworks());

//$sources = ['' => 'All', 'retail_orders' => 'Retail Orders', 'stock' => 'Stock Unlocks'];
?>

{!! BsForm::model($request, ['id' => 'unlock-search', 'class' => 'form-inline mb15', 'method' => 'get']) !!}
	{!! BsForm::groupSelect('status', $statuses, $request->status) !!}
	{!! BsForm::groupSelect('network', $networks, null) !!}
	{!! BsForm::groupText('imei', null, ['placeholder' => "Search by IMEI"], ['label' => false]) !!}
	{!! BsForm::groupText('stock_id', null, ['Placeholder' => 'Search by Stock Item'], ['label' => 'Stock ID']) !!}
{{--	{!! BsForm::groupSelect('source', $sources, null) !!}--}}
{!! BsForm::close() !!}
