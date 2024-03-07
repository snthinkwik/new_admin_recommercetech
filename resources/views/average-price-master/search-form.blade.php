<?php
use App\Models\EbayOrders;
use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Facades\Request;

$statusList = EbayOrders::getAvailableStatusWithKeys();
$category = Category::select('id','name')->get();
$productId=Product::select('id')->get();
$brands= Stock::select('make')->distinct()->get();
?>



<div class="row">
    {!! BsForm::open(['id' => 'ebay-order-search-form', 'class' => 'mb15', 'method' => 'get','class' => 'spinner']) !!}
    <div class='universal-loader' style="display: none">
        <div class='universal-spinner'></div>
    </div>


    <div class="col-sm-2">
        <div class="form-group">
            {!! BsForm::text('filter', Request::input('filter'), ['id' => 'ebay-sales-record-search-term', 'placeholder' => 'Product Name,MPN,EAN,Model No', 'size' => 20]) !!}
        </div>
    </div>
    <div class="col-sm-2">
        <div class="form-group">
            <select name="condition" class="form-control">
                <option value="">Select Condition Filter</option>
                <option value="New" @if(Request::input('condition')==="New") selected @endif> New</option>
                <option value="Open box" @if(Request::input('condition')==="Open box") selected @endif> Open box</option>
                <option value="New with defects" @if(Request::input('condition')==="New with defects") selected @endif>New with defects</option>
                <option value="Certified - Refurbished" @if(Request::input('condition')==="Certified - Refurbished") selected @endif>Certified - Refurbished</option>
                <option value="Excellent - Refurbished" @if(Request::input('condition')==="Excellent - Refurbished") selected @endif> Excellent - Refurbished</option>
                <option value="Very Good - Refurbished" @if(Request::input('condition')==="Very Good - Refurbished") selected @endif> Very Good - Refurbished</option>
                <option value="Good - Refurbished" @if(Request::input('condition')==="Good - Refurbished") selected @endif> Good - Refurbished</option>
                <option value="Seller refurbished" @if(Request::input('condition')==="Seller refurbished") selected @endif> Seller refurbished</option>
                <option value="Like New" @if(Request::input('condition')==="New") selected @endif> Like New</option>
                <option value="Used" @if(Request::input('condition')==="Like New") selected @endif> Used</option>
                <option value="Very Good" @if(Request::input('condition')==="Very Good") selected @endif> Very Good</option>
                <option value="Good" @if(Request::input('condition')==="Good") selected @endif> Good</option>
                <option value="Acceptable" @if(Request::input('condition')==="Acceptable") selected @endif> Acceptable</option>
                <option value="For parts or not working" @if(Request::input('condition')==="For parts or not working") selected @endif> For parts or not working</option>
                <option value="Seller refurbished Grade A-Excellent" @if(Request::input('condition')==="Seller refurbished Grade A-Excellent") selected @endif> Seller refurbished Grade A-Excellent</option>
                <option value="Seller refurbished Grade B- Very Good" @if(Request::input('condition')==="Seller refurbished Grade B- Very Good") selected @endif> Seller refurbished Grade B- Very Good</option>
                <option value="Seller refurbished Grade C-Good" @if(Request::input('condition')==="Seller refurbished Grade C-Good") selected @endif>Seller refurbished Grade C-Good</option>


            </select>
        </div>
    </div>


    <div class="col-sm-1">
        <div class="form-group">
                <select name="validate" class="form-control">
                    <option value="">Select Validated</option>
                    <option value="Yes" @if(Request::input('validate')==="Yes") selected @endif>Yes
                    <option value="No" @if(Request::input('validate')==="No") selected @endif> No
                </select>
        </div>
    </div>


    <div class="col-sm-1">
        <div class="form-group">
                <select name="brand" class="form-control">
                    <option value="">Select Brand</option>
                    @foreach($brands as $brand)
                        <option value="{{$brand->make}}" @if(Request::input('brand')===$brand->make)  selected @endif>{{$brand->make}}</option>
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
            <select name="time" class="form-control">
                <option name="">Last Updated</option>
                <option name="24 hours" @if(Request::input('time')==="24 hours") selected @endif>Within 24 hours</option>
                <option name="within 1 week" @if(Request::input('time')==="within 1 week") selected @endif>Within 1 week</option>
                <option name="more then 1 week" @if(Request::input('time')==="more then 1 week") selected @endif>More Then 1 Week</option>
            </select>
        </div>
    </div>


    {!! BsForm::hidden('sort', '',['id'=>'sort']) !!}
    {!! BsForm::hidden('sortO', '',['id'=>'sortO']) !!}
    {!! BsForm::close() !!}



    <div class="col-md-3">
        <form method="post" action="{{route('update.validation')}}">
            <input type="hidden" name="_token" value="{{{ csrf_token() }}}"/>
            <div class="input-group">
                <div class="input-group-btn">
                    <select  name="category" class="form-control" placeholder="select Category" style="width: 150px;" required>
                        <option value="">Select Category</option>
                        @foreach($category as $cat)
                            <option value="{{$cat->id}}">{{$cat->name}}</option>
                        @endforeach
                    </select>
                </div>
                <input type="number" class="form-control" placeholder="Set % Validation Category" name="percentage" required>
                <span class="input-group-btn">
        <input type="submit" class="btn btn-info" value="Update"></input>
      </span>
            </div>
        </form>
    </div>
</div>
