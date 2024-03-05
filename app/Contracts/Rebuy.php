<?php namespace App\Contracts;

interface Rebuy {

	public function gradingResults($data);

	public function deliveryConfirmation($tradeInItemId);
}