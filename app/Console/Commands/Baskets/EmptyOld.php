<?php namespace App\Console\Commands\Baskets;

use App\Models\BasketItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

class EmptyOld extends Command {

	protected $name = 'baskets:empty-old';

	protected $description = 'Empty old baskets';

	public function handle()
	{
		$userIds = BasketItem::distinct('user_id')->pluck('user_id');

		$users = User::with('basket')->where('type', 'user')->whereIn('id', $userIds)->get();

		foreach ($users as $user) {
			$isOld = true;
			foreach ($user->basket as $item) {
				if ($item->pivot->created_at->diffInMinutes(Carbon::now()) < 15) {
					$isOld = false;
					break;
				}
			}
			if (!$isOld) {
				break;
			}

			Mail::send('emails.baskets.auto-empty', compact('user'), function(Message $message) use($user) {
				$message->subject("Items still in your basket - $user->first_name")
					->from(config('mail.sales_address'), config('mail.from.name'))
					->to($user->email, $user->full_name);
			});
			$user->basket()->sync([]);
		}
	}

}
