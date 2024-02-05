<?php

use App\Models\Colour;
use App\Models\Grade;
use App\Models\Network;
use App\Models\Stock;
use Carbon\Carbon;
use App\Models\Supplier;

$suppliers = ['' => 'None'] + Supplier::get()->pluck('name', 'id')->toArray();

//$networks = $item->purchase_country == Stock::PURCHASE_COUNTRY_US ? Stock::getAvailableNetworksUs(): Network::customOrder()->lists('pr_network');
$grades = Stock::getAvailableGradesWithKeys();
if ($item->make == 'Samsung') $grades = Stock::getAvailableGradesWithKeys('samsung');
$conditions = Stock::getAvailableConditionsWithKeys();
$colours = ['' => 'Please Select'] + $item->getAvailableColoursWithKeys();
$lcdStatuses = Stock::getAvailableLcdStatusesWithKeys();
$shownTo = Stock::getAvailableShownToWithKeys();
$touchIdWorkingOptions = ['' => 'Please Select'] + Stock::getAvailableTouchIdWorkingWithKeys();
$crackedBackOptions = ['' => 'Please Select'] + Stock::getAvailableCrackedBackWithKeys();
$availableFaults = Stock::getAvailableFaults();
$skuDisabled = in_array($item->shown_to, [Stock::SHOWN_TO_EBAY, Stock::SHOWN_TO_EBAY_AND_SHOP]) && $item->status == Stock::STATUS_IN_STOCK ? [] : ['disabled' => 'disabled'];
$purchaseCountries = Stock::getAvailablePurchaseCountriesWithKeys();
//if(!in_array($item->network, $networks)) {
//	$networks[] = $item->network;
//}
$vat_types = ['Margin' => 'Margin', 'Standard' => 'Standard'];
if (!in_array($item->grade, array_values($grades))) {
    $grades[$item->grade] = $item->grade;
}

$networksList = Stock::getAllAvailableNetworks();

$categoryList = [];
foreach (Stock::getProductType() as $key => $category) {
    $categoryList[$category['product_type']] = $category['product_type'];
}

$cosmeticsList = [];
$removeSpaceList = [];
if (!is_null($item->cosmetic_type)) {

    $cosmeticsList = explode(',', $item->cosmetic_type);

    foreach ($cosmeticsList as $ty) {
        array_push($removeSpaceList, trim($ty));
    }


}


$phoneCheck = \App\Models\PhoneCheck::where('stock_id', $item->id)->first();
if (!is_null($phoneCheck)) {

    $report = json_decode($phoneCheck->response);
}
?>
<div id="manual-sku-wrapper" class="collapse">
    <hr/>
    Manual SKU: {{ $item->manual_sku ? "Enabled" : "Disabled" }}
    {!! Form::model($item, ['method' => 'post', 'route' => 'stock.change-manual-sku']) !!}
    {!! BsForm::hidden('id', $item->id) !!}
    {!! BsForm::hidden('manual_sku', 0) !!}
    {!! BsForm::checkbox('manual_sku', 1, $item->manual_sku, ['data-toggle' => 'toggle']) !!}
    {!! BsForm::submit('Update', ['class' => 'confirmed', 'data-confirm' => 'Are you sure you want to Enable/Disable manual SKU?']) !!}
    {!! Form::close() !!}
    <hr/>
</div>

{!! Form::model($item, ['route' => 'stock.save', 'id' => 'stock-form']) !!}
<fieldset {{ (Auth::user()->type === 'user' || $item->failed_mdm) ? 'disabled' : '' }}>
    @if ($item->exists)
        {!! BsForm::hidden('id', $item->id) !!}
    @endif

    @if($item->phone_check_passed)
        <div class="form-group">
            <label>Device</label>


            @if($item->name_compare)

                <?php

                $network = '';
                if (isset($item->network)) {
                    if ($item->network !== '-') {
                        if ($item->network !== '') {
                            $network = "-" . $item->network;
                        }

                    }
                }
                $name = $report->Make . ' ' . $report->Model . ' ' . $report->Memory . '-' . $report->Color . $network;
                ?>
                {!! BsForm::text('phone_check_passed', $name, ['disabled' => 'disabled']) !!}
            @else
                {!! BsForm::text('phone_check_passed', null, ['disabled' => 'disabled']) !!}
            @endif
        </div>
    @else
        <div class="form-group">
            <label for="item-name">Make</label>
            {!! BsForm::text('make') !!}
        </div>
        @if($item->non_serialised)

            <div class="form-group">
                <label>Stock SKU</label>
                {!! BsForm::text('sku',$item->sku, ['disabled' => 'disabled']) !!}
            </div>

        @endif
        <div class="form-group">
            <label for="item-name">Stock Name</label>

            {!! BsForm::text('name',str_replace( array('@rt'), 'GB', $item->name)) !!}
        </div>


        <div class="form-group @hasError('capacity')">
            <label for="item-capacity">Capacity</label>
            <div class="input-group">
                {!! BsForm::text('capacity') !!}
                <div class="input-group-addon">GB</div>
            </div>
            @error('capacity') @enderror
        </div>
    @endif

    @if(!isset($item->product->non_serialised) || !$item->product->non_serialised )
        <div class="form-group @hasError('imei')">
            <label for="item-imei">IMEI</label>
            {!! BsForm::text('imei') !!}
            @error('imei') @enderror
        </div>
    @endif
    @if($item->status == Stock::STATUS_RETAIL_STOCK)
        <div class="form-group @hasError('new_sku')">
            <label for="item-new-sku">SKU</label>
            {!! BsForm::text('new_sku') !!}
            @error('new_sku') @enderror
        </div>
    @endif
    @if(!$item->phone_check_passed || $item->phone_check_colour_edit)
        <div class="form-group">
            <label for="item-colour">Colour</label>
            {!! BsForm::select('colour', $colours, null) !!}
        </div>
    @endif




    <div class="form-group">
        <label for="item-condition">Condition</label>
        {!! BsForm::select('condition', ['' => 'Please select'] + $conditions, null, ['required' => 'required']) !!}
    </div>
    <div class="form-group @hasError('grade')">
        <label for="item-grade">Grade</label>
        @if(Auth::user()->type !== 'admin' && !in_array($item->shown_to,[Stock::SHOWN_TO_ALL, Stock::SHOWN_TO_EBAY_AND_SHOP, Stock::SHOWN_TO_EBAY]))
            <p>Unknown</p>
        @else
            {!! BsForm::select('grade', $grades) !!}
        @endif
        @error('grade') @enderror
    </div>

    @if(!isset($item->product->non_serialised) || !$item->product->non_serialised )
        <div class="form-group @hasError('lcd_status')">
            <label for="item-lcd-status">LCD status</label>
            {!! BsForm::select('lcd_status', $lcdStatuses) !!}
            @error('lcd_status') @enderror
        </div>
    @endif


    @if(!isset($item->product->non_serialised) || !$item->product->non_serialised )
        @if(Auth::user()->type == 'admin')
            <div class="form-group @hasError('touch_id_working')">
                <label for="item-touch-id-working"> Touch/Face ID Working?</label>
                {!! BsForm::select('touch_id_working', $touchIdWorkingOptions, null) !!}
                @error('lcd_status') @enderror
            </div>
        @endif
    @endif

    @if(!isset($item->product->non_serialised) || !$item->product->non_serialised )

        <div class="form-group @hasError('touch_id_working')">
            <label for="item-touch-id-working">Cosmetic</label>
            <table class="table table-striped" style="font-size: 12px;font-weight:500">
                <tr>
                    <td><input type="checkbox" value="Camera lense chipped or cracked"
                               @if(in_array('Camera lense chipped or cracked',$removeSpaceList)) checked @endif> Camera
                        lense chipped or cracked
                    </td>
                    <td><input type="checkbox" value="Dust mark on rear camera"
                               @if(in_array('Dust mark on rear camera',$removeSpaceList)) checked @endif> Dust mark on
                        rear camera
                    </td>
                </tr>
                <tr>
                    <td><input type="checkbox" value="Bent" @if(in_array('Bent',$removeSpaceList)) checked @endif> Bent
                    </td>
                    <td><input type="checkbox" value="Minor cracked back glass"
                               @if(in_array('Minor cracked back glass',$removeSpaceList)) checked @endif> Minor cracked
                        back glass
                    </td>
                </tr>
                <tr>
                    <td><input type="checkbox" value="Minor scuffs/dents in shell"
                               @if(in_array('Minor scuffs/dents in shell',$removeSpaceList)) checked @endif > Minor
                        scuffs/dents in shell
                    </td>
                    <td><input type="checkbox" value="Minor chips/scratches/blemishes on LCD"
                               @if(in_array('Minor chips/scratches/blemishes on LCD',$removeSpaceList)) checked @endif>
                        Minor chips/scratches/blem on LCD
                    </td>
                </tr>
                <tr>
                    <td><input type="checkbox" value="Major scratches on LCD"
                               @if(in_array('Major scratches on LCD',$removeSpaceList)) checked @endif> Major scratches
                        on LCD
                    </td>
                    <td><input type="checkbox" value="Major scuffs/dents in shell"
                               @if(in_array('Major scuffs/dents in shell',$removeSpaceList)) checked @endif> Major
                        scuffs/dents in shell
                    </td>
                </tr>
                <tr>
                    <td><input type="checkbox" value="Major cracked back glass"
                               @if(in_array('Major cracked back glass',$removeSpaceList)) checked @endif> Major cracked
                        back glass
                    </td>
                    <td><input type="checkbox" value="Not CE Marked"
                               @if(in_array('Not CE Marked',$removeSpaceList)) checked @endif> Not CE Marked
                    </td>
                </tr>
                <tr>
                    <td><input type="checkbox" value="LCD doesnt fit well"
                               @if(in_array('LCD doesnt fit well',$removeSpaceList)) checked @endif> LCD doesnt fit well
                    </td>
                    <td><input type="checkbox" value="Cracked home button"
                               @if(in_array('Cracked home button',$removeSpaceList)) checked @endif> Cracked home button
                    </td>
                </tr>
                <tr>
                    <td><input type="checkbox" value="Sim Tray Missing"
                               @if(in_array('Sim Tray Missing',$removeSpaceList)) checked @endif> Sim Tray Missing
                    </td>

                    <td><input type="checkbox" value="Laser Engraved"
                               @if(in_array('Laser Engraved',$removeSpaceList)) checked @endif> Laser Engraved
                    </td>

                </tr>


                <tr>
                    <td><input type="checkbox" value="Scorched Home Button"
                               @if(in_array('Scorched Home Button',$removeSpaceList)) checked @endif> Scorched Home
                        Button
                    </td>

                    <td><input type="checkbox" value="Scorched Home Button"
                               @if(in_array('Dust under screen surface',$removeSpaceList)) checked @endif> Dust under
                        screen surface
                    </td>


                </tr>
            </table>
        </div>
    @endif
    @if( isset($item->product->non_serialised))
        <div class="form-group">
            <label>Product SKU</label>
            {!! BsForm::text('product_sku',$item->product->slug, ['disabled' => 'disabled']) !!}
        </div>
    @endif



    <div class="form-group">
        <label for="item-network">Network</label>
        <select class="network-select2 form-control" name="network">
            <option value=""></option>

            @foreach($networksList as $country => $networkArr)
                @if(!empty($networkArr))
                    <optgroup label="{{$country}}">
                        @foreach($networkArr as $network)
                            <option value="{{$network}}"
                                    @if($network===$item->network) SELECTED @endif>{{$network}}</option>
                        @endforeach
                    </optgroup>
                @endif
            @endforeach
        </select>

    </div>

    @if(!isset($item->product->non_serialised) || !$item->product->non_serialised )
        @if (Auth::user()->canRead('stock.serial'))
            <div class="form-group @hasError('serial')">
                <label for="item-serial">Serial number</label>
                {!! BsForm::text('serial') !!}
                @error('serial') @enderror
            </div>
        @endif
    @endif

    <div class="form-group">
        <label>Status</label>
        <div>
            @if(Auth::user()->type !== 'admin' && !in_array($item->shown_to,[Stock::SHOWN_TO_ALL, Stock::SHOWN_TO_EBAY]))
                In Testing
            @else
                {{ $item->status }}
                @if ($item->sold && Auth::user()->type === 'admin')
                    @if(!is_null($item->sale))
                        - <a href="{{ route('sales.single', $item->sale->id) }}">go to sale</a>
                    @endif
                @endif
                @if(!$item->sold && !in_array($item->status, [Stock::STATUS_REPAIR, Stock::STATUS_SOLD]) )
                    {!! BsForm::select('status', array_combine([Stock::STATUS_IN_STOCK,Stock::STATUS_READY_FOR_SALE,Stock::STATUS_RETAIL_STOCK,Stock::STATUS_LISTED_ON_AUCTION,Stock::STATUS_3RD_PARTY, Stock::STATUS_RESERVED_FOR_ORDER,Stock::STATUS_BATCH,Stock::STATUS_LOST,Stock::STATUS_ALLOCATED], [Stock::STATUS_IN_STOCK,Stock::STATUS_READY_FOR_SALE,Stock::STATUS_RETAIL_STOCK,Stock::STATUS_LISTED_ON_AUCTION,Stock::STATUS_3RD_PARTY, Stock::STATUS_RESERVED_FOR_ORDER,Stock::STATUS_BATCH,Stock::STATUS_LOST,Stock::STATUS_ALLOCATED])) !!}
                @else
                    {!! BsForm::hidden('status') !!}
                @endif
            @endif
        </div>
        @if ($item->status === Stock::STATUS_REPAIR && Auth::user()->canWrite('stock.in_repair'))
            <div>
                <a href="javascript:" class="in-repair-change-back">Change back</a>
                to {{ $item->in_repair_previous_status }}
            </div>
        @endif
    </div>
    @if(Auth::user()->type == 'admin' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
        <div class="form-group">
            <label for="item-sale-price">Sales price</label>
            <div class="input-group">
                <div class="input-group-addon">£</div>
                @if(in_array(Auth::user()->email, ['victoria@recomm.co.uk']))
                    {!! BsForm::text('sale_price', null, []) !!}

                @else
                    {!! BsForm::text('sale_price', null, $item->sold ? ['readonly'] : []) !!}

                @endif
            </div>
        </div>

        @if($item->vat_type === "Standard")
            <?php
            $total_price_ex_value = ($item->sale_price / 1.2);
            $vat = ($item->sale_price - $total_price_ex_value)
            ?>
            <div class="form-group">
                <label for="item-sale-price">Vat</label>
                <div class="input-group">
                    <div class="input-group-addon">£</div>
                    {!! BsForm::text('sale_vat', number_format($vat,2), ['readonly']) !!}
                </div>
            </div>
            <div class="form-group">
                <label for="item-sale-price">Total Price ex Vat</label>
                <div class="input-group">
                    <div class="input-group-addon">£</div>
                    {!! BsForm::text('total_price_ex_value', number_format($total_price_ex_value,2), ['readonly']) !!}
                </div>
            </div>
        @endif

        <div class="form-group">
            <label for="item-sale-price">Purchase price</label>
            <div class="input-group">
                <div class="input-group-addon">£</div>
                @if(in_array(Auth::user()->email, ['victoria@recomm.co.uk']))
                    {!! BsForm::text('purchase_price', null, []) !!}

                @else
                    {!! BsForm::text('purchase_price', null,[]) !!}

                @endif
            </div>
        </div>


        @if(!isset($item->product->non_serialised) || !$item->product->non_serialised )
            <div class="form-group">
                <label for="item-notes">Non OEM Parts</label>


                <table class="table table-bordered">
                    <th>Part Name</th>
                    <th>Notice</th>

                    @if(!is_null($item->oem_parts))
                        @foreach(json_decode($item->oem_parts) as  $oem_part)

                            <tr>
                                <td>{{$oem_part->name}}</td>
                                <td>{{$oem_part->notice}}</td>

                            </tr>

                        @endforeach
                    @else
                        <tr>
                            <td colspan="2" align="center">No Data Found</td>
                        </tr>
                    @endif

                </table>
            </div>
        @endif

    @endif
    <div class="form-group">
        <label for="item-notes">Notes</label>
        @if (Auth::user()->type === 'user')
            <p class="small">Please note that there may be other faults than what our engineers have listed, we do not
                open the phones.</p>
        @endif
        <small data-toggle="tooltip" title="If enabled, PhoneCheck won't overwrite notes.">Manual
            Notes: {{ $item->manual_notes ? 'Yes' : 'No' }}</small>
        {!! BsForm::textarea('notes', null, ['class' => 'form-control', 'rows' => 3]) !!}
    </div>
    <div class="form-group @hasError('third_party_ref')">
        <label for="item-third-party-ref">3rd-party ref</label>
        {!! BsForm::text('third_party_ref') !!}
        @error('third_party_ref') @enderror
    </div>
    @if(!$item->non_serialised)
        @if(Auth::user()->type == 'admin' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
            <div class="form-group">
                <a class="btn btn-default btn-block btn-sm" data-toggle="collapse" data-target="#more-info">More
                    Info</a>
                <div class="panel panel-default collapse" id="more-info">
                    <div class="panel-body">
                        {!! BsForm::groupText('purchase_order_number') !!}
                        {!! BsForm::groupSelect('purchase_country', $purchaseCountries, null) !!}
                        <div class="form-group @hasError('supplier_id')">
                            <label for="supplier_id">Supplier</label>
                            {!! BsForm::select('supplier_id', $suppliers, $item->supplier_id) !!}
                        </div>
                        <div class="form-group @hasError('purchase_date')">
                            <label for="item-purchase-date">Purchase date</label>
                            {!! BsForm::text('purchase_date', null, ['class' => 'has-datetimepicker']) !!}
                            @error('purchase_date') @enderror
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
    <div class="form-group">
        <label for="vat_type">Vat Type</label>
        {!! BsForm::select('vat_type', $vat_types, null, ['required' => 'required']) !!}
    </div>

    <div class="form-group">
        <label for="vat_type">P/S Model</label>
        {!! BsForm::select('ps_model',[''=>'Select','',0=>'No',1=>'Yes'],$item->ps_model, ['required' => 'required']) !!}
    </div>
    @if(in_array($item->status,[Stock::STATUS_SOLD,Stock::STATUS_PAID]))

        {!! BsForm::submit('Save', ['class' => 'confirmed mb10',
        'data-confirm' => " Please note you have updated the purchase price for an item that has already sold. You must have had prior management approval for this change."]) !!}

    @else
        <div class="form-group">
            {!! BsForm::submit('Save') !!}
        </div>
    @endif


</fieldset>

{!! Form::close() !!}


