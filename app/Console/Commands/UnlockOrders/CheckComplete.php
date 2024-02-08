<?php namespace App\Console\Commands\UnlockOrders;

use App\Unlock;
use App\Unlock\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class CheckComplete extends Command {

	protected $name = 'unlock-orders:check-complete';

	protected $description = 'Check unlock orders and update status if they\'re complete';

	public function fire()
	{
		$orders = Order::with('unlocks')->where('status', Order::STATUS_PAID)->get();
		$failedUnlocks = new Collection();

		foreach ($orders as $order) {
			$allComplete = true;
			foreach ($order->unlocks as $unlock) {
				if ($unlock->status !== Unlock::STATUS_UNLOCKED) {
					$allComplete = false;
				}
				if ($unlock->status === Unlock::STATUS_FAILED && !$unlock->fail_reported) {
					$failedUnlocks[] = $unlock;
					$unlock->fail_reported = true;
					$unlock->save();
				}
			}

			if ($allComplete) {
				$order->status = Order::STATUS_COMPLETE;
				$order->save();
			}
		}

		if (count($failedUnlocks)) {
			$messageUnlocks = "";
			foreach($failedUnlocks as $failedUnlock) {
				$user = $failedUnlock->user_id ? $failedUnlock->user_id." ".$failedUnlock->user->full_name : "";
				$messageUnlocks .= "\nID: $failedUnlock->id | IMEI: $failedUnlock->imei | Network: $failedUnlock->network | User: $user";
			}
			$this->info($messageUnlocks);
			alert("Some unlocks in user orders have failed. Their ids are: " . implode(', ', $failedUnlocks->lists('id')).$messageUnlocks);
		}
	}

}
