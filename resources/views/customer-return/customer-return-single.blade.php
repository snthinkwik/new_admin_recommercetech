<?php
$sold = \App\Models\Stock::where('status', \App\Models\Stock::STATUS_SOLD)->get();

?>
@extends('app')

@section('title', 'Update Customer Return')

@section('content')

    <div class="container">
        @include('messages')
        <div class="p5"><a href="{{route('customer.return.index')}}" class="btn btn-default">Back</a></div>
        <h2>Update Customer Return</h2>

        <div class="row">
            <div class="col-md-6">

                {!! BsForm::open(['method' => 'post', 'route' => 'customer.return.update']) !!}
                <div class="form-group">
                    <label for="platform_fees">Customer Name</label>

                    <input type="text" class="form-control" name="customer-name"
                           value="@if(!is_null($customerReturn)) {{$customerReturn->customer_name}}  @else -  @endif"
                           readonly>

                </div>
                <input type="hidden" value="{{$customerReturn->id}}" name="id">

                <div class="form-group">
                                       <label for="">Date of Issue</label>
                    <input type="date" name="date_of_issue"
                           @if($customerReturn->date_of_issue!=="-")value="{{\Carbon\Carbon::parse($customerReturn->date_of_issue)->format('Y-m-d')}}"
                           @endif class="form-control">
                </div>
                <div class="form-group">
                    <label>Return Status</label>
                    <select name="return_status" class="form-control">
                        <option value="RMA Issued" @if($customerReturn->return_status=="RMA Issued") selected @endif>RMA
                            Issued
                        </option>
                        <option value="Received" @if($customerReturn->return_status=="Received") selected @endif>
                            Received
                        </option>
                        <option value="In Repair" @if($customerReturn->return_status=="In Repair") selected @endif> In
                            Repair
                        </option>
                        <option value="Approved for Credit"
                                @if($customerReturn->return_status=="Approved for Credit") selected @endif> Approved for
                            Credit
                        </option>
                        <option value="Credited" @if($customerReturn->return_status=="Credited") selected @endif>
                            Credited
                        </option>
                        <option value="Completed" @if($customerReturn->return_status=="Completed") selected @endif>
                            Completed
                        </option>

                        <option value="Returned to Customer" @if($customerReturn->return_status=="Returned to Customer") selected @endif>
                            Returned to Customer
                        </option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Reason For The Return</label>
                    <select name="reason_for_the_return" class="form-control">
                        <option name="">Select Reason</option>
                        <option name="Changed Mind"
                                @if($customerReturn->reason_for_the_return==="Changed Mind") selected @endif>Changed
                            Mind
                        </option>
                        <option name="Wrong Product"
                                @if($customerReturn->reason_for_the_return==="Wrong Product") selected @endif>Wrong
                            Product
                        </option>
                        <option name="Grading Issue"
                                @if($customerReturn->reason_for_the_return==="Grading Issue") selected @endif>Grading
                            Issue
                        </option>
                        <option name="Faulty" @if($customerReturn->reason_for_the_return==="Faulty") selected @endif>
                            Faulty
                        </option>
                        <option name="Warranty Claim"
                                @if($customerReturn->reason_for_the_return==="Warranty Claim") selected @endif>Warranty
                            Claim
                        </option>

                        <option name="Lost in Transit"
                                @if($customerReturn->reason_for_the_return==="Lost in Transit") selected @endif>
                            Lost in Transit

                        </option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="">Date of Return Received</label>

                    <input type="date" name="date_return_received"
                           @if($customerReturn->date_return_received !=="-") value="{{\Carbon\Carbon::parse($customerReturn->date_return_received)->format('Y-m-d')}}"
                           @endif class="form-control">
                </div>

                <div class="form-group">
                    <label for="">Date of Credited</label>
                    <input type="date" name="date_credited"
                           @if($customerReturn->date_credited !=="-") value="{{ \Carbon\Carbon::parse($customerReturn->date_credited)->format('Y-m-d') }}"
                           @endif class="form-control">
                </div>

                <div class="form-group">
                    <label for="">QB Credit Note Ref</label>
                    <input type="text" class="form-control" value="{{$customerReturn->qb_credit_note_ref}}"
                           name="qb_credit_note_ref">
                </div>
                <div class="form-group">
                    <label for="">Return Tracking Ref</label>
                    <input type="text" class="form-control" name="tracking_ref"
                           value="{{$customerReturn->tracking_ref}}">
                </div>

                <div class="form-group">
                    <label for="platform_fees">Note</label>
                    <textarea name="note" class="form-control">@if($customerReturn->notes){{$customerReturn->notes}}@endif</textarea>

                </div>

                <br>

                {!! BsForm::submit('Update Customer Return', ['class' => 'btn btn-info btn-sm btn-block']) !!}

                {!! BsForm::close() !!}

            </div>

        </div>
    </div>
@endsection


@section('scripts')
    <script>
        $(document).ready(function () {
            $('.js-example-basic-multiple').select2({
                width: '520px',
                placeholder: 'Search By RCT12345',
            });
        });

    </script>
@endsection
