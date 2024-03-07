
@extends('app')

@section('title', 'Customer Return')

@section('content')

<div class="container">
    <div class="p5"><a href="{{route('customer.return.index')}}" class="btn btn-default">Back</a></div>
    @include('messages')
    <h2>Create Customer Return</h2>

    <div class="row">

        <div class="col-md-6">

            {!! BsForm::open(['method' => 'post', 'route' => 'customer.return.save']) !!}
            <div class="form-group">
                <label for="platform_fees">Select Stock</label>
                <select class="js-example-basic-multiple" name="ids[]" multiple="multiple" style="border: 1px solid #aaa;" required>
                </select>

            </div>

            <div class="form-group">

                <label for="">Date of Issue</label>
                <input type="date" name="date_of_issue" class="form-control" value="{{Carbon\Carbon::now()->format('Y-m-d')}}" required>
            </div>
            <div class="form-group">
                <label>Reason For The Return</label>
                <select name="reason_for_the_return" class="form-control" required>
                    <option name="">Select Reason</option>
                    <option name="Changed Mind">Changed Mind</option>
                    <option name="Wrong Product">Wrong Product</option>
                    <option name="Grading Issue">Grading Issue</option>
                    <option name="Faulty">Faulty</option>
                    <option name="Warranty Claim">Warranty Claim</option>
                    <option name="Lost in Transit">Lost in Transit</option>
                </select>
            </div>


            <div class="form-group">
                <label for="">Date of Return Received</label>
                <input type="date" name="date_return_received" class="form-control" >
            </div>
            <div class="form-group">
                <label for="">Date of Credited</label>
                <input type="date" name="date_credited" class="form-control">
            </div>

            <div class="form-group">
                <label for="">QB Credit Note Ref</label>
                <input type="text" class="form-control" name="qb_credit_note_ref">
            </div>

            <div class="form-group">
                <label for="">Return Tracking Ref</label>
                <input type="text" class="form-control" name="tracking_ref">
            </div>
            <div class="form-group">
                <label for="platform_fees">Note</label>
                <textarea name="note"  class="form-control"></textarea>

            </div>

            <br>

            {!! BsForm::submit('Create Customer Return', ['class' => 'btn btn-info btn-sm btn-block']) !!}

            {!! BsForm::close() !!}

        </div>

    </div>
</div>
@endsection


@section('scripts')
<script>
    $(document).ready(function () {
        $('.js-example-basic-multiple').select2({
            width:'520px',
            placeholder: 'Search By RCT12345',
            minimumInputLength:1,
            ajax: {
                url: '{{ route("customer.return.data") }}',
                dataType: 'json',
            },

        });

        {{--$(document).ready(function() {--}}
        {{--    $('#city').select2({--}}
        {{--        minimumInputLength: 3,--}}
        {{--        ajax: {--}}
        {{--            url: '{{ route("api.cities.search") }}',--}}
        {{--            dataType: 'json',--}}
        {{--        },--}}
        {{--    });--}}
        {{--});--}}
    });

</script>
@endsection