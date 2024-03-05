<?php

use App\Models\Stock;
use  App\Models\SellerFees;

$days = ['' => 'Select Days', 'current' => 'Today', 'week' => 'This Week', 'month' => 'This Month'];
$month = [
    '' => 'Select Month',
    '1' => 'January',
    '2' => 'February',
    '3' => 'March',
    '4' => 'April',
    '5' => 'May',
    '6' => 'June',
    '7' => 'July',
    '8' => 'August',
    '9' => 'September',
    '10' => 'October',
    '11' => 'November',
    '12' => 'December',
];

$years = [
    '' => 'Select Year',
    '2025' => '2025',
    '2024' => '2024',
    '2023' => '2023',
    '2022' => '2022',
    '2021' => '2021',
    '2020' => '2020',
    '2019' => '2019',
    '2018' => '2018',
    '2017' => '2017',
    '2016' => '2016',
    '2015' => '2015',
    '2014' => '2014',
    '2013' => '2013',
    '2012' => '2012',
    '2011' => '2011',
    '2010' => '2010',
    '2009' => '2009',
    '2008' => '2008',
    '2007' => '2007',
    '2006' => '2006',
    '2005' => '2005',
    '2004' => '2004',
    '2003' => '2003',
    '2002' => '2002',
    '2001' => '2001',
    '2000' => '2000',

];
$SellerFess = SellerFees::groupBy('platform')->get();
$platformList = [];

foreach ($SellerFess as $key => $platform) {
    $platformList[''] = "Select PlatForm";
    $platformList[$platform['platform']] = $platform['platform'];
}

$categoryList = [];
foreach (Stock::getProductType() as $key => $category) {
    $categoryList[''] = "Select Category";
    $categoryList[$category['product_type']] = $category['product_type'];
}

$vatType = ['' => 'Select Vat Type', 'Standard' => 'Standard', 'Margin' => 'Margin'];
?>
@extends('app')

@section('title', 'Dashboard')

@section('content')
    <div class="container-fluid">
        {!! Form::open(['id' => 'universal-search-form', 'class' => 'spinner form-inline mb15', 'method' => 'get']) !!}

        <div class="form-group">

            <div class="input-group">
                <div class="input-group-addon">Days</div>
                {!! BsForm::select('days', $days,'month') !!}
            </div>
        </div>
        <div class="form-group">
            <div class="input-group">
                <div class="input-group-addon">Year</div>
                {!! BsForm::select('year',$years) !!}
            </div>
        </div>
        <div class="form-group">
            <div class="input-group">
                <div class="input-group-addon">Month</div>
                {!! BsForm::select('month',$month) !!}
            </div>
        </div>
        <div class="form-group">
            <div class="input-group">
                <div class="input-group-addon">PlatFrom</div>
                {!! BsForm::select('platform', $platformList) !!}
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <div class="input-group-addon">Category</div>
                {!! BsForm::select('category', $categoryList ) !!}
            </div>
        </div>

        <div class="form-group">
            <div class="input-group">
                <div class="input-group-addon">VatType</div>
                {!! BsForm::select('vat_type', $vatType) !!}
            </div>
        </div>


        <div class="form-group mt-2">
            <div class="input-group">
                <div class="input-group-addon">Date Rang</div>
                <input type="date" name="start"> to <input type="date" name="end">
            </div>
        </div>


        {!! Form::close() !!}

        @include('messages')
        <div id="universal-table-wrapper">
            @include('sales.counting')
        </div>

    </div>
@endsection

