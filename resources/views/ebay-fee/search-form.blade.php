<?php
$fee_type = \App\EbayFees::select('fee_type')->groupBy('fee_type')->get();
$matchedList = \App\EbayFees::getAvailableMatched();
$ownerList = \App\EbayOrderItems::getAvailableOwnerWithKeys();
?>
<div class="row">
    <div class="alert alert-success" role="alert" id="message" style="display: none">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                aria-hidden="true">&times;</span></button>
        Data Successfully Added
    </div>
    {!! BsForm::open(['id' => 'ebay-fee-search-form', 'class' => 'spinner mb15', 'method' => 'get']) !!}
    <div class='universal-loader' style="display: none" ><div class='universal-spinner'></div></div>
    <div class="col-sm-3">
        <div class="form-group">
            <select name="field" id="ebayFilter" class="form-control">
                <option value="">Select Filter</option>
                <option value="ebay_username" @if(Request::input('field')=="ebay_username") selected @endif>eBay Username</option>
                <option value="item_number" @if(Request::input('field')=="item_number") selected @endif >Item Number</option>
                <option value="title" @if(Request::input('field')=="title") selected @endif >Title</option>
            </select>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            {!! BsForm::text('filter_value', Request::input('filter_value'), ['id' => 'ebay-sales-record-search-term', 'placeholder' => 'Select Filter', 'size' => 20]) !!}
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            <div class="input-group">
                <span class="input-group-addon">Date</span>
                <?php
                $date = null;
                if (Request::input('date')) {
                    $date = date_format(date_create(Request::input('date')), "Y-m-d");
                }
                ?>
                <input type="date" value="{{$date}}" name="date" class="form-control">
            </div>
        </div>
    </div>

    <div class="col-sm-3">
        <div class="form-group">
            <div class="input-group">
                <span class="input-group-addon">Fee Type</span>
                <select name="fee_type" class="form-control">
                    <option value="">Please Select Fee Type</option>
                    @if(count($fee_type)>0)
                    @foreach($fee_type as $type)
                    <option value="{{$type->fee_type}}" @if(Request::input('fee_type')==$type->fee_type) selected @endif>{{$type->fee_type}}</option>
                    @endforeach
                    @endif

                </select>
            </div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            <div class="input-group">
                <span class="input-group-addon">Matched</span>
                <select class="form-control" name="matched">
                    <option value="">Please Select</option>
                    @if(count($matchedList))
                    @foreach($matchedList as $key=>$value)
                    <option value="{{$value}}" @if(Request::input('matched')==$value) selected @endif>{{$value}}</option>
                    @endforeach
                    @endif
                </select>
            </div>
        </div>
    </div>


    {!! BsForm::hidden('sort', '',['id'=>'sort']) !!}
    {!! BsForm::hidden('sortO', '',['id'=>'sortO']) !!}
    {!! BsForm::close() !!}

    <div class="col-lg-4">
        <div class="input-group">
            <span class="input-group-addon">Company</span>
            <select class="form-control" id="owner">
                <option  value="">Select Owner</option>
                @foreach($ownerList as $owner)
                <option  value="{{$owner}}" >{{$owner}}</option>
                @endforeach

            </select>
            <span class="input-group-btn">
                <input id="manually-assign-button" class="btn btn-primary" type="submit" value="Manually Assign">
            </span>
        </div>
    </div>
</div>
