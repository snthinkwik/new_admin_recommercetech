{!! BsForm::open(['id' => 'universal-search-form', 'class' => 'spinner form-inline mb15', 'method' => 'get']) !!}
<div class='universal-loader' style="display: none" ><div class='universal-spinner'></div></div>
<div class="form-group">
    <div class="input-group">
        <span class="input-group-addon">
            Search
        </span>
        {!! BsForm::text('sku', Request::input('sku'), ['id' => 'ebay-search-sku', 'placeholder' => 'Search Sku', 'size' => 30]) !!}
    </div>
</div>
{!! BsForm::close() !!}