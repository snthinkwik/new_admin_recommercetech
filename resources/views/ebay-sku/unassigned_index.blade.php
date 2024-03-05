<?php
$ownerList = \App\EbayOrderItems::getAvailableOwnerWithKeys();
?>

@extends('app')
@section('title', 'Unassigned Sku')

@section('nav-right')
@if (Auth::user()->type !== 'user' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
<div class="navbar-form navbar-right pr0">
    <div class="btn-group">
        <a href="{{ route('ebay.export.unassigned') }}" class="btn btn-default">
            Export Unassigned
        </a>
    </div>
</div>
@endif
@endsection
@section('content')

<div class="container">
    <div class="row">
        <div class="col-sm-12 col-lg-5">
            <div class="mb-4">
                <a class="btn btn-default" href="{{ session('admin.ebay-orders') ?: route('admin.ebay-orders') }}">Back
                    to list</a>
            </div>
        </div>
    </div>
    <div class="row">
        {!! BsForm::open(['id' => 'unassigned-search-form', 'class' => 'spinner mb15', 'method' => 'get']) !!}
        <div class='universal-loader' style="display: none" ><div class='universal-spinner'></div></div>
        <div class="col-sm-3">
            <div class="form-group">
                <select name="field" id="ebayFilter" class="form-control">
                    <option value="">Select Filter</option>
                    <option value="order_id" @if(Request::input('field')=="order_id") selected @endif>Order Number</option>
                    <option value="sales_record_number" @if(Request::input('field')=="sales_record_number") selected @endif>Sales Record No.</option>
                    <option value="item_name" @if(Request::input('field')=="item_name") selected @endif>Item Name</option>
                    <option value="item_sku" @if(Request::input('field')=="item_sku") selected @endif>Custom Label</option>
                </select>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                {!! BsForm::text('filter_value', Request::input('filter_value'), ['id' => 'unassigned-search-term', 'placeholder' => 'Search text', 'size' => 20]) !!}
            </div>
        </div>


        {!! BsForm::hidden('sort', '',['id'=>'sort']) !!}
        {!! BsForm::hidden('sortO', '',['id'=>'sortO']) !!}
        {!! BsForm::close() !!}
    </div>
    <div class="row">
        <div class="col-lg-4">
            <div class="input-group">
                <span class="input-group-addon">Owner</span>
                <select class="form-control" id="owner">
                    <option  value="">Select Owner</option>
                    @foreach($ownerList as $owner)
                    <option  value="{{$owner}}" >{{$owner}}</option>
                    @endforeach

                </select>
                <span class="input-group-btn">
                    <input id="manually-assign-owner-button" class="btn btn-primary" type="submit" value="Bulk assign">
                </span>
            </div>
        </div>
    </div><br>

    <div class="alert alert-success" role="alert" id="message" style="display: none">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                aria-hidden="true">&times;</span></button>
        Owners  assigned to Order item successfully
    </div>
    @include('messages')
    <div class="row">
        <div class="col-md-12">
            <div id="unassigned-invoice-wrapper">

                @include('ebay-sku.unassigned_list')
            </div>
            <div id="unassigned-pagination-wrapper">{!! $ebayOrderItems->appends(Request::all())->render() !!}</div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        $("#unassigned-invoice-wrapper").on("click", "#checkAll", function (e) {
            e.stopPropagation();
            $('input:checkbox').not(this).prop('checked', this.checked);
        });

        $("#manually-assign-owner-button").on('click', function () {
            var owner = $("#owner").val();
            var ebayOrderItemsIds = [];
            $.each($("input[name='owner']:checked"), function () {
                ebayOrderItemsIds.push($(this).val());
            });

            const $selected = $("input[name='owner']:checked");
            if (!$selected.length) {
                return alert("You didn't select anything.");
            }
            else if (!confirm("Are you sure you want to update Owner?")) {
                return;
            }

            this.loadXhr = $.ajax({
                url: "{{ route('ebay.update-owner') }}",
                type: 'post',
                data: {ids: ebayOrderItemsIds, owner: owner},
                success: function (data) {
                    $("#message").show();
                    setTimeout(function () {
                        window.location.reload(1);
                    }, 150);
                }
            });

        })
    });
</script>
@endsection