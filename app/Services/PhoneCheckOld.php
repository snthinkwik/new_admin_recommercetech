<?php namespace App\Services;

use App\Contracts\PhoneCheckOld as PhoneCheckOldContract;

class PhoneCheckOld implements PhoneCheckOldContract {

	protected $key;

	protected $username;

	protected $url = "https://clientapiv2.phonecheck.com/cloud/cloudDB/getAllDevices/";

	protected $urlDevice = "https://clientapiv2.phonecheck.com/cloud/cloudDB/getDeviceInfo/";

	public function __construct($key, $username)
	{
		$this->key = $key;
		$this->username = $username;
	}

	public function check($imei)
	{
		$data = [
			'Imei' => $imei,
			'UserName' => $this->username,
			'ApiKey' => $this->key
		];

		$data = json_decode(json_encode($data));

		$ch = curl_init($this->urlDevice);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$res = curl_exec($ch);
		curl_close($ch);

		$res = json_decode($res); // response is json

		return $res;
	}

	public function checkDate($date)
	{
		$data = [
			'Date' => $date,
			'UserName' => $this->username,
			'ApiKey' => $this->key
		];


		$ch = curl_init($this->url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$res = curl_exec($ch);
		curl_close($ch);

		$res = json_decode($res); // response is json

		return $res;
	}
}