<?php namespace App\Contracts;

interface BackMarket {
	
	public function makeGetRequest($endpoint, $parameters);
	
	public function makePostRequest($endpoint, $parameters);
	
}