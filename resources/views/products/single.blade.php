<?php
use App\Models\Colour;
use App\Models\Stock;
use App\Models\Category;
$colours =Colour::orderBy('pr_colour')->pluck('pr_colour', 'pr_colour')->toArray();
$makes = Stock::getMake();

$makeList = [];


$category = Category::select('name')->get();
$categoryList = [];
foreach ($category as $key => $category) {
    $categoryList[$category['name']] = $category['name'];
}



foreach (Stock::getMake() as $key => $make) {
    $makeList[$make['make']] = $make['make'];
}
$vatTypeList = ['' => 'Select Vat Type', 'Margin' => 'Margin', 'Standard' => 'Standard']
?>
@extends('app')

@section('title', 'Product - '.$product->model)
@include('scripts', ['required' => 'ckeditor'])
@section('scripts')
    <script>
        CKEDITOR.replaceAll('ckeditor-textarea');


        //$("#vatType").hide();
        //$("#lab_vat").hide();
        $("#non_serialised").on('click', function () {
            if ($(this).is(':checked')) {
                $("#multi_qty").prop('readonly', false);
                $("#vatType").show();
                $("#lab_vat").show();
                $("#purchase_price").show();
                $("#pur_lab").show();
            } else {

                $("#multi_qty").prop('readonly', true);
                $("#vatType").hide();
                $("#lab_vat").hide();
                $("#purchase_price").hide();
                $("#pur_lab").hide();


            }
        })

    </script>
@endsection


@section('content')

    <div class="container">
        @if(!is_null($page))
            <a href="{{route('products')}}?page={{$page}}" class="btn btn-default">Back</a>
        @else
            <a href="{{route('products')}}" class="btn btn-default">Back</a>
        @endif

        <h2>Product Details - {{ $product->product_name }}</h2>

        @include('messages')

        {!! BsForm::model($product, ['method' => 'post', 'route' => 'products.update', 'files' => 'true']) !!}
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">Details</div>
                    <div class="panel-body">
                        {!! BsForm::hidden('id') !!}
                        {!! BsForm::groupText('full_product_name',$product->make.' '.$product->product_name, ['disabled' => 'disabled']) !!}
                        {!! BsForm::groupSelect('make',$makeList,$product->make, ['required' => 'required','class'=>'network-select2']) !!}
                        {!! BsForm::groupSelect('category',['' => 'Select Category']+$categoryList,$product->category, ['required' => 'required']) !!}
                        {!! BsForm::groupText('product_name',$product->product_name, ['required' => 'required'],['label' => 'Name']) !!}
                        {{--{!! BsForm::groupText('id', null, ['disabled' => 'disabled'], ['label' => 'ID']) !!}--}}

                        {!! BsForm::groupText('model', null) !!}
                        {!! BsForm::groupText('slug', null, [],['label' => 'MPN']) !!}
                        {!! BsForm::groupText('ean', $product->ean,[],['label' => 'EAN']) !!}
                        <input type="checkbox" name="retail_comparison" @if($product->retail_comparison) checked @endif>
                        Retail Comparison
                        {!! BsForm::groupText('capacity', null) !!}


                        <input type="checkbox" name="non_serialised" id="non_serialised"
                               @if($product->non_serialised) checked @endif> Non Serialised
                        {!! BsForm::groupText('multi_quantity',number_format($product->multi_quantity),[!$product->non_serialised ? 'readonly':'','id'=>'multi_qty']) !!}

                        {!! BsForm::groupText('weight', $product->weight,[],['label' => 'Weight']) !!}
                        {!! BsForm::groupText('pco2', $product->pco2,[],['label' => 'PCO2']) !!}
                        {!! BsForm::groupText('back_market_id', $product->back_market_id,[],['label' => 'Back Market Id']) !!}

                        {!! BsForm::groupText('epd', $product->epd,[],['label' => 'eBay ID(EPD)']) !!}
                        {!! BsForm::groupText('asw', $product->asw,[],['label' => 'Amazon ID(ASW)']) !!}
                        {!! BsForm::groupText('ma', $product->ma,[],['label' => 'Mobile Advantage(MA)']) !!}

                        <label for="item-capacity" id="pur_lab"
                               @if(!$product->non_serialised) style="display: none;" @endif>Purchase price</label>
                        <div class="input-group" id="purchase_price"
                             @if(!$product->non_serialised) style="display: none;" @endif>
                            <div class="input-group-addon">£</div>
                            {!! BsForm::text('purchase_price',$product->purchase_price) !!}

                        </div>
                        <br>

                        <label id="lab_vat" @if(!$product->non_serialised) style="display: none;" @endif>Vat
                            Type</label>

                        <select id="vatType" class="form-control" name="vat_type"
                                @if(!$product->non_serialised) style="display: none;" @endif>
                            <option value="">Select Vat Type</option>
                            <option value="Margin" @if($product->vat==="Margin")selected @endif>Margin</option>
                            <option value="Standard" @if($product->vat==="Standard")selected @endif>Standard</option>
                        </select>


                        <label>Archive:</label>

                        <div class="input-group">
                            {!! BsForm::checkbox('archive',1,$product->archive, ['data-toggle' => 'toggle', 'class' => 'toggle-yes-no']) !!}
                        </div>


                        {!! BsForm::groupTextarea('sort_description', null,['class' => 'ckeditor-textarea']) !!}
                        {!! BsForm::groupTextarea('product_features', null,['class' => 'ckeditor-textarea']) !!}
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">Image</div>
                    <div class="panel-body">
                        @if(count($product->images)>0)

                            <div class="row">
                                @foreach($product->images as $image)
                                    <div class="col-md-4 col-xs-12 p10">
                                        <img src="{{asset("/img/products/".$image->name)  }}" class="img-responsive"
                                             width="100" height="100">
                                        <a href="{{route('image.remove',['id'=>$image->id])}}"><i class="fa fa-trash-o"
                                                                                                  aria-hidden="true"></i>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <input type="file" name="image[]" class="form-control" multiple/>
                    </div>
                </div>


            </div>
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">Refurbished Price</div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4" style="border-right: 1px solid #000000">
								<span>

									<label>Always In Stock</label>
									 <input type="checkbox" name="always_in_stock_A" value="1"
                                            @if($product->always_in_stock_A) checked="checked" @endif>
									{!! BsForm::groupText('price_a', $product->refurbished_price_A,[],['label' => 'Price A']) !!}

								</span>

                            </div>
                            <div class="col-md-4" style="border-right: 1px solid #000000">
                                <label>Always In Stock</label>
                                <input type="checkbox" name="always_in_stock_B" value="1"
                                       @if($product->always_in_stock_B) checked="checked" @endif >
                                {!! BsForm::groupText('price_b', $product->refurbished_price_B,[],['label' => 'Price B']) !!}


                            </div>
                            <div class="col-md-4">
                                <label>Always In Stock</label>
                                <input type="checkbox" name="always_in_stock_C" value="1"
                                       @if($product->always_in_stock_C) checked="checked" @endif>
                                {!! BsForm::groupText('price_c', $product->refurbished_price_C,[],['label' => 'Price C']) !!}

                            </div>
                        </div>


                    </div>
                </div>


            </div>
            <div class="col-md-12">
                {!! BsForm::groupSubmit('Update', ['class' => 'btn-block']) !!}
            </div>
        </div>
        {!! BsForm::close() !!}

    </div>

@endsection
