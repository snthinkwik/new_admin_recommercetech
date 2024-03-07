<?php
use Illuminate\Support\Facades\Request;
use App\RepairStatus;
use App\RepairEngineer;

$repairEngineers = ['' => 'All'] + RepairEngineer::get()->lists('name', 'id');
//$repairStatuses = ['' => 'All'] + RepairStatus::get()->lists('name', 'id');
$repairType = ['' => 'All'] + \App\RepairsItems::getTypesWithKeys();

$repairStatuses = ['all' => 'All']+\App\RepairsItems::getStatusWithKeys();

?>
{!! BsForm::open(['id' => 'universal-search-form', 'class' => 'form-inline mb15', 'method' => 'get']) !!}
<div class="form-group">
    {!! BsForm::select('search_type',[''=>'select filter','repair_id'=>'Searching By Repair Id','imei'=>'Searching By IMEI','serial'=>'Searching By Serial'], Request::input('search_type')) !!}
    {!! BsForm::text('term', Request::input('term'), ['id' => 'repairs-search-term', 'placeholder' => 'Search Item', 'size' => 30]) !!}

    <div class="input-group">
        <span class="input-group-addon">Status</span>
        {!! BsForm::select('status', $repairStatuses,'Open') !!}
    </div>

    <div class="input-group">
        <span class="input-group-addon">Engineer</span>
        {!! BsForm::select('engineer', $repairEngineers, Request::input('engineer')) !!}
    </div>

    <div class="input-group">
        <span class="input-group-addon">Type</span>
        {!! BsForm::select('type',$repairType, Request::input('type')) !!}
    </div>

    <div class="input-group">

        <span class="input-group-addon">Create At Date Rang</span>


       <input type="date" name="start" class="">To<input type="date" name="end" class="">
    </div>
</div>

{!! BsForm::close() !!}