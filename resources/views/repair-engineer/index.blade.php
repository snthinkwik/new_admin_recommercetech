@extends('app')

@section('title', 'Repair Engineer')

@section('content')

    <div class="container">


        @include('messages')

        <h2>Repair Engineer</h2>

        <div class="p5">
        <a href="#" data-toggle="modal"  data-target="#addNewModal" class="btn btn-success"    ><i class="fa fa-edit"></i> Add New </a>
        </div>

        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">

            <div class="modal-dialog" role="document">
                {!! BsForm::open(['method' => 'post', 'route' => 'engineer.save']) !!}
                {!! BsForm::hidden('id', null,['id'=>"edit"]) !!}


                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Update Engineer</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        {!! BsForm::text('name', null, ['placeholder' => 'Enter Name','id'=>'name']) !!}
                        <br>
                        {!! BsForm::text('company', null, ['placeholder' => 'Enter Company','id'=>'company']) !!}


                    </div>
                    <div class="modal-footer">
                        {!! BsForm::submit('Save') !!}
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>

                    </div>

                </div>

                {!! BsForm::close() !!}
            </div>


        </div>


        <div class="modal fade" id="addNewModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">

            <div class="modal-dialog" role="document">
                {!! BsForm::open(['method' => 'post', 'route' => 'engineer.save']) !!}



                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Add New Engineer</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <label>Name</label>
                        {!! BsForm::text('name', null, ['placeholder' => 'Enter Name','id'=>'faults','required']) !!}
                        <br>
                        <label>Company</label>
                        {!! BsForm::text('company', null, ['placeholder' => 'Enter Company','id'=>'company']) !!}


                    </div>
                    <div class="modal-footer">
                        {!! BsForm::submit('Add New') !!}
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>

                    </div>

                </div>

                {!! BsForm::close() !!}
            </div>


        </div>
            <table class="table table-hover table-bordered">
                <thead>
                <tr>

                    <th class="col-xs-6">Name</th>
                    <th class="col-xs-3" >Company</th>
                    <th></th>


                </tr>
                </thead>
                <tbody>
                @foreach($engineer as $data)
                    <tr>

                        <td>{{ $data->name }}</td>

                        <td>{{$data->company}}</td>
                        <td width="5%">
                            <a href="#" data-toggle="modal"  data-target="#exampleModal" class="readMore edit" data-id="{{$data->id}}" ><i class="fa fa-edit"></i> </a>

                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>


        <div>{!! $engineer->appends(Request::all())->render() !!}</div>
    </div>


@endsection

@section('scripts')
    <script>
        $(".edit").on('click',function () {
            var id= $(this).data("id");
            $("#edit").val(id);


            $.ajax({
                url: "{{ route('engineer.data') }}",
                method: 'POST',
                data: {
                    "_token": "{{ csrf_token() }}",
                    id: id,
                },
                success: function (data) {
                    console.log(data);

                    $("#name").val(data.data.name);
                    $("#company").val(data.data.company);
                   // $("#faults").html(data.data.repaired_faults);

                }
            });

        })

    </script>
    @endsection