<?php namespace App\Console\Commands\Users;

use App\Csv\Parser;
use App\User;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class ImportCsv extends Command {

	protected $name = 'users:import-csv';

	protected $description = 'Import unregistered users from CSV';

	public function fire()
	{
		$path = $this->argument('file-path');
		$path = realpath($path);

		if (!file_exists($path)) {
			$this->output->writeln("File \"$path\" doesn't exist.");
			die;
		}

		$csv = new Parser($path, [
			'headerFilter' => function($header) {
				// Something weird going on with the headers in the CSV I got, let's remove everything but word characters.
				return preg_replace('/\W/', '', $header);
			}
		]);

		$countLines = exec("wc -l " . escapeshellarg($path)) - 1;
		$i = 0;

		while ($row = $csv->getRow()) {
			if (!User::where('email', $row['email'])->count()) {
				$user = new User;
				$user->forceFill([
					'email' => $row['email'],
					'marketing_emails_subscribe' => $row['unsubscribed'] === 'f',
					'registered' => false,
					'registration_token' => md5(rand()),
					'stock_fully_working' => true,
					'stock_minor_fault' => true,
					'stock_major_fault' => true,
					'stock_no_power' => true,
					'stock_icloud_locked' => true,
				]);
				$user->save();
			}

			$i++;
			progress($i, $countLines);
		}
	}

	protected function getArguments()
	{
		return [
			['file-path', InputArgument::REQUIRED, 'Path to the CSV.'],
		];
	}

}
