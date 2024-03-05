<?php namespace App\Observers;

use Log;
Use App\EmailTracking;
use App\User;
use Illuminate\Events\Dispatcher;

class MailObserver {

	public function onMailSent($message)
	{
		if($message->getBody() && $message->getSubject() && $message->getTo()) {
			if(isset(array_keys($message->getTo())[0]) && isset(array_keys($message->getFrom())[0])) {
				$user = User::withUnregistered()->where('email', array_keys($message->getTo())[0])->first();
				if ($user && $user->id) {
					$email = New EmailTracking();
					$email->user_id = $user->id;
					$email->type = "Sent";
					$email->to = array_keys($message->getTo())[0];
					$email->from = array_keys($message->getFrom())[0];
					$email->subject = $message->getSubject();
					$email->body = $message->getBody();
					$email->message_id = $message->getId();
					$emailId = $message->getHeaders()->get('email_id');
					if($emailId)
						$email->email_id = $emailId->getValue();
					if(isset($message->getTo()[array_keys($message->getTo())[0]])) {
						$email->to_name = $message->getTo()[array_keys($message->getTo())[0]];
					}
					if(isset($message->getFrom()[array_keys($message->getFrom())[0]])) {
						$email->from_name = $message->getFrom()[array_keys($message->getFrom())[0]];
					}
					$email->save();
				}
			}
		}
	}

	public function subscribe(Dispatcher $events)
	{
		$events->listen('mailer.sending', 'App\Observers\MailObserver@onMailSent');
	}

}