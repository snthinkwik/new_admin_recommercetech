@extends('app')

@section('title', 'Delete stock item')

@section('content')

<div class="container">
    <h1>Delete stock item</h1>
    @include('messages')
    <div class="row">
        <div class="col-md-6">
            {!! BsForm::open(['route' => 'stock.delete','id'=>"deleteVerify"]) !!}
            {!! BsForm::groupTextarea('imeis', old('imeis'), ['placeholder' => 'Separated by new lines or spaces or commas...'], ['label' => 'Serial, IMEI or 3rd-party ref']) !!}
            {!! BsForm::Button('Delete',['id'=>'deleteButton']) !!}
            <input type="hidden" id="code" name="code">
            {!! BsForm::close() !!}
        </div>
    </div>
</div>

<div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" id="mi-modal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Manager Authorisation Code Required</h4>
            </div>
            <div class="modal-body">
                <div id="errorModel"></div>
                <label>Code</label>
                <input type="text" name="code" id="verification_code" class="form-control" required>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" id="modal-btn-si">Verify</button>
            </div>
        </div>
    </div>

</form>
</div>

@endsection

@section('pre-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script>
    $("#deleteButton").on('click', function () {
        var modalConfirm = function (callback) {
            $("#mi-modal").modal('show');
            $("#modal-btn-si").on("click", function () {
                callback(true);
                $("#mi-modal").modal('hide');
            });

            $("#modal-btn-no").on("click", function () {
                callback(false);
                $("#mi-modal").modal('hide');
            });
        };

        modalConfirm(function (confirm) {
            if (confirm) {
                var code = $("#verification_code").val();
                $("#code").val(code);
                $("#deleteVerify").submit()
            } else {
                $("#result").html("NO CONFIRMADO");
            }
        });
    });

</script>

@endsection

