<?php namespace App\Events\User;

use App\Events\Event;

use App\User;
use Illuminate\Queue\SerializesModels;

class Registered extends Event {

	use SerializesModels;
	
	/**
	 * @var User
	 */
	public $user;
	
	public function __construct(User $user)
	{
		$this->user = $user;
	}

}
