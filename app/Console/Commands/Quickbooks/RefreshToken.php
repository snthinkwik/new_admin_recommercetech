<?php namespace App\Console\Commands\Quickbooks;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class RefreshToken extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'quickbooks:refresh-token';

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
		$quickbooks = app('App\Contracts\Quickbooks');
		$this->info($quickbooks->refreshToken());
	}

}
