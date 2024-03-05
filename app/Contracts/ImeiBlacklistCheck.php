<?php namespace App\Contracts;

interface ImeiBlacklistCheck {

	public function check($imei);

	public function gsxCheck($imei);

}