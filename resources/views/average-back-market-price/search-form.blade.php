<?php

use Illuminate\Support\Facades\Request;

$statusList = \App\EbayOrders::getAvailableStatusWithKeys();
$category=\App\Category::select('name')->get();
$categoryList=[];
foreach ($category as $key=>$category){
    $categoryList[$category['name']]=$category['name'];
}
$productId=\App\Product::select('id')->get();
$brands=\App\Stock::select('make')->distinct()->get();

?>


<div class="row">
    {!! BsForm::open(['id' => 'ebay-order-search-form', 'class' => 'mb15', 'method' => 'get','class' => 'spinner']) !!}
    <div class='universal-loader' style="display: none" ><div class='universal-spinner'></div></div>
    <div class="col-sm-3">
        <div class="form-group">
            {!! BsForm::text('filter', Request::input('filter'), ['id' => 'ebay-sales-record-search-term', 'placeholder' => 'Product Name,MPN,EAN,Model No', 'size' => 20]) !!}
        </div>
    </div>

    <div class="col-sm-2">
        <div class="form-group">
            <select name="condition"  class="form-control">
                <option value="">Select Condition Filter</option>
                <option value="1"> Excellent</option>
                <option value="2"> Good
                <option value="3">Fair
            </select>
        </div>
    </div>
    <div class="col-sm-1">
        <div class="form-group">
            <select name="brand" class="form-control">
                <option value="">Select Brand</option>
                @foreach($brands as $brand)
                    <option value="{{$brand->make}}">{{$brand->make}}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-sm-1">
        <div class="form-group">
            <div class="input-group">
                <select name="product_id" class="form-control product-select2">
                    <option value="">Select Validated</option>
                    @foreach($productId as $product)
                        <option value="{{$product->id}}">{{$product->id}}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>


    <div class="col-sm-1">
        <div class="form-group">
            <div class="input-group">
                <select name="buy_box" class="form-control ">
                    <option value="">Select BuyBox</option>
                    <option value="yes">Yes</option>
                    <option value="no">No</option>
                </select>
            </div>
        </div>
    </div>






    {!! BsForm::hidden('sort', '',['id'=>'sort']) !!}
    {!! BsForm::hidden('sortO', '',['id'=>'sortO']) !!}
    {!! BsForm::close() !!}
</div>
