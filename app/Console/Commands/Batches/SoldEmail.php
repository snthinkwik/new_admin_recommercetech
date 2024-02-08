<?php namespace App\Console\Commands\Batches;

use App\Batch;
use App\BatchOffer;
use App\Stock;
use App\User;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

class SoldEmail extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'batches:sold-email';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description.';

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$batch = Batch::findOrFail($this->argument('batch_id'));
		$user = User::findOrFail($this->argument('user_id'));
		$soldPrice = $this->argument('sold_price');
		$this->info('Batch: '.$batch->id);
		$this->info('Sold Price: '.$soldPrice);
		$this->info('User: '.$user->id.' '.$user->full_name);

		$soldPrice = money_format(config('app.money_format'), $soldPrice);
		$this->info("Sold Price");

		$userOffer = BatchOffer::where('user_id', $user->id)->first();
		if($userOffer) {
			$this->sendWinSms($user, $batch);
			$userOffer->seen = true;
			$userOffer->save();
		}

		$offers = BatchOffer::where('batch_id', $batch->id)->whereNotIn('user_id', [$user->id])->orderBy('offer', 'desc')->groupBy('user_id')->get();
		if(!count($offers)) {
			$this->info("No Offers");
			return;
		}
		$this->info("Offers found: ".$offers->count());

		foreach($offers as $offer) {
			$this->sendNotWinEmailSms($offer, $soldPrice);
			$offer->seen = true;
			$offer->save();
		}

	}

	protected function sendWinSms($user, $batch)
	{
		$this->question("Send Win SMS");
		$message = "Hi $user->first_name, Congratulations you were the highest offer for batch $batch->id. We will now raise an invoice for this order. Regards, Recomm";

		$txtlocal = app('App\Contracts\Txtlocal');
		$sms = $txtlocal->sendMessage($user->phone, $message);
		//alert($message);
	}

	protected function sendNotWinEmailSms($offer, $soldPrice)
	{
		$user = $offer->user;
		$subject = "Batch $offer->batch_id has now sold";

		$batchesForSale = Batch::where('id', '!=', $offer->batch_id)->NotAuction()->whereHas('stock', function ($q) {
			$q->where('status', Stock::STATUS_BATCH);
			$q->whereNull('sale_id');
		})->where('sale_price', '>', 0)->get();

		Mail::send(
			'emails.batches.offer-not-win', compact('user', 'offer', 'soldPrice', 'batchesForSale'),
			function($message) use($user, $subject) {
				$message->subject($subject)
					->from(config('mail.sales_old_address'), config('mail.from.name'))
					->bcc(config('mail.sales_old_address'), config('mail.from.name'))
					->to($user->email, $user->full_name);
			}
		);

		// send SMS
		$message = "Hi $user->first_name, Thanks for your offer on batch $offer->batch_id. The batch has now sold to the winning offer of $soldPrice. We we will have more batches available shortly. Regards, Recomm";

		$txtlocal = app('App\Contracts\Txtlocal');
		$sms = $txtlocal->sendMessage($user->phone, $message);
		//alert($message);
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [
			['batch_id', InputArgument::REQUIRED, 'Batch ID.'],
			['user_id', InputArgument::REQUIRED, 'User ID'],
			['sold_price', InputArgument::REQUIRED, 'Sold Price']
		];
	}

}
