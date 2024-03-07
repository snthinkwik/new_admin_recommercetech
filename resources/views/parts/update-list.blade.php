<h2>Update Stock and Costs</h2>
<div class="row">
    @if(count($parts) == 0)
    <div class="alert alert-danger">No Parts</div>
    @else
    <div class="col-md-12">
        {!! BsForm::open(['route' => 'parts.update-costs-submit']) !!}
        @foreach($parts as $part)
        <div class="row mb10">
            {!! Form::hidden('part['.$part->id.'][id]', $part->id) !!}
            <div class="col-md-1">
                {!! Form::label('id', 'Part No.') !!}
                {!! Form::text('id', $part->id, ['disabled', 'class' => 'form-control']) !!}
            </div>
            <div class="col-md-5">
                {!! Form::label('name', 'Part Name (Name - Colour - Type)') !!}
                {!! Form::text('long_name', $part->long_name, ['disabled', 'class' => 'form-control']) !!}
            </div>
            <div class="col-md-2">
                {!! Form::label('cost', 'Part Cost') !!}
                <div class="input-group">
                    <div class="input-group-addon">£</div>
                    {!! Form::number('part['.$part->id.'][cost]', $part->cost, ['class' => 'form-control', 'step' => 0.01]) !!}
                </div>
            </div>
            <div class="col-md-2">
                {!! Form::label('quantity', 'Update Qty') !!}
                <div class="input-group">
                    <div class="input-group-addon sub c-pointer">
                        <span type="button" id="sub" class="input-group-text">
                            -
                        </span>
                    </div>
                    {!! Form::number('part['.$part->id.'][quantity]', $part->quantity, ['class' => 'form-control trg_qty field text-center']) !!}
                    <div class="input-group-addon add  c-pointer">
                        <span type="button" id="add" class="input-group-text">
                            +
                        </span>
                    </div>
                </div>
            </div>

        </div>
        @endforeach
        {!! BsForm::submit('Update', ['class' => 'mt10 btn btn-primary btn-block']) !!}
        {!! BsForm::close() !!}
    </div>
    @endif
</div>
