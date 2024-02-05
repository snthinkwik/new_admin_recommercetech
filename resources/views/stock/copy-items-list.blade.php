<textarea class="batch-summary-textarea" style="height:0; width:0;">
@foreach($items as $item)
{{ $item->quantity }}x {{ $item->name }} - {{ $item->capacity_formatted }} - {{ $item->grade }}
@endforeach
</textarea>