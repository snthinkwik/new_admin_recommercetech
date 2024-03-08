<?php
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;
/** @var Collection $basket */
$basket = Auth::user() ? Auth::user()->basket : null;
if(Auth::user()) {
    $basket = Auth::user()->fresh(['basket'])->basket;
}

$part_basket = Auth::user() ? Auth::user()->part_basket : null;

?>
@if (($basket && count($basket) > 0))
    <?php
    $totalAmount = $basket->reduce(function($sum, $a) {
        // Admin users can change the price. Let's check the request and take the price from there. If not present, take
        // the item's sale price.
        $price = Request::input("items." . $a->id . ".price", $a->sale_price);
        return ($sum * 100 + $price * 100) / 100;
    });

    $count = count($basket);
    if(!is_null($part_basket) > 0) {
        $count += $part_basket->sum('quantity');
        $totalAmount += $part_basket->sum('part_total_amount');
    }

    ?>
    <div class="navbar-text navbar-right pr15" id="basket-count" data-count="{{ count($basket) }}">
        <a href="{{ route('basket') }}">
            <i class="fa fa-shopping-basket"></i>
            <span class="badge">{{ $count }} &mdash; {{ money_format($totalAmount) }}</span>
        </a>
    </div>
@endif
