<?php namespace App\Contracts;

interface Orderhub {

	public function getAccessToken();

	public function makeGetRequest($accessToken, $endpoint, $parameters);

	public function makePostRequest($accessToken, $endpoint, $parameters);

	public function makePutRequest($accessToken, $endpoint, $parameters, $etag);

}