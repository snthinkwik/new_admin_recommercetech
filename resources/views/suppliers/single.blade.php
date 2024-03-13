<?php
use App\Models\Supplier;
$returnForms = ['' => 'Please Select'] + array_flip(Supplier::getAvailableReturnForms());
$mapping=json_decode($supplier->grade_mapping);
$selectMapping=json_decode($supplier->select_grade);


?>

@extends('app')

@section('title', 'Supplier Details')

@section('content')


	<div class="container">

		@include('messages')
		<a class="btn btn-default" href="{{ route('suppliers') }}"><i class="fa fa-reply"></i> Back to Suppliers List</a>

		<h2>Supplier Details - {{ $supplier->name }}</h2>

		<div class="row">

			<div class="col-md-6">

				<div class="panel panel-default">
					<div class="panel-heading">Details</div>
					<div class="panel-body">
						{!! BsForm::model($supplier, ['method' => 'post', 'route' => 'suppliers.update']) !!}
								{!! BsForm::hidden('id', $supplier->id) !!}

						{!! BsForm::groupNumber('crm_id', null, [],['label' => 'CRM ID']) !!}
								{!! BsForm::groupText('name', null, ['required' => 'required']) !!}

								{!! BsForm::groupText('address_1', null) !!}

								{!! BsForm::groupText('address_2') !!}

								{!! BsForm::groupText('town', null) !!}

								{!! BsForm::groupText('county', null) !!}

								{!! BsForm::groupText('postcode', null) !!}

								{!! BsForm::groupText('email_address', null) !!}

								{!! BsForm::groupText('contact_name', null) !!}

								{!! BsForm::groupText('returns_email_address', null) !!}

								{!! BsForm::groupSelect('returns_form', $returnForms, null) !!}

								{!! BsForm::submit('Update', ['class' => 'btn btn-info btn-sm btn-block']) !!}

						{!! BsForm::close() !!}
					</div>

				</div>

			</div>
			<div class="col-md-6">
				<h3>Supplier Grade Mapping</h3>
				{!! BsForm::open(['route' => 'suppliers.grade-mapping', 'method' => 'post']) !!}
				<table class="table table-responsive">

					<th>Supplier Grade</th>
					<th>Recomm Grade</th>

					<input type="hidden" value="{{$supplier->id}}" name="supplier_id">

					<tr>

						<td><input type="text" class="form-control" name="s_1"  value="@if(isset($mapping->g1)) {{$mapping->g1->s}} @else 1 @endif"  ></td>
						<td><input type="text" class="form-control" name="g_1"  value="@if(isset($mapping->g1)) {{$mapping->g1->r}} @else A @endif"  ></td>
					</tr>
					<tr>



						<td><input type="text" class="form-control" name="s_2"  value="@if(isset($mapping->g2)) {{$mapping->g2->s}} @else 2 @endif"  ></td>
						<td><input type="text" class="form-control" name="g_2"  value="@if(isset($mapping->g2)) {{$mapping->g2->r}} @else B @endif" ></td>
					</tr>
					<tr>


						<td><input type="text" class="form-control" name="s_3"  value="@if(isset($mapping->g3)) {{$mapping->g3->s}} @else 3 @endif"  ></td>
						<td><input type="text" class="form-control" name="g_3"  value="@if(isset($mapping->g3)) {{$mapping->g3->r}} @else C @endif" ></td>
					</tr>
					<tr>


						<td><input type="text" class="form-control" name="s_4"  value="@if(isset($mapping->g4)) {{$mapping->g4->s}} @else 4 @endif"  ></td>
						<td><input type="text" class="form-control" name="g_4" value="@if(isset($mapping->g4)) {{$mapping->g4->r}} @else D @endif" ></td>

					</tr>
					<tr>

						<td><input type="text" class="form-control" name="s_5"  value="@if(isset($mapping->g5)) {{$mapping->g5->s}} @else 5 @endif"  ></td>
						<td><input type="text" class="form-control" name="g_5" value="@if(isset($mapping->g5)) {{$mapping->g5->r}} @else E @endif" ></td>
					</tr>
					<tr>


						<td><input type="text" class="form-control" name="s_6"  value="@if(isset($mapping->g6)) {{$mapping->g6->s}} @else 6 @endif"  ></td>
						<td><input type="text" class="form-control" name="g_6" value="@if(isset($mapping->g6)) {{$mapping->g6->r}} @else F @endif" ></td>
					</tr>

				</table>


				{!! BsForm::submit('Update', ['class' => 'btn btn-info btn-sm btn-block']) !!}
				{!! BsForm::close() !!}
				<br>

				<h3>P/S Model</h3>

				<form method="post" action="{{route('suppliers.ps-percentage')}}">

					<input type="hidden" value="{{$supplier->id}}" name="supplier_id">

					<input type="hidden" name="_token" value="{{ csrf_token() }}">

					<div class="input-group">
						<span class="input-group-addon">Recomm P/S</span>
						<input id="universal-search-term"  class="form-control" value="{{$supplier->recomm_ps}}" name="recomm_ps" type="text">
						<span class="input-group-addon">%</span>
					</div>
					<br>

					<div class="input-group p-2">
						<span class="input-group-addon">Supplier P/S</span>
						<input id="universal-search-term"  class="form-control" value="{{$supplier->supplier_ps}}" name="supplier_ps" type="text">
						<span class="input-group-addon">%</span>
					</div>
					<br>
					<input type="submit" value="Update" class="btn btn-info btn-sm btn-block">
				</form>



			</div>

		</div>
	</div>

@endsection
