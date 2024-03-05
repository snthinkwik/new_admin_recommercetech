<?php namespace App\Contracts;

interface Txtlocal {

	public function send($phone);

	public function sendRepair($phone, $name, $device, $detail);

	public function sendBulkRepair($phone, $name, $amount);

	public function sendCodeRequest($phone, $code);

	public function sendTriedToCall($phone, $name);

	public function sendRepairsPaid($phone, $amount, $count);

	public function sendAwaitingPayment($phone, $name, $saleId, $amount);

	public function sendTrackingNumber($phone, $name, $saleId, $courier, $trackingNumber);

	public function sendMessage($phone, $message);

	public function sendMessageSender($phone, $message, $sender);
}