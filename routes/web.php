<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\UnlocksController;
use App\Http\Controllers\PartsController;
use App\Http\Controllers\PhoneCheckReportController;
use App\Http\Controllers\EbayOrderController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
//
//Route::get('/', function () {
//    return view('welcome');
//});
//
Auth::routes();
//
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


Route::group(['middleware' => ['auth']], function () {

    Route::get('/deleted-sales', ['middleware' => 'admin', 'uses' => 'CustomerReturnController@getDeleteSaleData', 'as' => 'repairs']);
    Route::get('/stats', ['middleware' => 'admin', 'as' => 'stats', function () {
        return view('stats');
    }]);
    Route::get('/stock-stats', ['middleware' => 'admin', 'as' => 'stock-stats', 'uses' => 'StockController@getStockStats']);
    //Route::get('/trade-in-stats', ['middleware' => 'admin', 'uses' => 'Trg\TradeInsController@getStats', 'as' => 'trade-in-stats']);
    Route::get('/items-sold-report', ['middleware' => 'admin', 'as' => 'items-sold-report', 'uses' => 'StockController@getItemsSoldReport']);

    Route::group(['prefix' => 'unlock-mapping', 'middleware' => ['admin']], function () {
        Route::get('/', ['uses' => 'UnlockMappingController@getindex', 'as' => 'unlock-mapping']);
        Route::post('/add', ['uses' => 'UnlockMappingController@postAdd', 'as' => 'unlock-mapping.add']);
        Route::post('/delete', ['uses' => 'UnlockMappingController@postDelete', 'as' => 'unlock-mapping.delete']);
    });

    // Home
    Route::group(['prefix' => 'home'], function () {
        Route::get('/', ['uses' => 'HomeController@getIndex', 'as' => 'home']);
        Route::get('/products/{name}', ['uses' => 'HomeController@getSingleProduct', 'as' => 'home.single-product']);
        Route::get('/search', ['uses' => 'HomeController@getSingleProductSearch', 'as' => 'home.single-search']);
        Route::post('/add-to-basket', ['uses' => 'HomeController@postAddToBasket', 'as' => 'home.add-to-basket']);
        Route::group(['middleware' => ['admin']], function () {
            Route::post('/bulk-update-price', ['uses' => 'HomeController@postBulkUpdatePrice', 'as' => 'home.bulk-update-price']);
        });
    });

    // Repairs
    Route::group(['prefix' => 'repairs', 'middleware' => ['admin']], function () {
        Route::get('/', ['uses' => 'RepairController@getIndex', 'as' => 'repairs']);
        // Route::get('/external', ['uses' => 'RepairController@getExternalIndex', 'as' => 'repairs.external.single']);
        Route::get('/external/{id}', ['uses' => 'RepairController@getExternalSingle', 'as' => 'repairs.external.single'])->where('id', '[0-9]+');
        Route::get('/internal/{id}', ['uses' => 'RepairController@getSingle', 'as' => 'repairs.single'])->where('id', '[0-9]+');
        Route::post('import', ['uses' => 'RepairController@postImport', 'as' => 'repairs.import']);
        Route::post('get-faults', ['uses' => 'RepairController@getfaults', 'as' => 'repairs.faults']);
        Route::post('update-cost', ['uses' => 'RepairController@updateRepairCost', 'as' => 'repairs.update.cost']);
        Route::get('external-export/{id}', ['uses' => 'RepairController@getExternalRepairConstExport', 'as' => 'repairs.external.export']);
        Route::post('add-external-repair', ['uses' => 'RepairController@addNewExternalRepair', 'as' => 'repairs.external.add']);
        Route::get('export-repair-template', ['uses' => 'RepairController@getTemplate', 'as' => 'repairs.download.template']);
        Route::post('external-delete', ['uses' => 'RepairController@deleteExternal', 'as' => 'repairs.external.delete']);
        Route::post('close-repair', ['uses' => 'RepairController@closeRepair', 'as' => 'repairs.close']);
    });

    // Exception Logs
    Route::group(['prefix' => 'exception-logs', 'middleware' => ['admin']], function () {
        Route::get('/', ['uses' => 'ExceptionLogController@getIndex', 'as' => 'exception-logs']);
        Route::get('/{id}', ['uses' => 'ExceptionLogController@getSingle', 'as' => 'exception-logs.single']);
    });

    /*Route::group(['prefix' => 'back-market', 'middleware' => ['admin']], function() {
        Route::get('/', ['uses' => 'BackMarketController@getIndex', 'as' => 'back-market']);
        Route::get('/update-logs', ['uses' => 'BackMarketController@getUpdateLogs', 'as' => 'back-market.update-logs']);
        Route::get('/update-logs/{id}', ['uses' => 'BackMarketController@getUpdateLogsSingle', 'as' => 'back-market.update-logs-single']);
        Route::post('/cron-settings', ['uses' => 'BackMarketController@postCronSettings', 'as' => 'back-market.cron-settings']);
    });*/

    // Zendesk
    Route::group(['prefix' => 'zendesk', 'middleware' => ['admin']], function () {
        Route::get('/', ['uses' => 'ZendeskController@getIndex', 'as' => 'zendesk']);
        Route::get('/ticket/{id}', ['uses' => 'ZendeskController@getTicket', 'as' => 'zendesk.ticket']);
        Route::get('/tags', ['uses' => 'ZendeskController@getTags', 'as' => 'zendesk.tags']);
        Route::get('/ticket-comments/{id}', ['uses' => 'ZendeskController@getTicketComments', 'as' => 'zendesk.ticket-comments']);
    });

    // Channel Grabber
    /*Route::group(['prefix' => 'channel-grabber', 'middleware' => ['admin']], function() {
        Route::get('/', ['uses' => 'ChannelGrabberController@getIndex', 'as' => 'channel-grabber']);
        Route::get('/update-logs', ['uses' => 'ChannelGrabberController@getUpdateLogs', 'as' => 'channel-grabber.update-logs']);
        Route::get('/update-logs/{id}', ['uses' => 'ChannelGrabberController@getUpdateLogsSingle', 'as' => 'channel-grabber.update-logs-single']);
    });*/


    // Stock Take
    Route::group(['prefix' => 'stock-take', 'middleware' => ['admin', 'not_staff']], function () {
        Route::get('/', ['uses' => 'StockTakeController@getIndex', 'as' => 'stock-take']);
        Route::post('/mark-as-seen', ['uses' => 'StockTakeController@postMarkAsSeen', 'as' => 'stock-take.mark-as-seen']);
        Route::get('/missing-items', ['uses' => 'StockTakeController@getMissingItems', 'as' => 'stock-take.missing-items']);
        Route::get('/missing-items-table-all', ['uses' => 'StockTakeController@getMissingItemsTableAll', 'as' => 'stock-take.missing-items-table-all']);
        Route::get('/mark-as-lost', ['uses' => 'StockTakeController@getMarkAsLost', 'as' => 'stock-take.mark-as-lost']);
        Route::post('/mark-as-lost', ['uses' => 'StockTakeController@postMarkAsLost', 'as' => 'stock-take.mark-as-lost-submit']);
        Route::get('/view-lost-items', ['uses' => 'StockTakeController@getViewLostItems', 'as' => 'stock-take.view-lost-items']);
        Route::get('/view-lost-items-export', ['uses' => 'StockTakeController@getViewLostItemsExport', 'as' => 'stock-take.view-lost-items-export']);
        Route::get('/view-deleted-items', ['uses' => 'StockTakeController@getViewDeletedItems', 'as' => 'stock-take.view-deleted-items']);
        Route::get('/scanner', ['uses' => 'StockTakeController@getScanner', 'as' => 'stock-take.scanner']);
        Route::post('/delete-all-stock-take-records', ['uses' => 'StockTakeController@postDeleteAllStockTakeRecords', 'as' => 'stock-take.delete-all-stock-take-records']);
    });

    // Parts
    Route::group(['prefix' => 'parts', 'middleware' => ['admin']], function () {
        Route::any('/', [PartsController::class,'getIndex'])->name('parts');
        Route::get('/add', [PartsController::class,'getAdd'])->name('parts.add');
        Route::post('/save', [PartsController::class,'postAddOrEdit'])->name('parts.save');
        Route::post('/delete', [PartsController::class,'postDelete'])->name('parts.delete');
        Route::get('/part/{id}', [PartsController::class,'getSingle'])->name('parts.single');
        Route::get('/stock-levels', [PartsController::class,'getStockLevels'])->name('parts.stock-levels');
        Route::post('/stock-levels', [PartsController::class,'postUpdateStockLevels'])->name('parts.stock-levels-update');
        Route::get('/update-costs', [PartsController::class,'getUpdateCosts'])->name('parts.update-costs');
        Route::post('/update-costs', [PartsController::class,'postUpdateCosts'])->name('parts.update-costs-submit');
        Route::get('/summary', [PartsController::class,'getSummary'])->name('parts.summary');
        Route::get('/search', [PartsController::class,'getSearch'])->name('parts.search');


    });



    // Saved Baskets
    Route::group(['prefix' => 'saved-baskets', 'middleware' => ['admin']], function () {
        Route::get('/', ['uses' => 'SavedBasketController@getIndex', 'as' => 'saved-baskets']);
        Route::get('/{id}', ['uses' => 'SavedBasketController@getSingle', 'as' => 'saved-baskets.single']);
        Route::post('/create-sale', ['uses' => 'SavedBasketController@postCreateSale', 'as' => 'saved-baskets.create-sale']);
        Route::post('/delete', ['uses' => 'SavedBasketController@postDelete', 'as' => 'saved-baskets.delete']);
        Route::post('/delete-from-basket', ['uses' => 'SavedBasketController@postDeleteFromBasket', 'as' => 'saved-baskets.delete-from-basket']);
    });

    // Products
    Route::group(['prefix' => 'products', 'middleware' => ['admin']], function () {
        Route::get('/', ['uses' => 'ProductsController@getIndex', 'as' => 'products']);
        Route::get('export-data', ['uses' => 'ProductsController@getAllExport', 'as' => 'product.export-data']);
        Route::get('create', ['uses' => 'ProductsController@create', 'as' => 'product.create']);
        Route::get('/{id}/{page?}', ['uses' => 'ProductsController@getSingle', 'as' => 'products.single']);
        Route::post('/save', ['uses' => 'ProductsController@postCreate', 'as' => 'products.save']);
        Route::post('/update', ['uses' => 'ProductsController@postUpdate', 'as' => 'products.update']);
        Route::get('image/remove/{id}', ['uses' => 'ProductsController@removeImage', 'as' => 'image.remove']);
        Route::get('deleted/{id}', ['uses' => 'ProductsController@deletedProduct', 'as' => 'product.delete']);
        Route::post('/import', ['uses' => 'ProductsController@importCsv', 'as' => 'product.import']);

        // Route::get('/temp-upload/remove',['uses'=>'ProductsController@removeTemporaryFile','as'=>'products.temp-remove']);
        //	Route::post('/temp-upload', ['uses' => 'ProductsController@temporaryUpload', 'as' => 'products.temp-update']);


    });

    // Notifications
    Route::group(['prefix' => 'notifications', 'middleware' => ['admin']], function () {
        Route::get('/', ['uses' => 'NotificationsController@getIndex', 'as' => 'notifications']);
    });

    // Purchases/Suppliers (without using TRG namespace)
    Route::group(['prefix' => 'purchases/suppliers', 'middleware' => ['admin', 'not_staff']], function () {
        Route::get('/', ['uses' => 'SuppliersController@getIndex', 'as' => 'suppliers']);
        Route::post('/add', ['uses' => 'SuppliersController@postAdd', 'as' => 'suppliers.add']);
        Route::get('/returns', ['uses' => 'SuppliersController@getSupplierReturns', 'as' => 'suppliers.returns']);
        Route::get('/redirect', ['uses' => 'SuppliersController@getRedirect', 'as' => 'suppliers.redirect']);
        Route::get('/return-create', ['uses' => 'SuppliersController@getSupplierReturnCreate', 'as' => 'suppliers.return-create']);
        Route::get('/returns/{id}', ['uses' => 'SuppliersController@getSupplierReturnSingle', 'as' => 'suppliers.return-single']);
        Route::get('/returns/{id}/export', ['uses' => 'SuppliersController@getSupplierReturnSingleExport', 'as' => 'suppliers.return-single-export']);
        Route::get('/returns/{id}/export/rma', ['uses' => 'SuppliersController@getSupplierReturnSingleExportRMA', 'as' => 'suppliers.return-single-export-rma']);
        Route::post('/return-update', ['uses' => 'SuppliersController@postSupplierReturnUpdate', 'as' => 'suppliers.return-update']);
        Route::post('/return-remove-item', ['uses' => 'SuppliersController@postSupplierReturnRemoveItem', 'as' => 'suppliers.return-remove-item']);
        Route::post('/return-update-item', ['uses' => 'SuppliersController@postSupplierReturnUpdateItem', 'as' => 'suppliers.return-update-item']);
        Route::post('/return-update-tracking-courier', ['uses' => 'SuppliersController@postSupplierReturnUpdateTrackingCourier', 'as' => 'suppliers.return-update-tracking-courier']);
        Route::get('/{id}', ['uses' => 'SuppliersController@getSingle', 'as' => 'suppliers.single']);
        Route::post('/update', ['uses' => 'SuppliersController@postUpdate', 'as' => 'suppliers.update']);
        Route::get('/delete/{id}', ['uses' => 'SuppliersController@removeSupplier', 'as' => 'suppliers.delete']);
        Route::post('/grade-mapping', ['uses' => 'SuppliersController@updateGradeMapping', 'as' => 'suppliers.grade-mapping']);

        Route::post('/grade-mapping', ['uses' => 'SuppliersController@updateGradeMapping', 'as' => 'suppliers.grade-mapping']);
        Route::post('/ps-model', ['uses' => 'SuppliersController@updatePSModelPercentage', 'as' => 'suppliers.ps-percentage']);
    });

    // Outbound - UserContact

    // Mobicode
    Route::group(['prefix' => 'mobicode', 'middleware' => ['admin']], function () {
        Route::post('/check-gsx', ['uses' => 'MobicodeController@postGSXcheck', 'as' => 'mobicode.gsx-check']);
    });

    // Static pages
    Route::get('/contact-us', ['as' => 'contact', function () {
        return view('static.contact');
    }]);
    Route::get('/grades', ['as' => 'grades', function () {
        return view('static.grades');
    }]);

    // Customers
    Route::group(['prefix' => 'customers', 'middleware' => ['admin']], function () {
        Route::get('/get-details', ['uses' => 'CustomersController@getDetails', 'as' => 'customers.details']);
        Route::post('/save', ['uses' => 'CustomersController@postSave', 'as' => 'customers.save']);
    });

    // Administration
    Route::group(['prefix' => 'admin', 'middleware' => ['admin']], function () {
        Route::group(['prefix' => 'ebay'], function () {
            Route::get('/', [EbayOrderController::class,'index'])->name('admin.ebay-orders');
            Route::get('sync', [EbayOrderController::class,'syncToEbayOrder'])->name('admin.ebay-order.sync');
            Route::get('view/{id}', [EbayOrderController::class,'view'])->name('admin.ebay-orders.view');
            Route::post('dpd/import', [EbayOrderController::class,'dpdImport'])->name('admin.dpd.import');
            Route::get('refunds', [EbayOrderController::class,'EbayRefund'])->name('admin.ebay.refund');
            Route::get('/invoice/{id}', [EbayOrderController::class,'getInvoice'])->name('admin.ebay.invoice');
            Route::get('/invoice-fees/{id}', [EbayOrderController::class,'getInvoiceFees'])->name('admin.ebay.invoice-fees');
            Route::get('/credit-memo/{id}', [EbayOrderController::class,'getCreditMemo'])->name('admin.ebay.credit-memo');
            Route::post('/assign-stock', [EbayOrderController::class,'AssignToStock'])->name('admin.ebay.assign-stock');
            Route::post('/unassigned-stock', [EbayOrderController::class,'UnassignedToStock'])->name('admin.ebay.unassigned-stock');
            Route::post('/update-rate', [EbayOrderController::class,'updateRate'])->name('admin.ebay.update-rate');
            Route::post('/update-contact-info', [EbayOrderController::class,'updateEmailAndPhone'])->name('admin.ebay.update-contact-info');

            Route::group(['prefix' => 'ready_for_invoice'], function () {
                Route::get('/', [EbayOrderController::class,'ready_for_invoice'])->name('admin.ebay.ready-invoice.view');
                Route::get('export', [EbayOrderController::class,'export_ready_for_invoice_csv'])->name('admin.ebay.ready-for-invoice.export');
                Route::get('manually-assigned', [EbayOrderController::class,'eBayFeeAssigment'])->name('admin.ebay.ready-invoice.manually-assigned');
                Route::get('manual-fee-assignment/export', [EbayFeesController::class,'exportCSVManualEbayFeeAssignment'])->name('admin.ebay.ready-invoice.manually-assigned.export');
            });

            Route::group(['prefix' => 'delivery-settings'], function () {
                Route::get('/', ['uses' => 'DeliverySettingsController@index', 'as' => 'admin.ebay.delivery-settings']);
                Route::get('missing-delivery-fees', ['uses' => 'DeliverySettingsController@missingDeliveryFees', 'as' => 'admin.missing.delivery.fees']);

                Route::post('save', ['uses' => 'DeliverySettingsController@postSave', 'as' => 'admin.delivery-settings.save']);
                Route::post('update/manual-owner-assignment', ['uses' => 'DeliverySettingsController@updateOwner', 'as' => 'delivery-settings.bulk-update-owner']);
                Route::group(['prefix' => 'dpd'], function () {
                    Route::get('/', ['uses' => 'DeliverySettingsController@getDpd', 'as' => 'admin.delivery-settings.dpd']);
                    Route::get('matched', ['uses' => 'DeliverySettingsController@matchedDPD', 'as' => 'admin.delivery-settings.dpd.matched']);
                });
            });

            Route::group(['prefix' => 'sku'], function () {
                Route::get('/', [EbaySkuController::class,'index'])->name('ebay.sku.index');
                Route::post('{id?}', [EbaySkuController::class,'postSave'])->name('ebay.sku.save');
                Route::get('template', [EbaySkuController::class,'getTemplate'])->name('ebay-sku.template');
                Route::get('cron', [EbaySkuController::class,'updateOwnerCron'])->name('ebay-sku.cron');
                Route::post('import', [EbaySkuController::class,'postImport'])->name('sku.import');
                Route::get('export', [EbaySkuController::class,'getExport'])->name('ebay.sku.export');
                Route::post('update/location', [EbaySkuController::class,'addLocation'])->name('ebay.sku.location');
                Route::post('update/shipping-method', [EbaySkuController::class,'updateShippingMethod'])->name('ebay.update.shipping-method');
                Route::get('unassigned', [EbaySkuController::class,'ExportUnassignedSku'])->name('ebay.export.unassigned');
                Route::get('show-unassinged', [EbaySkuController::class,'Unassigned'])->name('ebay.sku.unassigned');
                Route::post('update/manual-owner-assignment', [EbaySkuController::class,'updateOwner'])->name('ebay.update-owner');
            });

            Route::post("bulk-update", [EbayOrderController::class,'postBulkRetry'])->name('ebay.bulk-update-status');
            Route::get('history-log', [EbayOrderController::class,'historyLog'])->name('ebay.history-log');
            Route::get('stats', [EbayOrderController::class,'getStats'])->name('ebay.stats');
            Route::put('owner-update', [EbayOrderController::class,'updateOwner'])->name('ebay.owner.update');
            Route::put('sale-type-update', [EbayOrderController::class,'updateSaleType'])->name('ebay.sale-type.update');
            Route::post('ebay-create-invoice', [EbayOrderController::class,'createBayInvoice'])->name('ebay.create.invoice');
            Route::get('ebay-access-token', [EbayOrderController::class,'getUserAccessToken'])->name('ebay.access-token');
            Route::get('refresh-ebay-access-token', [EbayOrderController::class,'GeneratedNewAccessToken'])->name('refresh.ebay.access-token');
            Route::get('ebay-access-token-second', [EbayOrderController::class,'getUserAccessTokenSecond'])->name('ebay.access-token-second');
            Route::get('refresh-ebay-access-second', [EbayOrderController::class,'GeneratedNewAccessTokenSecond'])->name('refresh.ebay.access-token-second');
            Route::get('ebay-access-token-third', [EbayOrderController::class,'getUserAccessTokenThird'])->name('ebay.access-token-third');
            Route::get('refresh-ebay-access-third', [EbayOrderController::class,'GeneratedNewAccessTokenThird'])->name('refresh.ebay.access-token-third');
            Route::get('ebay-access-token-forth', [EbayOrderController::class,'getUserAccessTokenForth'])->name('ebay.access-token-forth');
            Route::get('refresh-ebay-access-forth', [EbayOrderController::class,'GeneratedNewAccessTokenForth'])->name('refresh.ebay.access-token-forth');
            Route::get('dpd-shipping', [EbayOrderController::class,'createShipping'])->name('ebay.dpd');


        });

        Route::group(['prefix' => 'fees'], function () {
            Route::get('/', ['uses' => 'EbayFeesController@index', 'as' => 'ebay-fee.index']);
            Route::get('template', ['uses' => 'EbayFeesController@getTemplate', 'as' => 'ebay-fee.template']);
            Route::post('import', ['uses' => 'EbayFeesController@postImport', 'as' => 'ebay-fee.import']);
            Route::post('status-update', ['uses' => 'EbayOrderController@updateStatus', 'as' => 'status-update']);
            Route::get('match-fee', ['uses' => 'EbayFeesController@updateEbayFeeUsername', 'as' => 'ebay-fee.update-username']);
            Route::get('edit/{id}', ['uses' => 'EbayFeesController@edit', 'as' => 'ebay-fee.update-fees']);
            Route::put('edit/{id}', ['uses' => 'EbayFeesController@edit', 'as' => 'ebay-fee.update-fees']);
            Route::get('history', ['uses' => 'EbayFeesController@eBayFeesHistory', 'as' => 'ebay-fee.history']);
            Route::get('export-unmatched', ['uses' => 'EbayFeesController@getUnmatchedExport', 'as' => 'ebay-fee.export-unmatched']);
            Route::post('manual-fee-assignment', ['uses' => 'EbayFeesController@addInManualEbayFeeAssignment', 'as' => 'fee-manual-fee-assignment']);
        });

        Route::group(['prefix' => 'users'], function () {
            Route::get('/', [ UserController::class ,'getIndex'])->name('admin.users');
            Route::get('/unregistered', ['uses' => 'UserController@getUnregistered'])->name('admin.users.unregistered');
            Route::delete('/unregistered/delete', ['uses' => 'UserController@deleteUnregistered', 'as' => 'admin.users.unregistered-delete']);
            Route::get('/new', ['uses' => 'UserController@getNewUserForm', 'as' => 'admin.users.new-user']);
            Route::post('/new-create', ['uses' => 'UserController@postCreateNewUser', 'as' => 'admin.users.new-user-create']);
            Route::post('/add/product', ['uses' => 'UserController@addQuickBooksProductService', 'as' => 'admin.user.quick_books.product.add']);
            Route::post('/save', ['uses' => 'UserController@postSave', 'as' => 'admin.users.save']);
            Route::post('/update-address', ['uses' => 'UserController@postUpdateAddress', 'as' => 'admin.users.update-address']);
            Route::post('/update-billing-address', ['uses' => 'UserController@postUpdateBillingAddress', 'as' => 'admin.users.update-billing-address']);
            Route::post('/login', ['uses' => 'UserController@postLogin', 'as' => 'admin.users.login']);
            Route::get('/autocomplete', ['uses' => 'UserController@getAutocomplete', 'as' => 'admin.users.autocomplete']);
            Route::get('/bulk-add-form', ['uses' => 'UserController@getBulkAdd', 'as' => 'admin.users.bulk-add-form']);
            Route::post('/bulk-add', ['uses' => 'UserController@postBulkAdd', 'as' => 'admin.users.bulk-add']);
            Route::get('/export', ['uses' => 'UserController@getExport', 'as' => 'admin.users.export']);
            Route::post('/register', ['uses' => 'UserController@postRegisterUnregisteredForm', 'as' => 'admin.users.register']);
            Route::post('/register/save', ['uses' => 'UserController@postRegisterUnregistered', 'as' => 'admin.users.register-save']);
            Route::post('/suspend-user', ['uses' => 'UserController@postSuspendUser', 'as' => 'admin.users.suspend-user']);
            Route::post('/update-notes', ['uses' => 'UserController@postUpdateNotes', 'as' => 'admin.users.update-notes']);
            Route::post('/marketing-emails', ['uses' => 'UserController@postMarketingEmails', 'as' => 'admin.users.marketing-emails']);
            Route::post('/create-quickbooks-customer', ['uses' => 'UserController@postCreateQuickbooksCustomer', 'as' => 'admin.users.create-quickbooks-customer']);
            Route::get('/whats-app-users', ['uses' => 'UserController@getWhatsAppUsers', 'as' => 'admin.users.whats-app-users']);
            Route::post('/whats-app-users-added', ['uses' => 'UserController@postWhatsAppUsersAdded', 'as' => 'admin.users.whats-app-users-added']);
            Route::get('/customers-with-balance', ['uses' => 'UserController@getCustomersWithBalance', 'as' => 'admin.users.customers-with-balance']);
            Route::post('/update-balance-due-date', ['uses' => 'UserController@postUpdateBalanceDueDate', 'as' => 'admin.users.update-balance-due-date']);
            Route::post('/customers-with-balance-reminders', ['uses' => 'UserController@postCustomersWithBalanceReminders', 'as' => 'admin.users.customers-with-balance-reminders']);
            Route::post('/customers-with-balance-hide', ['uses' => 'UserController@postCustomersWithBalanceHide', 'as' => 'admin.users.customers-with-balance-hide']);
            Route::get('/recommercetech-users', ['uses' => 'UserController@getRecommercetechUsers', 'as' => 'admin.users.recommercetech-users']);
            Route::post('/update-admin-type', ['uses' => 'UserController@postUpdateAdminType', 'as' => 'admin.users.update-admin-type']);
            Route::post('/create-admin', ['uses' => 'UserController@postCreateAdmin', 'as' => 'admin.users.create-admin']);
            Route::post('/delete-admin', ['uses' => 'UserController@postDeleteAdmin', 'as' => 'admin.users.delete-admin']);
            Route::post('/update-station-id', ['uses' => 'UserController@postUpdateStationId', 'as' => 'admin.users.update-station-id']);
            Route::get('/{id}', [UserController::class , 'getSingle'])->name('admin.users.single');
            Route::get('/lcd-user/{id}', ['uses' => 'UserController@getLCDUserSingle', 'as' => 'admin.lcd-users.single']);
            Route::get('/{id}/emails', ['uses' => 'UserController@getUserEmails', 'as' => 'admin.users.single.emails']);
            Route::post('/emails/preview', ['uses' => 'UserController@postUserEmailPreview', 'as' => 'admin.users.emails.preview']);
            Route::post('/emails/send', ['uses' => 'UserController@postUserEmailSend', 'as' => 'admin.users.emails.send']);
            Route::post('/api-generate-key', ['uses' => 'UserController@postApiGenerateKey', 'as' => 'admin.users.api.generate-key']);
            Route::post('/send-email', ['uses' => 'UserController@sendEmail', 'as' => 'admin.users.send-email']);
            Route::post('/delete', ['uses' => 'UserController@removeDeleted', 'as' => 'admin.users.remove-user']);
            Route::post('/save/sub-admin', ['uses' => 'UserController@addSubAdmin', 'as' => 'sub-admin.add']);
            Route::get('/delete/sub-admin/{id}', ['uses' => 'UserController@removeSubAdmin', 'as' => 'sub-admin.remove']);
        });

        Route::group(['prefix' => 'settings', 'middleware' => ['not_staff']], function () {
            Route::get('/', ['uses' => 'SettingsController@getIndex', 'as' => 'admin.settings']);
            Route::post('/', ['uses' => 'SettingsController@postIndex', 'as' => 'admin.settings.submit']);
            Route::post('/cron', ['uses' => 'SettingsController@postRunCron', 'as' => 'admin.settings.run-cron']);
            Route::post('/free-delivery', ['uses' => 'SettingsController@postFreeDelivery', 'as' => 'admin.settings.free-delivery']);
            Route::get('/quickbooks', ['uses' => 'QuickbooksController@getIndex', 'as' => 'admin.quickbooks']);
            Route::any(
                '/quickbooks/oauth-start',
                ['uses' => 'QuickbooksController@getOAuthStart', 'as' => 'admin.quickbooks.oauth.start']
            );
            Route::get(
                '/quickbooks/oauth-callback',
                ['uses' => 'QuickbooksController@getOAuthCallback', 'as' => 'admin.quickbooks.oauth.callback']
            );
            Route::get(
                '/quickbooks/oauth-success',
                ['uses' => 'QuickbooksController@getOAuthSuccess', 'as' => 'admin.quickbooks.oauth.success']
            );
            Route::any(
                '/quickbooks/oauth-refresh-token',
                ['uses' => 'QuickbooksController@getOAuth2RefreshToken', 'as' => 'admin.quickbooks.oauth.refresh-token']
            );
            Route::any('/quickbooks/oauth-company-info', ['uses' => 'QuickbooksController@getOauth2CompanyInfo', 'as' => 'admin.quickbooks.oauth.company-info']);
            Route::get('/clear-stock', ['uses' => 'SettingsController@getClearStock', 'as' => 'admin.settings.clear-stock']);
            Route::post('/change-shown-to', ['uses' => 'SettingsController@postChangeShownToNone', 'as' => 'admin.settings.shown-to-none']);
            Route::post('/change-in-stock-to-inbound', ['uses' => 'SettingsController@postChangeInStockToInbound', 'as' => 'admin.settings.change-in-stock-to-inbound']);
            Route::get('/allowed-ips', ['uses' => 'SettingsController@getAllowedIps', 'as' => 'admin.settings.allowed-ips']);
            Route::post('/allowed-ips/add', ['uses' => 'SettingsController@postAllowedIpsAdd', 'as' => 'admin.settings.allowed-ips-add']);
            Route::post('/allowed-ips/remove', ['uses' => 'SettingsController@postAllowedIpsRemove', 'as' => 'admin.settings.allowed-ips-remove']);
            Route::get('/ignore-sku', ['uses' => 'SettingsController@getIgnoreSku', 'as' => 'admin.settings.ignore-sku']);
            Route::post('/ignore-sku/add', ['uses' => 'SettingsController@postIgnoreSkuAdd', 'as' => 'admin.settings.ignore-sku-add']);
            Route::post('/ignore-sku/remove', ['uses' => 'SettingsController@postIgnoreSkuRemove', 'as' => 'admin.settings.ignore-sku-remove']);
            Route::post('/change-ebay-shown-to-none', ['uses' => 'SettingsController@postChangeEbayShownToNone', 'as' => 'admin.settings.ebay-shown-to-none']);
            Route::get('/quickbooks/query', ['uses' => 'QuickbooksController@getQuery', 'as' => 'admin.quickbooks.query']);
            Route::get('/update-stock', ['uses' => 'SettingsController@updateStock', 'as' => 'admin.settings.update-stock']);
            Route::get('/ebay', ['uses' => 'SettingsController@getEbaySetting', 'as' => 'admin.settings.ebay']);
            Route::get('/email-format', ['uses' => 'SettingsController@getUploadDocumentEmailFormat', 'as' => 'admin.email-format']);
            Route::post('/email-format', ['uses' => 'SettingsController@saveUploadDocumentEmailFormat', 'as' => 'admin.save.email-format']);
            Route::get('/export-buyback-product', ['uses' => 'SettingsController@ExportBuyBackProduct', 'as' => 'admin.export.buyback-product']);
            Route::get('/dpd-shipping', ['uses' => 'SettingsController@DpdShipping', 'as' => 'admin.dpd-shipping']);
            Route::get('/dpd-shipping/refresh-token', ['uses' => 'SettingsController@DpdRefreshToken', 'as' => 'admin.dpd-shipping.refresh-token']);
            Route::post('/dpd-shipping/status', ['uses' => 'SettingsController@dpdShippingStatus', 'as' => 'admin.dpd-shipping.status']);
        });

        Route::get('/testing-results', ['uses' => 'TestingResultController@index', 'as' => 'admin.testing-result']);

    });

    // Batches
    Route::group(['prefix' => 'batches', 'middleware' => ['admin']], function () {
        Route::get('/', ['uses' => 'BatchesController@getIndex', 'as' => 'batches']);
        Route::get('/{id}', ['uses' => 'BatchesController@getSingle', 'as' => 'batches.single'])->where('id', '[0-9]+');;
        Route::post('/delete', ['uses' => 'BatchesController@postDelete', 'as' => 'batches.delete']);
        Route::post('/update', ['uses' => 'BatchesController@postUpdate', 'as' => 'batches.update']);
        Route::get('/{id}/deal-sheet', ['uses' => 'BatchesController@getDealSheet', 'as' => 'batches.deal-sheet']);
        Route::get('/{id}/overview', ['uses' => 'BatchesController@getOverview', 'as' => 'batches.overview']);
        Route::get('/{id}/summary', ['uses' => 'BatchesController@getSingleSummary', 'as' => 'batches.single-summary']);
        Route::get('/{id}/summary-export', ['uses' => 'BatchesController@getSingleSummaryExport', 'as' => 'batches.single-summary-export']);
        Route::get('/{id}/export/{option}/{email?}', ['uses' => 'BatchesController@getExport', 'as' => 'batches.export']);
        Route::get('/new/custom', ['uses' => 'BatchesController@getNewCustom', 'as' => 'batches.new-custom']);
        Route::post('/new/custom-submit', ['uses' => 'BatchesController@postNewCustomSubmit', 'as' => 'batches.new-custom-submit']);
        Route::get('/summary', ['uses' => 'BatchesController@getSummary', 'as' => 'batches.summary']);
        Route::post('/clear-notes', ['uses' => 'BatchesController@postClearNotes', 'as' => 'batches.clear-notes']);
        Route::post('/update-notes', ['uses' => 'BatchesController@postUpdateNotes', 'as' => 'batches.update-notes']);
        Route::post('/deal-sheet-submit', ['uses' => 'BatchesController@postDealSheetSubmit', 'as' => 'batches.deal-sheet-submit']);
        Route::post('/deal-sheet-notify-best-price', ['uses' => 'BatchesController@postDealSheetNotifyBestPrice', 'as' => 'batches.deal-sheet-notify-best-price']);
        Route::post('/deal-sheet-delete-offer', ['uses' => 'BatchesController@postDealSheetDeleteOffer', 'as' => 'batches.deal-sheet-delete-offer']);
        Route::post('/deal-sheet-mark-as-seen', ['uses' => 'BatchesController@postDealSheetMarkAsSeen', 'as' => 'batches.deal-sheet-mark-as-seen']);
        Route::post('/deal-sheet-mark-all-as-seen', ['uses' => 'BatchesController@postDealSheetMarkAllAsSeen', 'as' => 'batches.deal-sheet-mark-all-as-seen']);
        Route::post('/send', ['uses' => 'BatchesController@postSend', 'as' => 'batches.send']);
        Route::post('/send-to-user', ['uses' => 'BatchesController@postSendToUser', 'as' => 'batches.send-to-user']);
        Route::post('/merge', ['uses' => 'BatchesController@postMerge', 'as' => 'batches.merge']);
        Route::post('/send-batches', ['uses' => 'BatchesController@postSendBatches', 'as' => 'batches.send-batches']);
    });

    // Stock
    Route::group(['prefix' => 'stock'], function () {
        Route::group(['middleware' => ['admin']], function () {
            Route::post('/create-repair', [StockController::class,'postSaveRepair'])->name('stock.repair.add');
            Route::get('/set-items', [StockController::class,'getRedirectBatch'])->name('stock.redirect-batch');
            Route::get('/ebay-remove-sales/{id?}', [StockController::class,'removeEbaySales'])->name('stock.ebay-remove-sales');
            Route::get('/create', [StockController::class,'getCreateBatch'])->name('stock.create-batch');

            Route::post('/create/new', [StockController::class,'postCreateNewBatch'])->name('stock.create-new-batch');
            Route::post('/create/add', [StockController::class,'postCreateAddBatch'])->name('stock.create-add-batch');
            Route::post('/import', [StockController::class,'postImport'])->name('stock.import');
            Route::get('/template', [StockController::class,'getTemplate'])->name('stock.template');
            Route::post('/save', [StockController::class,'postSave'])->name('stock.save');
            //Route::get('/trg-item-import', [StockController::class,'getTrgItemImport', 'as' => 'stock.trg-item-import']);
            //Route::post('/trg-item-import', [StockController::class,'postTrgItemImport', 'as' => 'stock.trg-item-import.save']);
            Route::post('/receive', [StockController::class,'postReceive'])->name('stock.receive');
            Route::get('/delete', [StockController::class,'getDelete'])->name('stock.delete-form');
            Route::post('/delete', [StockController::class,'postDelete'])->name('stock.delete');



            Route::post('/change-status', [StockController::class,'postChangeStatus'])->name('stock.change-status');
            Route::post('/change-manual-sku', [StockController::class,'postChangeManualSku'])->name('stock.change-manual-sku');
            Route::post('/in-repair-change-back', [StockController::class,'postInRepairChangeBack'])->name('stock.in-repair-change-back');
            Route::get('/export-custom', [StockController::class,'getCustomExport'])->name('stock.export-custom');
            Route::get('/purchase-order-stats', [StockController::class,'getPurchaseOrderStats'])->name('stock.purchase-order-stats');
            Route::get('/purchase-order-stats-export', [StockController::class,'getPurchaseOrderStatsExport'])->name('stock.purchase-order-stats-export');
            Route::get('/purchase-order-stats-export-phone-diagnostics', [StockController::class,'getPurchaseOrderStatsPhoneDiagnosticsExport'])->name('stock.purchase-order-stats-phone-diagnostics-export');
            Route::get('/purchase-order-stats-export-all', [StockController::class,'getPurchaseOrderStatsPhoneDiagnosticsExportAll'])->name('stock.purchase-order-stats-phone-diagnostics-export-all');
            Route::get('/purchase-order-stats-export-missing-notes', [StockController::class,'getPurchaseOrderStatsPhoneDiagnosticsExportMissingNotes'])->name('stock.purchase-order-stats-phone-diagnostics-export-missing-notes');
            Route::get('/purchase-order-stats-export-stats', [StockController::class,'getPurchaseOrderStatsExportStats'])->name('stock.purchase-order-stats-export-stats');
            Route::post('/purchase-order-update-purchase-country', [StockController::class,'postPurchaseOrderUpdatePurchaseCountry'])->name('stock.purchase-order-update-purchase-country');
            Route::post('/purchase-order-update-ps-model', [StockController::class,'postPurchaseOrderUpdatePSModel'])->name('stock.purchase-order-update-ps-model');
            Route::post('/purchase-order-update-purchase-date', [StockController::class,'postPurchaseOrderUpdatePurchaseDate'])->name('stock.purchase-order-update-purchase-date');
            Route::get('/purchase-order-all/csv', [StockController::class,'exportCsvPurchaseOrdersAll'])->name('stock.purchase-order.csv');
            Route::get('/purchase-orders-all', [StockController::class,'getPurchaseOrdersAll'])->name('stock.purchase-orders-all');


            Route::get('/purchase-overview', [StockController::class,'getPurchaseOverview'])->name('stock.purchase-overview');
            Route::get('/purchase-overview-stats', [StockController::class,'getPurchaseOverviewStats'])->name('stock.purchase-overview-stats');
            Route::post('/shown-to-save', [StockController::class,'postShownToSave'])->name('stock.shown-to-save');
            Route::post('/remove-from-batch', [StockController::class,'postRemoveFromBatch'])->name('stock.remove-from-batch');
            Route::get('/other-recycles/', [StockController::class,'getOtherRecycles'])->name('stock.other-recycles');
            Route::post('/other-recycles/', [StockController::class,'postOtherRecycles'])->name('stock.other-recycles-add');
            Route::get('/other-recycles/check', [StockController::class,'getCheckToBuy'])->name('stock.other-recycles-check');
            Route::post('/other-recycles/check', [StockController::class,'postCheckToBuy'])->name('stock.other-recycles-check');
            Route::post('/change-grade', [StockController::class,'postChangeGrade'])->name('stock.change-grade');
            Route::post('/change-grade-fully-working', [StockController::class,'postChangeGradeFullyWorking'])->name('stock.change-grade-fully-working');
            Route::get('/quick-order', [StockController::class,'getQuickOrder'])->name('stock.quick-order-form');
            Route::post('/quick-order', [StockController::class,'postQuickOrder'])->name('stock.quick-order');
            Route::get('/engineer-report', [StockController::class,'getEngineerReport'])->name('stock.engineer-report');
            Route::post('/add-stock', [StockController::class,'postAddStock'])->name('stock.add-stock');


            Route::get('/bulk-receive', [StockController::class,'getBulkReceive'])->name('stock.bulk-receive');
            Route::post('/bulk-receive', [StockController::class,'postBulkReceive'])->name('stock.bulk-receive-submit');
            Route::post('/item-receive', [StockController::class,'postItemReceive'])->name('stock.item-receive');
            Route::post('/item-delete', [StockController::class,'postItemDelete'])->name('stock.item-delete');
            Route::get('/export-aged-stock', [StockController::class,'getExportAgedStock'])->name('stock.export-aged-stock');
            Route::post('/lock-check-re-check', [StockController::class,'postLockCheckReCheck'])->name('stock.lock-check-re-check');
            Route::get('/ebay-whats-app-items', [StockController::class,'getEbayWhatsAppItems'])->name('stock.ebay-whats-app-items');
            Route::get('/stock-sales-export', [StockController::class,'getStockSalesExport'])->name('stock.stock-sales-export');
            Route::post('/set-sales-price-to-purchase-price', [StockController::class,'postSetAllSalesPriceToPurchasePrice'])->name('stock.set-sales-price-to-purchase-price');
            Route::post('/parts-add', [StockController::class,'postPartsAdd'])->name('stock.parts-add');
            Route::post('/parts-remove', [StockController::class,'postPartsRemove'])->name('stock.parts-remove');
            Route::post('/move-to-stock', [StockController::class,'postMoveToStock'])->name('stock.move-to-stock');
            Route::post('/delete-permanently', [StockController::class,'postDeletePermanently'])->name('stock.delete-permanently');
            Route::get('/ready-for-sale', [StockController::class,'getReadyForSale'])->name('stock.ready-for-sale');
            Route::get('/ready-for-sale-export', [StockController::class,'getReadyForSaleExport'])->name('stock.ready-for-sale-export');
            Route::get('/retail-stock', [StockController::class,'getRetailStock'])->name('stock.retail-stock');
            Route::get('/retail-stock-export', [StockController::class,'getRetailStockExport'])->name('stock.retail-stock-export');
            Route::post('/update-retail-stock-quantities', [StockController::class,'postUpdateRetailStockQuantities'])->name('stock.update-retail-stock-quantities');
            Route::post('/update-new-sku', [StockController::class,'postUpdateNewSku'])->name('stock.update-new-sku');
            Route::post('/assign-product', [StockController::class,'postAssignProduct'])->name('stock.assign-product');
            Route::post('/remove-product-assignment', [StockController::class,'postRemoveProductAssignment'])->name('stock.remove-product-assignment');
            Route::post('/change-product-type', [StockController::class,'postChangeProductType'])->name('stock.change-product-type');
            Route::get('/phone-check/{id}', [StockController::class,'phoneCheck'])->name('stock.single.phone-check');
            Route::post('add-non-serialised-stock', [StockController::class,'postAddNonSerialisedStock'])->name('stock.non-serialised.add');


        });
        Route::get("/export/in-stock", [StockController::class,'getInStockExport'])->name('stock.in-stock.export');
        Route::get('/export/{option?}', [StockController::class,'getExport'])->name('stock.export');
        Route::post('/export-filter', [StockController::class,'getExportByFilter'])->name('stock.export.filter');
        Route::get('/all-model', [StockController::class,'getAllModel'])->name('stock.all.model');
        Route::get('/batches', [StockController::class,'getBatches'])->name('stock.batches'); #batches
        Route::get('/batch/{id}', [StockController::class,'getBatch'])->name('stock.batch'); #batches
        Route::get('/batch/{id}/summary', [StockController::class,'getViewBatchSummary'])->name('stock.batch-view-summary'); #batches
        Route::get('/batch/{id}/export', [StockController::class,'getViewBatchSummaryExport'])->name('stock.batch-view-summary-export'); #batches
        Route::match(['get', 'post'], '/check', [StockController::class,'checkCloud'])->name('stock.check-icloud');

        Route::get('/', [StockController::class,'getIndex'])->name('stock');

        Route::get('/location', [StockController::class,'getLocationConfig'])->name('stock.locations');
        Route::post('/location', [StockController::class,'postLocationConfig'])->name('stock.locations.save');
        Route::get('/overview', [StockController::class,'getOverview'])->name('stock.overview');
        Route::get('/faq', [StockController::class,'getFaq'])->name('stock.faq');

        Route::get('/{id}', [StockController::class,'getSingle'])->name('stock.single');


        Route::get('/status/{id}', [StockController::class,'updateStatus'])->name('stock.status.update');
        Route::post('get-information', [StockController::class,'getStockInformation'])->name('stock.info');
        Route::post('/external-repair', [StockController::class,'addExternalRepairCost'])->name('stock.external.repair');
        Route::post('/phone-check-result', [StockController::class,'getPhoneCheckResult'])->name('stock.external.phone-check-result');
        Route::get('/inventory/csv', [StockController::class,'inventoryExportCsv'])->name('inventory.export.csv');
        Route::post('/processing-image/upload', [StockController::class,'uploadProcessingImage'])->name('upload.processing-image');
        Route::get('/processing-image/delete/{id}', [StockController::class,'removeProcessingImage'])->name('delete.processing-image');


    });


    // Sales
    Route::group(['prefix' => 'sales'], function () {
        Route::group(['middleware' => ['admin']], function () {

            Route::get('/accessories', ['uses' => 'SalesController@getSalesAccessories', 'as' => 'sales.accessories']);
            Route::get('accessories/{id}', ['uses' => 'SalesController@getSalesAccessoriesSingle', 'as' => 'sales.accessories.single']);
            Route::post('/accessories/update', ['uses' => 'SalesController@postSalesAccessoriesUpdate', 'as' => 'sales.accessories.update']);
            Route::post('/accessories/create', ['uses' => 'SalesController@postSalesAccessoriesCreate', 'as' => 'sales.accessories.create']);
            Route::match(['get', 'post'], '/create', ['uses' => 'SalesController@getCreate', 'as' => 'sales.new']);
            Route::post('/change-status', ['uses' => 'SalesController@postChangeStatus', 'as' => 'sales.change-status']);
            Route::post('/single-change-status', ['uses' => 'SalesController@postSingleChangeStatus', 'as' => 'sales.single-change-status']);
            Route::post('/single-tracking-number', ['uses' => 'SalesController@postSingleTrackingNumber', 'as' => 'sales.single-tracking-number']);
            Route::post('/checkPaid', ['uses' => 'SalesController@postCheckPaid', 'as' => 'sales.check-paid']);
            Route::post('/delete', ['uses' => 'SalesController@postDelete', 'as' => 'sales.delete']);
            Route::post('/tracking-number', ['uses' => 'SalesController@postTrackingNumber', 'as' => 'sales.tracking-number']);
            Route::get('/modify-order', ['uses' => 'SalesController@getModify', 'as' => 'sales.modify']);
            Route::post('/swap-item', ['uses' => 'SalesController@postSwapItem', 'as' => 'sales.swap-item']);
            Route::post('/remove-item', ['uses' => 'SalesController@postRemoveItem', 'as' => 'sales.remove-item']);
            Route::post('/summary-auction-batch', ['uses' => 'SalesController@postSummaryAuctionBatch', 'as' => 'sales.summary-auction-batch']);
            Route::get('/summary-other', ['uses' => 'SalesController@getSummaryOther', 'as' => 'sales.summary-other']);
            Route::any('/save-other', ['uses' => 'SalesController@postSaveOther', 'as' => 'sales.save-other']);
            Route::post('/other-change-recycler', ['uses' => 'SalesController@postOtherChangeRecycler', 'as' => 'sales.other-change-recycler']);
            Route::post('/other-remove-item', ['uses' => 'SalesController@postOtherRemoveItem', 'as' => 'sales.other-remove-item']);
            Route::post('/other-change-price', ['uses' => 'SalesController@postOtherChangePrice', 'as' => 'sales.other-change-price']);
            Route::post('/send-order-imeis', ['uses' => 'SalesController@postSendOrderImeis', 'as' => 'sales.send-order-imeis']);
            Route::get('/print-receipt', ['uses' => 'SalesController@getPrintReceipt', 'as' => 'sales.print-receipt']);
            Route::get('/{id}/export', ['uses' => 'SalesController@getExport', 'as' => 'sales.export']);
            Route::get('/custom-order', ['uses' => 'SalesController@getCustomOrder', 'as' => 'sales.custom-order']);
            Route::post('/custom-order-create', ['uses' => 'SalesController@postCustomOrderCreate', 'as' => 'sales.custom-order-create']);
            Route::post('/re-create-invoice', ['uses' => 'SalesController@postReCreateInvoice', 'as' => 'sales.re-create-invoice']);
            Route::post('/remove-item-from-sale', ['uses' => 'SalesController@postRemoveItemFromSale', 'as' => 'sales.remove-item-from-sale']);
            Route::post('/change-item-sale-price', ['uses' => 'SalesController@postChangeItemSalePrice', 'as' => 'sales.change-item-sale-price']);
            Route::post('/change-multiple-item-sale-price', ['uses' => 'SalesController@postMultipleChangeItemSalePrice', 'as' => 'sales.change-multiple-item-sale-price']);
            Route::post('/check-all-networks', ['uses' => 'SalesController@postCheckAllNetworks', 'as' => 'sales.check-all-networks']);
            Route::post('/update-tracking', ['uses' => 'SalesController@postUpdateTracking', 'as' => 'sales.update-tracking']);
            Route::post('/bulk-update-sale-price', ['uses' => 'SalesController@postBulkUpdateSalePrice', 'as' => 'sales.bulk-update-sale-price']);
            Route::post('/update-price', ['uses' => 'SalesController@updatePrice', 'as' => 'sales.update-price']);
            Route::post('/shipping_cost', ['uses' => 'SalesController@updateShippingCost', 'as' => 'sales.shipping_cost']);
            Route::get('/dashboard', ['uses' => 'SalesController@getDashboard', 'as' => 'sales.dashboard']);
            Route::get('/customer_return', ['uses' => 'CustomerReturnsController@getIndex', 'as' => 'sales.customer_return']);
            Route::get('/customer_return/create', ['uses' => 'CustomerReturnsController@create', 'as' => 'sales.customer_return.create']);
            Route::post('/customer_return/save', ['uses' => 'CustomerReturnsController@postSave', 'as' => 'sales.customer_return.save']);
            Route::get('/customer_return/{id}', ['uses' => 'CustomerReturnsController@getCustomerReturn', 'as' => 'sales.customer_return.single']);
            Route::post('/export/csv', ['uses' => 'SalesController@exportCsv', 'as' => 'sales.export.filter']);
        });

        Route::get('/', ['uses' => 'SalesController@getIndex', 'as' => 'sales']);
        Route::get('/set-items', ['uses' => 'SalesController@getRedirect', 'as' => 'sales.redirect']);
        Route::group(['middleware' => ['suspended']], function () {
            Route::match(['get', 'post'], '/summary', ['uses' => 'SalesController@getSummary', 'as' => 'sales.summary']);
            Route::post('/summary-batch', ['uses' => 'SalesController@postSummaryBatch', 'as' => 'sales.summary-batch']);
            Route::post('/save-batch', ['uses' => 'SalesController@postSaveBatch', 'as' => 'sales.save-batch']);
            Route::post('/save', ['uses' => 'SalesController@postSave', 'as' => 'sales.save']);
        });
        Route::post('/select-payment-method', ['uses' => 'SalesController@postSelectPaymentMethod', 'as' => 'sales.select-payment-method']);
        Route::get('/pay', ['uses' => 'SalesController@getPay', 'as' => 'sales.pay']);
        Route::post('/pay', ['uses' => 'SalesController@postPay', 'as' => 'sales.pay-submit']);
        Route::post('/payment-complete', ['uses' => 'SalesController@postPaymentComplete', 'as' => 'sales.payment-complete']);
        Route::post('/cancel', ['uses' => 'SalesController@postCancel', 'as' => 'sales.cancel']);
        Route::get('/status-check', ['uses' => 'SalesController@getStatusCheck', 'as' => 'sales.status-check']);
        Route::get('/{id}/invoice', ['uses' => 'SalesController@getInvoice', 'as' => 'sales.invoice']);
        Route::get('/{id}', ['uses' => 'SalesController@getSingle', 'as' => 'sales.single']);

        Route::get('/delivery-note/{id}', ['uses' => 'SalesController@deliveryNoteDownload', 'as' => 'sales.delivery-note']);


    });

    // Basket
    Route::group(['prefix' => 'basket'], function () {
        Route::get('/', ['uses' => 'BasketController@getIndex', 'as' => 'basket']);
        Route::post('/toggle', ['uses' => 'BasketController@postToggle', 'as' => 'basket.toggle']);
        Route::post('/delete', ['uses' => 'BasketController@postDelete', 'as' => 'basket.delete']);
        Route::get('/delete-item', ['uses' => 'BasketController@getDeleteItem', 'as' => 'basket.delete-item']);
        Route::post('/empty', ['uses' => 'BasketController@postEmpty', 'as' => 'basket.empty']);
        Route::get('/get-html', ['uses' => 'BasketController@getHtml', 'as' => 'basket.get-html']);
    });

    // Account
    Route::group(['prefix' => 'my-account'], function () {
        Route::get('/', ['uses' => 'AccountController@getIndex', 'as' => 'account']);
        Route::post('/', ['uses' => 'AccountController@postIndex', 'as' => 'account.save']);
        Route::get('/settings', ['uses' => 'AccountController@getSettings', 'as' => 'account.settings']);
        Route::post('/settings', ['uses' => 'AccountController@postSettings', 'as' => 'account.settings.save']);
        Route::get('/balance', ['uses' => 'AccountController@getBalance', 'as' => 'account.balance']);
        Route::get('/api', ['uses' => 'AccountController@getApi', 'as' => 'account.api']);
        Route::post('/api-generate-key', ['uses' => 'AccountController@postApiGenerateKey', 'as' => 'account.api.generate-key']);
        Route::post('/change-password', ['uses' => 'AccountController@postChangePassword', 'as' => 'account.change-password']);
    });

    // Unlocks Cost
    Route::group(['prefix' => 'unlocks-cost', 'middleware' => ['admin']], function () {
        Route::get('/', ['uses' => 'UnlocksCostController@getIndex', 'as' => 'unlocks-cost']);
        Route::post('/add', ['uses' => 'UnlocksCostController@postAdd', 'as' => 'unlocks-cost.add']);
        Route::post('/update', ['uses' => 'UnlocksCostController@postUpdate', 'as' => 'unlocks-cost.update']);
        Route::post('/delete', ['uses' => 'UnlocksCostController@postDelete', 'as' => 'unlocks-cost.delete']);
    });

    // Unlocks
    Route::group(['prefix' => 'unlocks'], function () {
        Route::get('/', [UnlocksController::class,'getIndex'])->name('unlocks');
        Route::post('/', [UnlocksController::class,'postAddAsUser'])->name('unlocks.add-as-user');

        Route::group(['middleware' => ['suspended']], function () {
            Route::get('/add', [UnlocksController::class,'getAdd'])->name('unlocks.add');
            Route::get('/own-stock-new-order', [UnlocksController::class,'getOwnStockNewOrder'])->name('unlocks.own-stock.new-order');
            Route::post('/own-stock-new-order', [UnlocksController::class,'postOwnStockNewOrder'])->name('unlocks.own-stock.new-order-save');
        });

        Route::get('/own-stock', [UnlocksController::class,'getOwnStock'])->name('unlocks.own-stock');
        Route::get('/own-stock-order-pay/{id}', [UnlocksController::class,'getOwnStockOrderPay'])->name('unlocks.own-stock.order-pay-form');
        Route::post('/own-stock-order-pay', [UnlocksController::class,'postOwnStockOrderPay'])->name('unlocks.own-stock.order-pay');
        Route::get('/own-stock-order-details/{id}', [UnlocksController::class,'getOwnStockOrderDetails'])->name('unlocks.own-stock.order-details');
        Route::post('/own-stock-order-cancel', [UnlocksController::class,'postOwnStockOrderCancel'])->name('unlocks.own-stock.order-cancel');
        Route::get('/pay_get', [UnlocksController::class,'getPay'])->name('unlocks.pay-get');
        Route::any('/pay_test/{id}', [UnlocksController::class,'postPay'])->name('unlocks.pay-submit');
        Route::post('/payment-complete', [UnlocksController::class,'postPaymentComplete'])->name('unlocks.payment-complete');
        Route::get('/own-stock-invoice/{id}', [UnlocksController::class,'getInvoice'])->name('unlocks.invoice');

        Route::group(['middleware' => ['admin']], function () {
            Route::post('/add', [UnlocksController::class,'postAddAsAdmin'])->name('unlocks.add-as-admin');
            Route::post('/mark-unlocked', [UnlocksController::class,'postMarkUnlocked'])->name('unlocks.mark-unlocked');
            Route::post('/retry', [UnlocksController::class,'postRetry'])->name('unlocks.retry');
            Route::post('/bulk-retry', [UnlocksController::class,'postBulkRetry'])->name('unlocks.bulk-retry');
            Route::get('/failed/{action?}/{id?}', [UnlocksController::class,'failedUnlocks'])->name('unlocks.failed');
            Route::post('/fail', [UnlocksController::class,'postFail'])->name('unlocks.fail');
            Route::post('/add-by-stock', [UnlocksController::class,'postAddByStock'])->name('unlocks.add-by-stock');
            Route::post('/retry-place-order-cron', [UnlocksController::class,'postRetryPlaceUnlockOrderCron'])->name('unlocks.retry-place-unlock-order-cron');
            Route::post('/update-item-name', [UnlocksController::class,'postUpdateItemName'])->name('unlocks.update-item-name');
        });
    });

    // Sage
    Route::group(['prefix' => 'sage'], function () {
        Route::get('/complete/{type}', ['uses' => 'SageController@getComplete', 'as' => 'sage.complete']);
    });

    // Emails
    Route::group(['prefix' => 'emails', 'middleware' => ['admin', 'not_staff']], function () {
        Route::get('/', ['uses' => 'EmailSenderController@getIndex', 'as' => 'emails']);
        Route::get('/create-form/{draft?}', ['uses' => 'EmailSenderController@getCreate', 'as' => 'emails.create-form']);
        Route::post('/save', ['uses' => 'EmailSenderController@postSave', 'as' => 'emails.save']);
        Route::post('/preview', ['uses' => 'EmailSenderController@postPreview', 'as' => 'emails.preview']);
        Route::get('/single/{id}', ['uses' => 'EmailSenderController@getSingle', 'as' => 'emails.single']);
        Route::get('/single/{id}/delivery-summary', ['uses' => 'EmailSenderController@getSingleDeliverySummary', 'as' => 'emails.single-delivery-summary']);
        Route::get('/check-statuses', ['uses' => 'EmailSenderController@getStatuses', 'as' => 'emails.check-statuses']);
        Route::post('/test-send', ['uses' => 'EmailSenderController@postTestSend', 'as' => 'emails.test-send']);
        Route::post('/save-draft', ['uses' => 'EmailSenderController@postSaveDraft', 'as' => 'emails.save-draft']);
        Route::get('/drafts', ['uses' => 'EmailSenderController@getDraftsIndex', 'as' => 'emails.drafts']);
        Route::delete('/delete-draft', ['uses' => 'EmailSenderController@deleteDraft', 'as' => 'emails.delete-draft']);
    });


    Route::group(['prefix' => 'engineer', 'middleware' => ['admin', 'not_staff']], function () {
        Route::get('/', ['uses' => 'RepairEngineerController@getIndex', 'as' => 'engineer.index']);
        Route::post('/save', ['uses' => 'RepairEngineerController@postSave', 'as' => 'engineer.save']);
        Route::post('/data', ['uses' => 'RepairEngineerController@getEngineer', 'as' => 'engineer.data']);


    });


    //Category

    Route::group(['prefix' => 'category', 'middleware' => ['admin', 'not_staff']], function () {
        Route::get('/', ['uses' => 'CategoryController@index', 'as' => 'category.index']);
        Route::get('/delete/{id}', ['uses' => 'CategoryController@removeCategory', 'as' => 'category.delete']);
        Route::get('/{id}', ['uses' => 'CategoryController@update', 'as' => 'category.update']);
        Route::get('/create', ['uses' => 'CategoryController@create', 'as' => 'category.create']);
        Route::post('/create/save', ['uses' => 'CategoryController@postSave', 'as' => 'category.save']);
        Route::get('cron-job/assigned', ['uses' => 'CategoryController@eBayCategoryIdAssignedCronJob', 'as' => 'cron-job.assigned']);
        Route::post('update-validation', ['uses' => 'CategoryController@updateValidation', 'as' => 'update.validation']);


    });

    //Colour

    Route::group(['prefix' => 'colour', 'middleware' => ['admin', 'not_staff']], function () {
        Route::get('/', ['uses' => 'ColourController@index', 'as' => 'colour.index']);
        Route::get('/{id}', ['uses' => 'ColourController@update', 'as' => 'colour.update']);
        Route::get('/create', ['uses' => 'ColourController@create', 'as' => 'colour.create']);
        Route::post('/create/save', ['uses' => 'ColourController@postSave', 'as' => 'colour.save']);
    });
    Route::group(['prefix' => 'ebay-seller', 'middleware' => ['admin', 'not_staff']], function () {
        Route::get('/', ['uses' => 'EbaySellerController@index', 'as' => 'ebay-seller.index']);
        Route::get('/delete/{id}', ['uses' => 'EbaySellerController@delete', 'as' => 'ebay-seller.delete']);
        Route::get('/{id}', ['uses' => 'EbaySellerController@update', 'as' => 'ebay-seller.update']);
        Route::get('/create', ['uses' => 'EbaySellerController@create', 'as' => 'ebay-seller.create']);
        Route::post('/create/save', ['uses' => 'EbaySellerController@postSave', 'as' => 'ebay-seller.save']);
//    Route::get('cron-job/assigned',['uses'=>'CategoryController@eBayCategoryIdAssignedCronJob','as'=>'cron-job.assigned']);
    });

// Average Price

    Route::group(['prefix' => 'average-price', 'middleware' => ['admin', 'not_staff']], function () {
        Route::get('/ebay', ['uses' => 'AveragePriceController@getEbayIndex', 'as' => 'average_price.ebay']);
        Route::get('/back-market', ['uses' => 'AveragePriceController@getBackMarketIndex', 'as' => 'average_price.back_market']);
        Route::get('remove-data', ['uses' => 'AveragePriceController@removeAllDataFromTable', 'as' => 'ebay.remove-all']);
        Route::get('/ebay/{id}', ['uses' => 'AveragePriceController@getSoldItem', 'as' => 'average_price.ebay.single']);
        Route::get('/back-market', ['uses' => 'AveragePriceBackMarketController@index', 'as' => 'average_price.back-market.single']);
        Route::get('/remove-tablet', ['uses' => 'AveragePriceController@removeTabletAndComputer', 'as' => 'average_price.remove-tablet']);
        Route::get('/remove-back-market', ['uses' => 'AveragePriceBackMarketController@removeAllDataFromTable', 'as' => 'average_price.back-market.remove-tablet']);
        Route::post('/advanced-search', ['uses' => 'AveragePriceController@advancedSearch', 'as' => 'advance.search']);
        Route::get('/master', ['uses' => 'MasterAverageController@index', 'as' => 'average_price.master']);
        Route::post('/master/edit-diff-percentage', ['uses' => 'MasterAverageController@editDiffPercentage', 'as' => 'average_price.master.edit']);
        Route::post('/search-product-info', ['uses' => 'AveragePriceController@searchProductInfo', 'as' => 'average_price.search-info']);
        Route::get('/master/remove-data', ['uses' => 'MasterAverageController@removeMasterData', 'as' => 'average_price.master.remove']);
        Route::get('/master/back-market/raw-data', ['uses' => 'AveragePriceBackMarketController@getRawData', 'as' => 'average_price.back_market.raw-data']);
    });


    //Seller Fees

    Route::group(['prefix' => 'seller_fees', 'middleware' => ['admin', 'not_staff']], function () {
        Route::get('/', ['uses' => 'SellerFeesController@getIndex', 'as' => 'seller_fees.index']);
        Route::get('/create', ['uses' => 'SellerFeesController@getCreate', 'as' => 'seller_fees.create']);
        Route::post('/save', ['uses' => 'SellerFeesController@postSave', 'as' => 'seller_fees.save']);
        Route::get('/{id}', ['uses' => 'SellerFeesController@getSellerFees', 'as' => 'seller_fees.single']);

    });

    Route::get('/customer-return', ['uses' => 'CustomerReturnController@index', 'as' => 'customer.return.index']);
    Route::get('/customer-return/create', ['uses' => 'CustomerReturnController@create', 'as' => 'customer.return.create']);
    Route::get('/customer-return/items/{id}', ['uses' => 'CustomerReturnController@getCustomerReturnItem', 'as' => 'customer.return.single']);
    Route::get('/customer-return/change-status/{id}', ['uses' => 'CustomerReturnController@changeStockStatus', 'as' => 'customer.return.change-status']);
    Route::get('/customer-return/view/{id}', ['uses' => 'CustomerReturnController@customerReturnSingle', 'as' => 'customer.return.view']);
    Route::get('/customer-return/data/sold', ['uses' => 'CustomerReturnController@getSoldDate', 'as' => 'customer.return.data']);
    Route::post('/customer-return/update/{id}', ['uses' => 'CustomerReturnController@customerReturnUpdate', 'as' => 'customer.return.update']);
    Route::post('/create/customer-return/save', ['uses' => 'CustomerReturnController@customerReturnCreate', 'as' => 'customer.return.save']);
    Route::get('/export/customer-return', ['uses' => 'CustomerReturnController@exportCsv', 'as' => 'customer.export']);
});

Route::get('/', ['uses' => 'HomeController@getRedirect', 'as' => 'home.redirect']);

Route::get('/tv', ['uses' => 'HomeController@getTvStats', 'as' => 'home.tv-stats']);
Route::get('/tv2', ['uses' => 'HomeController@getTv2Stats', 'as' => 'home.tv2-stats']);
Route::get('/tv3', ['uses' => 'HomeController@getTv3Stats', 'as' => 'home.tv3-stats']);
Route::get('/tv4', ['uses' => 'HomeController@getTv4Stats', 'as' => 'home.tv4-stats']);
Route::get('/tv5', ['uses' => 'HomeController@getTv5Stats', 'as' => 'home.tv5-stats']);
Route::get('/unsubscribe/{id?}', ['uses' => 'EmailSenderController@unSubscribe', 'as' => 'emails.unsubscribe']);

// PhoneCheck
Route::group(['prefix' => 'phonecheck'], function () {
    Route::any('/api/imei', ['uses' => 'PhoneCheckController@postApiImei', 'as' => 'phonecheck.api-imei']);
});


Route::group([], function () {
    Route::get('/terms', ['as' => 'terms', function () {
        return view('static.terms-and-conditions');
    }]);
    Route::get('/privacy-policy', ['as' => 'privacy-policy', function () {
        return view('static.privacy-policy');
    }]);
});

Route::post('/quickbooks/webhook', ['uses' => 'QuickbooksController@postWebhook', 'as' => 'quickbooks.webhook']);

Route::post('/email-webhooks/webhook', ['uses' => 'EmailWebhooksController@postWebhook', 'as' => 'email-webhooks.webhook']);

// Sage (no login)
Route::group(['prefix' => 'sage'], function () {
    Route::any('/notify', ['uses' => 'SageController@anyNotify', 'as' => 'sage.notify']);
});

// Account routes (no login)
Route::group(['prefix' => 'my-account'], function () {
    Route::get(
        '/disable-notifications',
        ['uses' => 'AccountController@getDisableNotifications', 'as' => 'account.disable-notifications']
    );
    Route::post(
        '/disable-notifications',
        ['uses' => 'AccountController@postDisableNotifications', 'as' => 'account.disable-notifications.save']
    );
    Route::get('/registered-disable-notifications',
        ['uses' => 'AccountController@getRegisteredDisableNotifications', 'as' => 'account.registered-disable-notifications']);
    Route::post('/registered-disable-notifications',
        ['uses' => 'AccountController@postRegisteredDisableNotifications', 'as' => 'account.registered-disable-notifications.save']);
    Route::any('/stripe-webhook', ['uses' => 'AccountController@anyStripeWebhook', 'as' => 'account.stripe-webhook']);
});

// API
Route::group(['prefix' => 'api', 'middleware' => 'api', 'namespace' => 'Api'], function () {
    Route::group(['prefix' => 'stock'], function () {
        Route::get('/', ['uses' => 'StockController@getIndex', 'as' => 'api.stock']);
    });
    Route::group(['prefix' => 'sales'], function () {
        Route::get('/', ['uses' => 'SalesController@getIndex', 'as' => 'api.sales']);
        Route::post('/save', ['uses' => 'SalesController@postSave', 'as' => 'api.sales.save']);
    });
    Route::group(['prefix' => 'unlocks'], function () {
        Route::get('/', ['uses' => 'UnlocksController@getIndex', 'as' => 'api.unlocks']);
        Route::post('/own-stock-new-order', ['uses' => 'UnlocksController@postOwnStockNewOrder', 'as' => 'api.unlocks.own-stock.new-order-save']);
    });
    Route::group(['prefix' => 'my-account'], function () {
        Route::get('/get-balance', ['uses' => 'AccountController@getBalance', 'as' => 'api.account.get-balance']);
    });
});

// Auth
Route::group(['prefix' => 'auth'], function () {
    Route::get('/login', [\App\Http\Controllers\Auth\AuthController::class,'getLogin', 'as' => 'auth.login'])->name('auth.login');
    Route::post('/login', [App\Http\Controllers\Auth\AuthController::class, 'as' => 'auth.login'])->name('auth.login');
    Route::get('/logout', [App\Http\Controllers\Auth\AuthController::class,'getLogout', 'as' => 'auth.logout'])->name('auth.logout');

    Route::group([], function () {
        Route::get('/register', ['uses' => 'Auth\AuthController@getRegister', 'as' => 'auth.register']);
        Route::post('/register', ['uses' => 'Auth\AuthController@postRegister', 'as' => 'auth.register.save']);
    });
    Route::get('/email-confirm/{userId}/{code}', ['uses' => 'Auth\AuthController@getEmailConfirm', 'as' => 'auth.email-confirm']);
    Route::post('/previous', ['uses' => 'Auth\AuthController@postPrevious', 'as' => 'auth.previous']);
    Route::get('/postcode', ['uses' => 'Auth\AuthController@getPostcode', 'as' => 'auth.postcode']);
});

// Password reset
Route::group(['prefix' => 'password'], function () {
    Route::get('email', 'Auth\PasswordController@getEmail');
    Route::post('email', 'Auth\PasswordController@postEmail');
    Route::get('reset/{token}', 'Auth\PasswordController@getReset');
    Route::post('reset', 'Auth\PasswordController@postReset');
});


Route::group(['prefix' => 'phone-check-report'], function () {
    Route::get('/eraser_report/{id}', [PhoneCheckReportController::class,'eraserReports'])->name('phone-check.eraser.report');
    Route::get('/{id}', [PhoneCheckReportController::class,'reports'])->name('phone-check.report');


});


Route::group(['prefix' => 'processing-image'], function () {

    Route::get('access/{id}', ['uses' => 'StockController@processingImageAccess', 'as' => 'access-processing-image']);


});


Route::get('phone-check', ['uses' => 'PhoneCheckController@getData']);

Route::get('/all-customer-return', ['uses' => 'CustomerReturnController@getAllCustomerReturn']);







////inventory
//Route::group(['prefix' => 'inventory'], function() {
//    Route::get('/', ['uses'=>'InventoryController@index','as'=>'inventory.index']);
//    Route::get('/create', ['uses'=>'InventoryController@create','as'=>'inventory.create']);
//    Route::get('/delete/{id}',['uses'=>'InventoryController@delete','as'=>'inventory.delete']);
//    Route::get('/{id}', ['uses'=>'InventoryController@single','as'=>'inventory.single']);
//    Route::post('/create/save',['uses'=>'InventoryController@postSave','as'=>'inventory.save']);
//
//
//
//});

