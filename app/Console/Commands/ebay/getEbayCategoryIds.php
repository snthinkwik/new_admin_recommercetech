<?php namespace App\Console\Commands\ebay;

use App\Models\AccessToken;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use GuzzleHttp\Client;

class getEbayCategoryIds extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'ebay:get-category-id';

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
	public function handle()
	{
	    //https://api.ebay.com/commerce/taxonomy/v1/category_tree/0/get_category_suggestions?q=Cell Phone Accessories

        $accessToken = AccessToken::where('platform', 'ebay')->first();

        $currentTime = Carbon::now();
        $addTime = Carbon::parse($accessToken->updated_at)->addSecond($accessToken->expires_in);

        $BasicHeaders = ebayBasicToken(config('services.ebay.client_id'),config('services.ebay.client_secret'));
        $this->comment("Initialization....");

        if ($currentTime->gt($addTime)) {

            $this->comment("Access Token is Expired....");
            $this->comment("New Generated Access Token....");
            $newAccessToken = getEbayRefreshTokenBaseToken($BasicHeaders, $accessToken->refresh_token);
            $accessToken->access_token = $newAccessToken['access_token'];
            $accessToken->expires_in = $newAccessToken['expires_in'];
            $accessToken->save();
            sleep(1);

        }


        $client = new Client();
        $client->setDefaultOption('headers', array('Authorization' => "Bearer {$accessToken->access_token}"));



        $categories=Category::whereNull('eBay_category_id')->get();


        foreach ($categories as $category){

            $response = $client->get("https://api.ebay.com/commerce/taxonomy/v1/category_tree/0/get_category_suggestions?q=".$category->name);
            $data = $response->json();


            $findCategory=Category::find($category->id);
            $findCategory->eBay_category_id=$data['categorySuggestions'][0]['category']['categoryId'];
            $findCategory->save();

            $this->info("eBay Category Id: ".$findCategory->eBay_category_id.' assigned to '. $findCategory->name." Category");

        }





    }



}
