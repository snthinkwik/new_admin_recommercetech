<?php namespace App\Contracts;

interface Quickbooks {

	public function getOAuth2();
	
	public function getDataService();
	
	public function connectToQuickbooks();
	
	public function getCompanyInfo();
	
	public function refreshToken();
	
	public function checkAccessTokenExpiresAt();
	
}