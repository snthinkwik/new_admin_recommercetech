<?php
use Illuminate\Support\Arr;
if (!is_array($required)) {
    $required = [$required];
}
$scriptSpecs = [
    'ckeditor' => [
        'section' => 'pre-scripts',
        'path' => 'js/ckeditor/ckeditor.js',
    ],
    'canvasjs' => [
        'section' => 'pre-scripts',
        'path' => 'js/canvasjs/dist/canvasjs.js'
    ]
]
?>

@foreach ($required as $script)
    @if (!Arr::get($GLOBALS, 'javascripts-added.' . $script))
        <?php
        Arr::set($GLOBALS, 'javascripts-added.' . $script, true);
        $scriptSpec = $scriptSpecs[$script];
        ?>
        @section($scriptSpec['section'])
            @parent
            <script src="{{ asset($scriptSpec['path']) }}"></script>
        @endsection
    @endif
@endforeach
