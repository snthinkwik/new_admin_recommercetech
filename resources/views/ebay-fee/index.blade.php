@extends('app')

@section('title', 'eBay Fees')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-sm-12 col-lg-8">
            <div class="mb-4">
                <a class="btn btn-default mb-2" href="{{ session('admin.ebay-orders') ?: route('admin.ebay-orders') }}">Back
                    to list</a>
                <a href="#ebay-fees" data-toggle="collapse" class="btn btn-default mb-2">Import eBay Fees</a>
                <a href="{{ route('ebay-fee.update-username') }}" class="btn btn-default mb-2">Match eBay Fees</a>
                <a href="{{route('ebay-fee.history')}}" class="btn btn-default mb-2"> eBay Fee History</a>
                <a class="btn btn-default mb-2" href="{{route('ebay-fee.export-unmatched')}}"><i class="fa fa-download mr-2"></i>Unmatched Fees Export</a>
            </div>
        </div>
        <div class="col-sm-12 col-lg-4">
            <div class="mb-4 text-bold text-right">
                <div class="row">
                    <div class="col-sm-12 col-lg-12">
                        Matched Records: <span class="text-success mr-2">{{number_format($count[0]->total_matched)}}</span>
                    </div>
                    <div class="col-sm-12 col-lg-12">
                        Unmatched Records: <span class="text-danger mr-2">{{number_format($count[0]->total_unmatched)}}</span>
                    </div>
                    <div class="col-sm-12 col-lg-12">
                        Latest Fee:
                        @if(!is_null($GetLastRecordDate))
                        {{date('d/m/Y H:i ',strtotime($GetLastRecordDate->formatted_fee_date))}}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="ebay-fees" class="collapse {{ session('ebay.csv_errors') ? 'in' : '' }} show-if-has-error mb15">
        <p><a href="#ebay-fees" data-toggle="collapse"><i class="fa fa-close"></i></a></p>
        @include('ebay-fee.import-ebay-fees')
    </div>
    @include('messages')
    @if(in_array(Auth::user()->admin_type, ['admin', 'manager']))
    <div id="ebay-fees" class="collapse  show-if-has-error mb15">
        <p class="mv20"><a href="{{ route('ebay-fee.template') }}">Click here to download a eBay Fees CSV
                template.</a></p>
        @include('ebay-fee.import-ebay-fees')
    </div>
    @endif
    @include('ebay-fee.search-form')
    <div id="ebay-fee-items-wrapper">
        @include('ebay-fee.list')
    </div>
    <div id="ebay-fee-pagination-wrapper">{!! $ebayFees->appends(Request::all())->render() !!}</div>
</div>
@endsection

@section('nav-right')
<div id="basket-wrapper" class="navbar-right pr0">
    @include('basket.navbar')
</div>

@section('scripts')
<script>
    $(document).ready(function () {
        $("#ebay-fee-items-wrapper").on("click","#checkAll", function (e) {
            e.stopPropagation();
            $('input:checkbox').not(this).prop('checked', this.checked);
        });
    });
</script>
@endsection