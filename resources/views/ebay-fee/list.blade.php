<?php

use Carbon\Carbon;
?>
<div class="table-responsive">
    <table class="table small  table-text-break">
        <thead>
            <tr id="ebay-fee-sort">
                <th><input type="checkbox" id="checkAll"/></th>
                <th width="30%" name="title">Title</th>
                <th name="date">Date</th>
                <th name="item_number">Item Number</th>
                <th name="fee_type">Fee Type</th>
                <th name="amount">Amount</th>
                <th name="received_top_rated_discount">eBay+ Discount</th>
                <th name="matched">Matched</th>
                <th width="10%" name="ebay_username">eBay Username</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ebayFees as $fee)
            <tr>
                <td>
                    @if($fee->matched==\App\EbayFees::MATCHED_NO)
                    <input type="checkbox" name="owner" value="{{$fee->id}}" data-id="{{$fee->id}}">
                    @endif
                </td>
                <td><a href="{{route('ebay-fee.update-fees',['id'=>$fee->id])}}" tabIndex="-1">{{$fee->title}}</a></td>
                <td>{{date('d-m-Y',strtotime($fee->date))}}</td>
                <td><a href="{{'https://ebay.co.uk/itm/'.$fee['item_number']}}" target="_blank" tabIndex="-1">{{$fee->item_number}}</a></td>
                <td>{{$fee->fee_type}}</td>
                <td><span @if(strpos($fee->amount, '-') !== false) style="color:green;" @endif> {{$fee->amount}}</span></td>
                <td>{{$fee->received_top_rated_discount}}</td>
                <td>
                    @if($fee->matched=="Yes")
                    @if(!is_null($fee['EbayOrders']))
                    <a href="{{route('admin.ebay-orders.view',['id'=>$fee['EbayOrders']['id']])}}" tabIndex="-1">{{$fee->matched}}</a>
                    @endif
                    @else
                    {{$fee->matched}}
                    @endif
                </td>
                <td>
                    @if($fee->ebay_username=="" || is_null($fee->ebay_username))
                    <?php
                    $returnId = null;
                    if (strstr($fee->title, 'Return ID:')) {
                        $returnId = trim(substr($fee->title, strrpos($fee->title, ':') + 1));
                    }
                    ?>

                    @if(!is_null($returnId))
                    <a href="{{'https://www.ebay.co.uk/sh/ord/return?filter=status%3ACLOSED_NON_US&search=returnId%3A'.$returnId}}" title="This record requires you to manually get the eBay username, please login to eBay to find the username and insert it into the fee record." target="_blank" tabIndex="-1">Get username</a>
                    @endif
                    @else
                    {{$fee->ebay_username}}
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
