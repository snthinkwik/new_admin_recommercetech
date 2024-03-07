<?php

use Illuminate\Support\Facades\Request;
use App\Models\EbayOrders;
use App\Models\Category;

$statusList = EbayOrders::getAvailableStatusWithKeys();
$category=Category::select('name')->get();
$categoryList=[];
foreach ($category as $key=>$category){
    $categoryList[$category['name']]=$category['name'];
}

?>


<div class="row">
    {!! BsForm::open(['id' => 'ebay-order-search-form', 'class' => 'mb15', 'method' => 'get','class' => 'spinner']) !!}
    <div class='universal-loader' style="display: none" ><div class='universal-spinner'></div></div>
    <div class="col-sm-3">
        <div class="form-group">
            {!! BsForm::text('filter', Request::input('filter'), ['id' => 'ebay-sales-record-search-term', 'placeholder' => 'Product Name,MPN,EAN,Model No', 'size' => 20]) !!}

{{--            <input type="text" class="form-control term" name="term" value="{{Request::input('term')}}" placeholder="Product Name,Model No,MPN" @if(isset($advance)) disabled @endif id="term">--}}

            <div id="productList"></div>
        </div>



    </div>

    <div class="col-sm-3">
        <div class="form-group">
            <select name="condition"  class="form-control">
                <option value="">Select Condition Filter</option>

                <option value="Excellent - Refurbished"> Excellent - Refurbished
                <option value="Very Good - Refurbished"> Very Good - Refurbished
                <option value="Good - Refurbished"> Good - Refurbished
                <option value="For parts or not working"> For parts or not working



            </select>
        </div>
    </div>






    {!! BsForm::hidden('sort', '',['id'=>'sort']) !!}
    {!! BsForm::hidden('sortO', '',['id'=>'sortO']) !!}
    {!! BsForm::close() !!}
</div>
