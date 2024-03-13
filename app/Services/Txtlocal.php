<?php namespace App\Services;

use App\Contracts\Txtlocal as TxtlocalContract;

/**
 * @property string phone
 * @property string response
 * @property string status
 */
class Txtlocal implements TxtlocalContract {

	/**
	 * @var string
	 */
	protected $key;

	protected $apiUrl = "https://api.txtlocal.com/send/";

	public function __construct($key)
	{
		$this->key = $key;
	}

	public function send($phone)
	{
		// check phone number, it should send SMS only if number starts with 07 or 447 or 00447

		if(substr($phone, 0, 2) == "07" || substr($phone,0, 3) == "447" || substr($phone,0,5) == "00447") {

			$apiKey = urlencode($this->key);
			// Message details

			$sender = urlencode('RCT');
			$message = rawurlencode('Thank you for registering with RCT. Want to place an order? Give us a call on 01494 303600 or visit https://www.recomm.co.uk');


			// Prepare data for POST request
			$data = array('apikey' => $apiKey, 'numbers' => $phone, "sender" => $sender, "message" => $message);

			// Send the POST request with cURL
			$ch = curl_init($this->apiUrl);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($ch);
			curl_close($ch);

			// Process your response here
			//alert($response);
			return $response;
		} else {
			return false;
		}
	}

	public function sendRepair($phone, $name, $device, $detail)
	{
		$apiKey = urlencode($this->key);
		// Message details

		$sender = urlencode('RCT');
		$message = rawurlencode("Hi ".$name.", we have assigned a ".$device." to be repaired by yourself. The fault detail is: ".$detail.". These need completing in 24 hours. Regards, RCT");


		// Prepare data for POST request
		$data = array('apikey' => $apiKey, 'numbers' => $phone, "sender" => $sender, "message" => $message);

		// Send the POST request with cURL
		$ch = curl_init($this->apiUrl);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

		// Process your response here
		//alert($response);
		return $response;
	}

	public function sendBulkRepair($phone, $name, $amount)
	{
		$apiKey = urlencode($this->key);
		// Message details

		$sender = urlencode('RCT');
		$message = rawurlencode("Hi ".$name.", we have assigned ".$amount." of repairs to you. Please come and collect them. Regards, RCT");


		// Prepare data for POST request
		$data = array('apikey' => $apiKey, 'numbers' => $phone, "sender" => $sender, "message" => $message);

		// Send the POST request with cURL
		$ch = curl_init($this->apiUrl);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

		// Process your response here
		//alert($response);
		return $response;
	}

	public function sendCodeRequest($phone, $code)
	{
		$apiKey = urlencode($this->key);
		// Message details

		$sender = urlencode('RCT');
		$message = rawurlencode("Code: ".$code);


		// Prepare data for POST request
		$data = array('apikey' => $apiKey, 'numbers' => $phone, "sender" => $sender, "message" => $message);

		// Send the POST request with cURL
		$ch = curl_init($this->apiUrl);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

		//$response = $code;

		// Process your response here
		//alert($response);
		return $response;
	}

	public function sendTriedToCall($phone, $name)
	{
		if(substr($phone, 0, 2) == "07" || substr($phone,0, 3) == "447" || substr($phone,0,4) == "+447" || substr($phone,0,5) == "00447") {

			$apiKey = urlencode($this->key);
			// Message details

			$sender = urlencode('RCT');
			$message = rawurlencode("Hi $name, I just tried to call but did not get through. Please call me back on 01494 303600 opt 2. Thanks, Dan @ RCT");


			// Prepare data for POST request
			$data = array('apikey' => $apiKey, 'numbers' => $phone, "sender" => $sender, "message" => $message);

			// Send the POST request with cURL
			$ch = curl_init($this->apiUrl);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($ch);
			curl_close($ch);

			// Process your response here
			//alert($response);
			return $response;
		} else {
			return false;
		}
	}

	public function sendRepairsPaid($phone, $amount, $count)
	{
		$apiKey = urlencode($this->key);
		// Message details

		$sender = urlencode('RCT');
		$message = rawurlencode("You have been paid ".money_format($amount)." for $count of repairs. Regards, Chris @ RCT");


		// Prepare data for POST request
		$data = array('apikey' => $apiKey, 'numbers' => $phone, "sender" => $sender, "message" => $message);

		// Send the POST request with cURL
		$ch = curl_init($this->apiUrl);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

		// Process your response here
		//alert($response);
		return $response;
	}

	public function sendAwaitingPayment($phone, $name, $saleId, $amount)
	{
		if(substr($phone, 0, 2) == "07" || substr($phone,0, 3) == "447" || substr($phone,0,4) == "+447" || substr($phone,0,5) == "00447") {
			$apiKey = urlencode($this->key);
			// Message details

			$sender = urlencode('RCT');
			$message = rawurlencode("Hi " . $name . ", we have not received payment yet for order " . $saleId . ". The order value is " . $amount . ". Please reply to my email this morning at 8.30am to notify us of when payment can be made. Regards, Recomm");

			// Prepare data for POST request
			$data = array('apikey' => $apiKey, 'numbers' => $phone, "sender" => $sender, "message" => $message);

			$ch = curl_init($this->apiUrl);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($ch);
			curl_close($ch);

			//alert($response);
			return $response;
		} else {
			return false;
		}
	}

	public function sendTrackingNumber($phone, $name, $saleId, $courier, $trackingNumber)
	{
		if(substr($phone, 0, 2) == "07" || substr($phone,0, 3) == "447" || substr($phone,0,4) == "+447" || substr($phone,0,5) == "00447") {
			$apiKey = urlencode($this->key);
			// Message details

			$sender = urlencode('RCT');
			$message = rawurlencode("Hi " . $name . ", We've now dispatched your order no. " . $saleId . " and it's been sent with " . $courier . ". The tracking number is: " . $trackingNumber . ". Regards, RCT");

			// Prepare data for POST request
			$data = array('apikey' => $apiKey, 'numbers' => $phone, "sender" => $sender, "message" => $message);

			$ch = curl_init($this->apiUrl);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($ch);
			curl_close($ch);


			//alert($response);
			return $response;
		} else {
			return false;
		}
	}

	public function sendMessage($phone, $message, $title='RCT')
	{
		$apiKey = urlencode($this->key);
		// Message details

		$sender = urlencode($title);
		$message = rawurlencode($message);

		// Prepare data for POST request
		$data = array('apikey' => $apiKey, 'numbers' => $phone, "sender" => $sender, "message" => $message);

		// Send the POST request with cURL
		$ch = curl_init($this->apiUrl);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

		// Process your response here
		//alert($response);
		return $response;
	}

	public function sendMessageSender($phone, $message, $sender)
	{
		$apiKey = urlencode($this->key);
		// Message details

		$sender = urlencode($sender);
		$message = rawurlencode($message);

		// Prepare data for POST request
		$data = array('apikey' => $apiKey, 'numbers' => $phone, "sender" => $sender, "message" => $message);

		// Send the POST request with cURL
		$ch = curl_init($this->apiUrl);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);

		// Process your response here
		//alert($response);
		return $response;
	}

}
