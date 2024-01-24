<?php namespace App\Contracts;

interface Click2Unlock {

	public function gsxCheck($imei);

	public function lockCheck($imei);

	public function getReport($check_id);

	public function unlock($imei, $service_id);

	public function iCloudRemove($imei);

	public function getBalance();

	public function getServices();
	public function getBlackListed($imei);

}