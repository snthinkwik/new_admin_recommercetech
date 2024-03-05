<?php namespace App\Services;

use App\Contracts\TrgStock as TrgStockContract;
use Carbon\Carbon;

class TrgStock implements TrgStockContract {
	
	protected $key;
	
	protected $url;
	
	public function __construct($key, $url)
	{
		$this->key = $key;
		$this->url = $url;
	}
	
	public function createEbayFeesInvoice($data)
	{
		$url = $this->url."/recomm/api/create-ebay-fees-invoice";
		$data = ['data' => $data];
		$headers = [];
		$headers[] = 'api-key: '.$this->key;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$res = curl_exec($ch);
		
		curl_close($ch);
		
		$res = json_decode($res); // response is json
		
		return $res;
	}
	
	public function getInvoiceDocument($id)
	{
		$url = $this->url."/recomm/api/invoice-document/$id";
		
		$headers = [];
		$headers[] = 'api-key: '.$this->key;
		
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$res = curl_exec($ch);
		curl_close($ch);
		$res = json_decode($res); // response is json
		
		return $res;
	}
}