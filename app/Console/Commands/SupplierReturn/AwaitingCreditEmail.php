<?php namespace App\Console\Commands\SupplierReturn;

use App\SupplierReturn;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Console\Command;

class AwaitingCreditEmail extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'supplier-returns:awaiting-credit-email';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Send email for each Returned supplier return.';

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$supplierReturns = SupplierReturn::where('status', SupplierReturn::STATUS_RETURNED)->get();

		if(!count($supplierReturns)) {
			$this->info("Nothing to Process.");
			return;
		}

		$this->info("Returns found: ".$supplierReturns->count());

		foreach($supplierReturns as $return) {
			$this->question($return->id." - ".$return->supplier->name);
			try {
				$this->sendEmail($return);
			} catch (\Exception $e) {
				$this->comment("Exception: ".$e->getMessage());
				alert("Supplier Return Awaiting Credit Email Exception: ".$e->getMessage());
			}
		}
	}

	protected function sendEmail($supplierReturn)
	{

		$attachmentPath = $supplierReturn->getAttachment();
		$extension =  $supplierReturn->supplier->name == 'Money4Machines' ? "docx" : "xlsx";

		Mail::send('emails.supplier-returns.awaiting-credit-email', ['supplierReturn' => $supplierReturn], function (Message $mail) use ($supplierReturn, $attachmentPath, $extension) {
			$mail->subject("Return Batch $supplierReturn->id Awaiting Credit (".$supplierReturn->supplier->name.")")
				->to($supplierReturn->supplier->returns_email_address, $supplierReturn->supplier->contact_name)
				->bcc(config('mail.chris_eaton.address'), config('mail.chris_eaton.name'))
				->from(config('mail.chris_eaton.address'), config('mail.chris_eaton.name'));
			$mail->attach($attachmentPath, ['as' => "RMA-Return_Batch-$supplierReturn->id.$extension"]);
		});
	}

}
