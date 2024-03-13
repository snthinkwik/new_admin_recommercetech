<?php

namespace App\Http\Controllers;

use App\Jobs\Batch\NotifyBestPrice;
use App\Models\Batch;
use App\Models\BatchOffer;
use App\Models\Email;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Image;
use Illuminate\Support\Facades\DB;

class BatchesController extends Controller
{
    public function getIndex(Request $request)
    {
        $batchesQuery = Batch::has('stock')->orderBy('id', 'desc');

        $status = $request->status ? : Batch::STATUS_FOR_SALE;

        if($status && $status != "all") {
            if($status == Batch::STATUS_FOR_SALE) {
                $batchesQuery->forSale();
            } elseif($status == Batch::STATUS_SOLD) {
                $batchesQuery->sold();
            }
        }

        $batches = $batchesQuery->paginate(config('app.pagination'));

        if($request->ajax()) {
            return response()->json([
                'itemsHtml' => View('batches.list', compact('batches'))->render(),
                'paginationHtml' => $batches->appends($request->all())->render()
            ]);
        }

        return view('batches.index', compact('batches'));
    }

    public function getSingle($id)
    {
        $batch = Batch::findOrFail($id);


        return view('batches.single', compact('batch'));
    }

    public function postUpdate(Request $request)
    {
        $batch = Batch::findOrFail($request->id);

        $message = "";

        $batch->fill($request->all());

        if($request->hasFile('file')) {
            $file = $request->file('file');
            $dir = base_path('public/files/batches/');
            $filename = $batch->id . "." . $file->getClientOriginalExtension();
            $file->move($dir, $filename);
            $batch->file = $filename;
            $message .= "File Uploaded\n";
        }

        if($request->hasFile('image')) {
            $file = $request->file('image');
            $dir = base_path('public/img/batches/');

            $filename = $batch->id . '.' . $file->getClientOriginalExtension();
            Image::make($file)->resize(2048, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->save($dir . $filename, 80);
            $batch->photo = $filename;
            $message .= "Image Uploaded\n";
        }

        if($request->end_time) {
            $batch->end_time = $request->end_time;
            $batch->extended_time = false;
        }

        if($batch->isDirty()) {
            foreach ($batch->getAttributes() as $key => $value) {
                if ($value !== $batch->getOriginal($key) && !checkUpdatedFields($value, $batch->getOriginal($key))) {
                    $message .= "Changed \"$key\" from \"{$batch->getOriginal($key)}\" to \"$value\".\n";
                }
            }
            $batch->save();
        }

        if($message) $batch->save(); // if image was updated (so image in db was not changed, it won't show as isDirty

        if(!$message) $message = "No changes";

        return back()->with('messages.info', $message);
    }

    public function getDealSheet($id)
    {
        $batch = Batch::findOrFail($id);

        return view('batches.deal-sheet', compact('batch'));
    }

    public function getOverview($id)
    {
        $batch = Batch::findOrFail($id);
        $items = Stock::query()
            ->select(DB::raw('count(id) as totalCount, count(sale_id) as totalSold, sum(purchase_price + part_cost + unlock_cost) as totalCost, sum(sale_price) as totalSale, count(case when sale_price > 0 then 1 else null end) as countSale'))
            ->where('batch_id', $batch->id)
            ->first();
        if($items->totalCount == $items->totalSold) {
            $saleId = Stock::query()->select('sale_id')->where('batch_id', $batch->id)->first();
            $sale = $saleId ? Sale::findOrFail($saleId->sale_id) : null;
        }
        $networks = Stock::query()
            ->select('network',DB::raw('count(id) as count'))
            ->where('batch_id', $batch->id)
            ->orderBy('count', 'desc')
            ->groupBy('network')
            ->get();
        $grades = Stock::query()
            ->select('grade',DB::raw('count(id) as count'))
            ->where('batch_id', $batch->id)
            ->orderBy('count', 'desc')
            ->groupBy('grade')
            ->get();

        return view('batches.overview', compact('batch', 'items', 'networks', 'grades', 'sale'));
    }

    public function getSingleSummary($id)
    {
        $batch = Batch::findOrFail($id);
        $items = Stock::where('batch_id', $batch->id)->groupBy('name', 'capacity', 'network')
            ->select(DB::raw('count(*) as quantity'), 'name', 'capacity', 'network')
            ->orderBy('quantity', 'desc')->get();

        return view('batches.single-summary', compact('batch', 'items'));
    }

    public function getSingleSummaryExport($id)
    {
        $batch = Batch::findOrFail($id);
        $batchItems = Stock::where('batch_id', $batch->id)->groupBy('name', 'capacity', 'network')
            ->select(DB::raw('count(*) as quantity'), 'name', 'capacity', 'network')
            ->orderBy('quantity', 'desc')->get();

        $items = [];

        foreach($batchItems as $item) {
            $items[] = [
                'Model Name' => $item->name." - ".$item->capacity_formatted." - ".$item->network,
                'Qty' => $item->quantity,
                'Price' => ''
            ];
        }

        $rBorder = "C";
        $filename = "Batch-$batch->id";
        $count = count($items) +1;
        $file = Excel::create($filename, function($excel) use($items, $count, $rBorder) {
            $excel->setTitle('Items');
            $excel->sheet('Items',function($sheet) use($items, $count, $rBorder) {
                $sheet->fromArray($items);
                $sheet->setFontSize(10);
                $sheet->setColumnFormat(array(
                    'C' => '£0.00'
                ));
                // Left Border
                $sheet->cells('A1:A'.$count, function($cells){
                    $cells->setBorder('none','none','none','medium');
                });
                // Right Border
                $sheet->cells($rBorder.'1:'.$rBorder.$count, function($cells){
                    $cells->setBorder('none','medium','none','none');
                });
                // Top+Bottom border - first row
                $sheet->row(1, function($row){
                    $row->setBorder('medium','medium','medium','medium');
                    $row->setFontSize(11);
                });
                // Bottom border - last row
                $sheet->row($count, function($row){
                    $row->setBorder('none','medium','medium','medium');
                });
            });
        });

        $file->download('xls');
        return back();
    }

    public function postDelete(Request $request)
    {
        $batch = Batch::findOrFail($request->id);

        $batchId = $batch->id;

        if($batch->deletable) {
            $items = Stock::where('batch_id', $batch->id)->get();

            foreach($items as $item)
            {
                if($item->status == Stock::STATUS_BATCH) {
                    $item->status = Stock::STATUS_IN_STOCK;
                    $item->locked_by = null;
                }
                $item->batch_id = null;
                $item->save();
            }

            $batch->delete();

            return redirect()->route('batches')->with('messages.success', "Batch #$batchId - deleted");
        }

        return back()->with('messages.error', "Unable to delete Batch #$batchId");
    }

    public function getNewCustom()
    {
        return view('batches.new-custom');
    }

    public function postNewCustomSubmit(Request $request)
    {
        $batch = new Batch();
        $batch->name = $request->name;
        $batch->description = $request->description;
        $batch->sale_price = $request->asking_price;
        $batch->custom_name = true;
        $batch->save();

        if($request->hasFile('image')) {
            $image = $request->file('image');
            $dir = base_path('public/img/batches/');
            $filename = $batch->id.".".$image->getClientOriginalExtension();
            Image::make($image)->resize(2048, null, function($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->save($dir.$filename, 80);
            $batch->photo = $filename;
            $batch->save();
        }

        if($request->hasFile('file')) {
            $file = $request->file('file');
            $dir = base_path('public/files/batches/');
            $filename = $batch->id.".".$file->getClientOriginalExtension();
            $file->move($dir, $filename);
            $batch->file = $filename;
            $batch->save();
        }

        return redirect()->route('batches')->with('messages.success', 'Batch Number: #'.$batch->id);
    }

    public function getSummary()
    {
        $batches = Stock::query()
            ->select('batch_id', 'status', DB::raw('count(id) as count'))
            ->where('status', Stock::STATUS_BATCH)
            ->groupBy('batch_id')
            ->get();

        foreach($batches as $key => $batch) {
            $batch->items = Stock::where('status',Stock::STATUS_BATCH)->where('batch_id', $batch->batch_id)->groupBy('name', 'capacity', 'network')
                ->select(DB::raw('count(*) as quantity'), 'name', 'capacity', 'network')
                ->orderBy('quantity', 'desc')->get();


            if(!is_null($batch->batch_id)){
                $batch->sale_price = Batch::where('id', $batch->batch_id)->first()->sale_price;
            }

            if(!$batch->sale_price) $batches->forget($key);
        }

        return view('batches.summary', compact('batches'));
    }

    public function postClearNotes(Request $request)
    {
        $batch = Batch::findOrFail($request->id);

        Stock::where('batch_id', $batch->id)->update(['notes' => ""]);

        return back()->with('messages.success', "Batch #$batch->id - items notes have been cleared.");
    }

    public function postUpdateNotes(Request $request)
    {
        $batch = Batch::findOrFail($request->id);

        $notes = $request->notes;
        $log = "Notes Updated: $notes | Batch Update Notes";

        $items = Stock::where('batch_id', $batch->id)->get();
        foreach($items as $item) {
            $item->notes = $notes;
            $item->save();
            StockLog::create(['user_id' => Auth::user()->id, 'stock_id' => $item->id, 'content' => $log]);
        }

        return back()->with('messages.success', 'Notes Updated');
    }

    public function getExport($id, $option, $email = null)
    {
        $batch = Batch::where('id',$id)->first();
        $options = ['download', 'batch', 'batch_imeis', 'auction'];
        if(!$batch)
            return back()->with('messages.error', "Unable to export - Batch doesn't exist");
        if(!in_array($option, $options))
            return back()->with('messages.error', "invalid option parameter.");

        $file = $batch->getXls($option);

        if(in_array($option, ['download', 'batch_imeis']))
            $file->download('xls');
        else {
            $storagePath = base_path('public/files/tmpSend/');
            $file->store('xls', $storagePath, $returnInfo = true);
            $fileSend['path'] = $storagePath.$file->filename.'.'.$file->ext;
            $fileSend['name'] = ucfirst($option).'.'.$file->ext;
            return $fileSend;
        }
        return back();
    }

    public function postDealSheetSubmit(Request $request)
    {
        $batch = Batch::findOrFail($request->id);

        $user = User::where('invoice_api_id', $request->customer_id)->first();
        if(!$user) {
            return back()->with('messages.error', 'Customer not found.');
        }

        if($user->suspended) {
            return back()->with('messages.error', 'Customer is suspended');
        }

        $deletePreviousOffers = BatchOffer::where('batch_id', $batch->id)->where('user_id', $user->id)->delete();

        $offer = new BatchOffer();
        $offer->user_id = $user->id;
        $offer->batch_id = $batch->id;
        $offer->offer = $request->offer;
        $offer->seen = true;
        $offer->save();


//        Queue::pushOn('emails', new OfferReceived($offer));
       dispatch( new \App\Jobs\Batch\OfferReceived($offer));

        return back()->with('messages.success', 'Offer has been saved');
    }

    public function postDealSheetNotifyBestPrice(Request $request)
    {
        $batch = Batch::findOrFail($request->id);

        if(!count($batch->batch_offers)) {
            return back()->with('messages.error', 'There are no offers');
        }

        $bestOffer = BatchOffer::where('batch_id', $batch->id)->orderBy('offer', 'desc')->first();
        $bestPrice = $bestOffer->offer_formatted;

        $offers = BatchOffer::where('batch_id', $batch->id)->whereNotIn('id', [$bestOffer->id])->whereNotIn('user_id', [$bestOffer->user_id])->orderBy('offer', 'desc')->groupBy('user_id')->get();

        foreach($offers as $offer) {
           // Queue::pushOn('emails', new NotifyBestPrice($offer, $bestPrice));
           dispatch(new NotifyBestPrice($offer, $bestPrice));
        }

        return back()->with('messages.success', 'Notify Best Price Emails/SMS have been sent');

    }

    public function postDealSheetDeleteOffer(Request $request)
    {
        $offer = BatchOffer::findOrFail($request->id);
        $offer->delete();

        return back()->with('messages.success', 'Batch Offer has been removed');
    }

    public function postDealSheetMarkAsSeen(Request $request)
    {
        $offer = BatchOffer::findOrFail($request->id);
        $offer->seen = true;
        $offer->save();

        return back()->with('messages.success', 'Offer has been marked as seen');
    }

    public function postDealSheetMarkAllAsSeen(Request $request)
    {
        $batch = Batch::findOrFail($request->id);
        $offers = $batch->batch_offers()->where('seen', false)->get();
        foreach($offers as $offer) {
            $offer->seen = true;
            $offer->save();
        }

        return back()->with('messages.success', 'All Offers have been marked as seen');
    }

    public function postSend(Request $request)
    {
        $id = $request->id;
        $batch = Batch::findOrFail($id);
        $totalSalesPrice = $batch->sale_price;
        if($totalSalesPrice == 0) {
            return back()->with('messages.error', "Batch Sale Price must be set.");
        }
        $totalSalesPrice = money_format($totalSalesPrice);

        if($batch->custom_name) {
            $subject = $batch->name;
            $body = "<p>Hi %%FIRST_NAME%%,</p>\r\n\r\n<p>We have the following batch for sale today.</p>\r\n\r\n<p>Batch Name: $batch->name.</p>\r\n\r\n<p>".nl2br($batch->description)."</p>\r\n\r\n<p>We are looking for $totalSalesPrice. What can you offer?</p>\r\n\r\n<p>Please see the attached and <a href='".$batch->trg_uk_url."?userhash=%USERHASH%'>click here</a> to make your live offer.</p>\r\n\r\n<p>Be quick as the batch will go.</p>\r\n";
        } else {
            $subject = "Recomm Mobile Phone Batch #".$batch->id." - Offers Needed";
            $body = "<p>Hi %%FIRST_NAME%%,</p>\r\n\r\n<p>We have the following batch for sale today.</p>\r\n\r\n<p>We are looking for $totalSalesPrice. What can you offer?</p>\r\n\r\n<p>Please see the attached and <a href='".$batch->trg_uk_url."?userhash=%USERHASH%'>click here</a> to make your live offer.</p>\r\n\r\n<p>Be quick as the batch will go.</p>\r\n";
        }

        $email = new Email();
        $email->to = Email::TO_REGISTERED;
        $email->subject = $subject;
        $email->body = $body;
        $email->from_email = config('mail.sales_old_address');
        $email->from_name =  'Recomm Sales';
        $email->save();

        $attachment = $batch->custom_name ? ['path' => $batch->file_url, 'name' => "Batch $batch->file"] : $this->getExport($batch->id, 'batch', $email->id);

        artisan_call_background(
            'email-sender:send',
            [$email->id, 'attachment-path' => $attachment['path'], 'attachment-name' => $attachment['name']]
        );

        return redirect()->route('emails')->with(
            'messages.success',
            "Email saved and dispatched for sending. You can check the send status here."
        );
    }

    public function postSendToUser(Request $request)
    {
        $batch = Batch::findOrFail($request->id);
        $user = User::findOrFail($request->user_id);

        $totalSalesPrice = $batch->sale_price;
        if($totalSalesPrice == 0) {
            return back()->with('messages.error', "Batch Sale Price must be set.");
        }
        $totalSalesPrice = money_format($totalSalesPrice);

        $subject = "Recomm Mobile Phone Batch #".$batch->id." - Offers Needed";
        $body = "<p>Hi %%FIRST_NAME%%,</p>\r\n\r\n<p>We have the following batch for sale today.</p>\r\n\r\n<p>We are looking for $totalSalesPrice. What can you offer?</p>\r\n\r\n<p>Please see the attached and and <a href='".$batch->trg_uk_url."?userhash=%USERHASH%'>click here</a> to make your live offer.</p>\r\n\r\n<p>Be quick as the batch will go.</p>\r\n";
        $from_email = config('mail.sales_old_address');
        $from_name =  'Recomm Sales';


        $attachment = $this->getExport($batch->id, 'batch');

        if($attachment)
            $template = 'emails.batches.send-batch';
        else
            $template = 'emails.email-sender.marketing-message';
        Mail::send(
            $template,
            ['body' => Email::getBodyHtml($body, $user), 'fromName' => $from_name, 'user' => $user],
            function (Message $message) use($subject, $from_email, $from_name, $user, $attachment) {
                $message->from($from_email, $from_name)
                    ->to($user->email, $user->full_name)
                    ->subject($subject);
                if ($attachment) {
                    $message->attach($attachment['path'] ,['as' => $attachment['name']]);
                }

            }
        );

        return back()->with('messages.success', "Email has been sent to user $user->id $user->full_name");

    }

    public function postSendBatches(Request $request)
    {

        $ids = $request->ids;
        if(!$ids || !count($ids)) {
            return back()->with('messages.error', 'No Batches Selected.');
        }

        $count = count($ids);

        if($count == 1) {
            return back()->with('messages.error', 'At least 2 Batches needs to be selected. To send 1 Batch, use Send Batch on Batch Details page');
        }

        $batches = Batch::whereIn('id', $ids)->get();

        $email = new Email();
        $email->to = Email::TO_REGISTERED;
        $email->subject = $count."x Batches of iPhones and Tablets - Offers Needed";
        $body = "<p>Hi %%FIRST_NAME%%,</p>\r\n\r\n<p>We have the following batches for sale today.</p>\r\n\r\n";
        foreach($batches as $batch) {
            $qty = $batch->stock()->count();
            $line = "<p>Batch $batch->id - $batch->name<br/>No. Devices: $qty<br/>Asking price: ".money_format($batch->sale_price)."<br/><a href='".$batch->trg_uk_url."?userhash=%USERHASH%'>Make your live offer</a></p>\r\n\r\n";
            $body .= $line;
        }
        $body .="<p>Please see the attached spreadsheets and make your offer.</p>\r\n\r\n<p>Be quick as the batches will go.</p>\r\n";
        $email->body = $body;
        $email->from_email = config('mail.sales_old_address');
        $email->from_name =  'Recomm Sales';
        $email->save();


        //Right Border Cell
        $rBorder = 'I';

        //Count+1 (row with keys)

        $files = [];

        foreach($batches as $batch) {
            $filename = "batch-$batch->id";
            $file = Excel::create($filename, function($excel) use($rBorder, $batch) {
                $excel->setTitle("Batch #$batch->id");

                $stockItems = Stock::orderBy('id', 'desc')
                    ->where('batch_id', $batch->id)
                    ->get();
                $stock = [];
                foreach($stockItems as $item)
                {
                    $stock[] = [
                        'Batch' => '#'.$item->batch_id,
                        'Ref' => $item->our_ref,
                        'Name' => $item->name,
                        'Capacity' => $item->capacity_formatted,
                        'Colour' => $item->colour,
                        'Condition' => $item->condition,
                        'Grade' => $item->grade,
                        'Network' => $item->network,
                        'Engineer Notes' => $item->notes
                    ];
                }
                //Right Border Cell
                $count = count($stock)+1;

                $excel->sheet("Batch #$batch->id", function ($sheet) use ($stock, $count, $rBorder) {
                    $sheet->fromArray($stock);
                    $sheet->setFontSize(10);
                    // Left Border
                    $sheet->cells('A1:A' . $count, function ($cells) {
                        $cells->setBorder('none', 'none', 'none', 'medium');
                    });
                    // Right Border
                    $sheet->cells($rBorder . '1:' . $rBorder . $count, function ($cells) {
                        $cells->setBorder('none', 'medium', 'none', 'none');
                    });
                    // Top+Bottom border - first row
                    $sheet->row(1, function ($row) {
                        $row->setBorder('medium', 'medium', 'medium', 'medium');
                        $row->setFontSize(11);
                    });
                    // Bottom border - last row
                    $sheet->row($count, function ($row) {
                        $row->setBorder('none', 'medium', 'medium', 'medium');
                    });
                });
            });

            $storagePath = base_path('public/files/tmpSend/');
            $file->store('xls', $storagePath, $returnInfo = true);
            $path = $storagePath.$file->filename.'.'.$file->ext;
            $name = "Batch_$batch->id.".$file->ext;
            $files[$name] = $path;
        }

        $email->attachment = Email::ATTACHMENT_FILES;
        $email->files = $files;
        $email->save();


        artisan_call_background('email-sender:send', $email->id);

        return redirect()->route('emails')->with(
            'messages.success',
            "Email saved and dispatched for sending. You can check the send status here."
        );

    }

    public function postMerge(Request $request)
    {

        if(!$request->batch_1 || !$request->batch_2) {
            return back()->with('messages.error', 'Batches are required.');
        }

        if($request->batch_1 == $request->batch_2) {
            return back()->with('messages.error', 'Batches must be different');
        }

        $batch1 = Batch::where('id', $request->batch_1)->first();
        $batch2 = Batch::where('id', $request->batch_2)->first();
        if(!$batch1 || !$batch2) {
            return back()->with('messages.error', 'at least one of batches was not found.');
        }

        $batch1->sale_price= 0;
        $batch1->save();
        $batch2->sale_price = 0;
        $batch2->save();

        Stock::where('batch_id', $batch1->id)->update(['batch_id' => $batch2->id]);

        return back()->with('messages.success', 'Batches '.$request->batch_1.' and '.$request->batch_2.' have been merged. Batch Sale Price have been changed to 0 (You need to change it in order to allow customers buy it)');
    }
}
