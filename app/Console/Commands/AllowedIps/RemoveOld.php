<?php namespace App\Console\Commands\AllowedIps;

use App\Models\AllowedIp;
use Illuminate\Console\Command;
use Carbon\Carbon;

class RemoveOld extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'allowed-ips:remove-old';

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
	public function handle()
	{
		$sub5Days = Carbon::now()->subDays(5);
		$this->info($sub5Days);

		$ips = AllowedIp::where('last_login', '<', $sub5Days)->get();

		if(!count($ips)) {
			$this->info("Nothing to Process");
			return;
		}

		$this->info("IPs Found: ".$ips->count());

		foreach($ips as $ip) {
			$this->question("IP: $ip->ip_address ".$ip->last_login->format("d/m/y H:i:s"));
			$ip->delete();
		}
	}

}
