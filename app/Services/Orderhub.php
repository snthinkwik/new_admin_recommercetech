<?php namespace App\Services;

use App\Contracts\Orderhub as OrderhubContract;

class Orderhub implements OrderhubContract
{
	
	/**
	 * @var string
	 */
	protected $client_id;
	
	/**
	 * @var string
	 */
	protected $client_secret;
	
	protected $apiUrl = "https://api.orderhub.io";
	
	public function __construct($client_id = null, $client_secret = null)
	{
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
	}
	
	public function getAccessToken()
	{
		$postfields = "grant_type=client_credentials&client_id=".$this->client_id."&client_secret=".$this->client_secret;
		
		$attempts = 0;
		do {
			$attempts++;
			$ch = curl_init();
			
			curl_setopt($ch, CURLOPT_URL, $this->apiUrl . "/accessToken");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			
			curl_setopt($ch, CURLOPT_POST, 1);
			
			$headers = array();
			$headers[] = "Content-Type: application/x-www-form-urlencoded";
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			
			$result = curl_exec($ch);
			if (curl_errno($ch)) {
				dd('Error:' . curl_error($ch) . " - " . $result);
			}
			curl_close($ch);
			
			$result = json_decode($result);
			if(isset($result->access_token) || $attempts > 15) {
				break;
			}
		} while (true);
		$accessToken = $result->access_token;
		
		return $accessToken;
	}
	
	public function makeGetRequest($accessToken, $endpoint, $parameters = [])
	{
		$url = $this->apiUrl."/".$endpoint;
		
		if(count($parameters)) {
			$url .="?".http_build_query($parameters);
		}
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		
		
		$headers = array();
		$headers[] = "Authorization: Bearer $accessToken";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			dd('Error:' . curl_error($ch)." - ".$result);
		}
		curl_close ($ch);
		
		$result = json_decode($result);
		return $result;
	}
	
	public function makePostRequest($accessToken, $endpoint, $parameters)
	{
		$url = $this->apiUrl."/".$endpoint;
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		curl_setopt($ch, CURLOPT_POST, 1);
		
		$headers = array();
		$headers[] = "Content-Type: application/json";
		$headers[] = "Authorization: Bearer $accessToken";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			//alert(curl_error($ch)." - ".$result);
			dd('Error:' . curl_error($ch)." - ".$result);
		}
		curl_close ($ch);
		
		//alert($result);
		
		$result = json_decode($result);
		return $result;
	}
	
	public function makePutRequest($accessToken, $endpoint, $parameters, $etag)
	{
		$url = $this->apiUrl."/".$endpoint;
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		
		$headers = array();
		$headers[] = "Content-Type: application/json";
		$headers[] = "Authorization: Bearer $accessToken";
		$headers[] = "If-Match: $etag";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			//alert(curl_error($ch)." - ".$result);
			dd('Error:' . curl_error($ch)." - ".$result);
		}
		curl_close ($ch);
		
		//alert($result);
		
		//$result = json_decode($result);
		return $result;
	}
	
}