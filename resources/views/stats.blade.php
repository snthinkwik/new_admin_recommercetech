<?php
use App\Models\Stock;
use App\Models\ImeiReport;
use App\Models\Sale;
use App\Models\Invoice;
use App\Models\SupplierReturn;


$otherRecyclers = Sale::whereNotNull('other_recycler')->groupBy('other_recycler')->pluck('other_recycler')->toArray();
$otherRecyclersStats = [];
foreach($otherRecyclers as $otherRecycler) {
    $otherRecyclersStats[$otherRecycler]['recycler'] = $otherRecycler;
    $otherRecyclersStats[$otherRecycler]['amount_due'] = Sale::where('other_recycler', $otherRecycler)->where('invoice_status', Invoice::STATUS_OPEN)->sum('invoice_total_amount');
    $otherRecyclerSales = Sale::where('other_recycler', $otherRecycler)->where('invoice_status', Invoice::STATUS_OPEN)->get();
    $noItems = 0;
    $salesIds = [];
    foreach($otherRecyclerSales as $oSale) {
        $noItems += count($oSale->stock);
        $salesIds[] = $oSale->id;
    }
    $otherRecyclersStats[$otherRecycler]['no_items'] = $noItems;
    $otherRecyclersStats[$otherRecycler]['sales'] = $salesIds;
}



$inStock = DB::select('select count(*) cnt from new_stock where status in ("in stock", "batch", "ready for sale", "retail stock", "listed on auction", "in repair", "re-test","reserved for order","allocated")')[0]->cnt;
$inbound = DB::select('select count(*) cnt from new_stock where status = "inbound"')[0]->cnt;
$inboundPurchaseValue = DB::select('select sum(purchase_price+unlock_cost+part_cost) cnt from new_stock where status = "inbound"')[0]->cnt;
$purchaseStockValue = DB::select('select sum(purchase_price+unlock_cost+part_cost) cnt from new_stock where status = "in stock"')[0]->cnt;
$salesStockValue = DB::select('select sum(sale_price) cnt from new_stock where status = "in stock"')[0]->cnt;
$purchaseValue = DB::select('select sum(purchase_price+unlock_cost+part_cost) cnt from new_stock where status in ("in stock", "batch", "ready for sale", "retail stock", "listed on auction", "in repair", "re-test","reserved for order","allocated")')[0]->cnt;

$salesValueMargin = DB::select('select sum(sale_price) cnt from new_stock where vat_type="Margin" AND status in ("in stock", "batch", "ready for sale", "retail stock", "listed on auction", "in repair", "re-test","reserved for order","allocated")')[0]->cnt;
$salesValueStandard = DB::select('select sum(total_price_ex_vat) cnt from new_stock where vat_type="Standard" AND  status in ("in stock", "batch", "ready for sale", "retail stock", "listed on auction", "in repair", "re-test","reserved for order","allocated")')[0]->cnt;

$salesValue=$salesValueMargin+$salesValueStandard;
$total_shop_stock_value = DB::select("SELECT SUM(purchase_price+unlock_cost+part_cost) as sum FROM new_stock WHERE status = 'in stock' AND grade REGEXP 'Shop Grade';");
$missingSalesValueCount = DB::select('select count(*) cnt from new_stock where status in ("in stock", "batch", "ready for sale", "retail stock", "listed on auction", "in repair", "re-test","reserved for order","allocated") AND sale_price = 0')[0]->cnt;
$userCount = DB::select('select count(*) cnt from users where type = "user" and registered = true')[0]->cnt;
$userUnregisteredCount = DB::select('select count(*) cnt from users where type = "user" and registered = false')[0]->cnt;


$partCostValue = DB::select('select sum(part_cost) cnt from new_stock where status in ("in stock", "batch", "ready for sale", "retail stock", "listed on auction", "in repair", "re-test","reserved for order","allocated")')[0]->cnt;;
$unlockCostValue = DB::select('select sum(unlock_cost) cnt from new_stock where status in ("in stock", "batch", "ready for sale", "retail stock", "listed on auction", "in repair", "re-test","reserved for order","allocated")')[0]->cnt;;

$inStockStock = DB::select('select count(*) cnt from new_stock where status = "In Stock"')[0]->cnt;
//$inStockStockPurchase = DB::select('select sum(purchase_price+unlock_cost+part_cost) cnt from new_stock where status = "In Stock"')[0]->cnt;
$inStockStockSales = DB::select('select sum(sale_price) cnt from new_stock where status = "In Stock"')[0]->cnt;

$retailStock = DB::select('select count(*) cnt from new_stock where status = "Retail Stock"')[0]->cnt;
//$retailStockPurchase = DB::select('select sum(purchase_price+unlock_cost+part_cost) cnt from new_stock where status = "Retail Stock"')[0]->cnt;
$retailStockSales = DB::select('select sum(sale_price) cnt from new_stock where status = "Retail Stock"')[0]->cnt;

$listedOnAuction = DB::select('select count(*) cnt from new_stock where status = "Listed on Auction"')[0]->cnt;
$listedOnAuctionPurchase = DB::select('select sum(purchase_price+unlock_cost+part_cost) cnt from new_stock where status = "Listed on Auction"')[0]->cnt;
$listedOnAuctionSales = DB::select('select sum(sale_price) cnt from new_stock where status = "Listed on Auction"')[0]->cnt;

$inRepair = DB::select('select count(*) cnt from new_stock where status = "In Repair"')[0]->cnt;
//$inRepairPurchase = DB::select('select sum(purchase_price+unlock_cost+part_cost) cnt from new_stock where status = "In Repair"')[0]->cnt;
$inRepairSales = DB::select('select sum(sale_price) cnt from new_stock where status = "In Repair"')[0]->cnt;
$retest = DB::select('select count(*) cnt from new_stock where status = "Re-test"')[0]->cnt;
//$retestPurchase = DB::select('select sum(purchase_price+unlock_cost+part_cost) cnt from new_stock where status = "Re-test"')[0]->cnt;
$retestSales = DB::select('select sum(sale_price) cnt from new_stock where status = "Re-test"')[0]->cnt;

$stockBatch = DB::select('select count(*) cnt from new_stock where status = "batch"')[0]->cnt;
//$stockBatchPurchase = DB::select('select sum(purchase_price+unlock_cost+part_cost) cnt from new_stock where status = "batch"')[0]->cnt;
$stockBatchSales = DB::select('select sum(sale_price) cnt from new_stock where status = "batch"')[0]->cnt;

$totalQtyOfMargin= DB::select('select count(*) cnt from new_stock where status in ("in stock", "batch", "ready for sale", "retail stock", "listed on auction", "in repair", "re-test","reserved for order","allocated") AND  vat_type = "Margin"')[0]->cnt;
$totalQtyOfStandard=DB::select('select count(*) cnt from new_stock where status in ("in stock", "batch", "ready for sale", "retail stock", "listed on auction", "in repair", "re-test","reserved for order","allocated") AND  vat_type = "Standard"')[0]->cnt;

$totalMarginalVatItemsInstock=($totalQtyOfMargin+$totalQtyOfStandard)-($totalQtyOfMargin);
$totalVATableItemsInStock=($totalQtyOfMargin+$totalQtyOfStandard)-($totalQtyOfStandard);
$totalSalesValueOfMargin= DB::select('select sum(sale_price) cnt from new_stock where status in ("in stock", "batch", "ready for sale", "retail stock", "listed on auction", "in repair", "re-test","reserved for order","allocated") AND  vat_type = "Margin"')[0]->cnt;
$totalSalesValueOfStandard= DB::select('select sum(sale_price) cnt from new_stock where status in ("in stock", "batch", "ready for sale", "retail stock", "listed on auction", "in repair", "re-test","reserved for order","allocated") AND  vat_type = "Standard"')[0]->cnt;

$unmpping= Stock::whereNull('product_id')
    // ->join('products','products.id','=','new_stock.product_id')
    ->whereIn('status', ['In Stock',
        // 'Inbound',
        'Re-test',
        'Batch',
        'In Repair',
        //   'Returned to Supplier',
        '3rd Party',
        'Ready for Sale',
        'Retail Stock',
        'Listed on Auction',
        'Reserved for Order',
        //  'Listed on eBay',
        'Allocated'])
    ->count();
$unmappingWithZero= Stock::where('product_id','=',0)
    ->whereIn('status', ['In Stock',
        // 'Inbound',
        'Re-test',
        'Batch',
        'In Repair',
        //   'Returned to Supplier',
        '3rd Party',
        'Ready for Sale',
        'Retail Stock',
        'Listed on Auction',
        'Reserved for Order',
        //  'Listed on eBay',
        'Allocated'])->count();

$itemsNoPurchasePrice = Stock::where('purchase_price', 0)->whereIn('status', [Stock::STATUS_IN_STOCK, Stock::STATUS_BATCH])->get();
$icloudLockedDevices = Stock::whereIn('status', [Stock::STATUS_IN_STOCK])->where('grade', Stock::GRADE_LOCKED)->count();
$icloudLockedCleanDevices = Stock::whereIn('status', [Stock::STATUS_IN_STOCK])->where('grade', Stock::GRADE_LOCKED_LOST)->count();
$icloudLockedLostDevices = Stock::whereIn('status', [Stock::STATUS_IN_STOCK])->where('grade', Stock::GRADE_LOCKED_CLEAN)->count();

$opened_or_returned_money = money_format(SupplierReturn::whereIn('status', ["Open", "Returned"])->get()->sum('total_purchase_value'));
$opened_or_returned_amount = SupplierReturn::whereIn('status', ["Open", "Returned"])->count();
$opened_or_returned_res=$opened_or_returned_amount." (".$opened_or_returned_money.")";

$stockList=Stock::whereIn('status',["in stock", "batch", "ready for sale", "retail stock", "listed on auction", "in repair", "re-test","reserved for order","allocated"])->get();
$totalPurchase=0;
$inStockStockPurchase=0;
$retailStockPurchase=0;
$inRepairPurchase=0;
$retestPurchase=0;
$stockBatchPurchase=0;
$TotalMarginalVatPurchaseValue=0;
$TotalVATblePurchaseValue=0;
$date = \Carbon\Carbon::today()->subDays(30);

$lastThirteenDyasQty=Stock::whereIn('status',["in stock", "batch", "ready for sale", "retail stock", "listed on auction", "in repair", "re-test","reserved for order","allocated"])->where('purchase_date','<=',$date)->count();
$lastThirteenDyasQtyPurchaseSum=Stock::whereIn('status',["in stock", "batch", "ready for sale", "retail stock", "listed on auction", "in repair", "re-test","reserved for order","allocated"])->where('purchase_date','<=',$date)->sum('purchase_price');


foreach($stockList as $stock){


    if($stock->vat_type==="Standard"){
        $TotalVATblePurchaseValue+=$stock->total_cost_with_repair;
    }
    if($stock->vat_type==="Margin"){
        $TotalMarginalVatPurchaseValue+=$stock->total_cost_with_repair;
    }
    if($stock->status===Stock::STATUS_IN_STOCK){
        $inStockStockPurchase+=$stock->total_cost_with_repair;
    }
    if($stock->status===Stock::STATUS_RETAIL_STOCK){
        $retailStockPurchase+=$stock->total_cost_with_repair;
    }
    if($stock->status===Stock::STATUS_REPAIR){
        $inRepairPurchase+=$stock->total_cost_with_repair;
    }
    if($stock->status===Stock::STATUS_RE_TEST){
        $retestPurchase+=$stock->total_cost_with_repair;
    }
    if($stock->status===Stock::STATUS_BATCH){
        $stockBatchPurchase+=$stock->total_cost_with_repair;
    }
    $totalPurchase+=$stock->total_cost_with_repair;
}





?>
@extends('app')

@section('title', 'Stats')

@section('content')

    <div class="container">
        <table class="table table-striped">
            <tbody>
            <tr>
                <th>Total number of items in stock:</th>
                <th><a href="{{ route('stock') }}">{{ number_format($inStock) }}</a></th>
            </tr>
            <tr>
                <th>Qty Items > 30 Days Old:</th>
                <th><a href="{{ route('stock') }}">{{ $lastThirteenDyasQty  }} ({{ money_format($lastThirteenDyasQtyPurchaseSum) }})</a></th>
            </tr>
            <tr>
                <td>Total purchase value:</td>
                <td>{{ money_format($totalPurchase) }}</td>
            </tr>

            <tr>
                <td>Sales Value (ex Vat for VATable):</td>
                <td>{{ money_format($salesValue) }}</td>
            </tr>
            <tr>
                <th>Total number of items Inbound:</th>
                <th><a href="{{ route('stock', ['status' => 'inbound']) }}">{{ number_format($inbound) }} ({{ money_format($inboundPurchaseValue) }})</a> <a class="btn btn-xs btn-info" href="{{ route('stock.export', ['option' => 'inbound']) }}"><i class="fa fa-download"></i> Export</a></th>
            </tr>



            <tr>
                <th>Total Marginal Vat Items In Stock:</th>
                <th>{{ $totalQtyOfMargin }}</th>
            </tr>

            <tr>
                <td>Total marginal Vat Purchase value</td>
                <td>{{money_format($TotalMarginalVatPurchaseValue) }}</td>
            </tr>

            <tr>
                <td>Total marginal VAT Sales Value:</td>
                <td>{{ money_format($totalSalesValueOfMargin) }}</td>
            </tr>

            <tr>
                <th>Total VATable Items In Stock:</th>
                <th>{{ $totalQtyOfStandard }}</th>
            </tr>
            <tr>
                <td>Total VATable Purchase value:</td>
                <td>{{ money_format($TotalVATblePurchaseValue) }}</td>
            </tr>
            <tr>
                <td>Total VATable Sales Value</td>
                <td>{{ money_format($totalSalesValueOfStandard) }}</td>
            </tr>

            <tr>
                <th>No. In Stock items:</th>
                <th>{{ $inStockStock }}</th>
            </tr>
            <tr>
                <td>Total In Stock items purchase price:</td>
                <td>{{ money_format($inStockStockPurchase) }}</td>
            </tr>
            <tr>
                <td>Total In Stock items sales price:</td>
                <td>{{ money_format($inStockStockSales) }}</td>
            </tr>



            <tr>
                <th>No. Retail Stock items:</th>
                <th>{{ $retailStock }}</th>
            </tr>
            <tr>
                <td>Total Retail Stock items purchase price:</td>
                <td>{{ money_format($retailStockPurchase) }}</td>
            </tr>
            <tr>
                <td>Total Retail Stock items sales price:</td>
                <td>{{ money_format($retailStockSales) }}</td>
            </tr>

            <tr>
                <th>No. In Repair items:</th>
                <th>{{ $inRepair }}</th>
            </tr>
            <tr>
                <td>Total In Repair items purchase price:</td>
                <td>{{ money_format($inRepairPurchase) }}</td>
            </tr>
            <tr>
                <td>Total In Repair items sales price:</td>
                <td>{{ money_format($inRepairSales) }}</td>
            </tr>
            <tr>
                <th>No. Re-test items:</th>
                <th>{{ $retest }}</th>
            </tr>
            <tr>
                <td>Total Re-test items purchase price:</td>
                <td>{{ money_format($retestPurchase) }}</td>
            </tr>
            <tr>
                <td>Total Re-test items sales price:</td>
                <td>{{ money_format($retestSales) }}</td>
            </tr>
            <tr>
                <th>No. Batch items:</th>
                <th>{{ $stockBatch }}</th>
            </tr>
            <tr>
                <td>Total Batch items purchase price:</td>
                <td>{{ money_format($stockBatchPurchase) }}</td>
            </tr>
            <tr>
                <td>Total Batch items sales price:</td>
                <td>{{ money_format($stockBatchSales) }}</td>
            </tr>

            <tr>
                <th>No. Devices missing sales value:</th>
                <th>{{ number_format($missingSalesValueCount) }}</th>
            </tr>

            <tr>
                <th>No. Devices missing purchase value:</th>
                <th>{{ $itemsNoPurchasePrice->count() }} @if(count($itemsNoPurchasePrice)) <a data-toggle="collapse" data-target="#items-no-purchase-price" class="btn btn-xs btn-default">Details</a> @endif </th>
            </tr>
            <tr>
                <th>Quantity Of Items Without  Mapped Product</th>
                <th>{{number_format($unmpping+$unmappingWithZero)}}</th>

            </tr>

            <tr>
                <th>No. registered users:</th>
                <th>{{ $userCount }}</th>
            </tr>

            <tr>
                <td>Supplier Returns</td>
                <td>{{ $opened_or_returned_res }}</td>
            </tr>
            </tbody>
        </table>

        <div class="panel panel-default collapse" id="items-no-purchase-price">
            <div class="panel-heading">Stock Items with no purchase price</div>
            <div class="panel-body">
                @if(!count($itemsNoPurchasePrice))
                    <div class="panel panel-info">Nothing to Display</div>
                @else
                    <table class="table table-hover table-striped">
                        <tr>
                            <th>RCT Ref</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Purchase Price</th>
                            <th>Sale Price</th>
                        </tr>
                        @foreach($itemsNoPurchasePrice as $item)
                            <tr>
                                <td><a href="{{ route('stock.single', ['id' => $item->id]) }}">{{ $item->our_ref }}</a></td>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->status }}</td>
                                <td>{{ $item->purchase_price_formatted }}</td>
                                <td>{{ $item->sale_price_formatted }}</td>
                            </tr>
                        @endforeach
                    </table>
                    @foreach($itemsNoPurchasePrice as $item)

                    @endforeach
                @endif
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">Other Recycler, Awaiting Payment</div>
            <div class="panel-body">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>Recycler</th>
                        <th>No. Items</th>
                        <th>Amount Due</th>
                        <th>Details</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($otherRecyclersStats as $otherRecycler)
                        <tr>
                            <td>{{ $otherRecycler['recycler'] }}</td>
                            <td>{{ $otherRecycler['no_items'] }}</td>
                            <td>{{ money_format($otherRecycler['amount_due']) }}</td>
                            <td>
                                @foreach($otherRecycler['sales'] as $sale)
                                    <a href="{{ route('sales.single', ['id' => $sale]) }}">#{{ $sale }}</a>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

            </div>
        </div>
    </div>

@endsection
