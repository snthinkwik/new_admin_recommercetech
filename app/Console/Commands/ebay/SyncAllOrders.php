<?php

namespace App\Console\Commands\ebay;

use App\Models\EbayOrderLog;
use App\Models\EbayOrders;
use App\Models\HistoryLog;
use App\Models\Stock;
use Illuminate\Console\Command;

class SyncAllOrders extends Command {

    /**
     * The console command name.
     *
     * @var string
     */

    protected $name = 'ebay:orderhub-sync-all-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $startDate = date('Y-m-d H:i:s');

        $clien_id = env('ORDERHUB_CLIENT_ID');
        $clien_secret = env('ORDERHUB_CLIENT_SECRET');

        $token = getAccessToken($clien_id, $clien_secret);
        $header = array(
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer ' . $token->access_token
        );
        $i = 1;
        $d = 0;
        do {
            $eBayApiResponse = getOrderData($header, $i);
            $j = ceil((isset($eBayApiResponse["total"]) ? $eBayApiResponse["total"] : 0) / (isset($eBayApiResponse["limit"]) ? $eBayApiResponse["limit"] : 1));
            if (isset($eBayApiResponse["_embedded"]->orders)) {
                foreach ($eBayApiResponse["_embedded"]->orders as $ebay) {

                    if (isset($ebay->orderItems[0]) && !empty($ebay->orderItems[0]->externalId)) {

                        $Order = EbayOrders::firstOrNew([
                                    'sales_record_number' => $ebay->externalId
                        ]);

                        if($ebay->status == "dispatching")
                            $ebay->status = EbayOrders::STATUS_DISPATCHED;

                      $orderId=  substr($ebay->id, strpos($ebay->id, "-") + 1);

                        $Order->sales_record_number = $ebay->externalId;
                        $Order->order_number = $ebay->accountId;
                        $Order->order_id=$orderId;
                        $Order->buyer_name = $ebay->billingAddressFullName;
                        $Order->buyer_email = $ebay->billingEmailAddress;
                        $Order->buyer_note = $ebay->buyerMessage;
                        $Order->buyer_address_1 = $ebay->billingAddress1;
                        $Order->buyer_address_2 = $ebay->billingAddress2;
                        $Order->buyer_city = $ebay->billingAddressCity;
                        $Order->buyer_county = $ebay->billingAddressCounty;
                        $Order->buyer_postcode = $ebay->billingAddressPostcode;
                        $Order->buyer_country = $ebay->billingAddressCountry;
                        $Order->post_to_name = $ebay->shippingAddressFullName;
                        $Order->post_to_phone = $ebay->shippingPhoneNumber;
                        $Order->post_to_address_1 = $ebay->shippingAddress1;
                        $Order->post_to_address_2 = $ebay->shippingAddress2;
                        $Order->post_to_city = $ebay->shippingAddressCity;
                        $Order->post_to_county = $ebay->shippingAddressCounty;
                        $Order->post_to_postcode = $ebay->shippingAddressPostcode;
                        $Order->post_to_country = $ebay->shippingAddressCountry;
                        $Order->postage_and_packaging = $ebay->shippingPrice;
                        $Order->total_price = $ebay->total;
                        $Order->payment_method = $ebay->paymentMethod;
                        $Order->sale_date = $ebay->purchaseDate;
                        $Order->paid_on_date = $ebay->paymentDate;
                        $Order->post_by_date = $ebay->dispatchDate;
                        $Order->paypal_transaction_id = $ebay->paymentReference;
                        $Order->delivery_service = $ebay->shippingCarrier;
                        $Order->tracking_number = $ebay->shippingTrackingNumber;
                        $Order->account_id = $ebay->accountId;
                        $Order->billing_address3 = $ebay->billingAddress3;
                        $Order->billing_address_company_name = $ebay->billingAddressCompanyName;
                        $Order->billing_address_country_code = $ebay->billingAddressCountryCode;
                        $Order->billing_phone_number = $ebay->billingPhoneNumber;
                        $Order->currency_code = $ebay->currencyCode;
                        $Order->discount_description = $ebay->discountDescription;
                        $Order->invoice_emailed_date = $ebay->invoiceEmailedDate;
                        $Order->invoice_number = $ebay->invoiceNumber;
                        $Order->invoice_printed_date = $ebay->invoicePrintedDate;
                        $Order->reason = $ebay->reason;
                        $Order->shipping_address3 = $ebay->shippingAddress3;
                        $Order->shipping_address_company_name = $ebay->shippingAddressCompanyName;
                        $Order->shipping_address_country_code = $ebay->shippingAddressCountryCode;
                        $Order->shipping_alias = $ebay->shippingAlias;
                        $Order->shipping_email = $ebay->shippingEmailAddress;
                        $Order->shipping_method = $ebay->shippingMethod;
                        $Order->status = $ebay->status;
                        $Order->tag = $ebay->tag ? json_encode(count($ebay->tag) > 0 ? $ebay->tag : null) : null;
                        $Order->total_discount = $ebay->totalDiscount;
                        $Order->platform =Stock::PLATFROM_EBAY;

                        $changesBayOrder = '';

                        if ($Order->exists) {
                            if ($Order->isDirty()) {
                                foreach ($Order->getAttributes() as $key => $value) {
                                    if ($value !== $Order->getOriginal($key) && !checkUpdatedFields($value, $Order->getOriginal($key))) {
                                        $orgVal = $Order->getOriginal($key);
                                        $changesBayOrder .= "Changed \"$key\" from \"$orgVal\" to \"$value\".\n";
                                    }
                                }

                                $Order->save();
                            }
                        } else {
                            $changesBayOrder .= "Imported New Order. Sales record number: " . $Order->sales_record_number . " with Status: " . $Order->status . "\n";
                            $Order->save();
                            $d++;
                        }

                        foreach ($ebay->orderItems as $item) {
                            $OrderItem = \App\EbayOrderItems::firstOrNew([
                                        'item_id' => $item->id,
                                        'order_id' => $Order->id
                            ]);

                            $OrderItem->order_id = $Order->id;
                            $OrderItem->sales_record_number = $Order->sales_record_number;
                            $OrderItem->item_id = $item->id;
                            $OrderItem->external_id = $item->externalId;
                            $OrderItem->item_number = strtok($item->externalId, "-");
                            $OrderItem->item_name = $item->name;
                            $OrderItem->item_sku = $item->sku;
                            $OrderItem->quantity = $item->quantity;
                            $OrderItem->individual_item_price = $item->individualItemPrice;
                            $OrderItem->individual_item_discount_price = $item->individualItemDiscountPrice;
                            $OrderItem->tax_percentage = $item->taxPercentage;
                            $OrderItem->giftwrap = json_encode($item->giftWrap);
                            $OrderItem->weight = $item->weight;
                            $OrderItem->item_image = count($item->images) > 0 ? $item->images[0]->url : '';

                            if ($OrderItem->exists) {
                                if ($OrderItem->isDirty()) {
                                    foreach ($OrderItem->getAttributes() as $key => $value) {
                                        if ($value !== $OrderItem->getOriginal($key) && !checkUpdatedFields($value, $OrderItem->getOriginal($key))) {
                                            $orgVal = $OrderItem->getOriginal($key);
                                            $changesBayOrder .= "Changed \"$key\" from \"$orgVal\" to \"$value\".\n";
                                        }
                                    }

                                    $OrderItem->save();
                                }
                            } else {
                                $changesBayOrder .= "Imported New Order Items. Item name: " . $OrderItem->item_name . "\n";
                                $OrderItem->save();
                            }
                        }

                        if (!empty($changesBayOrder)) {
                            $this->comment($changesBayOrder);

                            $ebayOrdersLogModel = new EbayOrderLog();
                            $ebayOrdersLogModel->orders_id = $Order->id;
                            $ebayOrdersLogModel->content = $changesBayOrder;
                            $ebayOrdersLogModel->save();
                        }
                    }
                }
            }

            $i++;
        } while ($j >= $i);

        $endDate = date('Y-m-d H:i:s');

        $historyLogModel = new HistoryLog();
        $historyLogModel->script_started = $startDate;
        $historyLogModel->script_finished = $endDate;
        $historyLogModel->import_count = $d;
        $historyLogModel->save();
    }

}
