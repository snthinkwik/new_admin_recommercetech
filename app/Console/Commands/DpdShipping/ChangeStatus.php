<?php namespace App\Console\Commands\DpdShipping;

use App\Stock;
use App\TrackingBackMarketDPDShipping;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ChangeStatus extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'back-market:status-change';

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

        $trackingBackMarket=TrackingBackMarketDPDShipping::whereNull('status')->where('platfrom',Stock::PLATFROM_BACKMARCKET)->get();
        foreach($trackingBackMarket as $data)
        {
            $postData=  [
                "order_id"=>$data->order_id,
                "new_state"=>3,
                "tracking_number"=>$data->tracking_number,
                'imei'=>$data->imei
            ];

            try {
                $header = array(
                    "Authorization:Basic ".config('services.back_market.token'),
                    "Accept-Language:en-gb"
                );
                $ua = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.A.B.C Safari/525.13';

                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://www.backmarket.fr/ws/orders/".$data->order_id,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                    CURLOPT_HTTPHEADER => $header,
                    CURLOPT_USERAGENT=>$ua,
                    CURLOPT_POSTFIELDS => $postData
                ));

                $result = curl_exec($curl);
                $this->info($result);

                if (!$result) {
                    die("Connection Failure");
                }
                $finalResult = (array) json_decode($result);
                $singleData=TrackingBackMarketDPDShipping::find($data->id);
                $singleData->status='done';
                $singleData->save();

            } catch (\Exception $e){
                $this->error($e->getMessage());
            }


        }



    }



}
