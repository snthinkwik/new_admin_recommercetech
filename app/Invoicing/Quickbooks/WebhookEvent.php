<?php namespace App\Invoicing\Quickbooks;

use App\Model;

class WebhookEvent extends Model {

	const STATUS_NEW = 'New';
	const STATUS_PROCESSED = 'Processed';

	protected $table = 'quickbooks_webhook_events';

	protected $fillable = ['payload'];

	protected $casts = ['payload' => 'array'];

}
