{!! BsForm::open(['id' => 'universal-search-form', 'class' => 'spinner form-inline mb15', 'method' => 'get']) !!}
<div class='universal-loader' style="display: none" ><div class='universal-spinner'></div></div>

<div class="form-group">
    <div class="input-group">
        <span class="input-group-addon">
            Search by Date
        </span>
        <input type="date" name="start_date" placeholder="From Date" style="height: 30px;">   &nbsp;
        <input type="date" name="end_date" placeholder="To Date" style="height: 30px;">
    </div>
</div>
{!! BsForm::close() !!}