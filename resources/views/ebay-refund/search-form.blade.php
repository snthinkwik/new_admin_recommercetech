<?php
$ownerList = \App\EbayOrderItems::getAvailableOwnerWithKeys();
?>
<div class="row">
    {!! BsForm::open(['id' => 'refund-ebay-order-search-form', 'class' => 'mb15', 'method' => 'get','class' => 'spinner']) !!}
    <div class='universal-loader' style="display: none" ><div class='universal-spinner'></div></div>

    <div class="col-sm-3">
        <div class="form-group">
            {!! BsForm::text('sales_record_number', Request::input('sales_record_number'), ['id' => 'refund-ebay-sales-record-search-term', 'placeholder' => 'Sales Record No.', 'size' => 20]) !!}
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            <div class="input-group">
                <span class="input-group-addon">Process?</span>
                {!! BsForm::select('processed', ['' => 'All','Yes' => 'Yes', 'No' => 'No'], Request::input('processed')? Request::input('processed') : "No" ) !!}
            </div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            <div class="input-group">
                <span class="input-group-addon">Owner</span>
                {!! BsForm::select('owner', ['' => 'Please Select'] + $ownerList,Request::input('owner') ? Request::input('owner') : \App\EbayOrderItems::RECOMM) !!}
            </div>
        </div>
    </div>

    {!! BsForm::hidden('sort', '',['id'=>'sort']) !!}
    {!! BsForm::hidden('sortO', '',['id'=>'sortO']) !!}
    {!! BsForm::close() !!}
</div>
