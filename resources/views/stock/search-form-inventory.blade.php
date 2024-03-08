<?php
use App\Models\Stock;
use App\Models\Colour;
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
<div class="form-group">
    <div class="input-group">
        <span class="input-group-addon">Category</span>
        {!! BsForm::select('product_type',[''=>'All']+$categoryList, Request::input('product_type'),['id' => 'item-search-product-type']) !!}
    </div>
</div>





{!! BsForm::hidden('sort', '',['id'=>'sort']) !!}
{!! BsForm::hidden('sortO', '',['id'=>'sortO']) !!}

{!! Form::close() !!}
