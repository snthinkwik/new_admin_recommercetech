<a class="btn btn-default btn-block" data-toggle="collapse" data-target="#billing_address"> User Billing Address </a>
<div class="panel panel-default collapse" id="billing_address">
    <div class="panel-body">

        {!! BsForm::model($user->billingAddress, ['method' => 'post', 'route' => 'admin.users.update-billing-address']) !!}
        {!! BsForm::hidden('id', $user->id) !!}
        {!! BsForm::groupText('line1',null ,['id'=>'billing_address_line1']) !!}
        {!! BsForm::groupText('line2',null,['id'=>'billing_address_line2']) !!}
        {!! BsForm::groupText('city',null,['id'=>'billing_address_city']) !!}
        {!! BsForm::groupText('county',null,['id'=>'billing_address_county']) !!}
        {!! BsForm::groupText('postcode',null,['id'=>'billing_address_postcode']) !!}
        {!! BsForm::groupText('country',null,['id'=>'billing_address_country']) !!}
        Same address copy for Shipping Address <input type="checkbox" id="copy_shipping">
        {!! BsForm::groupSubmit('Update', ['class' => 'btn-block']) !!}
        {!! BsForm::close() !!}


        {{--{!! BsForm::submit('Save', ['class' => 'btn btn-primary btn-block']) !!}--}}
        {!! Form::close() !!}

    </div>
</div>



@section('pre-scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script>

        $("#copy_shipping").click(function () {

            var checked= $("#copy_shipping").is(":checked");


            var address_country=$("#billing_address_country").val();
            var address_line1=$("#billing_address_line1").val();
            var address_line2=$("#billing_address_line2").val();
            var address_city=$("#billing_address_city").val();
            var address_county=$("#billing_address_county").val();
            var address_postcode=$("#billing_address_postcode").val();

            if(checked){
                $("#address_country").val(address_country);
                $("#address_line1").val(address_line1);
                $("#address_line2").val(address_line2);
                $("#address_city").val(address_city);
                $("#address_county").val(address_county);
                $("#address_postcode").val(address_postcode)
            }else{
                $("#address_country").val('');
                $("#address_line1").val('');
                $("#address_line2").val('');
                $("#address_city").val('');
                $("#address_county").val('');
                $("#address_postcode").val('')
            }



        })

    </script>
@endsection
