<?php namespace App\Services;

use App\Contracts\ImeiCheck as ImeiCheckContract;
use Cache;

class ImeiCheck implements ImeiCheckContract {

	const BASE_URL = 'http://private.iunlocker.net/api/api_getserial_more.php';

	/**
	 * @var string
	 */
	protected $apiKey;

	/**
	 * @var string
	 */
	protected $error;

	/**
	 * @var string
	 */
	protected $errorType;

	/**
	 * @var int Cache time in minutes.
	 */
	protected $cacheTime;

	public function __construct($apiKey, $cacheTime = 1)
	{
		$this->apiKey = $apiKey;
		$this->cacheTime = intval($cacheTime);
	}

	public function checkImei($imei)
	{
		$this->error = $this->errorType = null;
		$imei = trim($imei);
		$cacheKey = "imei.$imei";

		if (!$imei) {
			$this->error = 'IMEI cannot be empty.';
			$this->errorType = self::ERROR_VALIDATION;
			return false;
		}
		// OK, so this isn't an IMEI checker anymore. It can also check serial numbers. This validation is for IMEIs.
		// If $imei is a number, it has to have 15 digits. If it's not just digits, it's considered a correct serial
		// number (if not empty, see the condition above).
		if (ctype_digit($imei) && strlen($imei) !== 15) {
			$this->error = 'IMEI is not correct. It needs to be 15 digits.';
			$this->errorType = self::ERROR_VALIDATION;
			return false;
		}

		if ($this->cacheTime && Cache::has($cacheKey)) {
			$cached = Cache::get($cacheKey);
			if ($cached['success']) {
				return $cached['data'];
			}
			else {
				$this->error = $cached['error'];
				$this->errorType = $cached['errorType'];
				return false;
			}
		}

		$data_url = http_build_query([
			'imei' => $imei,
			'key' => $this->apiKey,
		]);
		$data_len = strlen ($data_url);

		$http_response_header = null;
		$res = file_get_contents(
			'http://private.iunlocker.net/api/api_getserial_more.php',
			false,
			stream_context_create([
				'http' => [
					'method' => 'POST',
					'header' => "Content-type: application/x-www-form-urlencoded\r\n" .
						"Connection: close\r\nContent-Length: $data_len\r\n",
					'content' => $data_url
				],
			])
		);

		if (empty($http_response_header) || empty($http_response_header[0])) {
			$this->error = 'Unexpected API response.';
			$this->errorType = self::ERROR_API;
			if ($this->cacheTime) {
				Cache::put(
					$cacheKey,
					['success' => false, 'error' => $this->error, 'errorType' => $this->errorType],
					$this->cacheTime
				);
			}
			return false;
		}

		list(, $code) = explode(' ', $http_response_header[0]);
		if (!$code || intval($code{0}) !== 2) {
			$this->error = 'API returned error.';
			$this->errorType = self::ERROR_API;
			if ($this->cacheTime) {
				Cache::put(
					$cacheKey,
					['success' => false, 'error' => $this->error, 'errorType' => $this->errorType],
					$this->cacheTime
				);
			}
			return false;
		}

		preg_match(
			'/
				^
				(?<imei>\w+)\W+
				(?<serial>\w+)\W+
				(?<spec>.*?)\W+
				(?<status>\w+)
				<br>
			/xs',
			trim($res),
			$infoMatches
		);

		if (empty($infoMatches)) {
			$this->error = 'API didn\'t return a result.';
			$this->errorType = self::ERROR_API;
			if ($this->cacheTime) {
				Cache::put(
					$cacheKey,
					['success' => false, 'error' => $this->error, 'errorType' => $this->errorType],
					$this->cacheTime
				);
			}
			return false;
		}

		foreach (['spec', 'serial', 'status'] as $field) {
			if (empty($infoMatches[$field])) {
				$this->error = "API problem - field \"$field\".";
				$this->errorType = self::ERROR_API;
				if ($this->cacheTime) {
					Cache::put(
						$cacheKey,
						['success' => false, 'error' => $this->error, 'errorType' => $this->errorType],
						$this->cacheTime
					);
				}
				return false;
			}
		}

		$device_locked = $infoMatches['status'] === 'ON' ;

		$ret = [
			'serial' => $infoMatches['serial'],
			'spec' => $infoMatches['spec'],
			'locked' => $device_locked,
			'capacity' => null,
			'colour' => null,
		];

		if (preg_match('/(?<name>.*?)(?<capacity>\d+)\s*gb (?<colour>.*)/i', $infoMatches['spec'], $specMatches)) {
			$ret['capacity'] = intval($specMatches['capacity']);
			$ret['colour'] = ucfirst(strtolower(trim($specMatches['colour']))) ?: null;
			$ret['name'] = $specMatches['name'];
		}

		if ($this->cacheTime) {
			Cache::put($cacheKey, ['success' => true, 'data' => $ret], $this->cacheTime);
		}

		return $ret;
	}

	public function getLastError()
	{
		return $this->error;
	}

	public function getLastErrorType()
	{
		return $this->errorType;
	}

}