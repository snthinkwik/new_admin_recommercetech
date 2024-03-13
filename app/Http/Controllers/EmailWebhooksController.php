<?php

namespace App\Http\Controllers;

use App\Models\EmailWebhook;
use Illuminate\Http\Request;

class EmailWebhooksController extends Controller
{
    public function postWebhook()
    {
        $response = file_get_contents('php://input');

        $emailWebhook = new EmailWebhook();
        $emailWebhook->status = EmailWebhook::STATUS_NEW;
        $emailWebhook->response = $response;
        $emailWebhook->save();

        die("ok");

    }
}
