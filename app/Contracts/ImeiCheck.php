<?php namespace App\Contracts;

interface ImeiCheck {

	const ERROR_VALIDATION = 'validation';
	const ERROR_API = 'api';

	/**
	 * @param string $imei
	 * @return array
	 */
	public function checkImei($imei);

	/**
	 * @return string
	 */
	public function getLastError();

	/**
	 * @return string
	 */
	public function getLastErrorType();

}