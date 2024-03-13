<?php
$lostReasons = ['' => 'Please Select Reason'] + \App\Models\Stock::getAvailableLostReasonsWithKeys();
?>

<div class="row">
    <div class="alert alert-success" role="alert" id="message" style="display: none">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
        Status Successfully Update
    </div>
</div><br>
<div class="row">
    {!! BsForm::open(['id' => 'lost-items-search-form', 'class' => 'mb15', 'method' => 'get','class' => 'spinner']) !!}
    <div class='universal-loader' style="display: none" ><div class='universal-spinner'></div></div>

    <div class="col-sm-3">
        <div class="form-group">
            {!! BsForm::text('term', Request::input('term'), ['id' => 'item-search', 'placeholder' => 'Search', 'size' => 20]) !!}
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            {!!
              BsForm::select(
                  'colour',
                  ['' => 'Any Colour'] + $colours = \App\Models\Colour::orderBy('pr_colour')->pluck('pr_colour', 'pr_colour')->toArray(),
                  Request::input('colour'),
                  ['id' => 'item-search-colour']
              )
          !!}
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            {!! BsForm::select('lost_reason', $lostReasons, null, ['required' => 'required']) !!}
        </div>
    </div>

    {!! BsForm::hidden('sort', '',['id'=>'sort']) !!}
    {!! BsForm::hidden('sortO', '',['id'=>'sortO']) !!}
    {!! BsForm::close() !!}
</div>
