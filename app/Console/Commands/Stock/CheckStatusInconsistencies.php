<?php namespace App\Console\Commands\Stock;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckStatusInconsistencies extends Command {

	protected $name = 'stock:check-status-inconsistencies';

	protected $description = 'Check for status inconsistencies and send email when found. Needed to track down a bug.';

	public function fire()
	{
		$sql =
			"select count(*) cnt
			from new_stock 
			where locked_by <> '' 
			and locked_by not like 'batch%' 
			and status <> 'Pre-ordered' 
			and status <> 'Deleted'
			and (status in ('inbound', 'in stock') || sale_id is null)";
		$count = DB::select($sql)[0]->cnt;

		if ($count) {
			alert("Status inconsistency detected in the database - $count items.");
		}
	}
}
