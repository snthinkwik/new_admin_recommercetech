<?php
use Carbon\Carbon;
use App\Models\EbayOrders;
$statusList = EbayOrders::getAvailableStatusWithKeys();
?>
<div class="table-responsive">
    <table class="table table-bordered" border="1">
        <thead>
        <tr id="ebay-order-sort">
            <th style="text-align: center">ID</th>
            <th name="date_of_issue" style="text-align: center">Date of Issue</th>
            <th name="sales_id" style="text-align: center">Recomm Order Id</th>
            <th name="sales_record_number" style="text-align: center">Customer Name</th>
            <th name="Buyers Ref" style="text-align: center"> Buyers Ref</th>

            <th name="sold_on_platform" style="text-align: center">Sold on Platform</th>
            <th name="product_name" style="text-align: center">Product</th>
            <th>Supplier</th>
            <th name="reason_for_the_return" style="text-align: center">Reason For The Return</th>
            <th>Date of Sale</th>
            <th name="total_sales_value_ex_vat" style="text-align: center">Total Sales Value</th>
            <th name="total_purchase_cost_of_return_ex_vat" style="text-align: center">Total Purchase Cost of Return ExVat</th>
            <th name="returns_tracking_ref" style="text-align: center">Returns Tracking Ref</th>

            <th name="date_return_received" style="text-align: center">Date Return Received</th>
            {{--<th name="sale_date">Sale date</th>--}}
            <th name="date_credited" style="text-align: center">Date Credited</th>
            <th name="qb_credit_note_ref" style="text-align: center">QB Credit Note Ref</th>
            <th name="return_status" style="text-align: center">Return Status</th>
            <th name="note" style="text-align: center" >Note</th>
            <th style="text-align: center">Action</th>
            <th></th>


        </tr>
        </thead>
        <tbody>

        @foreach($customerReturn as $return)

           <tr>

               <td style="text-align: center;" ><a href="{{route('customer.return.single',['id'=>$return->sales_id])}}"> {{$return->id}}</a></td>
               <td style="text-align: center">
                   {{date('d/m/y', strtotime($return->date_of_issue))}}
               </td>
               <td style="text-align: center;">{{ $return->sales_id}}</td>
               <td style="text-align: center">{{$return->customer_name}}</td>
               <td style="text-align: center">
                   {{$return->buyers_ref}}
{{--                   @if($return->sales)--}}
{{--                       {{$return->sales->buyers_ref}}--}}
{{--                   @endif--}}
               </td>
               <td style="text-align: center">{{$return->sold_on_platform}}</td>
               <td style="text-align: center">

                   	<?php
			$nameList=[];


            if(count($return->customerReturnsItems)){
                foreach ($return->customerReturnsItems as $returnItems){
                    array_push($nameList,str_replace( array('@rt'), 'GB', $returnItems->name));
                }
            }

			?>

                   @if(count($nameList))

                        @if(strlen(implode(', ', $nameList)) > 60)
                            {{substr(implode(', ', $nameList),0,60)}}
                            <span class="read-more-show hide_content">More<i class="fa fa-angle-down"></i></span>

                            <span class="read-more-content"> {{substr(implode(', ', $nameList),60,strlen(implode(', ', $nameList)))}}
            <span class="read-more-hide hide_content">Less <i class="fa fa-angle-up"></i></span> </span>
                        @else
                            {{implode(', ', $nameList)}}
                        @endif

                        @else
                        -
                        @endif


{{--                    @if(isset($return->product_name))  @foreach ($return->product_name as $product) {{$product}}<br> @endforeach @endif--}}
               </td>
               <td>
                   @if(!is_null($return->customerReturnsItems[0]['stock']))
                       {{$return->customerReturnsItems[0]->stock->supplier_name}}
                   @else
                       -
                   @endif

               </td>

               <td style="text-align: center">{{$return->reason_for_the_return}}</td>
               <td>@if(!is_null($return->sales)){{  $return->sales->created_at->format('d/m/y')}} @else - @endif</td>
               <td style="text-align: center">{{ money_format($return->total_sales) }}</td>
               <td style="text-align: center">{{  money_format($return->total_purchase)}}</td>
               <td style="text-align: center">{{$return->tracking_ref}}</td>
               <td style="text-align: center">
                   @if($return->date_return_received !=='-') {{date('d/m/y', strtotime($return->date_return_received))}}@else - @endif
               </td>
               <td style="text-align: center">

                   @if($return->date_credited !=='-')
                       {{date('d/m/y', strtotime($return->date_credited))}}
                   @else
                       -
                   @endif
               </td>
               <td style="text-align: center">{{$return->qb_credit_note_ref}}</td>
               <td style="text-align: center">{{$return->return_status}}</td>
               <td style="text-align: center">

                   @if(strlen($return->notes) > 60)
                       {{substr($return->notes,0,60)}}
                       <span class="read-more-show hide_content">More<i class="fa fa-angle-down"></i></span>

                       <span class="read-more-content"> {{substr($return->notes,60,strlen($return->notes))}}
                        <span class="read-more-hide hide_content">Less <i class="fa fa-angle-up"></i></span> </span>
                   @else
                      {{$return->notes}}
                   @endif


               </td>
               <td style="text-align: center"><a href="{{route('customer.return.change-status',['id'=>$return->id])}}" class="btn btn-success" @if($return->return_status==="Completed")  disabled @endif>Return To In Stock</a></td>
               <td><a href="{{route('customer.return.view',['id'=>$return->id])}}"> <i class="fa fa-pencil-square text-secondary fa-2x" aria-hidden="true"></i></a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div id="ebay-order-pagination-wrapper">{!! $customerReturn->appends(Request::all())->render() !!}</div>
</div>
