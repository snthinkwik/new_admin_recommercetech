<div class="row">
    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-body">
                @if (session('ebay.csv_errors'))
                    <div class="alert alert-danger">
                        There were errors in the uploaded CSV file. Please see the list below and fix the problems before re-uploading.
                    </div>
                    @foreach (session('ebay.csv_errors') as $rowData)
                        <h5 class="text-danger">Row {{ $rowData['rowIdx'] + 1 }}</h5>
                        @foreach ($rowData['errors']->all() as $error)
                            <p class="text-danger">- {{ $error }}</p>
                        @endforeach
                    @endforeach
                @endif

                {!! Form::open(['route' => 'sku.import', 'files' => true]) !!}
                <div class="form-group">
                    {!! Form::file('ebay-sku', ['accept' => '.csv']) !!}
                </div>
                <div class="form-group">
                    {!! Form::submit('Import', ['class' => 'btn btn-primary']) !!}
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>