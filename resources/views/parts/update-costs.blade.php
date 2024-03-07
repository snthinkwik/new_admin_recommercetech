@extends('app')

@section('title', 'Parts - Update Costs')

@section('scripts')
    <script>
        function setZero() {
            var confirm = window.confirm('Are you sure to set 0 to all qtys?')
            if (confirm) {
                // $('.inbound_qty').val(0);
                // $('.trg_qty').val(0);
              $.ajax({
                type: "POST",
                data: {
                  "setzero":1,
                  "_token": "{{ csrf_token() }}"
                },
                url:'/parts/update-costs',
                success: (res) => {
                  window.location.reload();

                }
              });
            } else {
                return false;
            }

        }


    </script>
@endsection

@section('content')

    <div class="container">

        @include('messages')
        <p>
            <a class="btn btn-default" href="{{ route('parts') }}">Back to parts</a>
            <a class="btn btn-default" onclick="setZero();">Set All to 0 Qty</a>
        </p>

        @include('parts.type-search-form')
        <div id="universal-table-wrapper">
            @include('parts.update-list')
        </div>
    </div>


@endsection

