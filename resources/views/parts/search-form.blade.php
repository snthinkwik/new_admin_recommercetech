<?php

use Illuminate\Support\Facades\Request;
use App\Models\Part;

$colours = ['' => ''] + Part::select('colour')->groupBy('colour')->get()->pluck('colour', 'colour')->toArray();
$types = ['' => ''] + Part::select('type')->groupBy('type')->get()->pluck('type', 'type')->toArray();
?>
{!! BsForm::open(['id' => 'part-search-form', 'class' => 'form-inline mb15', 'method' => 'get']) !!}
<div class="form-group">
    {!! BsForm::text('term', Request::input('term'), ['id' => 'parts-search-term', 'placeholder' => 'Search', 'size' => 30]) !!}

    <div class="input-group">
        <span class="input-group-addon">Type</span>
        {!! BsForm::select('type', $types, Request::input('type')) !!}
    </div>

    <div class="input-group">
        <span class="input-group-addon">Colour</span>
        {!! BsForm::select('colour', $colours, Request::input('colour')) !!}
    </div>
</div>

{!! BsForm::hidden('sort', '',['id'=>'sort']) !!}
{!! BsForm::hidden('sortO', '',['id'=>'sortO']) !!}

{!! BsForm::close() !!}
