<?php
$currenturl = URL::current();
if (isset($_REQUEST['page'])) {
    $page = $_REQUEST['page'];
}else{
    $page=null;
}


?>
@if(!count($products))
    <div class="alert alert-info">Nothing Found</div>
@else
    <table class="table table-bordered table-hover">
        <tr>
            <th>ID</th>
            <th>Make</th>
            <th>Product Name</th>
            <th>Model</th>
            <th>MPN</th>
            <th>EAN</th>
            <th>eBay (EPID)</th>
            <th>Amazon (ASIN)</th>
            <th>BackMarket ID</th>
            <th>MA ID</th>
            <th>Category</th>
            <th>Non Serialised</th>
            <th></th>

        </tr>
        @foreach($products as $product)
            <tr>

                <td>
                    @if(is_null($page))
                        <a href="{{ route('products.single', ['id' => $product->id]) }}">{{ $product->id }}</a>
                    @else
                        <a href="{{ route('products.single', ['id' => $product->id,'page'=>$page]) }}">{{ $product->id }}</a>
                    @endif
                </td>
                <td>{{ $product->make }}</td>
                <td>{{$product->product_name}}</td>
                <td>{{ $product->model }}</td>
                <td>{{ $product->slug }}</td>
                <td>{{ $product->ean }}</td>
                <td>{{$product->epd}}</td>
                <td>{{$product->asw}}</td>
                <td>{{ $product->back_market_id }}</td>
                <td>{{$product->ma}}</td>
                <td>{{ $product->category }}</td>
                <td>{{$product->non_serialised ? 'Yes':"No"}}</td>
                <td><a href="{{route('product.delete',['id'=>$product->id])}}"
                       onclick="return confirm('Are you sure you want to delete this product?');"><i
                                class="fa fa-trash text-danger" aria-hidden="true"></i>
                    </a></td>
            </tr>
        @endforeach
    </table>
@endif	