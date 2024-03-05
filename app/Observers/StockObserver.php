<?php namespace App\Observers;

use App\Commands\Sales\EmailSend;
use App\Models\Stock;
use App\Models\Unlock;
use Illuminate\Events\Dispatcher;
use Queue;

class StockObserver {

	public function saved(Stock $item)
	{
		$this->unlock($item);
	}

	protected function unlock(Stock $item)
	{
		return;
		/*$shouldAdd =
			$item->getOriginal('status') === Stock::STATUS_SOLD &&
			$item->status === Stock::STATUS_PAID &&
			$item->batch_id == null &&
			$item->free_unlock_eligible &&
			Unlock::where('stock_id', $item->id)->count() === 0 &&
			Unlock::where('imei', $item->imei)->count() === 0;
		if (!$shouldAdd) {
			return;
		}

		$unlock = new Unlock();
		$unlock->fill([
			'imei' => $item->imei,
			'network' => $item->network,
		]);
		$unlock->stock_id = $item->id;
		if (!empty($item->sale->user_id) ) {
			$unlock->user_id = $item->sale->user_id;
		}
		$unlock->save();*/
	}

}
