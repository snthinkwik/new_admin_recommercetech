<?php namespace App\Services;

use App\Contracts\BackMarket as BackMarketContract;

class BackMarket implements BackMarketContract
{
	
	/**
	 * @var string
	 */
	protected $accessToken;
	
	/**
	 * @var string
	 */
	protected $baseUrl;
	
	public function __construct($accessToken = null, $baseUrl = null)
	{
		$this->accessToken = $accessToken;
		$this->baseUrl = $baseUrl;
	}
	
	public function makeGetRequest($endpoint, $parameters = [])
	{
		$res = [
			'status' => 'error',
			'content' => null
		];
		$url = $this->baseUrl."/".$endpoint;
		
		if(count($parameters)) {
			$url .="?".http_build_query($parameters);
		}
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		$headers = array();
		$headers[] = "Authorization: Basic $this->accessToken";
		$headers[] = "Content-type: application/json";
		$headers[] = "Accept: application/json";
		$headers[] = "Accept-Language: en-gb";
		$headers[] = "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.90 Safari/537.36";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			$res['status'] = 'error';
			$res['content'] = curl_error($ch);
		} else {
			$res['status'] = 'success';
			$res['content'] = json_decode($result);
		}
		curl_close ($ch);
		
		$res = json_decode(json_encode($res));
		return $res;
	}
	
	public function makePostRequest($endpoint, $parameters)
	{
		$res = [
			'status' => 'error',
			'content' => null
		];
		$url = $this->baseUrl."/".$endpoint;

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		curl_setopt($ch, CURLOPT_POST, 1);

		$headers = array();
		$headers[] = "Authorization: Basic $this->accessToken";
		$headers[] = "Content-type: application/json";
		$headers[] = "Accept: application/json";
		$headers[] = "Accept-Language: en-gb";
		$headers[] = "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.90 Safari/537.36";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			$res['status'] = 'error';
			$res['content'] = curl_error($ch);
		} else {
			$res['status'] = 'success';
			$res['content'] = json_decode($result);
		}
		curl_close ($ch);

		$res = json_decode(json_encode($res));
		return $res;
	}
}