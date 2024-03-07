<?php
use App\Models\Stock;
?>
<div class="row">
    <div class="alert alert-success" role="alert" id="message" style="display: none">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
        Status Successfully Update
    </div>
</div><br>
<div class="row">
    {!! BsForm::open(['id' => 'ebay-order-search-form', 'class' => 'mb15', 'method' => 'get','class' => 'spinner']) !!}
    <div class='universal-loader' style="display: none" ><div class='universal-spinner'></div></div>
    <div class="col-sm-3">
        <div class="form-group">
            <select name="platform" class="form-control" id="status">
                <option value=""> Select Sold Platform</option>
                <option value="{{Stock::PLATFROM_EBAY}}" @if(Request::input('platform')==Stock::PLATFROM_EBAY) selected @endif>{{Stock::PLATFROM_EBAY}}</option>
                <option value="{{Stock::PLATFROM_MOBILE_ADVANTAGE}}" @if(Request::input('platform')==Stock::PLATFROM_MOBILE_ADVANTAGE) selected @endif>{{Stock::PLATFROM_MOBILE_ADVANTAGE}}</option>
                <option value="{{Stock::PLATFROM_BACKMARCKET}}" @if(Request::input('platform')==Stock::PLATFROM_BACKMARCKET) selected @endif>{{Stock::PLATFROM_BACKMARCKET}}</option>
                <option value="{{Stock::PLATFROM_RECOMM}}" @if(Request::input('platform')==Stock::PLATFROM_RECOMM) selected @endif>{{Stock::PLATFROM_RECOMM}}</option>
            </select>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            <select name="status" class="form-control" id="status">
                <option value="All" @if(Request::input('status')=="All") selected @endif>All Open</option>
                <option value="RMA Issued" @if(Request::input('status')=="RMA Issued") selected @endif>RMA Issued</option>
                <option value="Received" @if(Request::input('status')=="Received") selected @endif>Received</option>
                <option value="In Repair" @if(Request::input('status')=="In Repair") selected @endif> In Repair</option>
                <option value="Approved for Credit" @if(Request::input('status')=="Approved for Credit") selected @endif> Approved for Credit</option>
                <option value="Credited" @if(Request::input('status')=="Credited") selected @endif>Credited</option>
                <option value="Completed" @if(Request::input('status')=="Completed") selected @endif>Completed</option>
                <option value="Returned to Customer" @if(Request::input('status')=="Returned to Customer") selected @endif>Returned to Customer</option>
            </select>
        </div>
    </div>

    <div class="col-sm-3">
{{--        <div class="form-group">--}}
{{--            <div class="input-group">--}}
{{--                <span class="input-group-addon">Allocated?</span>--}}
{{--                {!! BsForm::select('allocated',['' => 'All','Yes' => 'Yes', 'No' => 'No'],Request::input('allocated') ,['id' => 'allocated']) !!}--}}
{{--            </div>--}}
{{--        </div>--}}
    </div>

    <div class="col-sm-3">
{{--        <div class="form-group">--}}
{{--            <div class="input-group">--}}
{{--                <span class="input-group-addon">Category</span>--}}
{{--                {!! BsForm::select('category',[''=>'All']+ $categoryList ,Request::input('category') ,['id' => 'category']) !!}--}}
{{--            </div>--}}
{{--        </div>--}}
    </div>

    <div class="col-sm-3">

    </div>


    {!! BsForm::hidden('sort', '',['id'=>'sort']) !!}
    {!! BsForm::hidden('sortO', '',['id'=>'sortO']) !!}
    {!! BsForm::close() !!}
</div>
