<?php namespace App\Contracts;

use App\Models\Customer;
use App\EbayOrderItems;
use App\EbayOrders;
use App\EbayRefund;
use App\Invoice;
use App\Models\Sale;
use App\Unlock\Order;
use App\Models\User;
use Illuminate\Support\Collection;

interface Invoicing {

	const DELIVERY_UK = 'GB Delivery';
	const DELIVERY_EUROPE = 'EU & NI Delivery';
	const DELIVERY_WORLD = 'Worldwide Delivery';

	const SALE_UK = 'B2B Device Sales';
	const SALE_EUROPE = 'EU B2B Device Sales';
	const SALE_WORLD = 'Rest of World Device Sales';
	const SALE_EBAY = 'eBay Sales'; // [TODO] remove
	const SALE_MIGHTY_DEALS = 'Mighty Deals'; // [TODO] remove
	const SALE_OTHER = 'Misc Sales';
	const SALE_BACKMARKET ='Backmarket Sales';
    const SALE_CONSUMER='Consumer Device Sales';
    const SALE_MOBILE_ADVANTAGE='MobileAdvantage Device Sales';
    const SALE_MOBILE_ADVANTAGE_REFUNDS='MobileAdvantage Discounts/Refunds Given';


	const UK_B2B_MARGIN='UK B2B Device Sales (Marginal)';
    const UK_B2B_STANDER='UK B2B Devices Sales (Vatable)';

    const UK_CONSUMER_MARGIN='Consumer Device Sales (Marginal)';
    const UK_CONSUMER_STANDER='Consumer Device Sales (Vatable)';



    const BACKMARKET_UK ='Backmarket Account UK';
    const BACKMARKET_EU ='Backmarket Account EU';
    const UK_BM_MARGIN = 'UK Backmarket Sales (Marginal)';
    const UK_BM_VATABLE = 'UK Backmarket Sales (Vatable)';
    const EU_BM_VATABLE = 'EU Backmarket Sales (Vatable)';


    const UK_EBAY_MARGIN = 'UK eBay Sales (Marginal)';
    const UK_EBAY_VATABLE = 'UK eBay Sales (Vatable)';
    const EU_EBAY_VATABLE = 'UK eBay Sales (Vatable)';



	const UNLOCK_ORDER = 'Unlock Sales'; // [TODO] remove
	const CARD_PROCESSING_FEE = "Card Processing Fee"; // [TODO] remove
	const REBUY_SERVICE = "Rebuy Service"; // [TODO] remove

	const EBAY_SALES = "eBay Sales";
	const EBAY_RETURNS = "eBay Returns";

	const EBAY_DELIVERY_FEES = "Delivery Fees";
	const EBAY_EBAY_FEES = "eBay Fees";
	const EBAY_PAYPAL_FEES = "PayPal Fees";

	public function markInvoicePaid(Sale $sale);

	public function markUnlockOrderInvoicePaid(Order $order);

	public function getSystemName();

	public function createDeliveries();

	/**
	 * @return Collection Should return collection of \App\Customer objects. See the Customer class properties for
	 *                    description of required data.
	 */
	public function getCustomers($ids = []);

	/**
	 * @return Collection Like getCustomers() but only returns people registered in this system.
	 */
	public function getRegisteredCustomers($ids = []);

	/**
	 * @return Collection Like GetRegisteredCustomers but only selected ones (See Sales getIndex method)
	 */
	public function getRegisteredSelectedCustomers($ids = []);

	public function getRegisteredCustomersWithBalance();

	public function getCustomersWithBalance($ids);

	public function getCustomersWithNegativeBalance();

	/**
	 * @return Collection Should return collection of \App\Invoice objects. See the Invoice class properties for
	 *                    description of required data.
	 */
	public function getInvoices();

	public function getAccounts($names = []);

	/**
	 * @param string $id
	 * @return Invoice
	 */
	public function getInvoice($id);

	/**
	 * @param string $id
	 * @return Customer
	 */
	public function getCustomer($id);

	public function updateCustomer(Customer $customer);

	/**
	 * @param Customer $customer
	 * @return string Id of the newly created customer. Depending on the API it might be int or string, so we have to
	 *                treat it as a string.
	 */
	public function createCustomer(Customer $customer);

	/**
	 * @param Sale $sale
	 * @return string Path to the invoice document. It should be saved in storage/app/invoices. We'll automatically
	 *                delete the files saved in that directory when they get old. So if the class implementing this
	 *                method caches the resulting file path, it should check if it exists before returning it via cache.
	 */
	public function getInvoiceDocument(Sale $sale);

	/**
	 * @param Order $order
	 * @return string Path to the invoice document
	 */
	public function getUnlockOrderInvoiceDocument(Order $order);

	/**
	 * @return string Path to the invoice document
	 */
	public function getTech360InvoiceDocument($id);

	/**
	 * @param int $id
	 * @return string Path to the CreditMemo document
	 */
	public function getCreditMemoDocument($id);

	/**
	 * @param Sale $sale
	 * @param User $customer
	 * @param string $saleName One of self::SALE_* constants.
	 * @param string|null $deliveryName One of self::DELIVERY_* constants. Can be empty, meaning no delivery (or free delivery).
	 * @return string array['id'] Invoice id. Might be int or string, depending on the API.
	 * @return string array['number'] Invoice number. Might or might not be the same as invoice id.
	 * @return float array['amount'] Total amount of the invoice. See 'Invoices > Amount' in readme for explanation
	 *                               on why we use that.
	 */
	public function createInvoice(Sale $sale, User $customer, $saleName, $deliveryName = null, $fee = null);

	//public function createEbayInvoice(Sale $sale, User $customer, $saleName, $fee = null);

	public function createEbayItemInvoice(EbayOrderItems $item, $customerId, $saleName, $deliveryName);

	public function createOrderhubInvoice(Sale $sale, User $customer, $saleName, $fee = null);

	public function createMightyDealsInvoice(Sale $sale, User $customer, $saleName, $fee = null);

	public function createCustomOrderInvoice(Sale $sale, User $customer, $saleName, $fee = null);

	public function createBatchInvoice(Sale $sale, User $customer, $saleName, $deliveryName = null, $batch, $price, $fee = null);

	public function createTech360Invoice(User $customer, $saleName, $deliveryName = null, $items);

	public function createUnlockOrderInvoice(Order $order, User $customer, $saleName, $deliveryName = null);

	public function createSubscriptionInvoice(User $user);

	public function createZapperPurchaseOrder($items);

	public function createZapperCamoradaPartnerPurchaseOrder($items);

	public function createRebuyBillingInvoice($customerId, $saleName, $deliveryName = null, $items);

	public function createEbayRefundCreditNote(EbayRefund $ebayRefund, $customerId, $saleName);

	public function createEbayFeesSupplierBill($supplierId, $data);

	public function getSupplierBillDocument($id);

	public function addCardProcessingFee(Sale $sale);

	/**
	 * @return string Path to the PO Document
	 */
	public function getPurchaseOrderDocument($id);

	/**
	 * @param Sale $sale
	 * @return void
	 */
	public function voidInvoice(Sale $sale);

	/**
	 * @param Sale $sale
	 * @return void
	 */
	public function deleteInvoice(Sale $sale);

	/**
	 * @param $minutes For how long should the class cache costly data. Set to 0 to disable caching. Please note that
	 *                 setting a value lower than used before doesn't mean that previously cached data will expire
	 *                 faster. But setting it to 0 guarantees that no cached data will be returned, not just that future
	 *                 call results won't be cached.
	 * @return void
	 */
	public function setCacheTime($minutes);

}
