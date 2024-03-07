
@extends('app')

@section('title', 'Customer Return')

@section('content')

    <div class="container">
        <a href="{{route('sales.customer_return')}}" class="btn btn-default">Back</a>
        @include('messages')
        <h2>Customer Return</h2>
        <div class="row">
            <div class="col-md-6">
                {!! BsForm::open(['route' => 'sales.customer_return.save']) !!}
                @if(isset($customerReturn->id))
                    {!! BsForm::hidden('id',isset($customerReturn->id)? $customerReturn->id:null) !!}
                @endif

                <div class="form-group">
                    <label for="platform">Items Credited</label>
                    <div class="input-group">
                        {{--{!! BsForm::text('items_credited',isset($sellerFees)?$sellerFees->platform:null,['placeholder' => 'Plat Form']) !!}--}}
                        {!! BsForm::text('items_credited',isset($customerReturn)?$customerReturn->items_credited:null,['placeholder' => 'Enter Credit']) !!}
                        <div class="input-group-addon"></div>
                    </div>

                </div>

                <div class="form-group">
                    <label for="platform_fees">Total Credit Note Value</label>
                    <div class="input-group">
                        {{--{!! BsForm::text('value_of_credited',isset($sellerFees) ? $sellerFees->platform_fees:null, ['placeholder' => 'PlatForm Fees']) !!}--}}
                        {!! BsForm::text('value_of_credited',isset($customerReturn) ? $customerReturn->value_of_credited:null, ['placeholder' => 'Enter Value of Credit']) !!}
                        <div class="input-group-addon"></div>
                    </div>
                </div>


                <div class="form-group">
                    <label for="platform_fees">Profit Lost</label>
                    <div class="input-group">
                        {{--{!! BsForm::text('value_of_credited',isset($sellerFees) ? $sellerFees->platform_fees:null, ['placeholder' => 'PlatForm Fees']) !!}--}}
                        {!! BsForm::text('profit_lost',isset($customerReturn) ? $customerReturn->profile_lost:null, ['placeholder' => 'Enter Profit Lost']) !!}
                        <div class="input-group-addon"></div>
                    </div>
                </div>




                <br>

                {!! BsForm::submit('Save', ['class' => 'btn btn-info btn-sm btn-block']) !!}

                {!! BsForm::close() !!}

            </div>

        </div>
    </div>
@endsection


@section('scripts')
    <script>
        $(document).ready(function () {
            $('.supplierSelect2').select2({
                placeholder: "Select Supplier",
            });
        });
    </script>
@endsection