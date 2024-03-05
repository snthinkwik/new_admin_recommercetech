<?php namespace App\Services;

class UrlShortener
{
	public static function getShortUrl($url)
	{
		$data = ['longUrl' => $url];
		$data = json_encode($data);
		$response = self::curlRequest($data);
		return $response->id;
	}

	protected static function curlRequest($data)
	{
		$url = 'https://www.googleapis.com/urlshortener/v1/url?key='.config('services.url_shortener.api_key');
		$headers = [];
		$headers[] = "Content-Type: application/json";
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_POST => true,
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_POSTFIELDS => $data
		]);

		$result = curl_exec($curl);
		curl_close($curl);

		$result = json_decode($result);

		return $result;
	}
}