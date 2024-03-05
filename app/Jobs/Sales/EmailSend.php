<?php

namespace App\Jobs\Sales;

use App\Contracts\Invoicing;
use App\Models\Sale;
use App\Models\SaleLog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class EmailSend implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    const EMAIL_CANCELLED = 'sale cancelled';
    const EMAIL_CREATED = 'sale created';
    const EMAIL_DISPATCHED = 'order dispatched';
    const EMAIL_PAID = 'invoice paid';
    const EMAIL_PAYMENT_NEEDED = 'payment needed';
    const EMAIL_READY_FOR_DISPATCH = 'ready for dispatch';
    const EMAIL_TRACKING_NUMBER = 'tracking number added';
    const EMAIL_AWAITING_PAYMENT = 'awaiting payment';
    const EMAIL_PAID_ON_INVOICE = 'paid on invoice';

    use SerializesModels;

    /**
     * @var array This looks like it could be static but that won't work with serialisation.
     */
    protected $typeToView = [
        self::EMAIL_CANCELLED => 'emails.sales.cancelled',
        self::EMAIL_CREATED => 'emails.sales.created',
        self::EMAIL_DISPATCHED => 'emails.sales.dispatched',
        self::EMAIL_PAID => 'emails.sales.invoice-paid',
        self::EMAIL_PAYMENT_NEEDED => 'emails.sales.payment-needed',
        self::EMAIL_READY_FOR_DISPATCH => 'emails.sales.ready-for-dispatch',
        self::EMAIL_TRACKING_NUMBER => 'emails.sales.tracking-number',
        self::EMAIL_AWAITING_PAYMENT => 'emails.sales.awaiting-payment',
        self::EMAIL_PAID_ON_INVOICE => 'emails.sales.paid-on-invoice',
    ];

    protected $fieldsForCsvs = [
        'purchaseList' => [
            'RCT Ref' => 'our_ref', 'Name' => 'name', 'Capacity' => 'capacity_formatted', 'Colour' => 'colour',
            'Serial' => 'serial', 'IMEI' => 'imei',
        ],
        'pickList' => [
            'RCT Ref' => 'our_ref', 'Item Name' => 'name', 'Capacity' => 'capacity_formatted', 'Colour' => 'colour',
            'Condition' => 'grade', 'Serial' => 'serial', 'IMEI' => 'imei', 'Location' => 'location',
        ],
    ];

    /**
     * @var Sale
     */
    protected $sale;

    /**
     * @var string One of self::EMAIL_* constants
     */
    protected $type;

    /**
     * @var int
     */
    protected $customerId;

    /**
     * @var Invoicing
     */
    protected $invoicing;

    /**
     * @var Auction
     */
    protected $auction;
    protected $newCustomer;
    public function __construct(Sale $sale, $type,$customer, $auction = null)
    {

        $this->sale = $sale;
        $this->type = $type;
        $this->auction = $auction;
        $this->newCustomer = $customer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {


        switch ($this->type) {
            case self::EMAIL_CANCELLED:
                $this->saleCancelledEmail();
                break;
            case self::EMAIL_CREATED:
                $this->saleCreatedEmail();
                break;
            case self::EMAIL_DISPATCHED:
                $this->orderDispatchedEmail();
                break;
            case self::EMAIL_PAID:
                $this->invoicePaidEmail();
                break;
            case self::EMAIL_PAYMENT_NEEDED:
                $this->orderPaymentNeeded();
                break;
            case self::EMAIL_READY_FOR_DISPATCH:
                $this->orderReadyForDispatch();
                break;
            case self::EMAIL_TRACKING_NUMBER:
                $this->trackingNumberAdded();
                break;
            case self::EMAIL_AWAITING_PAYMENT:
                $this->awaitingPaymentEmail();
                break;
            case self::EMAIL_PAID_ON_INVOICE:
                $this->paidOnInvoiceEmail();
                break;
        }
    }

    protected function paidOnInvoiceEmail()
    {
        $customer = $this->invoicing->getCustomer($this->sale->customer_api_id);
        $sale = $this->sale;

        if(!$customer) {
            alert("Can't send Paid on Invoice email to customer for sale \"{$sale->id}\" - customer not found.");
            return;
        }

        if (!$customer->email) {
            alert("Can't send Paid on Invoice email to customer for sale \"{$sale->id}\" - email address empty.");
            return;
        }

        $invoicePath = $this->invoicing->getInvoiceDocument($this->sale);

        Mail::send(
            $this->typeToView[$this->type],
            compact('customer', 'sale'),
            function(Message $mail) use($customer, $invoicePath, $sale) {
                $mail->subject("Invoice issued on Terms")
                    ->to($customer->email, $customer->full_name)
                    ->from(config('mail.support_address'), config('mail.chris_eaton.name'))
                    ->attach($invoicePath);
            }
        );
        SaleLog::create([
            'sale_id' => $sale->id,
            'content' => 'Email sent, subject "Invoice issued on Terms"'
        ]);
    }

    protected function awaitingPaymentEmail()
    {

        $customer = $this->invoicing->getCustomer($this->sale->customer_api_id);
        $sale = $this->sale;

        if(!$customer) {
            alert("Can't send Awaiting Payment email to customer for sale \"{$sale->id}\" - customer not found.");
            return;
        }

        if (!$customer->email) {
            alert("Can't send Awaiting Payment email to customer for sale \"{$sale->id}\" - email address empty.");
            return;
        }

        $invoicePath = $this->invoicing->getInvoiceDocument($this->sale);

        Mail::send(
            $this->typeToView[$this->type],
            compact('customer', 'sale'),
            function(Message $mail) use($customer, $invoicePath, $sale) {
                $mail->subject("Action Required: Awaiting Payment for Order #$sale->invoice_number")
                    ->to($customer->email, $customer->full_name)
                    ->bcc(config('mail.support_address'), config('mail.from.name'))
                    ->from(config('mail.support_address'), config('mail.chris_eaton.name'))
                    ->attach($invoicePath);
            }
        );
        SaleLog::create([
            'sale_id' => $sale->id,
            'content' => "Email sent, subject: 'Action Required: Awaiting Payment for Order #$sale->invoice_number'"
        ]);

    }

    protected function trackingNumberAdded()
    {
        $sale = $this->sale;
        $customer = $this->invoicing->getCustomer($this->sale->customer_api_id);

        if(!$customer) {
            alert("Can't send email to customer for sale \"{$sale->id}\" - customer not found.");
            return;
        }

        if (!$customer->email) {
            alert("Can't send tracking number email to customer for sale \"{$sale->id}\" - email address empty.");
            return;
        }

        $user = User::where('invoice_api_id', $customer->external_id)->first();

        if (!$user) {
            alert("Can't send tracking number email to customer for sale \"{$sale->id}\" - user not found.");
            return;
        }

        Mail::send(
            $this->typeToView[$this->type],
            compact('customer', 'sale', 'user'),
            function(Message $mail) use($customer, $sale) {
                $mail->subject(config('app.company_name') . " Order #$sale->invoice_number Tracking Number")
                    ->to($customer->email, $customer->full_name)
                    ->from(config('mail.sales_address'), config('mail.from.name'));
            }
        );
        SaleLog::create([
            'sale_id' => $sale->id,
            'content' => "Email sent, subject: '".config('app.company_name')." Order #$sale->invoice_number Tracking Number'"
        ]);
        if(in_array($sale->courier, [\App\Sale::COURIER_DHL, Sale::COURIER_FEDEX, Sale::COURIER_DPD_LOCAL, Sale::COURIER_ROYAL_MAIL, Sale::COURIER_UPS, Sale::COURIER_TNT, Sale::COURIER_UK_MAIL])) {
            $phone = $customer->phone ? : $user->phone;
            $saleId = $sale->invoice_number;
            $name = $customer->first_name;
            if($phone) {
                $txtlocal = app('App\Contracts\Txtlocal');
                $sms = $txtlocal->sendTrackingNumber($phone, $name, $saleId, $sale->courier, $sale->tracking_number);
                SaleLog::create([
                    'sale_id' => $sale->id,
                    'content' => "SMS sent -  Tracking Number"
                ]);
            }
        }
    }

    protected function orderReadyForDispatch()
    {
        $sale = $this->sale;
        $csvPath = $this->getListCsv($sale, $this->fieldsForCsvs['pickList']);

        Mail::send(
            $this->typeToView[$this->type],
            compact('sale', 'customer'),
            function(Message $mail) use($sale, $csvPath) {
                $mail->subject(config('app.company_name') . " Order #$sale->invoice_number - Ready for Dispatch")
                    ->to(config('mail.support_address'), config('app.company_name'))
                    ->from(config('mail.sales_address'), config('mail.from.name'))
                    ->attach($csvPath);
            }
        );

        SaleLog::create([
            'sale_id' => $sale->id,
            'content' => "Email sent, subject: '".config('app.company_name')." Order #$sale->invoice_number - Ready for Dispatch'"
        ]);
        unlink($csvPath);
    }

    protected function saleCancelledEmail()
    {

        dd("ksjksajska");
        $sale = $this->sale;
   //     $customer = $this->invoicing->getCustomer($this->sale->customer_api_id);

        $fullName=$this->newCustomer['first_name'].''.$this->newCustomer['last_name'];
        $email=$this->newCustomer['email'];

        if(!empty($this->newCustomer)) {
            alert("Can't send email to customer for sale \"{$sale->id}\" - customer not found.");
            return;
        }

        if (!$email) {
            alert("Can't send email to customer for sale \"{$sale->id}\" - email address empty.");
            return;
        }

        Mail::send(
            $this->typeToView[$this->type],
            compact('sale'),
            function(Message $mail) use($fullName,$email, $sale) {
                $invoicePath = $this->invoicing->getInvoiceDocument($this->sale);
                $mail->subject("Order with " . config('app.company_name') . " has been cancelled - #$sale->invoice_number")
                    ->to('demo@gmail.com', 'demo');
            }
        );

        SaleLog::create([
            'sale_id' => $sale->id,
            'content' => "Email sent, subject: 'Order with ".config('app.company_name')." has been cancelled - #$sale->invoice_number'"
        ]);
    }

    protected function orderPaymentNeeded()
    {
        $sale = $this->sale;
        $customer = $this->invoicing->getCustomer($this->sale->customer_api_id);

        if(!$customer) {
            alert("Can't send email to customer for sale \"{$sale->id}\" - customer not found.");
            return;
        }

        if (!$customer->email) {
            alert("Can't send email to customer for sale \"{$sale->id}\" - email address empty.");
            return;
        }

        $invoicePath = $this->invoicing->getInvoiceDocument($this->sale);

        Mail::send(
            $this->typeToView[$this->type],
            compact('customer', 'sale'),
            function(Message $mail) use($customer, $invoicePath, $sale) {
                $mail->subject("ACTION REQUIRED: Payment needed for invoice #$sale->invoice_number")
                    ->to($customer->email, $customer->full_name)
                    ->from(config('mail.sales_address'), config('mail.from.name'))
                    ->attach($invoicePath);
            }
        );

        SaleLog::create([
            'sale_id' => $sale->id,
            'content' => "Email sent, subject: 'ACTION REQUIRED: Payment needed for invoice #$sale->invoice_number'"
        ]);
    }

    protected function orderDispatchedEmail()
    {
        $sale = $this->sale;
        $customer = $this->invoicing->getCustomer($this->sale->customer_api_id);

        if(!$customer) {
            alert("Can't send email to customer for sale \"{$sale->id}\" - customer not found.");
            return;
        }

        if (!$customer->email) {
            alert("Can't send email to customer for sale \"{$sale->id}\" - email address empty.");
            return;
        }
        $items = $sale->stock;
        if(!$items) {
            alert("Items not found for sale \"{$this->sale->id}\" - Sale Order imeis Email.");
            return;
        }


        $fields = [
            'RCT_ref' => 'our_ref',
            'Name' => 'name',
            'Capacity' => 'capacity_formatted',
            'Network' => 'network',
            'IMEI' => 'imei',
            'Serial' => 'serial',
            'Sales price' => 'sale_price',
            'Sales Price ex Vat' =>'sales_price_ex_vat',
            'Vat Type' =>'vat_type'
        ];


        $csvPath = tempnam('/tmp', 'stock-device-list-');
        unlink($csvPath);
        $csvPath .= '.csv';
        $fh = fopen($csvPath, 'w');
        fputcsv($fh, array_keys($fields));

        foreach ($items as $item) {
            if($item->vat_type==="Standard"){
                $item->sales_price_ex_vat= money_format(config('app.money_format'),$item->total_price_ex_vat) ;
            }else{
                $item->sales_price_ex_vat= '-' ;
            }
            $row = array_map(function ($field) use ($item) {
                return $item->$field;
            }, $fields);
            fputcsv($fh, $row);
        }


        fclose($fh);
        shell_exec("iconv -f UTF-8 -t ISO-8859-1 $csvPath > $csvPath.converted");
        unlink($csvPath);
        rename("$csvPath.converted", $csvPath);

        $invoiceNumber =  $sale->invoice_number.'-'.$sale->invoice_doc_number;

        Mail::send(
            $this->typeToView[$this->type],
            compact('sale', 'customer'),
            function(Message $mail) use($sale, $customer, $csvPath,$invoiceNumber) {
                $mail->subject("Your order #$invoiceNumber with " . config('app.company_name') . " has been dispatched")
                    ->to($customer->email, $customer->full_name)
                    ->from(config('mail.sales_address'), config('mail.from.name'));
                if($csvPath)
                    $mail->attach($csvPath);
            }
        );

        SaleLog::create([
            'sale_id' => $sale->id,
            'content' => "Email sent, subject: 'Your order #$sale->invoice_number with ".config('app.company_name')." has been dispatched'"
        ]);

        if($csvPath)
            unlink($csvPath);
    }

    protected function saleCreatedEmail()
    {
        dd("ssjjss");
        $customer = $this->invoicing->getCustomer($this->sale->customer_api_id);
        $sale = $this->sale;

        if(!$customer) {
            alert("Can't send email to customer for sale \"{$sale->id}\" - customer not found.");
            return;
        }

        if (!$customer->email) {
            alert("Can't send email to customer for sale \"{$sale->id}\" - email address empty.");
            return;
        }

        $invoicePath = $this->invoicing->getInvoiceDocument($this->sale);

        $invoiceNumber=$sale->invoice_number.'-'.$sale->invoice_doc_number;
        Mail::send(
            $this->typeToView[$this->type],
            compact('customer', 'sale'),
            function(Message $mail) use($customer, $invoicePath, $sale,$invoiceNumber) {
                $mail->subject("Your new order with " . config('app.company_name') . " - #$invoiceNumber")
                    ->to($customer->email, $customer->full_name)
                    ->bcc(config('mail.support_address'), config('mail.sale_cc.name'))
                    ->from(config('mail.support_address'), config('mail.chris_eaton.name'))
                    ->attach($invoicePath);
            }
        );

        SaleLog::create([
            'sale_id' => $sale->id,
            'content' => "Email sent, subject: 'Your new order with ".config('app.company_name')." - #$invoiceNumber'"
        ]);

        $user = User::where('invoice_api_id', $customer->external_id)->first();
        $phone = $customer->phone ? : $user->phone;

        $name = $customer->first_name;
        $message = "Hi $name, thank you for your order with Recomm. We have now emailed your invoice to you. Order total: $sale->amount_formatted. Regards, Chris";
        if($phone) {
            $txtlocal = app('App\Contracts\Txtlocal');
            $sms = $txtlocal->sendMessage($phone, $message);
            SaleLog::create([
                'sale_id' => $sale->id,
                'content' => "SMS Sent: '".$message."'"
            ]);
        }
    }

    protected function invoicePaidEmail()
    {
        $sale = $this->sale;
        $customer = $this->invoicing->getCustomer($this->sale->customer_api_id);
        if($sale->other_recycler)
            return;

        if(!$customer) {
            alert("Can't send email to customer for sale \"{$sale->id}\" - customer not found.");
            return;
        }
        $items = $sale->stock;
        if (!$customer->email) {
            alert("Can't send email to customer for sale \"{$sale->id}\" - email address empty.");
            return;
        }




        $fields = [
            'RCT_ref' => 'our_ref',
            'Name' => 'name',
            'Capacity' => 'capacity_formatted',
            'Network' => 'network',
            'IMEI' => 'imei',
            'Serial' => 'serial',
            'Sales price' => 'sale_price',
            'Sales Price ex Vat' =>'sales_price_ex_vat',
            'Vat Type' =>'vat_type'
        ];


        $csvPath = tempnam('/tmp', 'stock-device-list-');
        unlink($csvPath);
        $csvPath .= '.csv';
        $fh = fopen($csvPath, 'w');
        fputcsv($fh, array_keys($fields));
        foreach ($items as $item) {
            if($item->vat_type==="Standard"){
                $item->sales_price_ex_vat= money_format(config('app.money_format'),$item->total_price_ex_vat) ;
            }else{
                $item->sales_price_ex_vat= '-' ;
            }
            $row = array_map(function ($field) use ($item) {
                return $item->$field;
            }, $fields);
            fputcsv($fh, $row);
        }


        fclose($fh);
        shell_exec("iconv -f UTF-8 -t ISO-8859-1 $csvPath > $csvPath.converted");
        unlink($csvPath);
        rename("$csvPath.converted", $csvPath);

        $invoiceNumber =  $sale->invoice_number.'-'.$sale->invoice_doc_number;


        Mail::send(
            $this->typeToView[$this->type],
            compact('sale', 'customer'),
            function(Message $mail) use($sale, $customer, $csvPath,  $invoiceNumber) {
                $mail->subject("Recomm Order Marked as Paid - #$invoiceNumber")
                    ->to($customer->email, $customer->full_name)
                    ->from(config('mail.sales_address'), config('mail.from.name'))
                    ->bcc(config('mail.support_address'), config('mail.from.name'));
                if($csvPath)
                    $mail->attach($csvPath);
            }
        );
        SaleLog::create([
            'sale_id' => $sale->id,
            'content' => "Email sent, subject: 'Recomm Order Marked as Paid - #$sale->invoice_number'"
        ]);
        if($csvPath) {
            unlink($csvPath);
        }
    }

    /**
     * @param Sale $sale
     * @param array $fields Fields that should be put in the CSV. Keys are headers, values are field names. See
     *                      self::$fieldsForCsvs.
     * @return string Path to CSV. Remember to delete it after sending the email.
     */
    protected function getListCsv(Sale $sale, $fields)
    {
        $csvPath = tempnam('/tmp', 'stock-device-list-');
        unlink($csvPath);
        $csvPath .= '.csv';
        $fh = fopen($csvPath, 'w');
        fputcsv($fh, array_keys($fields));
        foreach ($sale->stock as $item) {
            $row = array_map(function($field) use($item) { return $item->$field; }, $fields);
            fputcsv($fh, $row);
        }
        fclose($fh);
        shell_exec("iconv -f UTF-8 -t ISO-8859-1 $csvPath > $csvPath.converted");
        unlink($csvPath);
        rename("$csvPath.converted", $csvPath);
        return $csvPath;
    }
}
