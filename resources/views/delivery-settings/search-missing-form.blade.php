<?php
use Illuminate\Support\Facades\Request;
?>
<div class="row">
    {!! BsForm::open(['id' => 'ebay-order-search-form', 'class' => 'mb15', 'method' => 'get','class' => 'spinner']) !!}
    <div class='universal-loader' style="display: none" ><div class='universal-spinner'></div></div>

    <div class="col-sm-3">
        <div class="form-group">
            <select name="field" id="ebayFilter" class="form-control">
                <option value="">Select Filter</option>
                <option value="sales_record_number" @if(Request::input('field')=="sales_record_number") selected @endif>Sales Record No.</option>
                <option value="item_sku" @if(Request::input('field')=="item_sku") selected @endif>Custom Label</option>
                <option value="item_number" @if(Request::input('field')=="item_number") selected @endif>Item Number</option>
                <option value="ebay_username" @if(Request::input('field')=="ebay_username") selected @endif>eBay Username</option>
                <option value="buyer_name" @if(Request::input('field')=="buyer_name") selected @endif>Customer Name</option>
                <option value="post_to_postcode" @if(Request::input('field')=="post_to_postcode") selected @endif>Postcode</option>
                <option value="buyer_email" @if(Request::input('field')=="buyer_email") selected @endif>Buyer Email</option>
                <option value="item_name" @if(Request::input('field')=="item_name") selected @endif>Item title</option>
            </select>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            {!! BsForm::text('sales_record', Request::input('sales_record'), ['id' => 'ebay-sales-record-search-term', 'placeholder' => 'Sales Record No.', 'size' => 20]) !!}
        </div>
    </div>    

    {!! BsForm::hidden('sort', '',['id'=>'sort']) !!}
    {!! BsForm::hidden('sortO', '',['id'=>'sortO']) !!}
    {!! BsForm::close() !!}
</div>
