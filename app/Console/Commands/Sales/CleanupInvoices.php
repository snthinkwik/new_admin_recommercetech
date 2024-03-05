<?php namespace App\Console\Commands\Sales;

use Illuminate\Console\Command;

class CleanupInvoices extends Command {
	
	protected $name = 'sales:cleanup-invoices';
	
	protected $description = 'Deletes old invoice files.';
	
	public function handle()
	{
		$path = storage_path('app/invoices');
		$cmd = "find $path -type f -mtime +0 -not -name '.gitignore' -delete";
		exec($cmd);
	}
	
}
