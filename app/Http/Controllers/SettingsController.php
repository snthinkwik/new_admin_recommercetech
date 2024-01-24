<?php

namespace App\Http\Controllers;

use App\AccessToken;
use App\AllowedIp;
use App\BackMarketProduct;
use App\Batch;
use App\EmailFormat;
use App\IgnoreSku;
use App\Stock;
use App\StockLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function getIndex() {
        $batch = View::make('emails.batches.new-batch', ['user' => Auth::user(), 'batch' => Batch::orderBy('id', 'desc')->first() ? : new Batch()]);
        $batch = (string) $batch;

        return view('admin.settings.index', compact('batch'));
    }

    public function postIndex(Request $request) {
        if ($request->mail_driver) {
            $env = file_get_contents(base_path('.env'));
            $env = preg_replace('/^MAIL_DRIVER=.*$/m', "MAIL_DRIVER=$request->mail_driver", $env);
            file_put_contents(base_path('.env'), $env);
        }

        Setting::set('crons', $request->crons);

        return back()->with('messages.success', "Settings changed.");
    }

    public function postFreeDelivery(Request $request) {
        Setting::set('free_delivery', $request->free_delivery);

        return back()->with('messages.success', "Free Delivery Updated.");
    }

    public function postRunCron(Request $request) {
        $crons = ['batch-create'];
        $cron = $request->cron;
        if (!in_array($cron, $crons)) {
            return back()->with('messages.error', 'Invalid script name');
        }

        if ($cron == "batch-create") {
            artisan_call_background('batches:create', ['run-once' => 'true']);
        }



        return back()->with('messages.success', "Script $cron was started");
    }

    public function getClearStock() {
        $inStock = Stock::where('status', Stock::STATUS_IN_STOCK)->where('locked_by', '');
        $inbound = Stock::where('status', Stock::STATUS_INBOUND)->where('locked_by', '');
        $inStockC = count($inStock->get());
        $inboundC = count($inbound->get());
        $inStock->delete();
        $inbound->delete();
        return redirect(route('stock'))->with('messages.error', 'Stock has been cleared. Deleted: In Stock - ' . $inStockC . ', Inbound - ' . $inboundC);
    }

    public function postChangeShownToNone(Request $request) {
        $stock = Stock::where('status', Stock::STATUS_IN_STOCK)->where('locked_by', '')->where('shown_to', Stock::SHOWN_TO_ALL)->update(['shown_to' => Stock::SHOWN_TO_NONE]);

        return redirect()->route('stock')->with('messages.success', "$stock Items Have been changed to Shown To None");
    }

    public function postChangeInStockToInbound(Request $request) {
        $count = 0;
        $user = Auth::user();
        $stockItems = Stock::where('status', Stock::STATUS_IN_STOCK)->where('locked_by', '')->get()->lists('id');

        $stockItemsUpdated = Stock::where('status', Stock::STATUS_IN_STOCK)->where('locked_by', '')->update(['status' => Stock::STATUS_INBOUND, 'shown_to' => Stock::SHOWN_TO_NONE]);

        $items = Stock::whereIn('id', $stockItems)->get();


        foreach ($items as $item) {
            if ($item->locked_by == '') {
                StockLog::create([
                    'user_id' => $user->id,
                    'content' => 'This item was moved OUT of stock by ' . $user->first_name,
                    'stock_id' => $item->id
                ]);
                $count++;
            }
        }


        return redirect()->route('stock')->with('messages.success', "$stockItemsUpdated Items Have been changed to Inbound. Logs created: $count");
    }

    public function getAllowedIps() {
        $ips = AllowedIp::orderBy('id', 'desc')->get();

        return view('admin.settings.allowed-ip', compact('ips'));
    }

    public function postAllowedIpsAdd(Request $request) {
        $ip_address = $request->ip_address;

        $check = AllowedIp::where('ip_address', $ip_address)->first();
        if ($check) {
            return back()->with('messages.error', 'IP Address already in db.');
        }

        $ip = new AllowedIp();
        $ip->ip_address = $ip_address;
        $ip->last_login = Carbon::now();
        $ip->save();

        return back()->with('messages.success', 'IP Address has been added/');
    }

    public function updateStock() {
        artisan_call_background('ebay:orderhub-update-stock-status');
        return redirect()->route('stock')->with('messages.success', 'update stock successfully');
    }

    public function postAllowedIpsRemove(Request $request) {
        $ip = AllowedIp::findOrFail($request->id);
        $ip->delete();

        return back()->with('messages.success', 'IP has been removed.');
    }

    public function getIgnoreSku() {
        $skus = IgnoreSku::orderBy('id', 'desc')->get();

        return view('admin.settings.ignore-sku', compact('skus'));
    }

    public function postIgnoreSkuAdd(Request $request) {
        $sku_list = preg_split('/[\s,]+/', $request->sku_list);

        $message = "";
        foreach ($sku_list as $sku) {
            if ($sku == "")
                continue;
            if (IgnoreSku::where('sku', $sku)->first()) {
                $message .= "$sku already in db.\n";
                continue;
            }

            $ignoreSku = new IgnoreSku();
            $ignoreSku->sku = $sku;
            $ignoreSku->save();

            $message .= "$sku added\n";
        }

        return back()->with('messages.info', $message);
    }

    public function postIgnoreSkuRemove(Request $request) {
        $ignoreSku = IgnoreSku::findOrFail($request->id);

        $oldSku = $ignoreSku->sku;

        $ignoreSku->delete();

        return back()->with('messages.success', "SKU $oldSku has been removed from Ignore SKU list.");
    }

    public function postChangeEbayShownToNone(Request $request) {
        $stockItemsUpdated = Stock::where('status', Stock::STATUS_IN_STOCK)->where('locked_by', '')->whereIn('shown_to', [Stock::SHOWN_TO_EBAY, Stock::SHOWN_TO_EBAY_AND_SHOP])->update(['shown_to' => Stock::SHOWN_TO_NONE]);

        return back()->with('messages.success', "In Stock eBay and eBay & Shop items shown to has been change to 'None': $stockItemsUpdated items");
    }

    public function postUpdateOrderhubQuantity(Request $request) {
        return redirect()->route('stock');
    }

    public function getEbaySetting(){

        $accessToken=AccessToken::where('platform','ebay')->first();
        $accessTokenSecond=AccessToken::where('platform','ebay-second')->first();
        $accessTokenThird=AccessToken::where('platform','ebay-third')->first();
        $accessTokenForth=AccessToken::where('platform','ebay-forth')->first();

        return view('admin.ebay.index',compact('accessToken','accessTokenSecond','accessTokenThird','accessTokenForth'));

    }

    public function getUploadDocumentEmailFormat(){

        $emailFormat=EmailFormat::where('email_format_name','multiple-upload-document')->first();
        return view('account.upload-document-email-format',compact('emailFormat'));
    }


    public function saveUploadDocumentEmailFormat(Request $request){

        $link="<a href=".env('TRG_UK_URL')."/multiple-upload-file/{userId}".">Go to Document Upload</a>";
        $content= str_replace("%%Link%%",$link,$request->message);
        $subject=$request->subject;

        $emailFormat = EmailFormat::firstOrNew([
            'email_format_name' => 'multiple-upload-document',

        ]);

        $emailFormat->email_format_name='multiple-upload-document';
        $emailFormat->subject=$subject;
        $emailFormat->message=$content;
        $emailFormat->regard=$request->kind_regard;
        $emailFormat->save();

        return back()->with('messages.success','email format successfully save');


    }

    public function ExportBuyBackProduct(){


        ini_set('max_execution_time', 120000);

        //  $backMarket= BackMarketProduct::where('id','122103')->orderBy('id', 'desc');
        $backMarket= BackMarketProduct::orderBy('id', 'desc');

        $fields = [
            'Id'=>'id',
            'Category Name'=>'category_name',
            'Brand' => 'brand',
            'Product Name' => 'title',
            'Back Market Id' => 'back_market_id',
            'State'=>'state',
            'EAN'=>'ean',
            'Weight'=>'weight',
        ];


        $csvPath = tempnam('/tmp', 'stock-full-export-');
        unlink($csvPath);
        $csvPath .= '.csv';
        $fh = fopen($csvPath, 'w');
        fputcsv($fh, array_keys($fields));


        $backMarket->chunk(500, function ($items) use ($fields, $fh) {


            foreach ($items as $item) {
                $trimTitle=  str_replace( array( '\'', '"', ',' , ';','–','‎','œur','-','Е6320','™','’’','•','’',' ','”','€','﻿','↑'), '', $item->title);
                $trimBrand=  str_replace( array( '\'', '"', ',' , ';', '‎' ), '', $item->brand);
                $trimBackMarketId=  str_replace( array( '\'', '"', ',' , ';', '‎' ), '', $item->back_market_id);
                $item->title=$trimTitle;
                $item->brand=$trimBrand;
                $item->back_market_id=$trimBackMarketId;

                $row = array_map(function ($field) use ($item) {
                    return $item->$field;
                }, $fields);
                fputcsv($fh, $row);
            }
        });


        fclose($fh);
        convert_file_encoding($csvPath);
        header('Content-length: ' . filesize($csvPath));
        header('Content-Disposition: attachment; filename="Product.csv"');
        header('Content-type: application/vnd.ms-excel');
        readfile($csvPath);
        die;




    }

    public function DpdShipping(){
        $setting=\App\Setting::where('key','dpd_shipping_status')->first();
        return view('admin.dpd-shipping.index',compact('setting'));
    }

    public function DpdRefreshToken(){

        $BASE="https://api.dpdlocal.co.uk/";

        $method = '/user/?action=login';

        $url = $BASE.$method;


        $options = array(
            'http' => array(
                'method'  => 'POST',
                'Host'  => 'api.dpd.co.uk',
                'header'=>  "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n".
                    "Authorization: Basic ". base64_encode(config('services.DPD_Shipping.user_name').":". config('services.DPD_Shipping.password')) ."\r\n".
                    "GEOClient: account/".config('services.DPD_Shipping.account'),
                "Content-Length: 0"
            )
        );


        $context     = stream_context_create($options);

        $result      = file_get_contents($url, false, $context);
        $data=(json_decode($result,true));
        $session=$data['data']['geoSession'];
        $setting=\App\Setting::firstOrNew([
            'key' => 'dpd_shipping_token'
        ]);
        $setting->key='dpd_shipping_token';
        $setting->value=$session;
        $setting->save();


        $dpdToken=\App\Setting::where('key','dpd_shipping_token')->first();

        return view('admin.dpd-shipping.index',compact('dpdToken'));



    }
    public function dpdShippingStatus(Request  $request){

        $setting=\App\Setting::firstOrNew([
            'key' => 'dpd_shipping_status'
        ]);
        $setting->key='dpd_shipping_status';
        $setting->value=$request->dpd_status;
        $setting->save();

        return back()->with('message.success','successfully updated');


    }
}
