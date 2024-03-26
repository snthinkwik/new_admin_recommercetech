<?php

namespace App\Console\Commands;

use App\Models\AccessToken;
use Illuminate\Console\Command;

class AddEbayToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'ebay:token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $token="v^1.1#i^1#r^0#I^3#f^0#p^3#t^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";
        $expriesIn=7200;
        $refreshToken="v^1.1#i^1#f^0#r^1#p^3#I^3#t^Ul4xMF8wOjNCQjY3NTk3MzM0RDQxOEVDRTVBMEE2NTc1MjUzQ0E5XzNfMSNFXjI2MA==";
        $refreshTokenExpiresin="47304000";
        $tokenType="User Access Token";


        $accessToken=AccessToken::firstOrNew([
            'platform'=>'ebay'
        ]);

        $accessToken->platform='ebay';
        $accessToken->access_token=$token;
        $accessToken->expires_in=$expriesIn;
        $accessToken->refresh_token=$refreshToken;
        $accessToken->refresh_token_expires_in=$refreshTokenExpiresin;
        $accessToken->token_type=$tokenType;
        $accessToken->save();

        $this->info("token successfully updated");

    }
}
