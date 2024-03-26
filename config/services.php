<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'alerts_domain' => env('MAILGUN_ALERTS_DOMAIN'),
        'alerts_secret' => env('MAILGUN_ALERTS_SECRET'),
    ],

    'mailjet' => [
        'driver' => env('MAIJLET_DRIVER'),
        'host' => env('MAILJET_DOMAIN'),
        'username' => env('MAILJET_USERNAME'),
        'password' => env('MAILJET_PASSWORD')
    ],

    'mandrill' => [
        'secret' => '',
    ],

    'mobicode' => [
        'key' => env('MOBICODE_KEY'),
        'account_id' => env('MOBICODE_ACCOUNT_ID'),
    ],

    'quickbooks' => [
        'consumer' => [
            'key' => env('QUICKBOOKS_CONSUMER_KEY'),
            'secret' => env('QUICKBOOKS_CONSUMER_SECRET'),
        ],
        'webhook_token' => env('QUICKBOOKS_WEBHOOK_TOKEN'),
        'oauth2' => [
            'client_id' => env('QUICKBOOKS_CLIENT_ID'),
            'client_secret' => env('QUICKBOOKS_CLIENT_SECRET'),
            'base_url' => env('QUICKBOOKS_BASE_URL')
        ],
        'userid'=>[
            'backmarket'=>[
                'uk'=>env('QuickBookBackMarketUk'),
                'eu'=>env('QuickBookBackMarketEU')
            ],
            'mobileadvantage'=> env('QuickBookMobileAdvantage')


        ]
    ],

    'sage' => [
        'test_mode' => env('SAGE_MODE') !== 'live',
        'vendor' => env('SAGE_VENDOR'),
    ],

    'stripe' => [
        'model'  => 'App\User',
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
        'secret_key' => env('STRIPE_SECRET_KEY'),
    ],

    'txtlocal' => [
        'key' => env('TXTLOCAL_KEY'),
    ],

    'postcode' => [
        'account' => env('POSTCODE_ACCOUNT'),
        'password' => env('POSTCODE_PASSWORD'),
    ],

    'click2unlock' => [
        'key' => env('CLICK2UNLOCK_KEY'),
        'url' => env('CLICK2UNLOCK_URL'),
    ],

    'trg_uk' => [
        'url' => env('TRG_UK_URL')
    ],

    'phonecheck' => [
        'key' => env('PHONECHECK_KEY'),
        'username' => env('PHONECHECK_USERNAME'),
        'open_api_key' =>  env('PHONECHECK_OPEN_API_KEY'),
        'new_api_key'=>env("PHONE_CHECK_API_KEY")
    ],

    'phonecheck_old' => [
        'key' => env('PHONECHECK_OLD_KEY'),
        'username' => env('PHONECHECK_OLD_USERNAME'),
        'open_api_key' =>  env('PHONECHECK_OLD_OPEN_API_KEY'),
    ],

    'url_shortener' => [
        'api_key' => env('URL_SHORTENER_KEY'),
    ],

    'orderhub' => [
        'client_id' => env('ORDERHUB_CLIENT_ID'),
        'client_secret' => env('ORDERHUB_CLIENT_SECRET')
    ],

    'backmarket' => [
        'access_token' => env('BACKMARKET_ACCESS_TOKEN'),
        'url' => env('BACKMARKET_URL')
    ],

    'trg_stock' => [
        'url' => env('TRG_STOCK_URL'),
        'api_key' => env('TRG_STOCK_API_KEY')
    ],
    'ebay'=>[
        'client_id'=>env('EBAY_PROD_APP_ID'),
        'client_secret'=>env('EBAY_PROD_CERT_ID'),
        'RU_Name'=>env('EBAY_RUNAME')

    ],

    'ebay2'=>[
        'client_id'=>env('EBAY_PROD_APP_ID_SECOND'),
        'client_secret'=>env('EBAY_PROD_CERT_ID_SECOND'),
        'RU_Name'=>env('EBAY_RUNAME_SECOND')

    ],

    'ebay3'=>[
        'client_id'=>env('EBAY_PROD_APP_ID_THIRD'),
        'client_secret'=>env('EBAY_PROD_CERT_ID_THIRD'),
        'RU_Name'=>env('EBAY_RUNAME_THIRD')

    ],
    'ebay4'=>[
        'client_id'=>env('EBAY_PROD_APP_ID_FOURTH'),
        'client_secret'=>env('EBAY_PROD_CERT_ID_FOURTH'),
        'RU_Name'=>env('EBAY_RUNAME_FOURTH')

    ],
    'back_market'=>[
        'token'=>env('BACK_MARKET_KEY')
    ],
    'mobile_advantage'=>[
        'secret'=>env('MOBILE_ADVANTAGE_SECRET',''),
        'client_id'=>env('MOBILE_ADVANTAGE_CLIENT_ID',''),
        'device'=>env('MOBILE_ADVANTAGE_DEVICE','')

    ],
    'DPD_Shipping'=>[
        'user_name'=>env('DPD_USER',''),
        'password'=>env('DPD_PASSWORD',''),
        'account'=>env('ACCOUNT_NUMBER','')
    ],
    'imei_check_api_key'=>env('IMEI_CHECK_API_KEY',''),
    'imei_check_service_code'=>env('IMEI_CHECK_SERVICE_CODE','')

];


