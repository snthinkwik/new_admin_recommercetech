
@extends('app')

@section('title', 'Seller Fees')

@section('content')

    <div class="container">
<a href="{{route('seller_fees.index')}}" class="btn btn-default">Back</a>
        @include('messages')
            <h2>Add Seller Fees</h2>
        <div class="row">
            <div class="col-md-6">
                {!! BsForm::open(['route' => 'seller_fees.save', 'files' => 'true']) !!}
                @if(isset($sellerFees->id))
                    {!! BsForm::hidden('id',isset($sellerFees->id)? $sellerFees->id:null) !!}
                @endif

                <div class="form-group">
                    <label for="platform">Platform</label>
                    <div class="input-group">
                     {!! BsForm::text('platform',isset($sellerFees)?$sellerFees->platform:null,['placeholder' => 'Plat Form']) !!}
                        <div class="input-group-addon"></div>
                    </div>

                </div>

                <div class="form-group">
                    <label for="platform_fees">PlatForm Fees</label>
                    <div class="input-group">
                {!! BsForm::text('platform_fees',isset($sellerFees) ? $sellerFees->platform_fees:null, ['placeholder' => 'PlatForm Fees']) !!}
                        <div class="input-group-addon">%</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="uk_shipping_cost_under_20">Av. UK Shipping Cost ex VAT = if item < £20</label>
                    <div class="input-group">
                {!! BsForm::text('uk_shipping_cost_under_20',isset($sellerFees)?$sellerFees->uk_shipping_cost_under_20:null, ['placeholder' => 'UK Shipping Under 20']) !!}
                        <div class="input-group-addon">£</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="uk_shipping_cost_above_20">Av. UK Shipping Cost ex VAT= if item > £20</label>
                    <div class="input-group">
                {!! BsForm::text('uk_shipping_cost_above_20',isset($sellerFees) ?$sellerFees->uk_shipping_cost_above_20:null, ['placeholder' => 'Av. UK Shipping Cost ex VAT= if item > £20']) !!}
                        <div class="input-group-addon">£</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="uk_non_shipping_cost_under_20">Av. Non UK  Shipping Cost ex VAT = if item < £20</label>
                    <div class="input-group">
                    {!! BsForm::text('uk_non_shipping_cost_under_20',isset($sellerFees)? $sellerFees->uk_non_shipping_cost_under_20:null, ['placeholder' => 'Av. Non UK  Shipping Cost ex VAT = if item < £20']) !!}
                        <div class="input-group-addon">£</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="uk_non_shipping_cost_under_20">Av. NON UK Shipping Cost ex VAT= if item > £20</label>
                    <div class="input-group">
                {!! BsForm::text('uk_non_shipping_above_under_20',isset($sellerFees)?$sellerFees->uk_non_shipping_above_under_20:null, ['placeholder' => 'Av. NON UK Shipping Cost ex VAT= if item > £20']) !!}
                        <div class="input-group-addon">£</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="uk_non_shipping_cost_under_20">Box/Add-on/Accessories cost ex VAT</label>
                    <div class="input-group">
                {!! BsForm::text('accessories_cost_ex_vat',isset($sellerFees)? $sellerFees->accessories_cost_ex_vat:null, ['placeholder' => 'Box/Add-on/Accessories cost ex VAT']) !!}
                        <div class="input-group-addon">£</div>
                    </div>
                </div>


                <div class="form-group">
                    <label for="uk_non_shipping_cost_under_20">Warranty Accrual</label>
                    <div class="input-group">
                        {!! BsForm::text('warranty_accrual',isset($sellerFees)? $sellerFees->warranty_accrual:null, ['placeholder' => 'Warranty Accrual']) !!}
                        <div class="input-group-addon">%</div>
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