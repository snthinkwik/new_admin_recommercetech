
<div class="row">
    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-body">
                {!! Form::open(['route' => 'repairs.import', 'files' => true]) !!}
                <div class="form-group">
                    {!! Form::file('csv', ['accept' => '.csv']) !!}

                    <input type="hidden" value="{{$repairId}}" name="repairs_id">
                </div>

                <div class="form-group">
                    {!! Form::submit('Import', ['class' => 'btn btn-primary']) !!}
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>