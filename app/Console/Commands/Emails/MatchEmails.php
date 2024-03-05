<?php namespace App\Console\Commands\Emails;

use App\Models\EmailTracking;
use App\Models\GoogleEmail;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MatchEmails extends Command {

	protected $name = 'emails:match-emails';

	protected $description = 'Checks if there are any matching emails in emails_tracking and google_emails';

	public function handle()
	{
		$processed = 0;
		$query = GoogleEmail::where('processed',0);
		$total = with($query)->count();
		$query->chunk(50, function($googleEmails) use(&$processed, $total) {
			foreach($googleEmails as $g) {
				$from = $g->from_email;
				$to = $g->to_email;
				$subject = $g->subject;
				$user = $g->user_id;


				$match = EmailTracking::where('from', $from)->where('to', $to)->where('subject', $subject)->where('user_id', $user)->get();
				foreach($match as $m){
					$eT = strtotime($m->created_at);
					$gE = strtotime($g->email_date);
					$diff = $eT - $gE;
					$this->info("\nEmailTracking: ".$m->id." - GoogleEmail: ".$g->id." - diff: ".$diff);
					// 15 minutes difference
					if($diff >=-900 && $diff<=900){
						$this->info("\nMatch: EmailTracking: ".$m->id." - GoogleEmail: ".$g->id." - diff: ".$diff);
						$g->email_tracking_id = $m->id;
						$g->processed = 1;
						$g->save();
						break;
					}
				}
				$g->processed = 1;
				$g->save();
				$processed++;
				progress($processed, $total);
			}
		});
		$this->info("Processed: ".$processed);


	}
}
