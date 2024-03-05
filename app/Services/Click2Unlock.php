<?php namespace App\Services;

use App\Contracts\Click2Unlock as Click2UnlockContract;
use Carbon\Carbon;

class Click2Unlock implements Click2UnlockContract {

	protected $key;

	protected $url;

	public function __construct($key, $url)
	{
		$this->key = $key;
		$this->url = $url;
	}

	public function gsxCheck($imei)
	{
		$data = [
			'key' => $this->key,
			'imei' => $imei,
			'extended' => "true",
			'service_id' => 118
		];

		$ch = curl_init($this->url."/check");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$res = curl_exec($ch);
		curl_close($ch);

		$res = json_decode($res); // response is json

		return $res;
	}

	public function lockCheck($imei)
	{
		$data = [
			'key' => $this->key,
			'imei' => $imei,
			'extended' => "true",
			'service_id' =>  118
		];

		$ch = curl_init($this->url."/check");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$res = curl_exec($ch);
		curl_close($ch);

		$res = json_decode($res); // response is json

		return $res;
	}

	public function getReport($check_id)
	{
		$query = http_build_query([
			'key' => $this->key,
			'check_id' => $check_id
		]);

		$url = $this->url."/results?".$query;
		$res = file_get_contents(
			$url,
			false
		);

		$res = json_decode($res); // response is json

		return $res;
	}

	public function unlock($imei, $service_id)
	{
		$data = [
			'key' => $this->key,
			'imei' => $imei,
			'extended' => "false",
			'service_id' => $service_id
		];

		$ch = curl_init($this->url."/check");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$res = curl_exec($ch);
		curl_close($ch);

		$res = json_decode($res); // response is json

		return $res;
	}

	public function iCloudRemove($imei)
	{
		$data = [
			'key' => $this->key,
			'imei' => $imei,
			'extended' => "false",
			'service_id' => 18
		];

		$ch = curl_init($this->url."/check");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$res = curl_exec($ch);
		curl_close($ch);

		$res = json_decode($res); // response is json

		return $res;
	}

	public function getBalance()
	{

		$query = http_build_query([
			'key' => $this->key,
		]);

		$url = $this->url."/balance?".$query;
		$res = file_get_contents(
			$url,
			false
		);

		$res = json_decode($res); // response is json

		return $res;
	}

	public function getServices()
	{
		$query = http_build_query([
			'key' => $this->key,
		]);

		$url = $this->url."/get-services?".$query;
		$res = file_get_contents(
			$url,
			false
		);

		$res = json_decode($res); // response is json

		return $res;
	}

	public function getBlackListed($imei){


        $data = [
            'key' => $this->key,
            'imei' => $imei,
            'service_id' => 118
        ];

        $ch = curl_init($this->url."/check-instant");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($res); // response is json

        return $res;


    }
}
