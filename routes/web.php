<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

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

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');


Route::group(['prefix' => 'admin',
    //'middleware' => ['admin']
], function() {
    Route::group(['prefix' => 'users'], function() {
        Route::get('/', [UserController::class,'getIndex'])->name('admin.users');

//        Route::get('/unregistered', ['uses' => 'UserController@getUnregistered'])->name('admin.users.unregistered');
//        Route::delete('/unregistered/delete', ['uses' => 'UserController@deleteUnregistered'])->name('admin.users.unregistered-delete');
//        Route::get('/new', ['uses' => 'UserController@getNewUserForm', 'as' => 'admin.users.new-user']);
//        Route::post('/new-create', ['uses' => 'UserController@postCreateNewUser', 'as' => 'admin.users.new-user-create']);
        Route::post('/add/product',[UserController::class,'addQuickBooksProductService'])->name('admin.user.quick_books.product.add');
        Route::post('/save', [UserController::class,'postSave'])->name('admin.users.save');
        Route::post('/update-address', [UserController::class,'postUpdateAddress'])->name('admin.users.update-address');
        Route::post('/update-billing-address', [UserController::class,'postUpdateBillingAddress'])->name('admin.users.update-billing-address');
        Route::post('/login', [UserController::class,'postLogin'])->name('admin.users.login');
//        Route::get('/autocomplete', ['uses' => 'UserController@getAutocomplete', 'as' => 'admin.users.autocomplete']);
//        Route::get('/bulk-add-form', ['uses' => 'UserController@getBulkAdd', 'as' => 'admin.users.bulk-add-form']);
//        Route::post('/bulk-add', ['uses' => 'UserController@postBulkAdd', 'as' => 'admin.users.bulk-add']);
//        Route::get('/export', ['uses' => 'UserController@getExport', 'as' => 'admin.users.export']);
//        Route::post('/register', ['uses' => 'UserController@postRegisterUnregisteredForm', 'as' => 'admin.users.register']);
//        Route::post('/register/save', ['uses' => 'UserController@postRegisterUnregistered', 'as' => 'admin.users.register-save']);
        Route::post('/suspend-user', ['uses' => 'UserController@postSuspendUser', 'as' => 'admin.users.suspend-user']);
//        Route::post('/update-notes', ['uses' => 'UserController@postUpdateNotes', 'as' => 'admin.users.update-notes']);
        Route::post('/marketing-emails', ['uses' => 'UserController@postMarketingEmails', 'as' => 'admin.users.marketing-emails']);
        Route::post('/create-quickbooks-customer', [UserController::class ,'postCreateQuickbooksCustomer'])->name('admin.users.create-quickbooks-customer');
//        Route::get('/whats-app-users', ['uses' => 'UserController@getWhatsAppUsers', 'as' => 'admin.users.whats-app-users']);
//        Route::post('/whats-app-users-added', ['uses' => 'UserController@postWhatsAppUsersAdded', 'as' => 'admin.users.whats-app-users-added']);
//        Route::get('/customers-with-balance', ['uses' => 'UserController@getCustomersWithBalance', 'as' => 'admin.users.customers-with-balance']);
//        Route::post('/update-balance-due-date', ['uses' => 'UserController@postUpdateBalanceDueDate', 'as' => 'admin.users.update-balance-due-date']);
//        Route::post('/customers-with-balance-reminders', ['uses' => 'UserController@postCustomersWithBalanceReminders', 'as' => 'admin.users.customers-with-balance-reminders']);
//        Route::post('/customers-with-balance-hide', ['uses' => 'UserController@postCustomersWithBalanceHide', 'as' => 'admin.users.customers-with-balance-hide']);
//        Route::get('/recommercetech-users', ['uses' => 'UserController@getRecommercetechUsers', 'as' => 'admin.users.recommercetech-users']);
//        Route::post('/update-admin-type', ['uses' => 'UserController@postUpdateAdminType', 'as' => 'admin.users.update-admin-type']);
//        Route::post('/create-admin', ['uses' => 'UserController@postCreateAdmin', 'as' => 'admin.users.create-admin']);
//        Route::post('/delete-admin', ['uses' => 'UserController@postDeleteAdmin', 'as' => 'admin.users.delete-admin']);
//        Route::post('/update-station-id', ['uses' => 'UserController@postUpdateStationId', 'as' => 'admin.users.update-station-id']);
       // Route::get('/{id}', ['uses' => 'UserController@getSingle', 'as' => 'admin.users.single']);
        Route::get('/{id}', [UserController::class,'getSingle'])->name('admin.users.single');
//        Route::get('/lcd-user/{id}', ['uses' => 'UserController@getLCDUserSingle', 'as' => 'admin.lcd-users.single']);
        Route::get('/{id}/emails', [UserController::class,'getUserEmails' ])->name('admin.users.single.emails');
//        Route::post('/emails/preview', ['uses' => 'UserController@postUserEmailPreview', 'as' => 'admin.users.emails.preview']);
//        Route::post('/emails/send', ['uses' => 'UserController@postUserEmailSend', 'as' => 'admin.users.emails.send']);
        Route::post('/api-generate-key', [UserController::class,'postApiGenerateKey'])->name('admin.users.api.generate-key');
        Route::post('/send-email', [UserController::class  ,'sendEmail'])->name('admin.users.send-email');
//        Route::post('/delete', ['uses' => 'UserController@removeDeleted', 'as' => 'admin.users.remove-user']);
        Route::post('/delete', [UserController::class ,'removeDeleted'])->name('admin.users.remove-user');
        Route::post('/save/sub-admin', [UserController::class,'addSubAdmin'])->name('sub-admin.add');
//        Route::get('/delete/sub-admin/{id}',['uses' => 'UserController@removeSubAdmin', 'as' => 'sub-admin.remove']);

});

});

Route::group(['prefix' => 'admin',
   // 'middleware' => ['admin']

], function() {
    Route::group(['prefix' => 'settings',
        //'middleware' => ['not_staff']

    ], function() {

        //Route::get('/', ['uses' => 'SettingsController@getIndex', 'as' => 'admin.settings']);
        Route::get('/', [\App\Http\Controllers\SettingsController::class,'getIndex'])->name('admin.settings');
        Route::post('/', ['uses' => 'SettingsController@postIndex', 'as' => 'admin.settings.submit']);
        Route::post('/cron', ['uses' => 'SettingsController@postRunCron', 'as' => 'admin.settings.run-cron']);
        Route::post('/free-delivery', ['uses' => 'SettingsController@postFreeDelivery', 'as' => 'admin.settings.free-delivery']);
        // Route::get('/quickbooks', ['uses' => 'QuickbooksController@getIndex', 'as' => 'admin.quickbooks']);
        Route::get('/quickbooks', [\App\Http\Controllers\QuickbooksController::class, 'getIndex'])->name('admin.quickbooks');
        Route::any(
            '/quickbooks/oauth-start',
            ['uses' => 'QuickbooksController@getOAuthStart', 'as' => 'admin.quickbooks.oauth.start']
        );
        Route::get(
            '/quickbooks/oauth-callback',
            [\App\Http\Controllers\QuickbooksController::class,'getOAuthCallback']
        )->name('admin.quickbooks.oauth.callback');
        Route::get(
            '/quickbooks/oauth-success',
            [\App\Http\Controllers\QuickbooksController::class,'getOAuthSuccess']
        )->name('admin.quickbooks.oauth.success');
        Route::any(
            '/quickbooks/oauth-refresh-token',
            [\App\Http\Controllers\QuickbooksController::class,'getOAuth2RefreshToken']
        )->name('admin.quickbooks.oauth.refresh-token');
        Route::any('/quickbooks/oauth-company-info', [\App\Http\Controllers\QuickbooksController::class,'getOauth2CompanyInfo'])->name('admin.quickbooks.oauth.company-info');



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
        Route::get('/update-stock',['uses'=>'SettingsController@updateStock','as'=>'admin.settings.update-stock']);
        Route::get('/ebay',['uses'=>'SettingsController@getEbaySetting','as'=>'admin.settings.ebay']);
        Route::get('/email-format',['uses'=>'SettingsController@getUploadDocumentEmailFormat','as'=>'admin.email-format']);
        Route::post('/email-format',['uses'=>'SettingsController@saveUploadDocumentEmailFormat','as'=>'admin.save.email-format']);
        Route::get('/export-buyback-product',['uses'=>'SettingsController@ExportBuyBackProduct','as'=>'admin.export.buyback-product']);
        Route::get('/dpd-shipping',['uses'=>'SettingsController@DpdShipping','as'=>'admin.dpd-shipping']);
        Route::get('/dpd-shipping/refresh-token',['uses'=>'SettingsController@DpdRefreshToken','as'=>'admin.dpd-shipping.refresh-token']);
        Route::post('/dpd-shipping/status',['uses'=>'SettingsController@dpdShippingStatus','as'=>'admin.dpd-shipping.status']);




    });


});



//Route::get('/quickbooks/oauth-callback', [ \App\Http\Controllers\QuickbooksController::class,'getOAuthCallback'])->name('admin.quickbooks.oauth.callback');

