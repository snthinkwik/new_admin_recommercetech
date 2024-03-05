<?php namespace App\Console\Commands\Sales;

use App\RecentOrderCheck;
use App\Sale;
use App\Stock;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Exception;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

class CheckNoRecentOrders extends Command {

	protected $name = 'sales:check-no-recent-orders';

	protected $description = 'Check if customer has not placed an order in 14+ days.';

	public function fire()
	{
		$query = User::where('invoice_api_id','!=','')->where('type', 'user')->where('marketing_emails_subscribe', true);
		$total = with($query)->count();
		$this->info("Users: $total");
		$processed = 0;
		$itemsInStock = Stock::where('status', Stock::STATUS_IN_STOCK)->where('shown_to', Stock::SHOWN_TO_ALL)->count();
		$now = Carbon::now();
		$query->chunk(50, function($users) use(&$processed, $total, $now, $itemsInStock) {
			foreach($users as $user) {
				$latestSale = Sale::where('customer_api_id', $user->invoice_api_id)->orderBy('id','desc')->first();
				if($latestSale) {
					//$this->info($user->full_name." - $user->invoice_api_id ".($latestSale ? $latestSale->created_at : 'none' ). " - DIFF: ".$latestSale->created_at->diffInDays($now));
					//$this->question("Diff: ".$latestSale->created_at->diffInDays($now));
					if($latestSale->created_at->diffInDays($now) > 14) {
						$latestCheck = RecentOrderCheck::where('customer_api_id', $user->invoice_api_id)->orderBy('id', 'desc')->first();
						$continue = true;
						if (isset($latestCheck)) {
							$diff = $latestCheck->created_at->diffInDays($now);
							//$this->info($latestSale->created_at . " - " . $latestCheck->created_at . " - " . $diff);
							if($diff <= 30) {
								$continue = false;
							}
						}

						if($continue == true) {
							$processed++;
							try {
								$this->sendMail($user, $itemsInStock);
								$newCheck = new RecentOrderCheck();
								$newCheck->customer_api_id = $user->invoice_api_id;
								$newCheck->save();
								$this->question("User: $user->full_name - API ID $user->invoice_api_id Check Saved, Email Sent");
							} catch(Exception $e) {
								alert("RecentOrders Exception: ".$e);
							}
						}
					}
				}
			}
		});
		alert("Check no recent orders - processed ".$processed);
		$this->question("Processed: ".$processed);
	}

	protected function sendMail($user, $itemsInStock)
	{
		$invoicing = app('App\Contracts\Invoicing');
		$customer = $invoicing->getCustomer($user->invoice_api_id);
		if(!$customer) {
			alert("Recent Orders Check: Can't send email to customer - customer not found.");
			return;
		}

		if (!$customer->email) {
			alert("Recent Orders Check: Can't send email to customer - email address empty.");
			return;
		}

		Mail::send('emails.sales.no-recent-orders', ['itemsInStock' => $itemsInStock, 'customer' => $customer], function (Message $mail) use ($customer) {
			$mail->subject($customer->first_name.", we miss you!")
				->to($customer->email, $customer->full_name)
				->from(config('mail.sales_address'), config('mail.from.name'));
		});
	}

}