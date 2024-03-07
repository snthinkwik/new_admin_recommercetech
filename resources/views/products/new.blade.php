<?php
$colours = \App\Colour::orderBy('pr_colour')->lists('pr_colour', 'pr_colour');

$category=\App\Category::select('name')->get();
$categoryList=[];
foreach ($category as $key=>$category){
    $categoryList[$category['name']]=$category['name'];
}


$makes=\App\Stock::getMake();

$makeList=[''=>'Please Select Make'];

foreach (\App\Stock::getMake() as $key=>$make){
    $makeList[$make['make']]=$make['make'];
}

$vatTypeList=[''=>'Select Vat Type','Margin'=>'Margin','Standard'=>'Standard']

?>
@extends('app')

@section('title', 'Create New Product')

@include('scripts', ['required' => 'ckeditor'])
@section('scripts')
    <script>
        $("#vatType").hide();
        $("#lab_vat").hide();
        $("#purchase_price").hide();
        $("#pur_lab").hide();
        CKEDITOR.replaceAll('ckeditor-textarea');


        $("#non_serialised").on('click',function () {
            if ($(this).is(':checked')) {
                $("#multi_qty").prop('readonly', false);

                $("#vatType").show();
                $("#lab_vat").show();
                $("#purchase_price").show();
                $("#pur_lab").show();
            }else{
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

        <a href="{{route('products')}}" class="btn btn-default">Back</a>
        <h2>Create New Product</h2>

        @include('messages')

        {!! BsForm::open(['method' => 'post', 'route' => 'products.save', 'files' => 'true']) !!}
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">Details</div>
                    <div class="panel-body">
                        {!! BsForm::groupSelect('make',$makeList, ['required' => 'required','class'=>'network-select2']) !!}
                        {!! BsForm::groupSelect('category',['' => 'Select Category']+$categoryList, ['required' => 'required']) !!}
                        {!! BsForm::groupText('product_name',null,null,['label' => 'Name']) !!}
                        {!! BsForm::groupText('model', null) !!}
                        {!! BsForm::groupText('sku', null, [],['label'=>'MPN']) !!}
                        {!! BsForm::groupText('ean', null,[],['label'=>'EAN']) !!}
                        <input type="checkbox" name="retail_comparison"> Retail Comparison
                        {{--{!! BsForm::groupSelect('vat_type', $vatTypeList, ['required' => 'required']) !!}--}}
                        {!! BsForm::groupText('capacity', null) !!}


                        {{--{!! BsForm::groupSelect('color',['' => 'Select Colour']+$colours, ['required' => 'required']) !!}--}}
                        {{--{!! BsForm::groupCheckbox('non_serialised',null,['id'=>'non_serialised']) !!}--}}
                        <input type="checkbox" name="non_serialised" id="non_serialised"> Non Serialised


                        {!! BsForm::groupText('multi_quantity',1,['readonly','id'=>'multi_qty']) !!}
                        {!! BsForm::groupText('weight',null,['label' => 'Weight']) !!}
                        {!! BsForm::groupText('pco2', null,['label' => 'PCO2']) !!}



                        <label for="item-capacity" id="pur_lab">Purchase price</label>
                        <div class="input-group" id="purchase_price">
                            <div class="input-group-addon">£</div>
                            {!! BsForm::text('purchase_price') !!}

                        </div>

                        {{--{!! BsForm::groupSelect('vat_type',['' => 'Select Vat Type']+['Margin'=>'Margin','Standard'=>'Standard'],['id'=>'vatType']) !!}--}}
                        <label id="lab_vat">Vat Type</label>
                        <select id="vatType" class="form-control" name="vat_type">
                            <option value="">Select Vat Type</option>
                            <option value="Margin">Margin</option>
                            <option value="Standard">Standard</option>
                        </select>



                        {!! BsForm::groupTextarea('sort_description', null,['class' => 'ckeditor-textarea']) !!}
                        {!! BsForm::groupTextarea('product_features', null,['class' => 'ckeditor-textarea']) !!}

                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">Image</div>
                    <div class="panel-body">
                        <input type="file" name="image[]" multiple class="form-control" />
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                {!! BsForm::groupSubmit('Save', ['class' => 'btn-block']) !!}
            </div>
        </div>
        {!! BsForm::close() !!}

    </div>

@endsection

{{--@section('scripts')--}}
    {{--<script>--}}
        {{--// alert("Hello");--}}
    {{----}}
    {{--</script>--}}
{{--@endsection--}}
