<?php
use App\Stock;
use App\Colour;
$statuses = Auth::user()
    ? array_combine(Auth::user()->allowed_statuses_viewing, Auth::user()->allowed_statuses_viewing)
    : Stock::getAvailableStatusesWithKeys();

$networksList=Stock::getAllAvailableNetworks();
$touchIdWorkingOptions = ['' => 'Please Select'] + Stock::getAvailableTouchIdWorkingWithKeys();
$crackedBackOptions = ['' => 'Please Select'] + Stock::getAvailableCrackedBackWithKeys();
$testStatusList=  Stock::getAvailableTestStatusWithKeys();
$categoryList=[];
foreach (Stock::getProductType() as $key=>$category){
    $categoryList[$category['product_type']]=$category['product_type'];
}


$cosmetic_type=[
    ''=>'Select Cosmetic Type',
    'Camera lense chipped or cracked'=>'Camera lense chipped or cracked',
    'Dust mark on rear camera'=>'Dust mark on rear camera',
    'Bent'=>' Minor cracked back glass',
    'Minor scuffs/dents in shell'=>'Minor scuffs/dents in shell',
    'Minor chips/scratches/blem on LCD'=>'Minor chips/scratches/blem on LCD',
    'Major scratches on LCD'=>'Major scratches on LCD',
    'Major scuffs/dents in shell'=>' Major scuffs/dents in shell',
    'Major cracked back glass'=>'Major cracked back glass',
    'Not CE Marked'=>'Not CE Marked',
    'LCD doesnt fit well'=>'LCD doesnt fit well',
    'Cracked home button'=>'Cracked home button',
    'Sim Tray Missing'=>' Sim Tray Missing'
]

?>
{{--<div class='universal-loader' style="display: none" ><div class='universal-spinner'></div></div>--}}
{!! Form::open(['id' => 'item-search-form', 'class' => 'form-inline mb15', 'method' => 'get']) !!}
<div class="form-group">
    {!! BsForm::text('term', Request::input('term'), ['id' => 'item-search', 'placeholder' => 'Search', 'size' => 30]) !!}
</div>

<div class="form-group">
    {!!
        BsForm::select(
            'grade',
            ['' => 'Any Grade'] + Stock::getAvailableGradesWithKeys('all'),
            Request::input('grade'),
            ['id' => 'item-search-grade']
        )
    !!}
</div>

{{--@if (!isset($showStatus) || $showStatus)--}}
{{--    <div class="form-group">--}}
{{--        {!!--}}
{{--            BsForm::select(--}}
{{--                'condition',--}}
{{--                ['' => 'Any Condition'] + Stock::getAvailableConditionsWithKeys(),--}}
{{--                Request::input('condition'),--}}
{{--                ['id' => 'item-search-condition']--}}
{{--            )--}}
{{--        !!}--}}
{{--    </div>--}}
{{--@endif--}}

@if (!isset($showStatus) || $showStatus)
    <div class="form-group">
        {!!
            BsForm::select(
                'status',
                ['' => 'Any Status'] + $statuses,
                Request::input('status'),
                ['id' => 'item-search-status']
            )
        !!}
    </div>
@endif



{{--<input type="checkbox" value="1" name="unsold" id="items-unsold"  @if(Request::input('unsold')) checked @endif  > Unsold--}}

{{--<select class="network-select2 form-control" id="item-search-network" name="network">--}}
{{--    <option value=""></option>--}}
{{--    @foreach($networksList as $country => $networkArr)--}}
{{--        @if(!empty($networkArr))--}}
{{--            <optgroup label="{{$country}}">--}}
{{--                @foreach($networkArr as $network)--}}
{{--                    <option value="{{$network}}">{{$network}}</option>--}}
{{--                @endforeach--}}
{{--            </optgroup>--}}
{{--        @endif--}}
{{--    @endforeach--}}
{{--</select>--}}

{{--<div class="form-group">--}}
{{--    {!!--}}
{{--        BsForm::select(--}}
{{--            'colour',--}}
{{--            ['' => 'Any Colour'] + $colours = Colour::orderBy('pr_colour')->lists('pr_colour', 'pr_colour'),--}}
{{--            Request::input('colour'),--}}
{{--            ['id' => 'item-search-colour']--}}
{{--        )--}}
{{--    !!}--}}
{{--</div>--}}
{{--<div class="form-group">--}}
{{--    {!!--}}
{{--        BsForm::select(--}}
{{--            'capacity',--}}
{{--            ['' => 'Any Capacity'] + Stock::getAvailableCapacityWithKeys(),--}}
{{--            Request::input('capacity'),--}}
{{--            ['id' => 'item-search-capacity']--}}
{{--        )--}}
{{--    !!}--}}
{{--</div>--}}

{{--@if(Auth::user()->type == 'admin' && in_array(Auth::user()->admin_type, ['admin', 'manager']))--}}
{{--    <div class="form-group">--}}
{{--        <div class="input-group">--}}
{{--            <span class="input-group-addon">Product Mapping?</span>--}}
{{--            {!! BsForm::select('product_mapping', ['' => 'All', 1 => 'Yes', 0 => 'No'], Request::input('product_mapping'),['id'=>'product_mapping']) !!}--}}
{{--        </div>--}}
{{--    </div>--}}
{{--@endif--}}

{{--<div class="form-group">--}}
{{--    {!!--}}
{{--        BsForm::select(--}}
{{--            'purchase_country',--}}
{{--            ['' => 'Any Country'] + Stock::getAvailablePurchaseCountriesWithKeys(),--}}
{{--            Request::input('purchase_country'),--}}
{{--            ['id' => 'item-search-country']--}}
{{--        )--}}
{{--    !!}--}}
{{--</div>--}}


<div class="form-group">
    {!!
        BsForm::select(
            'vat_type',
            ['' => 'Any Vat Type', 'Margin' => 'Margin', 'Standard' => 'Standard'],
            Request::input('vat_type'),
            ['id' => 'item-search-vat']
        )
    !!}
</div>

{{--<div class="form-group">--}}
{{--    <div class="input-group">--}}
{{--        <span class="input-group-addon">No Touch ID / No Face ID</span>--}}
{{--        {!! BsForm::select('touch_id_working',['' => 'All'] + $touchIdWorkingOptions, Request::input('touch_id_working'),['id' => 'item-search-touch-id-working']) !!}--}}
{{--    </div>--}}
{{--</div>--}}

{{--<div class="form-group">--}}
{{--    <div class="input-group">--}}
{{--        <span class="input-group-addon">Cracked Back</span>--}}
{{--        {!! BsForm::select('cracked_back',['' => 'All'] + $crackedBackOptions, Request::input('cracked_back'),['id' => 'item-search-cracked-back']) !!}--}}
{{--    </div>--}}
{{--</div>--}}

<div class="form-group">
    <div class="input-group">
        <span class="input-group-addon">Category</span>
        {!! BsForm::select('product_type',[''=>'All']+$categoryList, Request::input('product_type'),['id' => 'item-search-product-type']) !!}
    </div>
</div>

{{--<div class="form-group">--}}
{{--    <div class="input-group">--}}
{{--        <span class="input-group-addon">Test Status</span>--}}
{{--        {!! BsForm::select('test_status',[''=>'All']+$testStatusList, Request::input('test_status'),['id' => 'item-search-test-status']) !!}--}}
{{--    </div>--}}
{{--</div>--}}

{{--<div class="form-group">--}}
{{--    <div class="input-group">--}}
{{--        <span class="input-group-addon">Cosmetic Fault Type</span>--}}

{{--        {!! BsForm::select('cosmetic_fault_type',$cosmetic_type, Request::input('cosmetic_fault_type'),['id' => 'item-search-cosmetic_type']) !!}--}}
{{--    </div>--}}
{{--</div>--}}


{{--<div class="form-group">--}}
{{--    <div class="input-group">--}}
{{--        <span class="input-group-addon">MPN Mapping?</span>--}}

{{--        {!! BsForm::select('mpa_map',[''=>'All','1'=>'Yes','0'=>'No'], Request::input('mpa_map'),['id' => 'mpa_map']) !!}--}}
{{--    </div>--}}
{{--</div>--}}






{!! BsForm::hidden('sort', '',['id'=>'sort']) !!}
{!! BsForm::hidden('sortO', '',['id'=>'sortO']) !!}

{!! Form::close() !!}
