<?php
$ownerList = \App\EbayOrderItems::getAvailableOwnerWithKeys();
?>

@extends('app')

@section('title', 'DPD Invoice Data')

@section('content')

<div class="container">
    <div class="flexbox-md">
        <div class="mb-4">
            <a class="btn btn-default" href="{{ session('admin.ebay.delivery-settings') ?: route('admin.ebay.delivery-settings') }}">Back
                to list</a>

        </div>
        <div class="text-right d-inline-block text-bold mb-4">
            Total no. Records: <span class="text-success mr-2">{{$totalRecords}}</span>
            Matched: <span class="text-success mr-2">{{$totalMatched}}</span>
            Unmatched: <span class="text-success mr-2">{{$totalUnmatched}}</span>
            </span>
        </div>
    </div>


    <div class="alert alert-success" role="alert" id="message" style="display: none">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button> Owners assigned to Dpd Invoice successfully
    </div>
    @include('messages')

    @include('delivery-settings.search-dpd')
    <div class="col-md-12">
        <div id="dpd-list-table-wrapper">
            @include('delivery-settings.dpd_list')
        </div>
        <div id="dpd-pagination-wrapper">{!! $dpdList->appends(Request::all())->render() !!}</div>
    </div>
</div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        $("#dpd-list-table-wrapper").on("click", "#checkAll", function (e) {
            e.stopPropagation();
            $('input:checkbox').not(this).prop('checked', this.checked);
        });

        $("#manually-assign-owner-button").on('click', function () {
            var owner = $("#owner").val();
            var dpdInvoiceIds = [];
            $.each($("input[name='owner']:checked"), function () {
                dpdInvoiceIds.push($(this).val());
            });

            const $selected = $("input[name='owner']:checked");
            if (!$selected.length) {
                return alert("You didn't select anything.");
            }
            else if (!confirm("Are you sure you want to update Owner?")) {
                return;
            }

            this.loadXhr = $.ajax({
                url: "{{route('delivery-settings.bulk-update-owner')}}",
                type: 'post',
                data: {ids: dpdInvoiceIds, owner: owner},
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
