@if (Auth::user()->type !== 'user')
	<p><small><a href="{{ route('stock.photos', ['our_ref' => $item->id]) }}">Edit photos</a></small></p>
@endif


@if (Auth::user()->canRead('stock.photos'))
	<div class="stock-photo-list">
		@foreach ($item->photos as $photo)
			<p>
				<a href="{{ $photo->url }}" target="_blank"><img src="{{ $photo->url }}"></a>
			</p>
		@endforeach
	</div>
@endif