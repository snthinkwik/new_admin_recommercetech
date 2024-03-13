<?php

namespace App\Http\Controllers;

use Huddle\Zendesk\Facades\Zendesk;
use Illuminate\Http\Request;

class ZendeskController extends Controller
{
    public function getIndex()
    {
        $res = Zendesk::search(['query' => 'type:ticket tags:recomm']);
        dd($res);
        return response()->json($res);
    }

    public function getTicket($id)
    {
        $res = Zendesk::ticket($id)->find();

        return response()->json($res);
    }

    public function getTags()
    {
        $res = Zendesk::tags()->findAll();

        return response()->json($res);
    }

    public function getTicketComments($id)
    {
        $res = Zendesk::ticket($id)->comments()->findAll();

        $comments = collect($res->comments);

        return response()->json($comments);
    }
}
