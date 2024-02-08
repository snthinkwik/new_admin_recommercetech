<?php namespace App\Console\Commands\DpdShipping;

use App\Setting;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CreateShipping extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'dpd:create-shipping';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{

        $BASE="https://api.dpdlocal.co.uk/";

        $method = '/user/?action=login';

        $url = $BASE.$method;


        $options = array(
            'http' => array(
                'method'  => 'POST',
                'Host'  => 'api.dpd.co.uk',
                'header'=>  "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n".
                    "Authorization: Basic ". base64_encode(config('services.DPD_Shipping.user_name').":". config('services.DPD_Shipping.password')) ."\r\n".
                    "GEOClient: account/".config('services.DPD_Shipping.account'),
                "Content-Length: 0"
            )
        );


        $context     = stream_context_create($options);

        $result      = file_get_contents($url, false, $context);
        $data=(json_decode($result,true));
        $session=$data['data']['geoSession'];

        $setting=\App\Setting::firstOrNew([
            'key' => 'dpd_shipping_token'
        ]);
        $setting->key='dpd_shipping_token';
        $setting->value=$session;
        $setting->save();
        $dpd=Setting::where('key','dpd_shipping_token')->first();

        $this->info("Update Token:-".$dpd->value);

    }



}
