<div class="table-responsive">
    <table class="table small table-text-break">
        <thead>
            <tr id="ebay-order-sort">
                <th name="sales_record_number">Sales Record No.</th>
                <th name="ebay_username">eBay Username</th>
                <th name="status">Status</th>
                <th name="total_price">Total price</th>
                <th name="sale_date">Sale date</th>
                <th name="paid_on_date">Paid on date</th>
                <th name="post_by_date">Dispatched on date</th>
                <th name="tracking_number">Tracking number</th>
                <th name="status">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($EbayOrders as $ebay)
            <tr>                
                <td><a href="{{route('admin.ebay-orders.view',['id' => $ebay->id])}}">{{$ebay->sales_record_number}}</a></td>
                <td>{{$ebay->ebay_username}}</td>
                <td>{{ucfirst($ebay->status)}}</td>
                <td>
                    @if($ebay->currency_code=="GBP")
                    <i class="fa fa-gbp"></i>
                    @elseif($ebay->currency_code=="EUR")
                    <i class="fa fa-usd" aria-hidden="true"></i>
                    @endif
                    {{number_format($ebay->total_price,2)}}
                </td>
                <td>@if(!empty($ebay->sale_date)) {{date('d-m-Y',strtotime($ebay->sale_date))}} @endif</td>
                <td>@if(!empty($ebay->paid_on_date)) {{date('d-m-Y',strtotime($ebay->paid_on_date))}} @endif</td>
                <td>@if(!empty($ebay->post_by_date)) {{date('d-m-Y',strtotime($ebay->post_by_date))}} @endif</td>
                <td>
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
                </td>  
                <td>{{ucfirst($ebay->status)}}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>