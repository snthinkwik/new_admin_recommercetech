<?php

namespace App\Http\Controllers;

use App\Contracts\Quickbooks;
use App\Invoicing\Quickbooks\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use QuickBooksOnline\API\DataService\DataService;
use OAuth;
use Setting;

class QuickbooksController extends Controller
{
    public function getIndex(Quickbooks $quickbooks)
    {

        $dataService = $quickbooks->getOAuth2();
        $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
        $authUrl = $OAuth2LoginHelper->getAuthorizationCodeURL();


        return view('admin.quickbooks.index', compact('authUrl'));
    }

    public function postWebhook()
    {
        $payload = file_get_contents('php://input');
        // file_put_contents(base_path('/webhook-test.txt'), $payload . "\n\n" . print_r($_SERVER, true));
        $hash = hash_hmac('sha256', $payload, config('services.quickbooks.webhook_token'));
        $signature = bin2hex(base64_decode($_SERVER['HTTP_INTUIT_SIGNATURE']));
        if ($hash === $signature) {
            WebhookEvent::create(['payload' => json_decode($payload, true)]);
            die('ok');
        }
        else {
            die("Signature incorrect");
        }
    }

    public function getOAuthStart(Request $request)
    {
        die('test');
    }

    public function getOAuthCallback(Request $request)
    {
        $quickbooks = app('App\Contracts\Quickbooks');

        return $quickbooks->connectToQuickbooks();
    }

    public function getOAuthSuccess()
    {
        return redirect()->route('admin.quickbooks')->with('messages.success', 'Quickbooks credentials saved.');
    }

    public function getOAuth2RefreshToken(Quickbooks $quickbooks)
    {

        return $quickbooks->refreshToken();
    }

    public function getOAuth2CompanyInfo(Quickbooks $quickbooks)
    {
        $CompanyInfo = $quickbooks->getCompanyInfo();

        return "CompanyName: ".$CompanyInfo->CompanyName;
    }

    protected function getOAuth()
    {
        return new OAuth(
            config('services.quickbooks.consumer.key'),
            config('services.quickbooks.consumer.secret'),
            OAUTH_SIG_METHOD_HMACSHA1,
            OAUTH_AUTH_TYPE_URI
        );
    }

    protected function getOAuth2($refresh = false)
    {

        if($refresh) {
            return DataService::Configure([
                'auth_mode' => 'oauth2',
                'ClientID' => "BBCENgNvY6ADra2TgAHXCr22owZCpTSp5XBWPi4kCYUdiaGkAT",
                'ClientSecret' => "AzCJCxAsYPjfiYruhUdHojfjaNe3YHGgN30xusp4",
                'RedirectURI' => route('admin.quickbooks.oauth.callback'),
                //'scope' => "com.intuit.quickbooks.accounting",
                'baseUrl' => "Development",
                'refreshTokenKey' => Setting::get('quickbooks.oauth2.refresh_token'),
                'QBORealmID' => Setting::get('quickbooks.oauth2.realm_id')
            ]);
        }

        return DataService::Configure([
            'auth_mode' => 'oauth2',
            'ClientID' => "BBCENgNvY6ADra2TgAHXCr22owZCpTSp5XBWPi4kCYUdiaGkAT",
            'ClientSecret' => "AzCJCxAsYPjfiYruhUdHojfjaNe3YHGgN30xusp4",
            'RedirectURI' => route('admin.quickbooks.oauth.callback'),
            'scope' => "com.intuit.quickbooks.accounting",
            'baseUrl' => "Development"
        ]);
    }

    public function getQuery(Request $request)
    {
        $query = $request->get('query') ? : "Item";
        $quickbooks = app('App\Contracts\Quickbooks');
        $knownItems = $quickbooks->getDataService()->Query("select * from $query");
        $itemsByName = [];
        foreach ($knownItems as $item) {
            $itemsByName[$item->Name] = $item;
        }
        dd($itemsByName);
    }
}
