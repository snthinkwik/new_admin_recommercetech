<?php namespace App\Services;

use App\Contracts\Quickbooks as QuickbooksContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use QuickBooksOnline\API\Data\IPPCustomer;
use QuickBooksOnline\API\DataService\DataService;
use Setting;

class Quickbooks implements QuickbooksContract {

	/**
	 * @var string
	 */
	protected $ClientID;

	/**
	 * @var string
	 */
	protected $ClientSecret;

	protected $authMode;

	protected $scope;

	protected $RedirectURI;

	/**
	 * @var string
	 */
	protected $baseUrl;

	public function __construct($clientID, $clientSecret, $baseUrl)
	{
		$this->ClientID = $clientID;
		$this->ClientSecret = $clientSecret;
		$this->baseUrl = $baseUrl;
		$this->authMode = 'oauth2';
		$this->scope = "com.intuit.quickbooks.accounting";
		$this->RedirectURI = route('admin.quickbooks.oauth.callback');
	}

	public function connectToQuickbooks()
	{

		$dataService = $this->getOAuth2();

		$OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
		$parseUrl = $this->parseAuthRedirectUrl($_SERVER['QUERY_STRING']);

		$accessToken = $OAuth2LoginHelper->exchangeAuthorizationCodeForToken($parseUrl['code'], $parseUrl['realmId']);

		$dataService->updateOAuth2Token($accessToken);
		Setting::set('quickbooks.oauth2.access_token', $accessToken->getAccessToken());

		Setting::set('quickbooks.oauth2.refresh_token', $accessToken->getRefreshToken());
		Setting::set('quickbooks.oauth2.realm_id', $accessToken->getRealmID());
		Setting::set('quickbooks.oauth2.access_token_expires_at', $accessToken->getAccessTokenExpiresAt());
		Setting::set('quickbooks.oauth2.refresh_token_expires_at', $accessToken->getRefreshTokenExpiresAt());
		Setting::set('quickbooks.oauth2.session_access_token', serialize($accessToken));
        Setting::save();

        return
			'<script>
				window.opener.location = "' . route('admin.quickbooks.oauth.success') .'";
				window.close()
			</script>';
	}

	protected function parseAuthRedirectUrl($url)
	{
		parse_str($url,$qsArray);
		return array(
			'code' => $qsArray['code'],
			'realmId' => $qsArray['realmId']
		);
	}

	public function refreshToken()
	{

		try {
			$dataService = $this->getOAuth2(true);
			$OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
			$refreshedAccessTokenObj = $OAuth2LoginHelper->refreshToken();
			$dataService->updateOAuth2Token($refreshedAccessTokenObj);
			Setting::set('quickbooks.oauth2.access_token', $refreshedAccessTokenObj->getAccessToken());
			Setting::set('quickbooks.oauth2.refresh_token', $refreshedAccessTokenObj->getRefreshToken());
			Setting::set('quickbooks.oauth2.access_token_expires_at', $refreshedAccessTokenObj->getAccessTokenExpiresAt());
			Setting::set('quickbooks.oauth2.refresh_token_expires_at', $refreshedAccessTokenObj->getRefreshTokenExpiresAt());
			Setting::set('quickbooks.oauth2.session_access_token', serialize($refreshedAccessTokenObj));
			Setting::save();

			return "Token Refreshed, Expires At: ".$refreshedAccessTokenObj->getAccessTokenExpiresAt()." | ".Setting::get('quickbooks.oauth2.access_token_expires_at');
		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}

	public function getOAuth2($refresh = false)
	{
		if($refresh) {
			return DataService::Configure([
				'auth_mode' => $this->authMode,
				'ClientID' => $this->ClientID,
				'ClientSecret' => $this->ClientSecret,
				'RedirectURI' => $this->RedirectURI,
				'baseUrl' => $this->baseUrl,
				'refreshTokenKey' => Setting::get('quickbooks.oauth2.refresh_token'),
				'QBORealmID' => Setting::get('quickbooks.oauth2.realm_id')
			]);
		}

		return DataService::Configure([
			'auth_mode' => 'oauth2',
			'ClientID' => $this->ClientID,
			'ClientSecret' => $this->ClientSecret,
			'RedirectURI' => $this->RedirectURI,
			'scope' => $this->scope,
			'baseUrl' => $this->baseUrl
		]);
	}

	public function getDataService()
	{
        $quickbooks = app('App\Contracts\Quickbooks');
		$dataService = $this->getOAuth2();
		$accessToken = unserialize(Setting::get('quickbooks.oauth2.session_access_token'));
		$dataService->updateOAuth2Token($accessToken);
		return $dataService;
	}

	public function getCompanyInfo()
	{
		try {
			$dataService = $this->getDataService();

			$companyInfo = $dataService->getCompanyInfo();

			return $companyInfo;
		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}

	public function checkAccessTokenExpiresAt()
	{
		$accessToken = unserialize(Setting::get('quickbooks.oauth2.session_access_token'));

		return $accessToken->getAccessTokenExpiresAt() > Carbon::now();
	}

}
