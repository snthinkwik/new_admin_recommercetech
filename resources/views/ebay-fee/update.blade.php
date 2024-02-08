<?php
$ownerList = \App\EbayOrderItems::getAvailableOwnerWithKeys();
?>
@extends('app')

@section('title', 'eBay Edit Fees')

@section('content')

<div class="container">

    @include('messages')
    <p><a class="btn btn-default" href="{{ route('ebay-fee.index') }}">Back to eBay Fee</a></p>

    <h2>Edit eBay Fees</h2>

    <div class="row">
        <div class="col-md-6 col-lg-5">
            {!! BsForm::open(['route' =>['ebay-fee.update-fees', $eBayFee->id], 'method' => 'put']) !!}
            <div class="form-group">
                {!! Form::label('name', 'Title') !!}
                {!! BsForm::textarea('title', (isset($eBayFee->title) ? $eBayFee->title : null), ['placeholder' => 'Enter Title', 'style'=>'height:60px']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('name', 'Date') !!}
                <input type="date" value="{{ date('Y-m-d',strtotime($eBayFee->date))}}" name="date" disabled class="form-control">
            </div>
            <div class="form-group">
                {!! Form::label('name', 'eBay Username') !!}
                {!! BsForm::text('ebay_username', (isset($eBayFee->ebay_username) ? $eBayFee->ebay_username : null), ['placeholder' => 'Enter UserName']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('item_number', 'Item Number') !!}
                {!! BsForm::text('item_number', (isset($eBayFee->item_number) ? $eBayFee->item_number : null), ['placeholder' => 'Enter Item Number']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('fee_type', 'Fee Type') !!}
                {!! BsForm::select('fee_type', $feeTypeList, $eBayFee->fee_type, ['required' => 'required']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('amount', 'Amount') !!}
                {!! BsForm::text('amount', (isset($eBayFee->amount) ? $eBayFee->amount : null), ['placeholder' => 'Enter Number']) !!}
            </div>
            <div class="form-group">
                {!! Form::label('ebay_discount', 'eBay + Discount') !!}

                <select name="received_top_rated_discount" class="form-control">
                    <option class="form-control" @if($eBayFee->received_top_rated_discount=="Yes")selected @endif value="Yes">Yes</option>
                    <option class="form-control" @if($eBayFee->received_top_rated_discount=="" ||$eBayFee->received_top_rated_discount=="No" ) selected @endif value="No">No</option>                    
                </select>
            </div>            
            <div class="form-group">
                @if($eBayFee->matched == \App\EbayFees::MATCHED_MANUALLY_ASSIGNED)

                {!! Form::label('assign_fee_to_order', 'Manually assigned to')  !!}<br>
                {!! BsForm::select('manually_assign',['' => 'Please Select'] + $ownerList, isset($eBayFee->ManualEbayFeeAssignment->owner) ? $eBayFee->ManualEbayFeeAssignment->owner:"Null", [   'class'=>'owner-select2']) !!}
                @else
                {!! Form::label('assign_fee_to_order', 'Assign Fee To Order (Sales Record No.) ') !!}<br>
                {!! BsForm::select('sales_record_number',['' => 'Please Select'] + $salesRecordNumberList, $eBayFee->sales_record_number, [   'class'=>'ebay-select2']) !!}
                @endif
            </div>
            {!! BsForm::submit('Save', ['class' => 'mt10 btn btn-primary']) !!}
            {!! BsForm::close() !!}
        </div>

        @if(count($ebayFeesLogs)>0)
        <div class="col-md-6 col-lg-6">
            <div class="panel panel-default">
                <div class="panel-heading">Log</div>
                <div class="panel-body">
                    <table class="table table-bordered">
                        <tr>
                            <th>Content</th>
                            <th class="text-center"><i class="fa fa-calendar"></i></th>
                        </tr>
                        @foreach($ebayFeesLogs as $log)
                        <tr>
                            <td>
                                {{$log->content}}
                            </td>
                            <td class="small">{{ $log->created_at->format("d/m/Y H:i:s") }}</td>
                        </tr>
                        @endforeach
                    </table>
                    {!! $ebayFeesLogs->appends(Request::all())->render() !!}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        $('.ebay-select2').select2({
            placeholder: "Select eBay Order",
        });
        $('.owner-select2').select2({
            placeholder: "Select Owner",
        });
    });
</script>
@endsection