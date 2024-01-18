<?php
// Allow simple formatting of messages:
$formatter = function ($txt) {
    return nl2br(e($txt));
}
?>

<div id="messages-js"></div>
@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        {!! implode('', $errors->all('<div>:message</div>')) !!}
    </div>
@endif
@if (session('messages.error'))
    <div class="alert alert-danger" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <?= $formatter(session('messages.error')) ?>
    </div>
@endif

@if (session('messages.warning'))
    <div class="alert alert-warning" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <?= $formatter(session('messages.warning')) ?>
    </div>
@endif

@if (session('messages.success'))
    <div class="alert alert-success" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <?= $formatter(session('messages.success')) ?>
    </div>
@endif


@if (session('messages.info'))
    <div class="alert alert-info" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <?= $formatter(session('messages.info')) ?>
    </div>
@endif

@if(session('messages.error-custom'))
    <div class="alert alert-danger" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        {!! nl2br(session('messages.error-custom')) !!}
    </div>
@endif

@if(session('messages.info-custom'))
    <div class="alert alert-info" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        {!! nl2br(session('messages.info-custom')) !!}
    </div>
@endif
