<?php

use App\Models\Stock;
$categoryAll=\App\Models\Category::all();
$supplierAll=\App\Models\Supplier::all();

$supplierList=[];
$categoryList=[];
foreach ($categoryAll as $key=>$category){
    $categoryList[$category->name]=$category->name;
}
foreach ($supplierAll as $key=>$supplier){
    $supplierList[$supplier->id]=$supplier->name;
}



$productList=[];

foreach (Stock::getProduct() as $key=>$product){

    $productList[$product['id']]=$product['product_name'].'-'.$product['slug'];
}


$productNonSerialisedList=[];

foreach (Stock::getProductNonSerialised() as $key=>$product){

    $productNonSerialisedList[$product['id']]=$product['product_name'].'-'.$product['slug'];
}




?>
@if (Auth::user()->type !== 'user')

@if(in_array(Auth::user()->admin_type, ['admin', 'manager']))
<div id="stock-import" class="collapse {{ session('stock.csv_errors') ? 'in' : '' }} show-if-has-error mb15">
    <p><a href="#stock-import" data-toggle="collapse">Import stock</a></p>
    <p class="mv20"><a href="{{ route('stock.template') }}">Click here to download a CSV template.</a></p>
    @include('stock.import-form')
</div>
@endif

<div id="add-stock-form" class="panel panel-default @if (count($errors) > 0 &&  Session::get('non')!=="1") in @endif collapse">
    <div class="panel-heading">Add Stock</div>
    <div class="panel-body">
        {!! BsForm::open(['method' => 'post', 'route' => 'stock.add-stock','id'=>"verificationForm"]) !!}
        <div class="row">

            <div class="form-group @hasError('name') col-md-2">
                {!! BsForm::groupText('make', null, ['required' => 'required']) !!}
            </div>
            <div class="form-group @hasError('name') col-md-3">
                {!! BsForm::groupText('name', null, ['required' => 'required']) !!}
            </div>
            <div class="form-group @hasError('capacity') col-md-1">
                {!! BsForm::groupText('capacity', null, ['required' => 'required']) !!}
            </div>
            <div class="form-group @hasError('condition') col-md-3">
                {!! BsForm::groupSelect('condition', ['' => 'Please Select'] + Stock::getAvailableConditionsWithKeys(), null, ['required' => 'required']) !!}
            </div>

            <div class="form-group @hasError('grade') col-md-3">
                {!! BsForm::groupSelect('grade', ['' => 'Please Select'] + Stock::getAvailableGradesWithKeys(), null, ['required' => 'required']) !!}
            </div>
            <div class="form-group @hasError('third_party_ref') col-md-3">
                {!! BsForm::groupText('third_party_ref', null, ['required' => 'required']) !!}
            </div>
            <div class="form-group @hasError('lcd_status') col-md-3">
                {!! BsForm::groupSelect('lcd_status',['' => 'Please Select'] + Stock::getAvailableLcdStatusesWithKeys() ,null, ['required' => 'required']) !!}
            </div>
            <div class="form-group @hasError('imei') col-md-3">
                {!! BsForm::groupText('imei', null) !!}
            </div>
            <div class="form-group @hasError('serial') col-md-3">
                {!! BsForm::groupText('serial', null) !!}
            </div>
            <div class="form-group @hasError('sale_price') col-md-3">
                <label>Sale Price</label>
                <div class="input-group">
                    <div class="input-group-addon">£</div>
                    {!! BsForm::text('sale_price', null) !!}
                </div>
                {{--{!! BsForm::groupText('sale_price', null) !!}--}}
            </div>
            <div class="form-group @hasError('purchase_order_number') col-md-3">
                {!! BsForm::groupText('purchase_order_number', null) !!}
            </div>
            <div class="form-group @hasError('purchase_price') col-md-3">

                <label>Purchase Price</label>
                <div class="input-group">
                    <div class="input-group-addon">£</div>
                    {!! BsForm::text('purchase_price', null) !!}
                </div>

            </div>

            <div class="form-group @hasError('product_type') col-md-3">
            {!! BsForm::groupSelect('product_type',[''=>'Select Category']+$categoryList, Request::input('product_type'),['required' => 'required'],['label' => 'Category']) !!}
            </div>

            <div class="form-group @hasError('vat_type') col-md-3">
                {!! BsForm::groupSelect('vat_type',[''=>'Select  Vat Type']+['Margin'=>'Margin','Standard'=>'Standard'], Request::input('vat_type'),['required' => 'required'],['label' => 'Vat Type']) !!}
            </div>
            <div class="form-group @hasError('product_id') col-md-3">
                <label>Product</label><br>
                <select class="repair-select2 form-control" name="product_id" style="width: 465px !important; height: 100px;">
                    <option value=""></option>
                    @foreach ($productList as $key=>$product)
                        <option value="{{$key}}">{{$key.'-'.$product}}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group @hasError('ps_model') col-md-2">
                <label>P/S Model</label><br>
                <select class="form-control" name="ps_model" required  >
                    <option value="">Select P/S Model</option>
                    <option value="0">No</option>
                    <option value="1">Yes</option>

                </select>
            </div>

            <div class="form-group @hasError('supplier_name') col-md-2">
                <label>Supplier</label><br>
                <select class="supplier-select2 form-control" name="supplier_name" required  >
                    <option value="">Supplier</option>
                    @foreach($supplierList as $key=>$value)
                    <option value="{{$key}}">{{$value}}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group @hasError('supplier_name') col-md-2">
                <label>Don't Sim Lock Check</label><br>
                <input name="sim_lock_check" type="checkbox" >
            </div>



            <div class="form-group col-md-12">
                {!! BsForm::groupButton('Add Stock', ['class' => 'btn-sm btn-block addStock','id'=>'addStock']) !!}
            </div>
            <input type="hidden" id="code" name="code">
            {!! BsForm::close() !!}
        </div>
    </div>
</div>
<div id="add-non-serialised-stock-form" class="panel panel-default @if (count($errors) > 0 &&  Session::get('non')==="1") in @endif collapse">
    <div class="panel-heading">Add Non Serialised Stock</div>
    <div class="panel-body">
        {!! BsForm::open(['method' => 'post', 'route' => 'stock.non-serialised.add','id'=>"non_serialised_verificationForm"]) !!}
        <div class="row">

            <div class="form-group @hasError('product_id') col-md-3">
                <label>Repair id</label><br>
                <select class="repair-select2 form-control" name="product_id" style="width: 341px !important; height: 100px;">
                    <option value=""></option>
                    @foreach ($productNonSerialisedList as $key=>$product)
                        <option value="{{$key}}">{{$key.'-'.$product}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group @hasError('grade') col-md-3">
                {!! BsForm::groupSelect('grade', ['' => 'Please Select'] + Stock::getAvailableGradesWithKeys(), null) !!}
            </div>

            <div class="form-group @hasError('sale_price') col-md-3">
                <label>Sale Price</label>
                <div class="input-group">
                    <div class="input-group-addon">£</div>
                    {!! BsForm::text('sale_price', null) !!}
                </div>
            </div>

            <div class="form-group @hasError('vat_type') col-md-3">
                {!! BsForm::groupSelect('vat_type',[''=>'Select  Vat Type']+['Margin'=>'Margin','Standard'=>'Standard'], Request::input('vat_type'),['required' => 'required'],['label' => 'Vat Type']) !!}
            </div>


            <div class="form-group col-md-12">
                {{--{!! BsForm::groupButton('Add Stock', ['class' => 'btn-sm btn-block','id'=>'addStock']) !!}--}}
                {{--<input type="submit" value="Add" id="non_serialised_stock">--}}
                {{--<button id="non_serialised_stock">Add</button>--}}
                <div class="form-group col-md-12">
                    {!! BsForm::groupButton('Add Stock', ['class' => 'btn-sm btn-block','id'=>'non_serialised_stock']) !!}
                </div>
            </div>
            <input type="hidden" id="code_non_serialised" name="code">
            {!! BsForm::close() !!}
        </div>
    </div>
</div>

@endif


<div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="mi-modal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Manager Authorisation Code Required</h4>
            </div>
            <div class="modal-body">
                <div id="errorModel"></div>
                <label>Code</label>
                <input type="text" name="code" id="verification_code" class="form-control" required>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" id="modal-btn-si">Verify</button>
            </div>
        </div>
    </div>

</form>
</div>

<?php
Session::forget('non')
?>

<div class="mb15">
    @if (Auth::user()->type !== 'user')
    @if(in_array(Auth::user()->admin_type, ['admin', 'manager']))
    <a href="#stock-import" data-toggle="collapse">Import stock</a> |
    @endif
    <a data-toggle="collapse" data-target="#add-stock-form">Add Stock</a> |
        <a data-toggle="collapse" data-target="#add-non-serialised-stock-form">Add Non Serialised Stock</a> |
    @if(in_array(Auth::user()->admin_type, ['admin', 'manager']))
    <a href="{{ route('stock.overview') }}">Stock overview</a> |
    <a href="{{ route('stock.delete-form') }}">Delete Item</a> |
    <a href="{{ route('stock.purchase-order-stats') }}">PO stats</a> |
    <a href="{{ route('stock.purchase-orders-all') }}">PO overview</a> |
    <a href="{{ route('stock.purchase-overview') }}">Purchase Overview</a> |
    <a href="{{ route('stock.ready-for-sale') }}">Ready for Sale</a> |
    <a href="{{ route('stock.retail-stock') }}">Retail Stock</a>
    @endif
    @endif
    {{--<a href="{{route('stock.check-icloud')}}">iCloud check</a>--}}
</div>
