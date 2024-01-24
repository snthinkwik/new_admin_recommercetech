<?php namespace App\Contracts;

interface TrgStock {
	
	public function getInvoiceDocument($id);
	
	public function createEbayFeesInvoice($data);
	
}