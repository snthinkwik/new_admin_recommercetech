<?php namespace App\Invoicing;

/*require base_path('/vendor/quickbooks/config.php');
require_once(QUICKBOOKS_PATH_SDK_ROOT . 'Core/ServiceContext.php');
require_once(QUICKBOOKS_PATH_SDK_ROOT . 'DataService/DataService.php');
require_once(QUICKBOOKS_PATH_SDK_ROOT . 'PlatformService/PlatformService.php');*/
use App\Contracts\Quickbooks as QuickbooksContract;
use App\Models\Address;
use App\Models\Customer;
use App\EbayOrderItems;
use App\EbayOrders;
use App\EbayRefund;
use App\Invoice;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\SalePart;
use App\Unlock\Order;
use App\Models\User;
use Cache;
use Exception;
use Illuminate\Support\Collection;
use QuickBooksOnline\API\Data\IPPBill;
use QuickBooksOnline\API\Data\IPPCompany;
use QuickBooksOnline\API\Data\IPPCompanyInfo;
use QuickBooksOnline\API\Data\IPPCreditMemo;
use QuickBooksOnline\API\Data\IPPCustomer;
use QuickBooksOnline\API\Data\IPPCustomField;
use QuickBooksOnline\API\Data\IPPEmailAddress;
use QuickBooksOnline\API\Data\IPPInvoice;
use QuickBooksOnline\API\Data\IPPItem;
use QuickBooksOnline\API\Data\IPPItemBasedExpenseLineDetail;
use QuickBooksOnline\API\Data\IPPLine;
use QuickBooksOnline\API\Data\IPPLinkedTxn;
use QuickBooksOnline\API\Data\IPPPayment;
use QuickBooksOnline\API\Data\IPPPhysicalAddress;
use QuickBooksOnline\API\Data\IPPSalesItemLineDetail;
use QuickBooksOnline\API\Data\IPPShipMethod;
use QuickBooksOnline\API\Data\IPPTelephoneNumber;
use Setting;
use QuickBooksOnline\API\Data\IPPAccountBasedExpenseLineDetail;
use QuickBooksOnline\API\Data\IPPPurchaseOrder;
use App\Invoicing;

class Quickbooks extends Invoicing {

	/**
	 * @var \DataService;
	 */
	protected $dataService;

	/**
	 * @var \IPPTaxCode
	 */
	protected $standardTaxCode;

	public function __construct()
	{
		$this->dataService = $this->getDataService();
	}

	public function markInvoicePaid(Sale $sale)
	{

		Cache::forget("quickbooks.invoice_pdf.$sale->invoice_api_id");
		Cache::forget("quickbooks.invoices.$sale->invoice_api_id");
		Cache::forget("quickbooks.invoices");

		$qbPayment = new IPPPayment();
		$qbPayment->CustomerRef = $sale->customer_api_id;
		$qbPayment->TotalAmt = $sale->invoice_total_amount;
		$line = new IPPLine();
		$line->Amount = $sale->invoice_total_amount;
		$line->CurrencyRef = 'GBP';
		$linkedTxn = new IPPLinkedTxn();
		$linkedTxn->TxnId = $sale->invoice_api_id;
		$linkedTxn->TxnType = 'Invoice';
		$line->LinkedTxn = $linkedTxn;
		$qbPayment->Line = [$line];

		$newQbPayment = $this->dataService->Add($qbPayment);
		if (!$newQbPayment) {
			throw new Exception("Payment creation problem.\n\n" . $this->getLastErrorFullInfo());
		}
	}

	public function markUnlockOrderInvoicePaid(Order $order)
	{
		Cache::forget("quickbooks.invoice_pdf.$order->invoice_api_id");
		Cache::forget("quickbooks.invoices.$order->invoice_api_id");
		Cache::forget("quickbooks.invoices");

		$qbPayment = new IPPPayment();
		$qbPayment->CustomerRef = $order->customer_api_id;
		$qbPayment->TotalAmt = $order->invoice_total_amount;
		$line = new IPPLine();
		$line->Amount = $order->invoice_total_amount;
		$line->CurrencyRef = 'GBP';
		$linkedTxn = new IPPLinkedTxn();
		$linkedTxn->TxnId = $order->invoice_api_id;
		$linkedTxn->TxnType = 'Invoice';
		$line->LinkedTxn = $linkedTxn;
		$qbPayment->Line = [$line];

		$newQbPayment = $this->dataService->Add($qbPayment);
		if (!$newQbPayment) {
			throw new Exception("Payment creation problem.\n\n" . $this->getLastErrorFullInfo());
		}
	}

	public function getCompanyInfo()
	{
		return $this->dataService->Query("SELECT * FROM CompanyInfo")[0];
	}

	public function createSubscriptionInvoice(User $user)
	{
		$knownItems = $this->getKnownItems();
		$accounts = $this->getAllAccounts();
		$qbInvoice = new IPPInvoice();
		$qbInvoice->CustomerRef = $user->invoice_api_id;
		$qbInvoice->SalesTermRef = 1;

		// QB custom fields can only be 31 characters long or less.
		if (strlen($user->email) <= 31) {
			$customEmailFieldId = $this->getCustomFieldId('Email');
			if ($customEmailFieldId) {
				$emailField = new IPPCustomField();
				$emailField->DefinitionId = $customEmailFieldId;
				$emailField->Name = 'Email';
				$emailField->Type = 'StringType';
				$emailField->StringValue = $user->email;
				$qbInvoice->CustomField = [$emailField];
			}
		}

		$line = new IPPLine();
		$line->Amount = 149;
		$line->DetailType = 'SalesItemLineDetail';
		$line->Description = 'RCT VIP Subscription';
		$details = new IPPSalesItemLineDetail();
		$details->Qty = 1;
		$details->ItemRef = $knownItems['Service Sales']->Id;
		$details->TaxCodeRef = $knownItems['Service Sales']->SalesTaxCodeRef;
		$line->SalesItemLineDetail = $details;
		$qbInvoice->Line = [$line];

		$newQbInvoice = $this->dataService->Add($qbInvoice);
		if (!$newQbInvoice) {
			throw new Exception("Invoice creation problem.\n\n" . $this->getLastErrorFullInfo());
		}

		$qbPayment = new IPPPayment();
		$qbPayment->CustomerRef = $user->invoice_api_id;
		$qbPayment->TotalAmt = 178.80;
		$line = new IPPLine();
		$line->Amount = 178.80;
		$line->CurrencyRef = 'GBP';
		$linkedTxn = new IPPLinkedTxn();
		$linkedTxn->TxnId = $newQbInvoice->Id;
		$linkedTxn->TxnType = 'Invoice';
		$line->LinkedTxn = $linkedTxn;
		$qbPayment->Line = [$line];

		$newQbPayment = $this->dataService->Add($qbPayment);
		if (!$newQbPayment) {
			throw new Exception("Payment creation problem.\n\n" . $this->getLastErrorFullInfo());
		}

		return $newQbInvoice->Id;
	}

	public function createSaleServices()
	{
		$items = collect($this->getKnownItems())->keyBy('Name');
		$taxCodes = collect($this->getAllTaxCodes())->keyBy('Name');
		$accounts = collect($this->getAllAccounts())->keyBy('Name');

		if (!isset($taxCodes['20.0% S'])) {
			Cache::forget("quickbooks.taxes_codes");
			die(
				"There's no \"20.0% S\" tax. That's odd because it should be available by defaults. Is your company " .
				"a UK company? If you're sure you have a correct company, please create the tax with 20% value.\n"
			);
		}

		if (!isset($taxCodes['VAT Margin'])) {
			Cache::forget("quickbooks.taxes_codes");
			die(
				"There's no \"VAT Margin\" tax. Please create it in QuickBooks and give it the values of 0% for both " .
				"sales and purchases. I don't know what values it needs for other required fields as they're not shown " .
				"after saving. If you're doing this in Sandbox then just go with the first one for each field.\n"
			);
		}

		$requiredAccountData = [
			'UK B2B Device Sales' => ['vatName' => 'VAT Margin', 'detailType' => 'Sales of Product Income'],
			'EU B2B Device Sales' => ['vatName' => '0.0% ECG', 'detailType' => 'Sales of Product Income'],
			'Rest of World Device Sales' => ['vatName' => 'Exempt', 'detailType' => 'Sales of Product Income'],
			'Misc Sales' => ['vatName' => '20.0% S', 'detailType' => 'Service/Fee Income'],
		];

		foreach ($requiredAccountData as $accountName => $accountInfo) {
			if (!isset($accounts[$accountName])) {
				Cache::forget("quickbooks.income_accounts");
				die(
					"There's no \"$accountName\" account. Please create it in QuickBooks using the following details:\n" .
					"Category Type: Income\nDetail Type: $accountInfo[detailType] \nName: $accountName\n" .
						"Default VAT Code: $accountInfo[vatName]\n"
				);
			}
		}

		foreach ($this->getSaleServiceSpecs() as $name => $spec) {
			// Create sale service if it doesn't exist.
			if (!isset($items[$name])) {
				echo "Creating \"$name\"... ";
				$service = new IPPItem();
				$service->Type = 'Service';
				$service->Name = $name;
				$service->SalesTaxCodeRef = $taxCodes[$spec['taxCodeName']]->Id;
				$service->IncomeAccountRef = $accounts[$spec['accountName']]->Id;
				$res = $this->dataService->Add($service);
				if ($res) {
					echo "done.\n";
				}
				else {
					echo "\n";
					die("An error occurred: \n" . $this->getLastErrorFullInfo() . "\n");
				}
			}
			// Check if it's correct otherwise.
			else {
				echo "Sale service \"$name\" already exists.\n";
				$saleService = $items[$name];
				if ($saleService->SalesTaxCodeRef != $taxCodes[$spec['taxCodeName']]->Id) {
					echo "ERROR: tax code ref ($saleService->SalesTaxCodeRef) is not as expected " .
						"({$taxCodes[$spec['taxCodeName']]->Id}).\n";
				}
				if ($saleService->IncomeAccountRef != $accounts[$spec['accountName']]->Id) {
					echo "ERROR: account ref ($saleService->IncomeAccountRef) is not as expected " .
						"({$accounts[$spec['accountName']]->Id}).\n";
				}
			}
		}

		Cache::forget('quickbooks.known_items');
	}

	public function getSaleServiceSpecs()
	{
		return [
			Invoicing::SALE_UK => [
				'taxCodeName' => 'VAT Margin',
				'accountName' => 'UK B2B Device Sales',
			],
			Invoicing::SALE_EUROPE => [
				'taxCodeName' => '0.0% ECG',
				'accountName' => 'EU B2B Device Sales',
			],
			Invoicing::SALE_WORLD => [
				'taxCodeName' => 'Exempt',
				'accountName' => 'Rest of World Device Sales',
			],
			'Misc Sales' => [
				'taxCodeName' => '20.0% S',
				'accountName' => 'Misc Sales',
			],
			/*'Zapper Commision' => [
				'taxCodeName' => 'VAT Margin',
				'accountName' => 'Zapper Commision'
			]*/
		];
	}

	public function createDeliveries()
	{
		$items = collect($this->getKnownItems())->keyBy('Name');
		$taxCodes = collect($this->getAllTaxCodes())->keyBy('Name');
		$accounts = collect($this->getAllAccounts())->keyBy('Name');

		if (!isset($accounts['Shipping Income'])) {
			die(
				"There's no \"Shipping Income\" account. It's needed for categorising delivery income. Please create " .
				"it in QuickBooks using the following details: \nCategory Type: Income\nDetail Type: Service/Fee Income\n" .
				"Name: Shipping Income\nDefault VAT Code: 20.0% S\n"
			);
		}

		foreach ($this->getDeliverySpecs() as $name => $deliverySpec) {
			// Create delivery if it doesn't exist.
			if (!isset($items[$name])) {
				echo "Creating \"$name\"... ";
				$service = new IPPItem();
				$service->Type = 'Service';
				$service->Name = $name;
				$service->Description = $deliverySpec['description'];
				$service->UnitPrice = $deliverySpec['price'];
				$service->SalesTaxCodeRef = $taxCodes[$deliverySpec['taxCodeName']]->Id;
				$service->IncomeAccountRef = $accounts['Shipping Income']->Id;
				$res = $this->dataService->Add($service);
				if ($res) {
					echo "done.\n";
				}
				else {
					echo "\n";
					die("An error occurred: \n" . $this->getLastErrorFullInfo() . "\n");
				}
			}
			// Check if it's correct otherwise.
			else {
				echo "Delivery \"$name\" already exists.\n";
				$delivery = $items[$name];
				if ($delivery->UnitPrice != $deliverySpec['price']) {
					echo "ERROR: unit price ($delivery->UnitPrice) is not as expected ($deliverySpec[price]).\n";
				}
				if ($delivery->SalesTaxCodeRef != $taxCodes[$deliverySpec['taxCodeName']]->Id) {
					echo "ERROR: tax code ref ($delivery->SalesTaxCodeRef) is not as expected " .
						"({$taxCodes[$deliverySpec['taxCodeName']]->Id}).\n";
				}
				if ($delivery->Description != $deliverySpec['description']) {
					echo "ERROR: description ($delivery->Description) is not as expected ($deliverySpec[description]).\n";
				}
			}
		}

		Cache::forget('quickbooks.known_items');
	}

	public function getDeliverySpecs()
	{
		return [
			Invoicing::DELIVERY_UK => [
				'description' => 'Delivery to UK',
				'price' => 6.50,
				'taxCodeName' => '20.0% S',
			],
			Invoicing::DELIVERY_EUROPE => [
				'description' => Invoicing::DELIVERY_EUROPE,
				'price' => 29,
				'taxCodeName' => '0.0% ECS',
			],
			Invoicing::DELIVERY_WORLD => [
				'description' => Invoicing::DELIVERY_WORLD,
				'price' => 49,
				'taxCodeName' => 'Exempt',
			],
		];
	}

	public function getInvoiceDocument(Sale $sale)
	{
		$cacheKey = "quickbooks.invoice_pdf.$sale->invoice_api_id";
		if ($this->cacheTime && Cache::has($cacheKey) && file_exists(Cache::get($cacheKey))) {
			return Cache::get($cacheKey);
		}

		$qbInvoiceSearch = new IPPInvoice();
		$qbInvoiceSearch->Id = $sale->invoice_api_id;
		$invoicePath = $this->dataService->DownloadPDF($qbInvoiceSearch);
		$storagePath = tempnam(storage_path('app/invoices'), 'invoice-');
		unlink($storagePath);
		$storagePath = "$storagePath.pdf";
		rename($invoicePath, $storagePath);
		chmod($storagePath, 0777);

		if ($this->cacheTime) Cache::put($cacheKey, $storagePath, $this->cacheTime);
		return $storagePath;
	}

	public function getUnlockOrderInvoiceDocument(Order $order)
    {
        $cacheKey = "quickbooks.invoice_pdf.$order->invoice_api_id";
        if ($this->cacheTime && Cache::has($cacheKey) && file_exists(Cache::get($cacheKey))) {
            return Cache::get($cacheKey);
        }

        $qbInvoiceSearch = new IPPInvoice();
        $qbInvoiceSearch->Id = $order->invoice_api_id;
        $invoicePath = $this->dataService->DownloadPDF($qbInvoiceSearch);
        $storagePath = tempnam(storage_path('app/invoices'), 'invoice-');
        unlink($storagePath);
        $storagePath = "$storagePath.pdf";
        rename($invoicePath, $storagePath);
        chmod($storagePath, 0777);

        if ($this->cacheTime) Cache::put($cacheKey, $storagePath, $this->cacheTime);
        return $storagePath;
    }

    public function getTech360InvoiceDocument($id)
    {
	    $cacheKey = "quickbooks.invoice_pdf.$id";
	    if ($this->cacheTime && Cache::has($cacheKey) && file_exists(Cache::get($cacheKey))) {
		    return Cache::get($cacheKey);
	    }

	    $qbInvoiceSearch = new IPPInvoice();
	    $qbInvoiceSearch->Id = $id;
	    $invoicePath = $this->dataService->DownloadPDF($qbInvoiceSearch);
	    $storagePath = tempnam(storage_path('app/invoices'), 'invoice-');
	    unlink($storagePath);
	    $storagePath = "$storagePath.pdf";
	    rename($invoicePath, $storagePath);
	    chmod($storagePath, 0777);

	    if ($this->cacheTime) Cache::put($cacheKey, $storagePath, $this->cacheTime);
	    return $storagePath;
    }

	public function getSupplierBillDocument($id)
	{
		$cacheKey = "quickbooks.supplier_bill_pdf.$id";
		if ($this->cacheTime && Cache::has($cacheKey) && file_exists(Cache::get($cacheKey))) {
			//return Cache::get($cacheKey);
		}

		$qbBillSearch = new IPPBill();
		$qbBillSearch->Id = $id;
		$billPath = $this->dataService->Download($qbBillSearch);
		dd($billPath);
		$storagePath = tempnam(storage_path('app/invoices'), 'bill-');
		unlink($storagePath);
		$storagePath = "$storagePath.pdf";
		rename($billPath, $storagePath);
		chmod($storagePath, 0777);

		if ($this->cacheTime) Cache::put($cacheKey, $storagePath, $this->cacheTime);
		return $storagePath;
	}

	public function getCreditMemoDocument($id)
	{
		$cacheKey = "quickbooks.credit_memo_pdf.$id";
		if ($this->cacheTime && Cache::has($cacheKey) && file_exists(Cache::get($cacheKey))) {
			return Cache::get($cacheKey);
		}

		$qbCreditMemoSearch = new IPPCreditMemo();
		$qbCreditMemoSearch->Id = $id;
		$creditMemoPath = $this->dataService->downloadPDF($qbCreditMemoSearch);
		$storagePath = tempnam(storage_path('app/invoices'), 'credit-memo-');
		unlink($storagePath);
		$storagePath = "$storagePath.pdf";
		rename($creditMemoPath, $storagePath);
		chmod($storagePath, 0777);

		if ($this->cacheTime) Cache::put($cacheKey, $storagePath, $this->cacheTime);
		return $storagePath;
	}

	public function getInvoices($ids = [])
	{
		$rawInvoices = $this->getRawInvoices($ids);
		$invoices = new Collection();

		foreach ($rawInvoices as $qbInvoice) {
			$invoice = new Invoice();
			$invoice->api_id = $qbInvoice->Id;
			$invoice->total_amount = $qbInvoice->TotalAmt;
			if (!$qbInvoice->Balance) {
				$invoice->status = Invoice::STATUS_PAID;
			}
			else {
				$invoice->status = Invoice::STATUS_OPEN;
			}
			$invoices[] = $invoice;
		}

		return $invoices;
	}

	public function getCustomers($ids = [])
	{
		$customers = [];
		$rawCustomers = $this->getRawCustomers($ids);

		/** @var IPPCustomer $apiCustomer */
		foreach ($rawCustomers as $apiCustomer) {
			$customer = new Customer([
				'external_id' => $apiCustomer->Id,
				'first_name' => $apiCustomer->GivenName,
				'last_name' => $apiCustomer->FamilyName,
				'company_name' => $apiCustomer->CompanyName,
				'display_name' => $apiCustomer->DisplayName,
				'email' => $apiCustomer->PrimaryEmailAddr ? $apiCustomer->PrimaryEmailAddr->Address : '',
				'balance' => $apiCustomer->Balance,
			]);

			// If more than one email in the email field.
			if (strpos($customer->email, ',') !== false) {
				$customer->email = preg_split('/\s*,\s*/', $customer->email, -1, PREG_SPLIT_NO_EMPTY)[0];
			}

			$addressDefinitions = ['BillAddr' => 'billing_address', 'ShipAddr' => 'shipping_address'];
			foreach ($addressDefinitions as $qbAddressType => $ourAddressType) {
				if ($apiCustomer->$qbAddressType) {
					$customer->$ourAddressType = new Address([
						'line1' => $apiCustomer->$qbAddressType->Line1,
						'line2' => $apiCustomer->$qbAddressType->Line2,
						'city' => $apiCustomer->$qbAddressType->City,
						'country' => $apiCustomer->$qbAddressType->Country,
                        'county'=>$apiCustomer->$qbAddressType->CountrySubDivisionCode,
						'postcode' => $apiCustomer->$qbAddressType->PostalCode,
					]);
				}
			}

			$customers[] = $customer;
		}

		return (new Collection($customers))->sort(function($a, $b) {
			return strnatcasecmp($a->full_name, $b->full_name);
		});
	}

	public function getCustomersWithBalance($ids)
	{
		$rawCustomers = [];
		$customers = [];
		$query = "select * from Customer ";

		$query .= "where Balance > '0'";
		//if($ids)
		//	$query .= "and Id in ('" . implode("', '", array_map('intval', $ids)) . "')";


		$pageNumber = 0;
		$perPage = 500;

		do {
			$start = $pageNumber * $perPage + 1;
			$pagedQuery = "$query startPosition $start maxResults $perPage";
			$apiRes = $this->dataService->Query($pagedQuery);
			foreach ($apiRes ?: [] as $qbCustomer) {
				$rawCustomers[] = $qbCustomer;
			}
			$pageNumber++;
		}
			// If the number is lower than $perPage, avoid one unnecessary request - we know it's the end of data.
		while ($apiRes && count($apiRes) === $perPage);

		foreach ($rawCustomers as $apiCustomer) {
			$customer = new Customer([
				'external_id' => $apiCustomer->Id,
				'first_name' => $apiCustomer->GivenName,
				'last_name' => $apiCustomer->FamilyName,
				'company_name' => $apiCustomer->CompanyName,
				'display_name' => $apiCustomer->DisplayName,
				'email' => $apiCustomer->PrimaryEmailAddr ? $apiCustomer->PrimaryEmailAddr->Address : '',
				'balance' => $apiCustomer->Balance,
			]);

			// If more than one email in the email field.
			if (strpos($customer->email, ',') !== false) {
				$customer->email = preg_split('/\s*,\s*/', $customer->email, -1, PREG_SPLIT_NO_EMPTY)[0];
			}

			$addressDefinitions = ['BillAddr' => 'billing_address', 'ShipAddr' => 'shipping_address'];
			foreach ($addressDefinitions as $qbAddressType => $ourAddressType) {
				if ($apiCustomer->$qbAddressType) {
					$customer->$ourAddressType = new Address([
						'line1' => $apiCustomer->$qbAddressType->Line1,
						'line2' => $apiCustomer->$qbAddressType->Line2,
						'city' => $apiCustomer->$qbAddressType->City,
						'country' => $apiCustomer->$qbAddressType->Country,
						'postcode' => $apiCustomer->$qbAddressType->PostalCode,
					]);
				}
			}

			$customers[] = $customer;
		}

		return (new Collection($customers))->sort(function($a, $b) {
			return strnatcasecmp($a->full_name, $b->full_name);
		});
	}

	public function getCustomersWithNegativeBalance()
	{
		$rawCustomers = [];
		$customers = [];
		$query = "select * from Customer ";

		$query .= "where Balance < '0'";

		$pageNumber = 0;
		$perPage = 500;

		do {
			$start = $pageNumber * $perPage + 1;
			$pagedQuery = "$query startPosition $start maxResults $perPage";
			$apiRes = $this->dataService->Query($pagedQuery);
			foreach ($apiRes ?: [] as $qbCustomer) {
				$rawCustomers[] = $qbCustomer;
			}
			$pageNumber++;
		}
		// If the number is lower than $perPage, avoid one unnecessary request - we know it's the end of data.
		while ($apiRes && count($apiRes) === $perPage);

		foreach ($rawCustomers as $apiCustomer) {
			$customer = new Customer([
				'external_id' => $apiCustomer->Id,
				'first_name' => $apiCustomer->GivenName,
				'last_name' => $apiCustomer->FamilyName,
				'company_name' => $apiCustomer->CompanyName,
				'display_name' => $apiCustomer->DisplayName,
				'email' => $apiCustomer->PrimaryEmailAddr ? $apiCustomer->PrimaryEmailAddr->Address : '',
				'balance' => $apiCustomer->Balance,
			]);

			// If more than one email in the email field.
			if (strpos($customer->email, ',') !== false) {
				$customer->email = preg_split('/\s*,\s*/', $customer->email, -1, PREG_SPLIT_NO_EMPTY)[0];
			}

			$addressDefinitions = ['BillAddr' => 'billing_address', 'ShipAddr' => 'shipping_address'];
			foreach ($addressDefinitions as $qbAddressType => $ourAddressType) {
				if ($apiCustomer->$qbAddressType) {
					$customer->$ourAddressType = new Address([
						'line1' => $apiCustomer->$qbAddressType->Line1,
						'line2' => $apiCustomer->$qbAddressType->Line2,
						'city' => $apiCustomer->$qbAddressType->City,
						'country' => $apiCustomer->$qbAddressType->Country,
						'postcode' => $apiCustomer->$qbAddressType->PostalCode,
					]);
				}
			}

			$customers[] = $customer;
		}

		return (new Collection($customers))->sort(function($a, $b) {
			return strnatcasecmp($a->full_name, $b->full_name);
		});
	}

	/**
	 * @param Customer $customer
	 * @throws Exception
	 * @return void
	 */
	public function updateCustomer(Customer $customer)
	{
		$qbCustomer = $this->getRawCustomer($customer->external_id);
		$qbCustomer->GivenName = $customer->first_name;
		$qbCustomer->FamilyName = $customer->last_name;
		$qbCustomer->CompanyName = $customer->company_name;
		$qbCustomer->PrimaryEmailAddr = new IPPEmailAddress();
		$qbCustomer->PrimaryEmailAddr->Address = $customer->email;
		$qbCustomer->PrimaryPhone = new IPPTelephoneNumber();
		$qbCustomer->PrimaryPhone->FreeFormNumber = $customer->phone;

		$qbAddresses = [];
		// We don't use all the fields from Quickbooks so we don't want to overwrite the ones we don't use with empty
		// values. Therefore, we take the existing addresses if available and only create fresh objects when not.
		$qbAddresses['BillAddr'] = $qbCustomer->BillAddr ?: new IPPPhysicalAddress();
		$qbAddresses['ShipAddr'] = $qbCustomer->ShipAddr ?: new IPPPhysicalAddress();

		$addressDefinitions = ['BillAddr' => 'billing_address', 'ShipAddr' => 'shipping_address'];
		foreach ($addressDefinitions as $qbAddressType => $ourAddressType) {
			$ourAddressValues = $customer->$ourAddressType
				? array_only($customer->$ourAddressType->toArray(), ['line1', 'line2', 'city', 'country', 'postcode','county'])
				: ['line1' => '', 'line2' => '', 'city' => '', 'country' => '', 'postcode' => '','county'=>''];

			// We only want to set the address object if it already existed ($qbCustomer->$qbAddressType not empty)
			// or if we have something to put there ($ourAddressValues has some values).
			if ($qbCustomer->$qbAddressType || array_filter($ourAddressValues)) {
				$qbAddresses[$qbAddressType]->Line1 = $ourAddressValues['line1'];
				$qbAddresses[$qbAddressType]->Line2 = $ourAddressValues['line2'];
				$qbAddresses[$qbAddressType]->City = $ourAddressValues['city'];
				$qbAddresses[$qbAddressType]->Country = $ourAddressValues['country'];
				$qbAddresses[$qbAddressType]->PostalCode = $ourAddressValues['postcode'];
				$qbAddresses[$qbAddressType]->CountrySubDivisionCode = $ourAddressValues['county'];
				$qbCustomer->$qbAddressType = $qbAddresses[$qbAddressType];
			}
		}

		$res = $this->dataService->Update($qbCustomer);
		if (!$res) {
			throw new Exception("Could not update customer.\n\n" . $this->getLastErrorFullInfo());
		}
		Cache::forget('quickbooks.customers.'.$customer->external_id);
		Cache::forget('quickbooks.customers');
	}

	public function addCardProcessingFee(Sale $sale)
	{
		$qbInvoice = $this->getRawInvoices([$sale->invoice_api_id])[0];

		$knownItems = $this->getKnownItems();
		//dd($knownItems);
		$saleName = Invoicing::CARD_PROCESSING_FEE;

		$line = new IPPLine();
		$line->Amount = number_format($sale->invoice_total_amount*0.016+0.12, 2);
		$line->DetailType = 'SalesItemLineDetail';
		$line->Description = htmlspecialchars("");
		$details = new IPPSalesItemLineDetail();
		$details->Qty = 1;
		$details->ItemRef = $knownItems[$saleName]->Id;
		$details->TaxCodeRef = $knownItems[$saleName]->SalesTaxCodeRef;
		$line->SalesItemLineDetail = $details;
		//dd($line);
		$qbInvoice->Line[] = $line;

		$newQbInvoice = $this->dataService->Update($qbInvoice);
		if (!$newQbInvoice) {
			throw new Exception("Payment creation problem.\n\n" . $this->getLastErrorFullInfo());
		}

		return [
			'id' => $newQbInvoice->Id,
			'number' => $newQbInvoice->Id, // The same as id.
			'amount' => $newQbInvoice->TotalAmt,
		];
	}

	public function createCustomer(\App\Models\Customer $customer)
	{
		$qbCustomer = new IPPCustomer();
		$qbCustomer->GivenName = $customer->first_name;
		$qbCustomer->FamilyName = $customer->last_name;
		$qbCustomer->PrimaryEmailAddr = new IPPEmailAddress();
		$qbCustomer->PrimaryEmailAddr->Address = $customer->email;
		$qbCustomer->CompanyName = $customer->company_name;
		$qbCustomer->PrimaryPhone = new IPPTelephoneNumber();
		$qbCustomer->PrimaryPhone->FreeFormNumber = $customer->phone;

		$addressDefinitions = ['BillAddr' => 'billing_address', 'ShipAddr' => 'shipping_address'];
		foreach ($addressDefinitions as $qbAddressType => $ourAddressType) {
			if ($customer->$ourAddressType) {
				$qbCustomer->$qbAddressType = new IPPPhysicalAddress();
				$qbCustomer->$qbAddressType->Line1 = $customer->$ourAddressType->line1;
				$qbCustomer->$qbAddressType->Line2 = $customer->$ourAddressType->line2;
				$qbCustomer->$qbAddressType->City = $customer->$ourAddressType->city;
				$qbCustomer->$qbAddressType->CountrySubDivisionCode = $customer->$ourAddressType->county;
				$qbCustomer->$qbAddressType->PostalCode = $customer->$ourAddressType->postcode;
				$qbCustomer->$qbAddressType->Country = $customer->$ourAddressType->country;
			}
		}

		$qbCreated = $this->dataService->Add($qbCustomer);
		if (!$qbCreated) {
			throw new Exception("Could not create customer.\n\n" . $this->getLastErrorFullInfo());
		}

		Cache::forget('quickbooks.customers');
		return $qbCreated->Id;
	}

	public function createInvoice(Sale $sale, User $customer, $saleName, $platform=null,$deliveryName = null, $fee = null)
	{

		if (!$customer->invoice_api_id) {
			throw new Exception("User with id $customer->id doesn't have QuickBooks id.");
		}
		$urlResource = null;
		$knownItems = $this->getKnownItems();
		$knownTaxes = collect($this->getAllTaxCodes())->keyBy('Name');
		$tax = "20.0% S";

		// Create base invoice.

        $randomDigit = mt_rand(1000,9999);
        $docNumber=Sale::where('invoice_doc_number',$randomDigit)->first();
        if(!is_null($docNumber)){
            $randomNumber=$randomDigit+1;
        }else{
            $randomNumber=$randomDigit;
        }

		$qbInvoice = new IPPInvoice();
		$qbInvoice->CustomerRef = $customer->invoice_api_id;
		$qbInvoice->SalesTermRef = 1;
        $qbInvoice->DocNumber =$randomNumber ;
		$qbInvoice->Line = [];
		$qbInvoice->TrackingNum=$sale->tracking_number;
        $qbInvoice->ShipMethodRef=$sale->courier;


        $description='Thank you again for your continued business.
Information on the VAT Margin Scheme for second hand goods
can be found at www.recomm.co.uk. Customers should ensure
that they have read and understood the HMRC VAT Margin reporting requirements where applicable.

Bank Details: Recommerce Ltd
Lloyds Bank
Sortcode: 30-98-97
Account No: 49869160';


        $qbInvoice->CustomerMemo= $description;
        $qbInvoice->PrivateNote = "Stock item ids: " . implode(', ', $sale->stock->lists('id'));
	//	$qbInvoice->PrivateNote = "Stock item ids: " . implode(', ', $sale->stock->lists('id'));

		// QB custom fields can only be 31 characters long or less.
		if (strlen($customer->email) <= 31) {
			$customEmailFieldId = $this->getCustomFieldId('Email');
			if ($customEmailFieldId) {
				$emailField = new IPPCustomField();
				$emailField->DefinitionId = $customEmailFieldId;
				$emailField->Name = 'Email';
				$emailField->Type = 'StringType';
				$emailField->StringValue = $customer->email;
				$qbInvoice->CustomField = [$emailField];
			}
		}




		// Add each stock item.
		/** @var Stock $item */
        $taxCodeRef='';
        $vatType='';
        $total=0;
        $productIds=[];
        $totalPurchaseCost=0;
        $totalSalePrice=0;
        $totalPurchasePrice=0;
        $deliveryNoteItems=[];
        $noteSellerName='';
		foreach ($sale->stock as $item) {



		    if(count($item->product)>0){

		        if($item->product->non_serialised){

                    $productIds[$item->id]=$item->product->id;
		           // array_push($productIds,$item->product->id);
                }
            }

		    $total+=$item->total_cost_with_repair;

            $totalPurchaseCost+=$item->total_cost_with_repair;
            $totalSalePrice+=$item->sale_price;
            $totalPurchasePrice+=$item->purchase_price;



            if($customer->location === User::LOCATION_EUROPE ){
                $taxCodeRef=$knownTaxes['0.0% Z']->Id;
                $noteSellerName=$saleName;
            }elseif($customer->location !== User::LOCATION_EUROPE && $item->vat_type==="Standard"){
                $taxCodeRef=$knownTaxes['20.0% S']->Id;
                $noteSellerName=$saleName;
            }else{

                if($platform===Sale::PLATFORM_EBAY){
                    $noteSellerName='UK eBay Sales (Vat Margin)';
                }else{
                    $taxCodeRef=$knownItems[$saleName]->SalesTaxCodeRef;
                    $noteSellerName=$saleName;
                }

            }

			$line = new IPPLine();

            $totalCosts=0;
            $taxRate=0;
            if($platform===Sale::PLATFORM_EBAY) {
                if ($item->vat_type === "Standard") {
                    $vatType = "Standard";
                    $totalCosts = $item->total_cost_with_repair;
                    $taxRate = 0.20;
                } else {
                    $vatType = "Margin";
                }
                $calculations = calculationOfProfitEbay($taxRate, $item->sale_price, $totalCosts, $vatType, $item->purchase_price);
                if ($taxRate*100 > 0) {
                    $line->Amount = is_null($item->temporary_qty)?$calculations['total_price_ex_vat']:$calculations['total_price_ex_vat']*$item->temporary_qty  ;
                } else {
                    $line->Amount =is_null($item->temporary_qty)?$item->sale_price:$item->sale_price*$item->temporary_qty;
                }
            }else{
                if($item->vat_type==="Standard"){
                    $line->Amount =is_null($item->temporary_qty)?$item->total_price_ex_vat: $item->total_price_ex_vat*$item->temporary_qty;
                }else{
                    $line->Amount = is_null($item->temporary_qty) ? $item->sale_price: $item->sale_price * $item->temporary_qty;
                }
            }

			$line->DetailType = 'SalesItemLineDetail';
			$line->Description = htmlspecialchars($item->long_name_without_network);
			$details = new IPPSalesItemLineDetail();
			$details->Qty =is_null($item->temporary_qty)?1:$item->temporary_qty;
			$details->ItemRef = $knownItems[$saleName]->Id;
            $details->TaxCodeRef = $taxCodeRef;
			$line->SalesItemLineDetail = $details;
			$qbInvoice->Line[] = $line;
			$vatType=$item->vat_type;


            $deliveryNoteItems[]=[
                'service_name'=>$noteSellerName,
                'qty'=>is_null($item->temporary_qty)?1:$item->temporary_qty,
                'description'=>htmlspecialchars($item->long_name_without_network),
            ];




		}

//		if($platform===Sale::PLATFORM_EBAY){
//            if($customer->location === User::LOCATION_UK){
//                $deliveryName="UK Delivery";
//            }else{
//                $deliveryName="EU Delivery";
//            }
//        }



        // Add delivery



//        $deliveryNoteItems[]=[
//            'service_name'=>$deliveryName,
//            'qty'=>1,
//            'description'=>'',
//        ];


        if ($deliveryName) {
			$delivery = $knownItems[$deliveryName];
			if (!$delivery) {
				throw new Exception("Delivery not found. Delivery name: \"$deliveryName\".");
			}
			$line = new IPPLine();
			$line->DetailType = 'SalesItemLineDetail';
			$line->Amount = $delivery->UnitPrice;
			$details = new IPPSalesItemLineDetail();
			$details->Qty = 1;
			$details->ItemRef = $delivery->Id;
			$details->TaxCodeRef = $delivery->SalesTaxCodeRef;
			$line->SalesItemLineDetail = $details;
			$qbInvoice->Line[] = $line;
		}

		if($fee && !$sale->card_processing_fee) {
			$feeSaleName = Invoicing::CARD_PROCESSING_FEE;
			$line = new IPPLine();
			$line->Amount = number_format($sale->invoice_total_amount*0.016+0.12, 2);
			$line->DetailType = 'SalesItemLineDetail';
			$line->Description = htmlspecialchars("");
			$details = new IPPSalesItemLineDetail();
			$details->Qty = 1;
			$details->ItemRef = $knownItems[$feeSaleName]->Id;
			$details->TaxCodeRef = $knownItems[$feeSaleName]->SalesTaxCodeRef;
			$line->SalesItemLineDetail = $details;
			//dd($line);
			$qbInvoice->Line[] = $line;
		}
		// Store in API.
		$newQbInvoice = $this->dataService->Add($qbInvoice);
		if (!$newQbInvoice) {
			throw new Exception("Invoice creation problem.\n\n" . $this->getLastErrorFullInfo());
		}

		$billing_address=[];
		$shipping_address=[];

        $billAddr=(array) $newQbInvoice->BillAddr;
        $shipAddr=(array) $newQbInvoice->ShipAddr;

        $billing_address[]=[
                'line1'=>$billAddr['Line1'],
                'line2'=>$billAddr['Line2'],
                'city'=>$billAddr['City'],
                'county'=>null,
                'postcode'=>$billAddr['PostalCode'],
                'country'=>$billAddr['Country']

        ];

        $shipping_address[]=[
            'line1'=>$shipAddr['Line1'],
            'line2'=>$shipAddr['Line2'],
            'city'=>$shipAddr['City'],
            'county'=>null,
            'postcode'=>$shipAddr['PostalCode'],
            'country'=>$shipAddr['Country']

        ];


        $deliverNot=(array) (array) $deliveryNoteItems;




		return [
			'id' => $newQbInvoice->Id,
			'number' => $newQbInvoice->Id, // The same as id.
			'amount' => $newQbInvoice->TotalAmt,
            'invoice_no'=>$newQbInvoice->DocNumber,
            'vat_type'=>$vatType,
            'country'=>$customer->location,
            'delivery_charges'=>$deliveryName ?$delivery->UnitPrice:NULL,
            'product_ids'=>$productIds,
            'total_cost'=>$totalPurchaseCost,
            'total_sales'=>$totalSalePrice,
            'total_purchase'=>$totalPurchasePrice,
            'billing_address'=>$billing_address[0],
            'shipping_address'=>$shipping_address[0],
            'create_at'=>date('d/m/Y', strtotime($newQbInvoice->MetaData->CreateTime)),
            'item_list'=>$deliverNot,
            'customer_name'=>$customer->first_name.' '.$customer->last_name,
            'company_name'=>$customer->company_name,


		];
	}
    public function createBayInvoice(Sale $sale,EbayOrders $ebayOrders ,User $customer, $saleName, $deliveryName = null, $fee = null,$trackingNumber=null,$courier=null)
    {


        if (!$customer->invoice_api_id) {
            throw new Exception("User with id $customer->id doesn't have QuickBooks id.");
        }
        $urlResource = null;
        $knownItems = $this->getKnownItems();
        $knownTaxes = collect($this->getAllTaxCodes())->keyBy('Name');
        $tax = "20.0% S";

        // Create base invoice.

        $randomDigit = mt_rand(1000,9999);
        $docNumber=Sale::where('invoice_doc_number',$randomDigit)->first();
        if(!is_null($docNumber)){
            $randomNumber=$randomDigit+1;
        }else{
            $randomNumber=$randomDigit;
        }


        $qbInvoice = new IPPInvoice();
        $qbInvoice->CustomerRef = $customer->invoice_api_id;
        $qbInvoice->SalesTermRef = 1;
        $qbInvoice->DocNumber =$randomNumber ;
        $qbInvoice->Line = [];
        $qbInvoice->TrackingNum=$trackingNumber;
        $qbInvoice->ShipMethodRef=$courier;


        if($ebayOrders->platform === Stock::PLATFROM_MOBILE_ADVANTAGE){
            $ref=$ebayOrders->sales_record_number;
        }else{

            $ref=$ebayOrders->order_id;
        }
        $memo='';

        $memo.= $ref ."\n";
     //   $memo.="Buyers Ref:-".$ref ."\n";

        if(!empty($ebayOrders->shipping_email)){
            $memo.="Customer Email:-".$ebayOrders->shipping_email. "\n";
        }
        if(!is_null($ebayOrders->transaction_id)){
            $memo.="Transaction ID:-".$ebayOrders->transaction_id ."\n";
        }




        $qbInvoice->PrivateNote = $memo;
        $qbInvoice->CustomerMemo= $memo;


        // QB custom fields can only be 31 characters long or less.
        if (strlen($customer->email) <= 31) {
            $customEmailFieldId = $this->getCustomFieldId('Email');
            if ($customEmailFieldId) {
                $emailField = new IPPCustomField();
                $emailField->DefinitionId = $customEmailFieldId;
                $emailField->Name = 'Email';
                $emailField->Type = 'StringType';
                $emailField->StringValue = $customer->email;
                $qbInvoice->CustomField = [$emailField];
            }
        }



        // Add each stock item.
        /** @var Stock $item */

        $productIds=[];
        $i=0;
        $totalPurchaseCost=0;
        $totalSalePrice=0;
        $totalPurchasePrice=0;
        $vatType='';
        $noteSellerName='';
        $deliveryNoteItems=[];

        foreach ($ebayOrders->EbayOrderItems as $item) {

            $i++;
            $vatTypeCheck='';
            $imeiList=[];
            $description='';
            $taxRate = 0;
            $totalCosts = 0;
            $p_price=0;



            if($item->quantity>1){
                foreach (json_decode($item->stock_id) as $stockId){
                    $vatTypeCheck= getStockDetatils($stockId)->vat_type;


                    if(count(getStockDetatils($stockId)->product)>0){

                        if(getStockDetatils($stockId)->product->non_serialised){
                            //array_push($productIds,getStockDetatils($stockId)->product->id);

                            $productIds[$i.'-'.getStockDetatils($stockId)->product->id]=$item->quantity;

                        }
                    }

                    $totalCosts += getStockDetatils($stockId)->total_cost_with_repair;
                    $totalPurchaseCost += getStockDetatils($stockId)->total_cost_with_repair;
                    $taxRate = $item->tax_percentage * 100 > 0 ? ($item->tax_percentage) : 0;
                    $p_price+=getStockDetatils($stockId)->purchase_price;
                    $totalPurchasePrice+=getStockDetatils($stockId)->purchase_price;
                    $totalSalePrice+=getStockDetatils($stockId)->sale_price;



                    if(getStockDetatils($stockId)->imei!==""){

                        array_push($imeiList,' '.getStockDetatils($stockId)->imei);
                    }else{
                        array_push($imeiList,' '.getStockDetatils($stockId)->serial);
                    }
                }
                $description.=$item->quantity." X ";

                if(getStockDetatils(json_decode($item->stock_id)[0])->name_compare){
                    $description.= str_replace( array('@rt'), 'GB', getStockDetatils(json_decode($item->stock_id)[0])->name).' ('. getStockDetatils(json_decode($item->stock_id)[0])->condition .')';
                }else{

                    $description.=   getStockDetatils(json_decode($item->stock_id)[0])->name.' - '.getStockDetatils(json_decode($item->stock_id)[0])->capacity_formatted.' ('.getStockDetatils(json_decode($item->stock_id)[0])->condition .')';
                }
                $description.= ': '. implode(",",$imeiList)."\n";
                if ($item->tax_percentage * 100 > 0 || !$item->tax_percentage * 100 &&   $vatTypeCheck==="Standard" ) {
                    $vatType = "Standard";

                }else{
                    $vatType = "Margin";
                }

                if($ebayOrders->platform===Stock::PLATFROM_MOBILE_ADVANTAGE){
                    $saleName= getQuickBookServiceProductNameForMobileAdvantage($customer->quickbooks_customer_category,$vatType);
                }else{
                    $saleName= getQuickBookServiceProductName($customer->quickbooks_customer_category,$vatType,$customer->location,$ebayOrders->platform);
                }





            }else{
                $vatTypeCheck=$item->stock->vat_type;
                if(count($item->stock->product)>0){

                    if($item->stock->product->non_serialised){

                        $productIds[$i.'-'.$item->stock->product->id]=$item->quantity;
                    }
                }


                $totalCosts = $item->stock->total_cost_with_repair;
                $totalPurchaseCost = $item->stock->total_cost_with_repair;
                $taxRate = $item->tax_percentage * 100 > 0 ? ($item->tax_percentage) : 0;
                $p_price=$item->stock->purchase_price;
                $totalPurchasePrice=$item->stock->purchase_price;
                $totalSalePrice=$item->stock->sale_price;
                if($item->stock->imei !==""){
                    $sku=$item->stock->imei;
                }else{
                    $sku=$item->stock->serial;
                }


                $description.=$item->quantity." X ";

                if($item->stock->name_compare){

                    $description.= str_replace(array('@rt'), 'GB', $item->stock->name).' ('.$item->stock->condition.')';

                }else{
                    $description.=  $item->stock->name.' - '.$item->stock->capacity_formatted.' ('.$item->stock->condition.')';
                }

                $description.= ': '.$sku;

                if ($item->tax_percentage * 100 > 0 || !$item->tax_percentage * 100 &&   $vatTypeCheck==="Standard" ) {
                    $vatType = "Standard";

                }else{
                    $vatType = "Margin";
                }

                if($ebayOrders->platform===Stock::PLATFROM_MOBILE_ADVANTAGE){
                    $saleName= getQuickBookServiceProductNameForMobileAdvantage($customer->quickbooks_customer_category,$vatType);
                }else{
                    $saleName= getQuickBookServiceProductName($customer->quickbooks_customer_category,$vatType,$customer->location,$ebayOrders->platform);
                }


            }

            if($ebayOrders->post_to_country === "United Kingdom" || $ebayOrders->post_to_country === "Great Britain"){

                if($item->tax_percentage * 100 > 0 || !$item->tax_percentage * 100 &&   $vatTypeCheck==="Standard"){
                    $taxCodeRef=$knownTaxes['20.0% S']->Id;
                    $noteSellerName=$saleName;
                }else{
                    $taxCodeRef=$knownItems['UK eBay Sales (Vat Margin)']->SalesTaxCodeRef;

                }

            }else{
                $taxCodeRef=$knownTaxes['0.0% Z']->Id;
                $noteSellerName=$saleName;
            }

            $line = new IPPLine();

            if ($item->tax_percentage * 100 > 0 || !$item->tax_percentage * 100 &&   $vatTypeCheck==="Standard" ) {
                $vatType = "Standard";

            }else{
                $vatType = "Margin";
            }

            if($ebayOrders->platform===Stock::PLATFROM_MOBILE_ADVANTAGE || $ebayOrders->platform===Stock::PLATFROM_EBAY){
                $individual_item_price= $item['individual_item_price']*$item['quantity'];

            }else{
                $individual_item_price=  $item['individual_item_price'];
            }


            $calculations = calculationOfProfitEbay($taxRate, $individual_item_price, $totalCosts, $vatType,$p_price);
            if ($item->tax_percentage * 100 > 0 || !$item->tax_percentage * 100 &&  $vatTypeCheck==="Standard") {
                $line->Amount = $calculations['total_price_ex_vat'];
            } else {
                $line->Amount = $individual_item_price;
            }


            $line->DetailType = 'SalesItemLineDetail';
            $line->Description =  htmlspecialchars($description);
            $details = new IPPSalesItemLineDetail();
            $details->Qty = $item->quantity;
            $details->ItemRef = $knownItems[$saleName]->Id;
            $details->TaxCodeRef = $taxCodeRef;
            $line->SalesItemLineDetail = $details;
            $qbInvoice->Line[] = $line;



            $deliveryNoteItems[]=[
                'service_name'=>$noteSellerName,
                'qty'=>is_null($item->temporary_qty)?1:$item->temporary_qty,
                'description'=>htmlspecialchars($description),
            ];
        }

        // Add delivery


//        $deliveryNoteItems[]=[
//            'service_name'=>$deliveryName,
//            'qty'=>1,
//            'description'=>'',
//        ];


        if($ebayOrders->post_to_country === "United Kingdom" || $ebayOrders->post_to_country === "Great Britain"){
            $deliveryName="GB Delivery";
        }else{

            $deliveryName="EU & NI Delivery";
        }


        if ($deliveryName) {
            $delivery = $knownItems[$deliveryName];
            if (!$delivery) {
                throw new Exception("Delivery not found. Delivery name: \"$deliveryName\".");
            }
            $line = new IPPLine();
            $line->DetailType = 'SalesItemLineDetail';
            $line->Amount = ($ebayOrders->postage_and_packaging/1.2);
            $details = new IPPSalesItemLineDetail();
            $details->Qty = 1;
            $details->ItemRef = $delivery->Id;
            $line->Description =  htmlspecialchars($deliveryName);
            $details->TaxCodeRef = $delivery->SalesTaxCodeRef;
            $line->SalesItemLineDetail = $details;
            $qbInvoice->Line[] = $line;
        }

        if($fee && !$sale->card_processing_fee) {
            $feeSaleName = Invoicing::CARD_PROCESSING_FEE;
            $line = new IPPLine();
            $line->Amount = number_format($sale->invoice_total_amount*0.016+0.12, 2);
            $line->DetailType = 'SalesItemLineDetail';
            $line->Description = htmlspecialchars("");
            $details = new IPPSalesItemLineDetail();
            $details->Qty = 1;
            $details->ItemRef = $knownItems[$feeSaleName]->Id;
            $details->TaxCodeRef = $knownItems[$feeSaleName]->SalesTaxCodeRef;
            $line->SalesItemLineDetail = $details;
            //dd($line);
            $qbInvoice->Line[] = $line;
        }
        // Store in API.
        $newQbInvoice = $this->dataService->Add($qbInvoice);
        if (!$newQbInvoice) {
            throw new Exception("Invoice creation problem.\n\n" . $this->getLastErrorFullInfo());
        }



        $billAddr=(array) $newQbInvoice->BillAddr;
        $shipAddr=(array) $newQbInvoice->ShipAddr;
        $deliverNot=(array) (array) $deliveryNoteItems;

        $old_customer=$this->getCustomers([$newQbInvoice->CustomerRef]);

        return [
            'id' => $newQbInvoice->Id,
            'number' => $newQbInvoice->Id, // The same as id.
            'amount' => $newQbInvoice->TotalAmt,
            'invoice_no'=>$newQbInvoice->DocNumber,
            'ebay_country'=>$ebayOrders->post_to_country,
            'delivery_charges'=>$ebayOrders->postage_and_packaging>0 ?$ebayOrders->postage_and_packaging:NULL,
            'product_ids'=>$productIds,
            'total_cost'=>$totalPurchaseCost,
            'total_sales'=>$totalSalePrice,
            'total_purchase'=>$totalPurchasePrice,
            'vat_type'=>$vatType,
            'platform'=>$ebayOrders->platform,
            'ebay_order_id'=>$ebayOrders->id,
            'billing_address'=>$billAddr,
            'shipping_address'=>$shipAddr,
            'create_at'=>date('d/m/Y', strtotime($newQbInvoice->MetaData->CreateTime)),
            'item_list'=>$deliverNot,
            'customer_name'=>$ebayOrders->post_to_name,
            'payment_methods'=>!is_null($ebayOrders->payment_method)?$ebayOrders->payment_method:'',
            'payment_type'=>!is_null($ebayOrders->payment_type)?$ebayOrders->payment_type:'',
            'customer_email'=>$old_customer[0]['attributes']['email'],

        ];
    }

//    public function createEbayInvoice(Sale $sale, User $customer, $saleName, $fee = null)
//    {
//        if (!$customer->invoice_api_id) {
//            throw new Exception("User with id $customer->id doesn't have QuickBooks id.");
//        }
//        $urlResource = null;
//        $knownItems = $this->getKnownItems();
//
//        // Create base invoice.
//        $qbInvoice = new IPPInvoice();
//        $qbInvoice->CustomerRef = $customer->invoice_api_id;
//        $qbInvoice->SalesTermRef = 1;
//        $qbInvoice->Line = [];
//        $qbInvoice->PrivateNote = "Stock item ids: " . implode(', ', $sale->stock->lists('id'));
//
//        // QB custom fields can only be 31 characters long or less.
//        if (strlen($customer->email) <= 31) {
//            $customEmailFieldId = $this->getCustomFieldId('Email');
//            if ($customEmailFieldId) {
//                $emailField = new IPPCustomField();
//                $emailField->DefinitionId = $customEmailFieldId;
//                $emailField->Name = 'Email';
//                $emailField->Type = 'StringType';
//                $emailField->StringValue = $customer->email;
//                $qbInvoice->CustomField = [$emailField];
//            }
//        }
//
//        // Add each stock (Name taken from eBay Order)
//        foreach($sale->ebay_orders as $ebayOrder) {
//            $line = new IPPLine();
//            $line->Amount = $ebayOrder->stock->sale_price;
//            $line->DetailType = 'SalesItemLineDetail';
//            $line->Description = htmlspecialchars($ebayOrder->item_name);
//            $details = new IPPSalesItemLineDetail();
//            $details->Qty = 1;
//            $details->ItemRef = $knownItems[$saleName]->Id;
//            $details->TaxCodeRef = $knownItems[$saleName]->SalesTaxCodeRef;
//            $line->SalesItemLineDetail = $details;
//            $qbInvoice->Line[] = $line;
//        }
//
//        if($fee && !$sale->card_processing_fee) {
//            $feeSaleName = Invoicing::CARD_PROCESSING_FEE;
//
//            $line = new IPPLine();
//            $line->Amount = number_format($sale->invoice_total_amount*0.016+0.12, 2);
//            $line->DetailType = 'SalesItemLineDetail';
//            $line->Description = htmlspecialchars("");
//            $details = new IPPSalesItemLineDetail();
//            $details->Qty = 1;
//            $details->ItemRef = $knownItems[$feeSaleName]->Id;
//            $details->TaxCodeRef = $knownItems[$feeSaleName]->SalesTaxCodeRef;
//            $line->SalesItemLineDetail = $details;
//            //dd($line);
//            $qbInvoice->Line[] = $line;
//        }
//
//        // Store in API.
//        $newQbInvoice = $this->dataService->Add($qbInvoice);
//        if (!$newQbInvoice) {
//            throw new Exception("Invoice creation problem.\n\n" . $this->getLastErrorFullInfo());
//        }
//
//        return [
//            'id' => $newQbInvoice->Id,
//            'number' => $newQbInvoice->Id, // The same as id.
//            'amount' => $newQbInvoice->TotalAmt,
//        ];
//    }


    public function createEbayItemInvoice(EbayOrderItems $item, $customerId, $saleName, $deliveryName)
	{


	   // dd($item);
		$urlResource = null;
		$knownItems = $this->getKnownItems();
//dd($knownItems);
        $knownTaxes = collect($this->getAllTaxCodes())->keyBy('Name');



		$qbInvoice = new IPPInvoice();
		$qbInvoice->CustomerRef = $customerId;
		$qbInvoice->SalesTermRef = 1;
		$qbInvoice->Line = [];

       // $taxCodeRef=$knownTaxes['0.0% Z']->Id;
		$line = new IPPLine();
		$line->Amount = $item->individual_item_price;
		$line->DetailType = 'SalesItemLineDetail';
		$line->Description = htmlspecialchars("$item->item_name - $item->item_sku");
		$details = new IPPSalesItemLineDetail();
		$details->Qty = 1;
		$details->ItemRef = $knownItems[$saleName]->Id;
		$details->TaxCodeRef = $knownTaxes['20.0% S']->Id;//$knownItems[$saleName]->SalesTaxCodeRef;
		$line->SalesItemLineDetail = $details;
		$qbInvoice->Line[] = $line;

		// Add delivery
		if ($deliveryName) {
			$delivery = $knownItems[$deliveryName];
			if (!$delivery) {
				throw new Exception("Delivery not found. Delivery name: \"$deliveryName\".");
			}
			$line = new IPPLine();
			$line->DetailType = 'SalesItemLineDetail';
			$line->Amount = $item->order->postage_and_packaging ? : 0;
			$line->Description = "Delivery to the UK";
			$details = new IPPSalesItemLineDetail();
			$details->Qty = 1;
			$details->ItemRef = $delivery->Id;
			$details->TaxCodeRef = $knownTaxes['20.0% S']->Id;//$delivery->SalesTaxCodeRef;
			$line->SalesItemLineDetail = $details;
			$qbInvoice->Line[] = $line;
		}
		// Store in API.
		$newQbInvoice = $this->dataService->Add($qbInvoice);
		if (!$newQbInvoice) {
			throw new Exception("Invoice creation problem.\n\n" . $this->getLastErrorFullInfo());
		}

		return [
			'id' => $newQbInvoice->Id,
			'number' => $newQbInvoice->Id, // The same as id.
			'amount' => $newQbInvoice->TotalAmt,
		];
	}

	public function createEbayRefundCreditNote(EbayRefund $ebayRefund, $customerId, $saleName)
	{
		$knownItems = $this->getKnownItems();
		$knownTaxes = collect($this->getAllTaxCodes())->keyBy('Name');

		$creditNote = new IPPCreditMemo();
		$creditNote->CustomerRef = $customerId;
		$creditNote->SalesTermRef = 1;
		$creditNote->Line = [];

		$descriptionParts = [];

		foreach($ebayRefund->order->EbayOrderItems as $item) {
			$descriptionParts[] = "$item->item_name  - $item->item_sku Return";
		}
		$description = implode("\n", $descriptionParts);

		$line = new IPPLine();
		$line->Amount = $ebayRefund->refund_amount;
		$line->DetailType = 'SalesItemLineDetail';
		$line->Description = htmlspecialchars($description);
		$details = new IPPSalesItemLineDetail();
		$details->Qty = 1;
		$details->ItemRef = $knownItems[$saleName]->Id;
		$details->TaxCodeRef = $knownTaxes['VAT Margin']->Id;//$knownItems[$saleName]->SalesTaxCodeRef;
		$line->SalesItemLineDetail = $details;
		$creditNote->Line[] = $line;

		// Store in API.
		$newQbCreditNote = $this->dataService->Add($creditNote);
		if (!$newQbCreditNote) {
			throw new Exception("Invoice creation problem.\n\n" . $this->getLastErrorFullInfo());
		}

		return [
			'id' => $newQbCreditNote->Id,
			'number' => $newQbCreditNote->Id, // The same as id.
			'amount' => $newQbCreditNote->TotalAmt,
		];

	}

	public function createOrderhubInvoice(Sale $sale, User $customer, $saleName, $fee = null)
	{
		if (!$customer->invoice_api_id) {
			throw new Exception("User with id $customer->id doesn't have QuickBooks id.");
		}
		$urlResource = null;
		$knownItems = $this->getKnownItems();

		// Create base invoice.
		$qbInvoice = new IPPInvoice();
		$qbInvoice->CustomerRef = $customer->invoice_api_id;
		$qbInvoice->SalesTermRef = 1;
		$qbInvoice->Line = [];
		$qbInvoice->PrivateNote = "Stock item ids: " . implode(', ', $sale->stock->lists('id'));

		// QB custom fields can only be 31 characters long or less.
		if (strlen($customer->email) <= 31) {
			$customEmailFieldId = $this->getCustomFieldId('Email');
			if ($customEmailFieldId) {
				$emailField = new IPPCustomField();
				$emailField->DefinitionId = $customEmailFieldId;
				$emailField->Name = 'Email';
				$emailField->Type = 'StringType';
				$emailField->StringValue = $customer->email;
				$qbInvoice->CustomField = [$emailField];
			}
		}

		// Add each stock (Name taken from eBay Order)
		foreach($sale->orderhub_orders as $orderhubOrder) {
			foreach($orderhubOrder->order_items as $item) {
				$line = new IPPLine();
				$line->Amount = $item->stock->sale_price;
				$line->DetailType = 'SalesItemLineDetail';
				$line->Description = htmlspecialchars($item->name);
				$details = new IPPSalesItemLineDetail();
				$details->Qty = 1;
				$details->ItemRef = $knownItems[$saleName]->Id;
				$details->TaxCodeRef = $knownItems[$saleName]->SalesTaxCodeRef;
				$line->SalesItemLineDetail = $details;
				$qbInvoice->Line[] = $line;
			}
		}

		if($fee && !$sale->card_processing_fee) {
			$feeSaleName = Invoicing::CARD_PROCESSING_FEE;

			$line = new IPPLine();
			$line->Amount = number_format($sale->invoice_total_amount*0.016+0.12, 2);
			$line->DetailType = 'SalesItemLineDetail';
			$line->Description = htmlspecialchars("");
			$details = new IPPSalesItemLineDetail();
			$details->Qty = 1;
			$details->ItemRef = $knownItems[$feeSaleName]->Id;
			$details->TaxCodeRef = $knownItems[$feeSaleName]->SalesTaxCodeRef;
			$line->SalesItemLineDetail = $details;
			//dd($line);
			$qbInvoice->Line[] = $line;
		}

		// Store in API.
		$newQbInvoice = $this->dataService->Add($qbInvoice);
		if (!$newQbInvoice) {
			throw new Exception("Invoice creation problem.\n\n" . $this->getLastErrorFullInfo());
		}

		return [
			'id' => $newQbInvoice->Id,
			'number' => $newQbInvoice->Id, // The same as id.
			'amount' => $newQbInvoice->TotalAmt,
		];
	}

	public function createMightyDealsInvoice(Sale $sale, User $customer, $saleName, $fee = null)
	{
		if (!$customer->invoice_api_id) {
			throw new Exception("User with id $customer->id doesn't have QuickBooks id.");
		}
		$urlResource = null;
		$knownItems = $this->getKnownItems();

		// Create base invoice.
		$qbInvoice = new IPPInvoice();
		$qbInvoice->CustomerRef = $customer->invoice_api_id;
		$qbInvoice->SalesTermRef = 1;
		$qbInvoice->Line = [];
		$qbInvoice->PrivateNote = "Stock item ids: " . implode(', ', $sale->stock->lists('id'));

		// QB custom fields can only be 31 characters long or less.
		if (strlen($customer->email) <= 31) {
			$customEmailFieldId = $this->getCustomFieldId('Email');
			if ($customEmailFieldId) {
				$emailField = new IPPCustomField();
				$emailField->DefinitionId = $customEmailFieldId;
				$emailField->Name = 'Email';
				$emailField->Type = 'StringType';
				$emailField->StringValue = $customer->email;
				$qbInvoice->CustomField = [$emailField];
			}
		}

		// Add each stock
		$mightyDeal = $sale->mighty_deals_order;

		$line = new IPPLine();
		$line->Amount = $mightyDeal->stock->sale_price;
		$line->DetailType = 'SalesItemLineDetail';
		$line->Description = htmlspecialchars($mightyDeal->stock->name);
		$details = new IPPSalesItemLineDetail();
		$details->Qty = 1;
		$details->ItemRef = $knownItems[$saleName]->Id;
		$details->TaxCodeRef = $knownItems[$saleName]->SalesTaxCodeRef;
		$line->SalesItemLineDetail = $details;
		$qbInvoice->Line[] = $line;

		if($fee && !$sale->card_processing_fee) {
			$feeSaleName = Invoicing::CARD_PROCESSING_FEE;

			$line = new IPPLine();
			$line->Amount = number_format($sale->invoice_total_amount*0.016+0.12, 2);
			$line->DetailType = 'SalesItemLineDetail';
			$line->Description = htmlspecialchars("");
			$details = new IPPSalesItemLineDetail();
			$details->Qty = 1;
			$details->ItemRef = $knownItems[$feeSaleName]->Id;
			$details->TaxCodeRef = $knownItems[$feeSaleName]->SalesTaxCodeRef;
			$line->SalesItemLineDetail = $details;
			//dd($line);
			$qbInvoice->Line[] = $line;
		}


		// Store in API.
		$newQbInvoice = $this->dataService->Add($qbInvoice);
		if (!$newQbInvoice) {
			throw new Exception("Invoice creation problem.\n\n" . $this->getLastErrorFullInfo());
		}

		return [
			'id' => $newQbInvoice->Id,
			'number' => $newQbInvoice->Id, // The same as id.
			'amount' => $newQbInvoice->TotalAmt,
		];
	}

	public function createCustomOrderInvoice(Sale $sale, User $customer, $saleName, $fee = null)
	{
		if (!$customer->invoice_api_id) {
			throw new Exception("User with id $customer->id doesn't have QuickBooks id.");
		}
		$urlResource = null;
		$knownItems = $this->getKnownItems();
		$knownTaxes = collect($this->getAllTaxCodes())->keyBy('Name');

		// Create base invoice.
		$qbInvoice = new IPPInvoice();
		$qbInvoice->CustomerRef = $customer->invoice_api_id;
		$qbInvoice->SalesTermRef = 1;
		$qbInvoice->Line = [];
		$qbInvoice->PrivateNote = "Stock item ids: " . implode(', ', $sale->stock->lists('id'));

		// QB custom fields can only be 31 characters long or less.
		if (strlen($customer->email) <= 31) {
			$customEmailFieldId = $this->getCustomFieldId('Email');
			if ($customEmailFieldId) {
				$emailField = new IPPCustomField();
				$emailField->DefinitionId = $customEmailFieldId;
				$emailField->Name = 'Email';
				$emailField->Type = 'StringType';
				$emailField->StringValue = $customer->email;
				$qbInvoice->CustomField = [$emailField];
			}
		}

		// Add single line

		$line = new IPPLine();
		$line->Amount = $sale->invoice_total_amount;
		$line->DetailType = 'SalesItemLineDetail';
		$line->Description = htmlspecialchars($sale->item_name);
		$details = new IPPSalesItemLineDetail();
		$details->Qty = 1;
		$details->ItemRef = $knownItems[$saleName]->Id;
		$details->TaxCodeRef = $knownTaxes[$sale->vat_type]->Id;
		$line->SalesItemLineDetail = $details;
		$qbInvoice->Line[] = $line;

		if($fee && !$sale->card_processing_fee) {
			$feeSaleName = Invoicing::CARD_PROCESSING_FEE;

			$line = new IPPLine();
			$line->Amount = number_format($sale->invoice_total_amount*0.016+0.12, 2);
			$line->DetailType = 'SalesItemLineDetail';
			$line->Description = htmlspecialchars("");
			$details = new IPPSalesItemLineDetail();
			$details->Qty = 1;
			$details->ItemRef = $knownItems[$feeSaleName]->Id;
			$details->TaxCodeRef = $knownItems[$feeSaleName]->SalesTaxCodeRef;
			$line->SalesItemLineDetail = $details;
			//dd($line);
			$qbInvoice->Line[] = $line;
		}

		// Store in API.
		$newQbInvoice = $this->dataService->Add($qbInvoice);
		if (!$newQbInvoice) {
			throw new Exception("Invoice creation problem.\n\n" . $this->getLastErrorFullInfo());
		}

		return [
			'id' => $newQbInvoice->Id,
			'number' => $newQbInvoice->Id, // The same as id.
			'amount' => $newQbInvoice->TotalAmt,
		];
	}

	public function createBatchInvoice(Sale $sale, User $customer, $saleName, $deliveryName = null, $batch, $price, $fee = null)
	{
		if (!$customer->invoice_api_id) {
			throw new Exception("User with id $customer->id doesn't have QuickBooks id.");
		}
		$urlResource = null;
		$knownItems = $this->getKnownItems();

		// Create base invoice.
		$qbInvoice = new IPPInvoice();
		$qbInvoice->CustomerRef = $customer->invoice_api_id;
		$qbInvoice->SalesTermRef = 1;
		$qbInvoice->Line = [];
		$qbInvoice->PrivateNote = "Stock item ids: " . implode(', ', $sale->stock->lists('id'));

		// QB custom fields can only be 31 characters long or less.
		if (strlen($customer->email) <= 31) {
			$customEmailFieldId = $this->getCustomFieldId('Email');
			if ($customEmailFieldId) {
				$emailField = new IPPCustomField();
				$emailField->DefinitionId = $customEmailFieldId;
				$emailField->Name = 'Email';
				$emailField->Type = 'StringType';
				$emailField->StringValue = $customer->email;
				$qbInvoice->CustomField = [$emailField];
			}
		}

		// Add single stock item.

			$line = new IPPLine();
			$line->Amount = $price;
			$line->DetailType = 'SalesItemLineDetail';
			$line->Description = "Batch ".$batch." - ".date("d/m/Y")." - ".count($sale->stock)." items";
			$details = new IPPSalesItemLineDetail();
			$details->Qty = 1;
			$details->ItemRef = $knownItems[$saleName]->Id;
			$details->TaxCodeRef = $knownItems[$saleName]->SalesTaxCodeRef;
			$line->SalesItemLineDetail = $details;
			$qbInvoice->Line[] = $line;

		// Add delivery
		if ($deliveryName) {
			$delivery = $knownItems[$deliveryName];
			if (!$delivery) {
				throw new Exception("Delivery not found. Delivery name: \"$deliveryName\".");
			}
			$line = new IPPLine();
			$line->DetailType = 'SalesItemLineDetail';
			$line->Amount = $delivery->UnitPrice;
			$details = new IPPSalesItemLineDetail();
			$details->Qty = 1;
			$details->ItemRef = $delivery->Id;
			$details->TaxCodeRef = $delivery->SalesTaxCodeRef;
			$line->SalesItemLineDetail = $details;
			$qbInvoice->Line[] = $line;
		}

		if($fee && !$sale->card_processing_fee) {
			$feeSaleName = Invoicing::CARD_PROCESSING_FEE;

			$line = new IPPLine();
			$line->Amount = number_format($sale->invoice_total_amount*0.016+0.12, 2);
			$line->DetailType = 'SalesItemLineDetail';
			$line->Description = htmlspecialchars("");
			$details = new IPPSalesItemLineDetail();
			$details->Qty = 1;
			$details->ItemRef = $knownItems[$feeSaleName]->Id;
			$details->TaxCodeRef = $knownItems[$feeSaleName]->SalesTaxCodeRef;
			$line->SalesItemLineDetail = $details;
			//dd($line);
			$qbInvoice->Line[] = $line;
		}

		// Store in API.
		$newQbInvoice = $this->dataService->Add($qbInvoice);
		if (!$newQbInvoice) {
			throw new Exception("Invoice creation problem.\n\n" . $this->getLastErrorFullInfo());
		}

		return [
			'id' => $newQbInvoice->Id,
			'number' => $newQbInvoice->Id, // The same as id.
			'amount' => $newQbInvoice->TotalAmt,
		];
	}

	public function createUnlockOrderInvoice(Order $order, User $customer, $saleName, $deliveryName = null)
    {
        if (!$customer->invoice_api_id) {
            throw new Exception("User with id $customer->id doesn't have QuickBooks id.");
        }
        $urlResource = null;
        $knownItems = $this->getKnownItems();

        // Create base invoice.
        $qbInvoice = new IPPInvoice();
        $qbInvoice->CustomerRef = $customer->invoice_api_id;
        $qbInvoice->SalesTermRef = 1;
        $qbInvoice->Line = [];

        // QB custom fields can only be 31 characters long or less.
        if (strlen($customer->email) <= 31) {
            $customEmailFieldId = $this->getCustomFieldId('Email');
            if ($customEmailFieldId) {
                $emailField = new IPPCustomField();
                $emailField->DefinitionId = $customEmailFieldId;
                $emailField->Name = 'Email';
                $emailField->Type = 'StringType';
                $emailField->StringValue = $customer->email;
                $qbInvoice->CustomField = [$emailField];
            }
        }

        $imeis = "";
        foreach($order->imeis as $imei){
            $imeis .= $imei." ";
        }
        $line = new IPPLine();
        $line->Amount = $order->amount_before_vat;
        $line->DetailType = 'SalesItemLineDetail';
        $line->Description =  htmlspecialchars("Unlock IMEI: $imeis from the $order->network network");
        $details = new IPPSalesItemLineDetail();
        $details->Qty = 1;
        $details->ItemRef = $knownItems['Unlock Sales']->Id;
        $details->TaxCodeRef = $knownItems['Unlock Sales']->SalesTaxCodeRef;
        $line->SalesItemLineDetail = $details;
        $qbInvoice->Line[] = $line;

//        // Add delivery
//        if ($deliveryName) {
//            $delivery = $knownItems[$deliveryName];
//            if (!$delivery) {
//                throw new Exception("Delivery not found. Delivery name: \"$deliveryName\".");
//            }
//            $line = new \IPPLine();
//            $line->DetailType = 'SalesItemLineDetail';
//            $line->Amount = $delivery->UnitPrice;
//            $details = new \IPPSalesItemLineDetail();
//            $details->Qty = 1;
////            $details->ItemRef = $delivery->Id;
//            $details->TaxCodeRef = $delivery->SalesTaxCodeRef;
//            $line->SalesItemLineDetail = $details;
//            $qbInvoice->Line[] = $line;
//        }

        // Store in API.
        $newQbInvoice = $this->dataService->Add($qbInvoice);
        if (!$newQbInvoice) {
            throw new Exception("Invoice creation problem.\n\n" . $this->getLastErrorFullInfo());
        }

		return [
			'id' => $newQbInvoice->Id,
			'number' => $newQbInvoice->Id, // The same as id.
			'amount' => $newQbInvoice->TotalAmt,
		];
	}

	public function createTech360Invoice(User $customer, $saleName, $deliveryName = null, $items)
	{
		if (!$customer->invoice_api_id) {
			throw new Exception("User with id $customer->id doesn't have QuickBooks id.");
		}
		$urlResource = null;
		$knownItems = $this->getKnownItems();

		// Create base invoice.
		$qbInvoice = new IPPInvoice();
		$qbInvoice->CustomerRef = $customer->invoice_api_id;
		$qbInvoice->SalesTermRef = 1;
		$qbInvoice->Line = [];

		// QB custom fields can only be 31 characters long or less.
		if (strlen($customer->email) <= 31) {
			$customEmailFieldId = $this->getCustomFieldId('Email');
			if ($customEmailFieldId) {
				$emailField = new IPPCustomField();
				$emailField->DefinitionId = $customEmailFieldId;
				$emailField->Name = 'Email';
				$emailField->Type = 'StringType';
				$emailField->StringValue = $customer->email;
				$qbInvoice->CustomField = [$emailField];
			}
		}

		$tech_360_sales = [];
		// Add each item.
		foreach ($items as $item) {
			$itemLine = $item->item_no." - ".$item->vendor." - ".$item->item_name." Commission";
			$tech_360_sales[] = $item->sale_id;
			$line = new IPPLine();
			$line->Amount = $item->fee;
			$line->DetailType = 'SalesItemLineDetail';
			$line->Description = htmlspecialchars($itemLine);
			$details = new IPPSalesItemLineDetail();
			$details->Qty = 1;
			$details->ItemRef = $knownItems[$saleName]->Id;
			$details->TaxCodeRef = $knownItems[$saleName]->SalesTaxCodeRef;
			$line->SalesItemLineDetail = $details;
			$qbInvoice->Line[] = $line;
		}

		// Store in API.
		$newQbInvoice = $this->dataService->Add($qbInvoice);
		if (!$newQbInvoice) {
			throw new Exception("Invoice creation problem.\n\n" . $this->getLastErrorFullInfo());
		}

		return [
			'id' => $newQbInvoice->Id,
			'number' => $newQbInvoice->Id, // The same as id.
			'amount' => $newQbInvoice->TotalAmt,
			'tech_360_sales' => $tech_360_sales
		];
	}

	public function createRebuyBillingInvoice($customerId, $saleName, $deliveryName = null, $items)
	{
		$urlResource = null;
		$knownItems = $this->getKnownItems();

		// Create base invoice.
		$qbInvoice = new IPPInvoice();
		$qbInvoice->CustomerRef = $customerId;
		$qbInvoice->SalesTermRef = 1;
		$qbInvoice->Line = [];
		$qbInvoice->CustomerMemo = "Rebuy Ref: BE2019-0182.";

		// QB custom fields can only be 31 characters long or less.
		/*if (strlen($customer->email) <= 31) {
			$customEmailFieldId = $this->getCustomFieldId('Email');
			if ($customEmailFieldId) {
				$emailField = new \IPPCustomField();
				$emailField->DefinitionId = $customEmailFieldId;
				$emailField->Name = 'Email';
				$emailField->Type = 'StringType';
				$emailField->StringValue = $customer->email;
				$qbInvoice->CustomField = [$emailField];
			}
		}*/

		$rebuy_billing_ids = [];
		// Add each item.
		foreach ($items as $item) {
			$itemLine = $item->description;
			$rebuy_billing_ids[] = $item->id;
			$line = new IPPLine();
			$line->Amount = $item->cost*$item->qty;
			$line->DetailType = 'SalesItemLineDetail';
			$line->Description = htmlspecialchars($itemLine);
			$details = new IPPSalesItemLineDetail();
			$details->Qty = $item->qty;
			$details->UnitPrice = $item->cost;
			$details->ItemRef = $knownItems[$saleName]->Id;
			$details->TaxCodeRef = $knownItems[$saleName]->SalesTaxCodeRef;
			$line->SalesItemLineDetail = $details;
			$qbInvoice->Line[] = $line;
		}

		// Store in API.
		$newQbInvoice = $this->dataService->Add($qbInvoice);
		if (!$newQbInvoice) {
			throw new Exception("Invoice creation problem.\n\n" . $this->getLastErrorFullInfo());
		}

		return [
			'id' => $newQbInvoice->Id,
			'number' => $newQbInvoice->Id, // The same as id.
			'amount' => $newQbInvoice->TotalAmt,
			'rebuy_billing_ids' => $rebuy_billing_ids
		];
	}

	public function createEbayFeesSupplierBill($supplierId, $data)
	{
		$urlResource = null;
		$knownItems = $this->getKnownItems();

		// Create base invoice.
		$qbBill = new IPPBill();
		$qbBill->VendorRef = $supplierId;
		$qbBill->SalesTermRef = 1;
		$qbBill->Line = [];
		$qbBill->PrivateNote = "eBay Order: ".$data->ebay_order_number;

		$paypalFees = $data->paypal;
		$deliveryFees = $data->delivery;
		$ebayFees = $data->ebay;


		$itemLine = "eBay Order: ".$data->ebay_order_number;//"PayPal Fees";
		$line = new IPPLine();
		$line->Amount = $paypalFees;
		$line->DetailType = 'ItemBasedExpenseLineDetail';
		$line->Description = htmlspecialchars($itemLine);
		$details = new IPPItemBasedExpenseLineDetail();
		$details->Qty = 1;
		$details->UnitPrice = $paypalFees;
		$details->ItemRef = $knownItems[Invoicing::EBAY_PAYPAL_FEES]->Id;
		$details->TaxCodeRef = $knownItems[Invoicing::EBAY_PAYPAL_FEES]->PurchaseTaxCodeRef;
		$line->ItemBasedExpenseLineDetail = $details;
		$qbBill->Line[] = $line;

		$itemLine = "eBay Order: ".$data->ebay_order_number;//"Delivery Fees";
		$line = new IPPLine();
		$line->Amount = $deliveryFees;
		$line->DetailType = 'ItemBasedExpenseLineDetail';
		$line->Description = htmlspecialchars($itemLine);
		$details = new IPPItemBasedExpenseLineDetail();
		$details->Qty = 1;
		$details->UnitPrice = $deliveryFees;
		$details->ItemRef = $knownItems[Invoicing::EBAY_DELIVERY_FEES]->Id;
		$details->TaxCodeRef = $knownItems[Invoicing::EBAY_DELIVERY_FEES]->PurchaseTaxCodeRef;
		$line->ItemBasedExpenseLineDetail = $details;
		$qbBill->Line[] = $line;

		$itemLine = "eBay Order: ".$data->ebay_order_number;//"eBay Fees";
		$line = new IPPLine();
		$line->Amount = $ebayFees;
		$line->DetailType = 'ItemBasedExpenseLineDetail';
		$line->Description = htmlspecialchars($itemLine);
		$details = new IPPItemBasedExpenseLineDetail();
		$details->Qty = 1;
		$details->UnitPrice = $ebayFees;
		$details->ItemRef = $knownItems[Invoicing::EBAY_EBAY_FEES]->Id;
		$details->TaxCodeRef = $knownItems[Invoicing::EBAY_EBAY_FEES]->PurchaseTaxCodeRef;
		$line->ItemBasedExpenseLineDetail = $details;
		$qbBill->Line[] = $line;

		// Store in API.
		$newQbBill = $this->dataService->Add($qbBill);
		if (!$newQbBill) {
			throw new Exception("Supplier Bill creation problem.\n\n" . $this->getLastErrorFullInfo());
		}

		return [
			'id' => $newQbBill->Id,
			'number' => $newQbBill->Id, // The same as id.
			'amount' => $newQbBill->TotalAmt
		];
	}

	public function createZapperPurchaseOrder($items)
	{
		$knownItems = $this->getKnownItems();

		$knownAccounts = collect($this->getAllAccounts())->keyBy('Name');
		$serviceName = "Zapper Commission";
		//dd($items);
		//dd($this->getAllTaxCodes());
		//dd($knownAccounts[$serviceName]);
		//dd($knownItems);

		//1686 - Zapper
		$supplier = 1686;

		// Create Purchase Order.
		$qbPurchaseOrder = new IPPPurchaseOrder();
		$qbPurchaseOrder->Line = [];
		$qbPurchaseOrder->VendorRef = $supplier;

		$itemsIds = [];
		// Add items stock item.
		foreach($items as $item) {
			$itemsIds[] = $item->id;
			$itemLine = $item->item_ref." - ".$item->name." - Profit Share Commission";
			$line = new IPPLine();
			//$line->Id = 1;
			$line->Amount = $item->cost_of_sale;
			//$line->Qty = 1;
			$line->DetailType = 'AccountBasedExpenseLineDetail';
			$line->Description = htmlspecialchars($itemLine);
			$details = new IPPAccountBasedExpenseLineDetail();
			$details->Qty = 1;
			$details->AccountRef = $knownAccounts[$serviceName]->Id;
			$details->TaxCodeRef = $knownAccounts[$serviceName]->TaxCodeRef;
			$line->AccountBasedExpenseLineDetail = $details;

			$qbPurchaseOrder->Line[] = $line;
		}

		$result = $this->dataService->Add($qbPurchaseOrder);
		if(!$result)
			throw new Exception("Purchase Order creation problem.\n\n" . $this->getLastErrorFullInfo());
		return [
			'id' => $result->Id,
			'items' => $itemsIds
		];
	}

	public function createZapperCamoradaPartnerPurchaseOrder($items)
	{
		$knownAccounts = collect($this->getAllAccounts())->keyBy('Name');
		$serviceName = "Zapper Commission";
		$knownTaxes = collect($this->getAllTaxCodes())->keyBy('Name');
		$tax = "20.0% S";
		//dd($knownAccounts[$serviceName]);
		//dd($knownTaxes[$tax]);
		//dd($knownAccounts[$serviceName]);
		//dd($knownItems);

		//1686 - Zapper
		$supplier = 1686;

		// Create Purchase Order.
		$qbPurchaseOrder = new IPPPurchaseOrder();
		$qbPurchaseOrder->Line = [];
		$qbPurchaseOrder->VendorRef = $supplier;
		$qbPurchaseOrder->GlobalTaxCalculation = "TaxExcluded";


		$itemsRefs = [];
		// Add items stock item.
		foreach($items as $item) {
			$itemsRefs[] = $item->item_ref;
			$itemLine = "Commission for Trade-in item ".$item->item_ref." - ".$item->name;
			$line = new IPPLine();
			//$line->Id = 1;
			$line->Amount = $item->cost;
			//$line->Qty = 1;
			$line->DetailType = 'AccountBasedExpenseLineDetail';
			$line->Description = htmlspecialchars($itemLine);
			$details = new IPPAccountBasedExpenseLineDetail();
			$details->Qty = 1;
			$details->AccountRef = $knownAccounts[$serviceName]->Id;
			$details->TaxCodeRef = $knownTaxes[$tax]->Id;
			$line->AccountBasedExpenseLineDetail = $details;

			$qbPurchaseOrder->Line[] = $line;
		}

		$result = $this->dataService->Add($qbPurchaseOrder);
		if(!$result)
			throw new Exception("CamoradaPartner Purchase Order creation problem.\n\n" . $this->getLastErrorFullInfo());
		return [
			'id' => $result->Id,
			'items' => $itemsRefs
		];
	}

	public function getPurchaseOrderDocument($id)
	{
		// TODO: Implement getPurchaseOrderDocument() method.
	}

	public function deleteInvoice(Sale $sale)
	{
		$qbInvoiceSearch = new IPPInvoice();
		$qbInvoiceSearch->Id = $sale->invoice_api_id;

		$qbInvoiceFound = $this->dataService->FindById($qbInvoiceSearch);
		if (!$qbInvoiceFound) {
			alert("Quickbooks invoice \"$sale->invoice_api_id\" not found when trying to delete it.");
		}
		else {
			$res = $this->dataService->Delete($qbInvoiceFound);
			if (!$res) {
				throw new Exception("Could not delete invoice with id \"$sale->invoice_api_id\".");
			}
		}
		Cache::forget("quickbooks.invoice_pdf.$sale->invoice_api_id");
		Cache::forget("quickbooks.invoices.$sale->invoice_api_id");
		Cache::forget("quickbooks.invoices");
	}

	public function voidInvoice(Sale $sale)
	{
		$qbInvoiceSearch = new IPPInvoice();
		$qbInvoiceSearch->Id = $sale->invoice_api_id;
		// For some reason we have to find the invoice first instead of just voiding it by id.
		$qbInvoiceFound = $this->dataService->FindById($qbInvoiceSearch);
		$res = $this->dataService->Void($qbInvoiceFound);
		if (!$res) {
			throw new Exception("Could not void invoice with id \"$sale->invoice_api_id\".");
		}
		Cache::forget("quickbooks.invoice_pdf.$sale->invoice_api_id");
		Cache::forget("quickbooks.invoices.$sale->invoice_api_id");
		Cache::forget("quickbooks.invoices");
	}

	protected function getRawCustomers($ids = [])
	{
		$customers = [];
		$customerIdsUncached = [];

		foreach ($ids as $id) {
			$cacheKey = $cacheKey = "quickbooks.customers.$id";
			if ($this->cacheTime && Cache::has($cacheKey)) {
				$customers[] = Cache::get($cacheKey);
			}
			else {

				$customerIdsUncached[] = $id;

			}
		}

		if (!$ids && $this->cacheTime && Cache::has("quickbooks.customers")) {
			$customers = Cache::get("quickbooks.customers");
		}
		elseif (!$ids || $customerIdsUncached) {
			$query = "select * from Customer ";
			if ($customerIdsUncached) {
				$query .= "where Id in ('" . implode("', '", array_map('intval', $customerIdsUncached)) . "')";
			}


			$pageNumber = 0;
			$perPage = 500;

			do {
				$start = $pageNumber * $perPage + 1;
				$pagedQuery = "$query startPosition $start maxResults $perPage";
				$apiRes = $this->dataService->Query($pagedQuery);

				foreach ($apiRes ?: [] as $qbCustomer) {
					$customers[] = $qbCustomer;
					if ($this->cacheTime) Cache::put("quickbooks.customers.$qbCustomer->Id", $qbCustomer, $this->cacheTime);
				}
				$pageNumber++;
			}
			// If the number is lower than $perPage, avoid one unnecessary request - we know it's the end of data.
			while ($apiRes && count($apiRes) === $perPage);
		}


		if (!$ids && $this->cacheTime) {
			Cache::put("quickbooks.customers", $customers, $this->cacheTime);
		}


		return $customers;
	}

	/**
	 * If $ids parameter is present then every invoice is cached individually so that it can be invalidated individually.
	 * @param array $ids
	 * @return array
	 */
	public function getRawInvoices($ids = [])
	{
		$invoices = [];
		$invoiceIdsUncached = [];

		foreach ($ids as $id) {
			$cacheKey = "quickbooks.invoices.$id";

			if ($this->cacheTime && Cache::has($cacheKey)) {
				$invoices[] = Cache::get($cacheKey);
			}
			else {
				$invoiceIdsUncached[] = $id;
			}
		}

		if (!$ids && $this->cacheTime && Cache::has("quickbooks.invoices")) {
			$invoices = Cache::get("quickbooks.invoices");
		}
		elseif (!$ids || $invoiceIdsUncached) {
			$query = "select * from Invoice ";
			if ($invoiceIdsUncached) {
				$query .= "where Id in ('" . implode("', '", array_map('intval', $invoiceIdsUncached)) . "')";
			}
			$apiRes = $this->dataService->Query($query);
			if($apiRes) {
				foreach ($apiRes as $qbInvoice) {
					$invoices[] = $qbInvoice;
					if ($this->cacheTime) Cache::put("quickbooks.invoices.$qbInvoice->Id", $qbInvoice, $this->cacheTime);
				}
			}
		}

		if (!$ids && $this->cacheTime) {
			Cache::put("quickbooks.invoices", $invoices, $this->cacheTime);
		}

		return $invoices;
	}

	protected function getPreferences()
	{
		$cacheKey = "quickbooks.preferences";

		if ($this->cacheTime && Cache::has($cacheKey)) {
			$prefs = Cache::get($cacheKey);
		}
		else {
			$prefs = $this->dataService->FindAll('Preferences');
			if ($this->cacheTime) Cache::put($cacheKey, $prefs, $this->cacheTime);
		}

		return $prefs[0];
	}

	protected function getCustomFieldId($name)
	{
		$prefs = $this->getPreferences();

		foreach (
			is_array($prefs->SalesFormsPrefs->CustomField)
				? $prefs->SalesFormsPrefs->CustomField
				: [$prefs->SalesFormsPrefs->CustomField]
			as $customFieldGroup
		) {
			foreach (
				is_array($customFieldGroup->CustomField)
					? $customFieldGroup->CustomField
					: [$customFieldGroup->CustomField]
				as $field
			) {
				if ($field->StringValue === $name) {
					preg_match('/\d+$/', $field->Name, $definitionMatch);
					return $definitionMatch[0];
				}
			}
		}

		return null;
	}

	protected function getRawCustomer($id)
	{

		foreach ($this->getRawCustomers() as $customer) {
			if ($customer->Id == $id)  {
				return $customer;
			}
		}

		throw new Exception("Customer \"$id\" not found.");
	}

	protected function getDefaultTaxCode()
	{
		$cacheKey = "quickbooks.default_tax_code";

		if (!$this->standardTaxCode) {
			if ($this->cacheTime && Cache::has($cacheKey)) {
				$this->standardTaxCode = Cache::get($cacheKey);
			}
			else {
				$allTaxCodes = $this->getAllTaxCodes();
				$ecGoods = $vatMargin = null;
				foreach ($allTaxCodes as $code) {
					if ($code->Description === 'EC Goods Zero-rated') {
						$ecGoods = $code;
					}
					if (trim(strtolower($code->Description)) === 'vat margin') {
						$vatMargin = $code;
					}
				}
				// We want the "vat margin" tax code if available (used in the final live setup) or "EC Goods Zero-rated"
				// otherwise (similar tax available in the sandbox accounts).
				$this->standardTaxCode = $vatMargin ?: $ecGoods;
				if ($this->cacheTime) Cache::put($cacheKey, $this->standardTaxCode, $this->cacheTime);
			}
		}

		if (!$this->standardTaxCode) {
			throw new Exception("Standard tax code could not be found.");
		}

		return $this->standardTaxCode;
	}

	public function getAllAccounts()
	{
		$cacheKey = "quickbooks.income_accounts";

		if ($this->cacheTime && Cache::has($cacheKey)) {
			$accounts = Cache::get($cacheKey);
		}
		else {
			$accounts = $this->dataService->FindAll('Account', 0, 500);
			if ($this->cacheTime) Cache::put($cacheKey, $accounts, $this->cacheTime);
		}

		return $accounts;
	}

	public function getAccounts($names = [])
	{

		$accounts = [];

		$query = "select * from Account ";
		if ($names) {
			$query .= "where Name in ('" . implode("', '", $names) . "')";
		}

		$pageNumber = 0;
		$perPage = 500;

		do {
			$start = $pageNumber * $perPage + 1;
			$pagedQuery = "$query startPosition $start maxResults $perPage";
			$apiRes = $this->dataService->Query($pagedQuery);
			foreach ($apiRes ?: [] as $qbAccount) {
				$accounts[] = $qbAccount;
			}
			$pageNumber++;
		}
			// If the number is lower than $perPage, avoid one unnecessary request - we know it's the end of data.
		while ($apiRes && count($apiRes) === $perPage);

		$accounts = collect($accounts)->keyBy('Name');

		return $accounts;
	}

	protected function getAllTaxCodes()
	{
		$cacheKey = "quickbooks.taxes_codes";

		if ($this->cacheTime && Cache::has($cacheKey)) {
			$taxes = Cache::get($cacheKey);
		}
		else {
			$taxes = $this->dataService->FindAll('TaxCode', 0, 500);
			if ($this->cacheTime) Cache::put($cacheKey, $taxes, $this->cacheTime);
		}

		return $taxes;
	}

	protected function getAllTaxRates()
	{
		$cacheKey = "quickbooks.tax_rates";

		if ($this->cacheTime && Cache::has($cacheKey)) {
			$taxes = Cache::get($cacheKey);
		}
		else {
			$taxes = $this->dataService->FindAll('TaxRate', 0, 500);
			if ($this->cacheTime) Cache::put($cacheKey, $taxes, $this->cacheTime);
		}

		return $taxes;
	}

	protected function createItem(Stock $item)
	{
		$qbItem = new IPPItem();
		$qbItem->Name = $item->sku;
		$qbItem->Description = $item->long_name;
		$qbItem->Type = 'Service'; // 'NonInventory' seems appropriate, but they use "Service" so that's what we set here.
		$qbItem->IncomeAccountRef = 1;
		$qbItem->Sku = $item->sku;
		$res = $this->dataService->Add($qbItem);
		Cache::forget('quickbooks.known_items');
		return $res;
	}

	protected function getKnownItems()
	{
		$cacheKey = 'quickbooks.known_items';
		if ($this->cacheTime && Cache::has($cacheKey)) {
			return Cache::get($cacheKey);
		}

		$items = $this->dataService->Query("select * from Item");

		$itemsByName = [];
		foreach ($items as $item) {
			$itemsByName[$item->Name] = $item;
		}

		if ($this->cacheTime) Cache::put($cacheKey, $itemsByName, $this->cacheTime);
		return $itemsByName;
	}

	protected function getItemName(Stock $item)
	{
		return $item->sku;
	}

	protected function getDataService()
	{
		/*$serviceType = \IntuitServicesType::QBO;
		$realmId = Setting::get('quickbooks.oauth.realm_id');
		$requestValidator = new \OAuthRequestValidator(
			Setting::get('quickbooks.oauth.access_token.oauth_token'),
			Setting::get('quickbooks.oauth.access_token.oauth_token_secret'),
			config('services.quickbooks.consumer.key'),
			config('services.quickbooks.consumer.secret')
		);
		$serviceContext = new \ServiceContext($realmId, $serviceType, $requestValidator);
		$dataService = new \DataService($serviceContext);*/
		$quickbooks = app('App\Contracts\Quickbooks');
		$dataService = $quickbooks->getDataService();

		return $dataService;
	}

	protected function getLastErrorFullInfo()
	{
		return
			"Code: " . $this->dataService->getLastError()->getHttpStatusCode() . "\n\n" .
			"OAuth helper error: " . $this->dataService->getLastError()->getOAuthHelperError() . "\n\n" .
			"Response body: " . $this->dataService->getLastError()->getResponseBody() . "\n\n";
	}

	public function getSystemName()
	{
		return 'Quickbooks';
	}
    public static function getAvailableCustomerCategory()
    {
        return [Invoicing::SALE_UK,Invoicing::SALE_CONSUMER,Invoicing::EBAY_SALES,Invoicing::SALE_BACKMARKET,Invoicing::SALE_OTHER,Invoicing::SALE_WORLD,
            Invoicing::SALE_MOBILE_ADVANTAGE,Invoicing::SALE_MOBILE_ADVANTAGE_REFUNDS];
    }

    public static function getAvailableCustomerCategoryWithKeys()
    {
        return array_combine(self::getAvailableCustomerCategory(), self::getAvailableCustomerCategory());
    }

}
