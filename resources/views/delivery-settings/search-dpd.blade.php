<div class="row">
    <div class="col-lg-4">
        <div class="input-group">
            <span class="input-group-addon">Owner</span>
            <select class="form-control" id="owner">
                <option  value="">Select Owner</option>
                @foreach($ownerList as $owner)
                <option  value="{{$owner}}" >{{$owner}}</option>
                @endforeach

            </select>
            <span class="input-group-btn">
                <input id="manually-assign-owner-button" class="btn btn-primary" type="submit" value="Bulk assign">
            </span>
        </div>
    </div>
</div><br>
<div class="row">
    {!! BsForm::open(['id' => 'dpd-search-form', 'class' => 'spinner mb15', 'method' => 'get']) !!}
    <div class='universal-loader' style="display: none" ><div class='universal-spinner'></div></div>
    <div class="col-sm-3">
        <div class="form-group">
            <select name="field" id="ebayFilter" class="form-control">
                <option value="">Select Filter</option>
                <option value="parcel_number" @if(Request::input('field')=="parcel_number") selected @endif>Parcel Number</option>
                <option value="consignment_number" @if(Request::input('field')=="consignment_number") selected @endif>Consignment Number</option>
                <option value="product_description" @if(Request::input('field')=="product_description") selected @endif>Product Description</option>
                <option value="service_description" @if(Request::input('field')=="service_description") selected @endif>Service Description</option>
                <option value="delivery_post_code" @if(Request::input('field')=="delivery_post_code") selected @endif>Delivery Post Code</option>
            </select>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            {!! BsForm::text('filter_value', Request::input('filter_value'), ['id' => 'dpd-search-term', 'size' => 20]) !!}
        </div>
    </div>
    <div class="col-sm-3">
        <div class="input-group">
            <span class="input-group-addon">Owner</span>

            <select name="owner" class="form-group form-control" id="owner">
                <option value="">Select Owner</option>
                @foreach($ownerList as $owner)
                <option value="{{$owner}}">{{$owner}}</option>
                @endforeach

            </select>
        </div>

    </div>
    <div class="col-sm-3">
        <div class="form-group">
            <div class="input-group">
                <span class="input-group-addon">Matched?</span>
                {!! BsForm::select('matched', ['' => 'All','Yes'=>'Yes','No'=>'No','N/A'=>'N/A'], Request::input('matched')?Request::input('matched'):"No"?Request::input('matched'):"N/A") !!}
            </div>
        </div>
    </div>

    {!! BsForm::hidden('sort', '',['id'=>'sort']) !!}
    {!! BsForm::hidden('sortO', '',['id'=>'sortO']) !!}
    {!! BsForm::close() !!}
</div>
