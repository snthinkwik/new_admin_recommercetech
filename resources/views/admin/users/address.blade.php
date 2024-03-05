<a class="btn btn-default btn-block" data-toggle="collapse" data-target="#user_address"> User Shipping Address </a>
<div class="panel panel-default collapse" id="user_address">
    <div class="panel-body">
        {{--@if(!$user->invoice_api_id)--}}
        {!! BsForm::model($user->address, ['method' => 'post', 'route' => 'admin.users.update-address']) !!}
        {!! BsForm::hidden('id', $user->id) !!}
        {!! BsForm::groupText('line1',null,['id'=>'address_line1']) !!}
        {!! BsForm::groupText('line2',null,['id'=>'address_line2']) !!}
        {!! BsForm::groupText('city',null,['id'=>'address_city']) !!}
        {!! BsForm::groupText('county',null,['id'=>'address_county']) !!}
        {!! BsForm::groupText('postcode',null,['id'=>'address_postcode']) !!}
        {!! BsForm::groupText('country',null,['id'=>'address_country']) !!}
        {!! BsForm::groupSubmit('Update', ['class' => 'btn-block']) !!}
        {!! BsForm::close() !!}

    </div>
</div>
