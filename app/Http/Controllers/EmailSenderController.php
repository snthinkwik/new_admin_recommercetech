<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Email;
use App\Models\EmailDraft;
use App\Models\EmailTracking;
use App\Models\EmailWebhook;
use App\Http\Requests\Emails\EmailRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

class EmailSenderController extends Controller
{
    public function getIndex()
    {
        $emails = Email::orderBy('id', 'desc')->paginate(config('app.pagination'));
        foreach($emails as $email) {
            if($email->status == Email::STATUS_ERROR) {
                $email->sent_to = EmailTracking::where('subject', $email->subject)->count();
            }
        }
        return view('email-sender.index', compact('emails'));
    }

    public function postTestSend(Request $request)
    {


        if (!$request->recipient || !filter_var($request->recipient, FILTER_VALIDATE_EMAIL)) {
            die("Email \"$request->recipient\" is incorrect.");
        }

        try {
            Mail::send(
                'emails.email-sender.marketing-message',
                [
                    'body' => Email::getBodyHtml($request->body, User::where('type', 'user')->first()),
                    'fromName' => $request->from_name,
                ],
                function(Message $message) use ($request) {
                    $message->from(
                        $request->from_email && filter_var($request->from_email, FILTER_VALIDATE_EMAIL)
                            ? $request->from_email
                            : config('mail.from.address'),
                        $request->from_name ?: config('mail.from.name')
                    )
                        ->to($request->recipient)
                        ->subject(Email::getSubjectHtml($request->subject, User::where('type', 'user')->first()) ?: 'Test email');
                    if ($request->attachment === Email::ATTACHMENT_FILE) {
                        $message->attach(base_path('resources/files/Attachment sample.txt'));
                    }
                    elseif ($request->attachment === Email::ATTACHMENT_BATCH) {
                        $storagePath = base_path('public/files/tmpSend/');
                        $batch = Batch::findOrFail($request->batch_id);
                        $xls = $batch->getXls('batch');
                        $xls->store('xls', $storagePath);
                        $message->attach($storagePath . $xls->filename . '.' . $xls->ext, ['as' => "Batch.$xls->ext"]);
                    }
                }
            );
        }
        catch (Exception $e) {
            die($e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => "Test email sent successfully.",
        ]);
    }

    public function getStatuses(Request $request)
    {
        $emails = Email::whereIn('id', $request->ids)->get();

        $res = [];
        foreach ($emails as $email) {
            $res[] = [
                'email_id' => $email->id,
                'status' => $email->status,
                'html' => View::make('email-sender.list-item-status', compact('email'))->render(),
            ];
        }

        return $res;
    }

    public function getSingle($id)
    {
        $email = Email::findOrFail($id);
        $data = new \stdClass();
        $data->total = $email->email_trackings()->count();
        $data->delivered = $email->email_trackings()->whereHas('email_webhooks', function($q){
            $q->where('type', EmailWebhook::EVENT_DELIVERED);
        })->whereDoesntHave('email_webhooks', function($q){
            $q->where(function($qw) {
                $qw->where('type', EmailWebhook::EVENT_CLICKED);
                $qw->orWhere('type', EmailWebhook::EVENT_SPAM);
                $qw->orWhere('type', EmailWebhook::EVENT_OPENED);
            });
        })->count();
        $data->opened = $email->email_trackings()->whereHas('email_webhooks', function($q){
            $q->where('type', EmailWebhook::EVENT_OPENED);
        })->whereDoesntHave('email_webhooks', function($q){
            $q->where(function($qw) {
                $qw->where('type', EmailWebhook::EVENT_CLICKED);
                $qw->orWhere('type', EmailWebhook::EVENT_SPAM);
            });
        })->count();
        $data->clicked = $email->email_trackings()->whereHas('email_webhooks', function($q){
            $q->where('type', EmailWebhook::EVENT_CLICKED);
        })->whereDoesntHave('email_webhooks', function($q){
            $q->where('type', EmailWebhook::EVENT_SPAM);
        })->count();
        $data->failed = $email->email_trackings()->whereHas('email_webhooks', function($q){
            $q->where('type', EmailWebhook::EVENT_FAILED);
        })->count();
        $data->spam = $email->email_trackings()->whereHas('email_webhooks', function($q){
            $q->where('type', EmailWebhook::EVENT_SPAM);
        })->count();
        $data->delivered_formatted = $data->total > 0 ? number_format($data->delivered/$data->total*100, 2) : 0;
        $data->opened_formatted = $data->total > 0 ? number_format($data->opened/$data->total*100,2) : 0;
        $data->clicked_formatted = $data->total > 0 ? number_format($data->clicked/$data->total*100,2) : 0;
        $data->failed_formatted = $data->total > 0 ? number_format($data->failed/$data->total*100,2) : 0;
        $data->spam_formatted = $data->total > 0 ? number_format($data->spam/$data->total*100,2) : 0;
        return view('email-sender.single', compact('email', 'data'));
    }

    public function getSingleDeliverySummary($id, Request $request)
    {
        $email = Email::findOrFail($id);

        $eventsQuery = EmailWebhook::whereHas('email_tracking', function($q) use($email) {
            $q->where('email_id', $email->id);
        });
        if($request->type) {
            $eventsQuery->where('type', $request->type);
        }

        $eventsQuery = EmailTracking::where('email_id', $email->id);

        if($request->type) {
            $eventsQuery->whereHas('email_webhooks', function($q) use($request) {
                $q->where('type', $request->type);
            })->with(['email_webhooks' => function($q) use($request) {
                $q->where('type', $request->type);
            }]);
        } else {
            $eventsQuery->has('email_webhooks');
        }

        $events = $eventsQuery->paginate(config('app.pagination'));

        if($request->ajax()) {
            return response()->json([
                'itemsHtml' => View::make('email-sender.single-delivery-summary-list', compact('email', 'events'))->render(),
                'paginationHtml' => $events->appends($request->all())->render()
            ]);
        }

        return view('email-sender.single-delivery-summary', compact('email', 'events'));
    }

    public function postPreview(Request $request)
    {
        $subject = explode(' ',trim($request->subject));
        if((isset($subject[3]) && $subject[3] == 'Batch') || (isset($subject[1]) && $subject[1] == 'Auction'))
            $template = 'emails.batches.send-batch';
        else
            $template = 'emails.email-sender.marketing-message';
        $user = User::where('type', 'user')->first();
        return view(
            $template,
            [
                'body' =>  Email::getBodyHtml(
                    $request->body,
                    $user
                ),
                'fromName' => $request->from_name,
                'user' => $user,
                'brand' => $request->brand
            ]
        );
    }

    public function postSave(EmailRequest $request)
    {
        $email = new Email($request->all());
        $email->body = utf8_encode($request->body);
        if ($request->attachment === Email::ATTACHMENT_BATCH && $request->batch_id) {
            $email->batch_id = $request->batch_id;
        }
        $email->save();
        if ($request->attachment === Email::ATTACHMENT_FILE && $request->hasFile('file')) {
            $email->saveFile($request->file('file'));
        }

        artisan_call_background('email-sender:send', $email->id);

        return redirect()->route('emails')->with(
            'messages.success',
            "Email saved and dispatched for sending. You can check the send status here."
        );
    }

    public function getCreate($draft = null)
    {
        if($draft) {
            $draft = EmailDraft::findOrFail($draft);
            return view('email-sender.create', ['email' => $draft]);
        }
        return view('email-sender.create', ['email' => new Email]);
    }

    public function postSaveDraft(Request $request)
    {

        try {
            $emailDraft = new EmailDraft($request->all());
            $emailDraft->body = utf8_encode($request->body);
            $emailDraft->user_id = Auth::user()->id;
            $emailDraft->save();
        }
        catch (Exception $e) {
            die($e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => "Draft has been successfully saved $request->title.",
        ]);

    }

    public function getDraftsIndex()
    {
        $drafts = EmailDraft::orderBy('id', 'desc')->paginate(config('app.pagination'));

        return view('email-sender.drafts', compact('drafts'));
    }

    public function deleteDraft(Request $request)
    {
        $draft = EmailDraft::findOrFail($request->id);
        $title = $draft->title;
        $draft->delete();

        return back()->with('messages.success', "Draft $title has been successfully removed.");
    }

    public function unSubscribe($id){


        $userModel=User::find(Crypt::decrypt($id));
        $userModel->marketing_emails_subscribe=false;
        $userModel->save();

        echo "You have been unsubscribed  marketing emails.";

    }
}
