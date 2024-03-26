<?php

namespace App\Console\Commands;

use App\Models\AccessToken;
use Illuminate\Console\Command;

class RefreshTokenAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ebay:refresh-token';


    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $accessFirstToken=AccessToken::where('platform','ebay')->first();
        $headers=ebayBasicToken(config('services.ebay.client_id'),config('services.ebay.client_secret'));
        $newAccessToken= getEbayRefreshTokenBaseToken($headers,$accessFirstToken->refresh_token);
        $accessFirstToken->access_token=$newAccessToken['access_token'];
        $accessFirstToken->expires_in=$newAccessToken['expires_in'];
        $accessFirstToken->save();



        $accessSecondToken=AccessToken::where('platform','ebay-second')->first();
        $authorization = base64_encode(config('services.ebay2.client_id').':'.config('services.ebay2.client_secret'));
        $headers = array("Authorization: Basic {$authorization}", "Content-Type: application/x-www-form-urlencoded");
        $newAccessToken= getEbayRefreshTokenBaseToken($headers,$accessSecondToken->refresh_token);
        $accessSecondToken->access_token=$newAccessToken['access_token'];
        $accessSecondToken->expires_in=$newAccessToken['expires_in'];
        $accessSecondToken->save();





        $accessThirdToken=AccessToken::where('platform','ebay-third')->first();
        $authorization = base64_encode(config('services.ebay3.client_id').':'.config('services.ebay3.client_secret'));
        $headers = array("Authorization: Basic {$authorization}", "Content-Type: application/x-www-form-urlencoded");
        $newAccessToken= getEbayRefreshTokenBaseToken($headers,$accessThirdToken->refresh_token);
        $accessThirdToken->access_token=$newAccessToken['access_token'];
        $accessThirdToken->expires_in=$newAccessToken['expires_in'];
        $accessThirdToken->save();




        $accessForthToken=AccessToken::where('platform','ebay-forth')->first();
        $authorization = base64_encode(config('services.ebay4.client_id').':'.config('services.ebay4.client_secret'));
        $headers = array("Authorization: Basic {$authorization}", "Content-Type: application/x-www-form-urlencoded");

        $newAccessToken= getEbayRefreshTokenBaseToken($headers,$accessForthToken->refresh_token);

        $accessForthToken->access_token=$newAccessToken['access_token'];
        $accessForthToken->expires_in=$newAccessToken['expires_in'];
        $accessForthToken->save();


    }
}
