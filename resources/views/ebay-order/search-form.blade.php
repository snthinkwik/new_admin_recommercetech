<?php

use Illuminate\Support\Facades\Request;
use App\Models\EbayOrders;
use App\Models\Stock;

$statusList = EbayOrders::getAvailableStatusWithKeys();
$category=\App\Models\Category::select('name')->get();
$categoryList=[];
foreach ($category as $key=>$category){
    $categoryList[$category['name']]=$category['name'];
}

?>

<div class="row">
    <div class="alert alert-success" role="alert" id="message" style="display: none">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                aria-hidden="true">&times;</span></button>
        Status Successfully Update
    </div>

    <div class="col-lg-4">
        <div class="input-group">
            <span class="input-group-addon">Status</span>
            <select class="form-control" id="status">
                @foreach($statusList as $status)
                <option name="status" value="{{$status}}">{{ucfirst($status)}}</option>
                @endforeach
            </select>
            <span class="input-group-btn">
                <input id="bulk-retry-status-button" class="btn btn-primary" type="submit" value="Bulk Change">
            </span>
        </div>
    </div>
</div><br>
<div class="row">
    {!! BsForm::open(['id' => 'ebay-order-search-form', 'class' => 'mb15', 'method' => 'get','class' => 'spinner']) !!}
    <div class='universal-loader' style="display: none" ><div class='universal-spinner'></div></div>

    <div class="col-sm-3">
        <div class="form-group">
            <select name="field" id="ebayFilter" class="form-control">
                <option value="">Select Filter</option>
                <option value="order_id" @if(Request::input('field')=="order_id") selected @endif>Buyers ref</option>
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
    <div class="col-sm-3">
        <div class="form-group">
            <div class="input-group">
                <span class="input-group-addon">Allocated?</span>
                {!! BsForm::select('allocated',['' => 'All','Yes' => 'Yes', 'No' => 'No'],Request::input('allocated') ,['id' => 'allocated']) !!}
            </div>
        </div>
    </div>

    <div class="col-sm-3">
        <div class="form-group">
            <div class="input-group">
                <span class="input-group-addon">Category</span>
                {!! BsForm::select('category',[''=>'All']+ $categoryList ,Request::input('category') ,['id' => 'category']) !!}
            </div>
        </div>
    </div>

    <div class="col-sm-3">
        <div class="form-group">
            <div class="input-group">
                <span class="input-group-addon">PlatForm</span>
                {!! BsForm::select('platform',[''=>'All',Stock::PLATFROM_EBAY=>Stock::PLATFROM_EBAY,
                                                        Stock::PLATFROM_BACKMARCKET=>Stock::PLATFROM_BACKMARCKET,
                                                        Stock::PLATFROM_RECOMM=>Stock::PLATFROM_RECOMM,
                                                        Stock::PLATFROM_MOBILE_ADVANTAGE=>Stock::PLATFROM_MOBILE_ADVANTAGE

] ,Request::input('platform') ,['id' => 'platform']) !!}
            </div>
        </div>
    </div>


    {!! BsForm::hidden('sort', '',['id'=>'sort']) !!}
    {!! BsForm::hidden('sortO', '',['id'=>'sortO']) !!}
    {!! BsForm::close() !!}
</div>
