<?php
use Carbon\Carbon;
use App\Models\EbayOrders;
use App\Models\Stock;
use App\Models\Invoice;
$statusList = EbayOrders::getAvailableStatusWithKeys();
?>
<div class="table-responsive">
    <table class="table small table-text-break">
        <thead>
        <tr id="ebay-order-sort">
            <th></th>
            <th name="sales_record_number">Sales Record No.</th>
            <th name="sale_date">Sale date</th>
            <th>Buyers ref</th>
            <th>Buyers Name</th>
            <th name="platform">Platform</th>
            <th name="status">Status</th>
            <th name="sku">SKU</th>
            <th>Qty</th>
            <th name="total_price">Total price</th>
            <th name="vat_type">Vat Type</th>
            {{--<th name="sale_date">Sale date</th>--}}
            <th name="paid_on_date">Paid on date</th>
            <th name="post_by_date">Dispatched on date</th>
            <th name="tracking_number">Tracking number</th>
            <th>Allocated?</th>
            <th>Sale Id</th>
            <th>Transaction ID</th>

        </tr>
        </thead>
        <tbody>
        @foreach($ebayOrders as $ebay)
            <?php
            $vatType = '';
            $finalVatType = '';
            $vatType = '';
            $finalVatType = '';
            foreach ($ebay->EbayOrderItems as $items) {
                if (is_array(json_decode($items->stock_id))) {
                    if (!is_null(getStockDetatils(json_decode($items->stock_id)[0]))) {
                        $vatType = getStockDetatils(json_decode($items->stock_id)[0])->vat_type;
                    }
                } else {
                    if (!is_null(getStockDetatils($items->stock_id))) {
                        $vatType = getStockDetatils($items->stock_id)->vat_type;
                    }

                }
                    if ($items->tax_percentage * 100 > 0 || !$ebay->tax_percentage * 100 && $vatType === "Standard") {
                        $finalVatType = "Standard";
                    } else {
                        $finalVatType = "Margin";
                    }
            }
            ?>
            <tr>
                <td>
                    <input type="checkbox" name="status" value="{{$ebay->id}}" data-id="{{$ebay->id}}">
                </td>
                <td>
                    <a href="{{route('admin.ebay-orders.view',['id' => $ebay->id])}}">
                        {{$ebay->sales_record_number}}
                    </a>
                    <input type="hidden" value="{{$ebay->id}}">
                </td>
                <td>@if(!empty($ebay->sale_date)) {{date('d-m-Y',strtotime($ebay->sale_date))}} @endif</td>
                <td>{{$ebay->order_id}}</td>
                <td>{{$ebay->buyer_name}}</td>
                <td>{{$ebay->platform}}<br>

                    @if($ebay->platform===Stock::PLATFROM_MOBILE_ADVANTAGE)
                        {{$ebay->payment_method}} <br>@if(!is_null($ebay->payment_type)) ({{$ebay->payment_type}}) @endif
                    @endif

                </td>
                <td>{{ucfirst($ebay->status)}}</td>
                <td>
                    @foreach($ebay->EbayOrderItems as $itemSku)
                        @if($itemSku->item_sku!=="")
                            <span class="small">{{$itemSku->item_sku}}</span>
                        @else
                            <span class="small text-danger">VOID</span>
                        @endif

                        <br>
                    @endforeach

                </td>
                <?php
                $totalQuantity = 0;
                foreach ($ebay->EbayOrderItems as $items) {

                    $totalQuantity += $items->quantity;
                }
                ?>
                <td>{{$totalQuantity}}</td>
                <td>

{{--                    @if($ebay->currency_code=="GBP")--}}
{{--                        <i class="fa fa-gbp"></i>--}}
{{--                    @elseif($ebay->currency_code=="EUR")--}}
{{--                        <i class="fa fa-usd" aria-hidden="true"></i>--}}
{{--                    @endif--}}
{{--                    {{money_format(config('app.money_format'),$ebay->total_price)}}--}}
                    {{money_format($ebay->total_price)}}

                </td>
                <td>{{$finalVatType}}</td>
                {{--<td>@if(!empty($ebay->sale_date)) {{date('d-m-Y',strtotime($ebay->sale_date))}} @endif</td>--}}
                <td>@if(!empty($ebay->paid_on_date)) {{date('d-m-Y',strtotime($ebay->paid_on_date))}} @endif</td>
                <td>@if(!empty($ebay->post_by_date)) {{date('d-m-Y',strtotime($ebay->post_by_date))}} @endif</td>
                <td>

                    @if($ebay->platform===Stock::PLATFROM_EBAY)
                        @if($ebay->tracking_number!=="")
                            @if(strlen($ebay->tracking_number) == 14)
                                <a href="https://www.dpd.co.uk/apps/tracking/?reference={{$ebay->tracking_number}}"
                                   target="_blank"> {{$ebay->tracking_number}}</a>
                            @elseif(strlen($ebay->tracking_number) == 16)
                                <a href="https://new.myhermes.co.uk/track.html#/parcel/{{$ebay->tracking_number}}"
                                   target="_blank"> {{$ebay->tracking_number}}</a>
                            @else
                                <a href="https://www.royalmail.com/track-your-item#/tracking-results/{{$ebay->tracking_number}}"
                                   target="_blank">    {{$ebay->tracking_number}} </a>
                            @endif
                        @endif
                      @elseif($ebay->platform===Stock::PLATFROM_BACKMARCKET)
                        <a href="https://www.backmarket.co.uk/tracking/order?courier=dpd&trackingNo={{$ebay->tracking_number}}&lang=en"
                           target="_blank"> {{$ebay->tracking_number}}</a>

                    @endif
                </td>
                <td>
                    @if(isset($ebay->EbayOrderItems[0]))
                        @if(!is_null($ebay->EbayOrderItems[0]->stock_id))
                            <i class="fa fa-check text-success" aria-hidden="true"></i>
                        @else
                            <i class="fa fa-times text-danger" aria-hidden="true"></i>
                        @endif
                    @endif
                </td>
                <td>

                    @if(isset($ebay->Newsale))

                        @if($ebay->Newsale->invoice_status!==Invoice::STATUS_VOIDED)
                            <a href="{{route('sales.single',['id'=>$ebay->new_sale_id])}}">
                                {{$ebay->new_sale_id}}
                            </a>
                        @endif
                    @endif
                </td>
                <td>{{$ebay->transaction_id}}</td>

            </tr>
        @endforeach
        </tbody>
    </table>
</div>
