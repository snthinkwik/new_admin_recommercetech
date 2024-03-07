
@extends('app')

@section('title', 'Seller Fees')

@section('content')


    <div class="container">
<a class="btn btn-success" href="{{route('seller_fees.create')}}">Create Seller Fees</a>
        @include('messages')
    <table class="table table-bordered table-hover">
        <tr>

            <th>Platform</th>
            <th>Estimate Platform Fee ex VAT (% of total order value)</th>
            <th>Av. GB Shipping Cost ex VAT = if item < £20</th>
            <th>Av. GB Shipping Cost ex VAT= if item > £20</th>
            <th>Av. EU & NI Delivery  Shipping Cost ex VAT = if item < £20</th>
            <th>Av. EU & NI Delivery Shipping Cost ex VAT= if item > £20</th>
            <th>Default Box/Add-on's/Accessories cost ex VAT</th>
            <th>Warranty Accrual</th>
            <th class="text-center"><i class="fa fa-pencil"></i></th>
        </tr>
        @foreach($sellerFees as $fees)
            <tr>
                <td>{{ $fees->platform }}</td>
                <td>{{ $fees->platform_fees."%" }}</td>
                <td>{{ money_format($fees->uk_shipping_cost_under_20)  }}</td>
                <td>{{money_format($fees->uk_shipping_cost_above_20)   }}</td>
                <td>{{ money_format($fees->uk_non_shipping_cost_under_20)  }}</td>
                <td>{{ money_format($fees->uk_non_shipping_above_under_20)   }}</td>
                <td>
                    {{ money_format($fees->accessories_cost_ex_vat)  }}
                </td>
                <td>
                    @if(!is_null($fees->warranty_accrual))
                    {{ $fees->warranty_accrual."%" }}
                        @endif
                </td>
                <td>
                    <a class="btn btn-sm btn-default" href="{{route("seller_fees.single",['id'=>$fees->id])}}"><i class="fa fa-pencil"></i></a>
                </td>
            </tr>
        @endforeach
    </table>
    </div>
{{--@endif--}}

 @endsection
