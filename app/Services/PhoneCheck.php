<?php namespace App\Services;

use App\Contracts\PhoneCheck as PhoneCheckContract;

class PhoneCheck implements PhoneCheckContract {

	protected $key;

	protected $username;

	protected $url = "https://clientapiv2.phonecheck.com/cloud/cloudDB/GetAllDevices/";

	protected $urlDevice = "https://clientapiv2.phonecheck.com/cloud/cloudDB/GetDeviceInfo";
	protected $A4ReportUrl='http://clientapiv2.phonecheck.com/cloud/cloudDB/A4Report';
    protected $EraserReport='https://clientapiv2.phonecheck.com/cloud/cloudDB/EraserReport';
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

	public function getReports($report_id){

        $data = [
            'report_id' => $report_id,
            'username' => $this->username,
            'apikey' => $this->key
        ];





        $ch = curl_init($this->A4ReportUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);

        curl_close($ch);


        return $res;

	}



    public function getReportsEraserReport($report_id){

        $data = [
            'report_id' => $report_id,
            'username' => $this->username,
            'apikey' => $this->key
        ];





        $ch = curl_init($this->EraserReport);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);

        curl_close($ch);


        return $res;

    }


}
