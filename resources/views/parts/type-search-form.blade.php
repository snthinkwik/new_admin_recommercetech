<?php

use App\Models\Part;

$types = ['' => ''] + Part::select('type')->groupBy('type')->get()->pluck('type', 'type')->toArray();
?>
{!! BsForm::open(['id' => 'universal-search-form', 'class' => 'form-inline mb15', 'method' => 'get']) !!}
<div class="form-group">
    <div class="input-group">
        <span class="input-group-addon">Type</span>
        {!! BsForm::select('type', $types, Request::input('type')) !!}
    </div>
    <div class="input-group">
        {!! BsForm::text('term', Request::input('term'), [ 'placeholder' => 'Search no.', 'size' => 30]) !!}
    </div>
</div>

{!! BsForm::close() !!}
