<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //Need to check if it required or not becuse of without imei number not use full
        $schedule->command('queue-workers-check')->cron('* * * * *');
        $schedule->command('quickbooks:refresh-token')->cron('*/15 * * * *');
        $schedule->command('quickbooks:process-webhooks')->cron('* * * * *');


        // unlocks start
        $schedule->command('imeis:place-checkmynetwork-unlock-order')->cron('*/15 * * * *');
        $schedule->command('unlocks:reassign-unlocks')->cron('45 * * * * ');
        // unlocks end

        // phone checks start
        $schedule->command('phone-check:create-checks')->cron('*/20 * * * *');
        // phone checks end

        // gsx checks start
        $schedule->command('imeis:parse-gsx-check-reports-for-stock')->cron('*/20 * * * *');
        $schedule->command('imeis:parse-gsx-check-reports-for-unlocks')->cron('*/20 * * * *');

        $schedule->command('mobicode:create-stock-checks')->cron('*/20 * * * *');
        $schedule->command('mobicode:unknown-network-stock-reports')->cron('*/30 * * * *');
        // gsx checks end

        // sales start
        $schedule->command('sales:check-paid')->cron('*/15 * * * *');
        $schedule->command('sales:cleanup-invoices')->daily();
        $schedule->command('sales:awaiting-payment-email')->cron('30 8 * * 1-5');
        $schedule->command('sales:awaiting-payment-sms')->cron('0 11 * * 1-5');
        // sales end

        // stock start
        $schedule->command('stock:try-fix-missing-make')->cron('*/15 * * * *');
        $schedule->command('stock:faulty-name')->cron('0 * * * *');
        $schedule->command('stock:tablets-name')->cron('0 * * * *');
        $schedule->command('stock:assign-suppliers')->cron('*/30 * * * *');
        $schedule->command('stock:check-sold-items-shown-to')->cron('*/5 * * * *');
        $schedule->command('stock:change-network-unlock-requested')->cron('0 * * * *');
        $schedule->command('stock:ready-for-sale-unlocks')->cron('30 * * * *');
        $schedule->command('stock:map-products')->cron('*/20 * * * *');
        $schedule->command('stock:inventory')->cron('*/10 * * * *');
        // stock end

        // users start
        $schedule->command('users:try-fix-missing-customer')->cron('*/15 * * * *');
        $schedule->command('users:check-customer-address')->cron('0 6 * * *');
        // users end

        // saved baskets start
        $schedule->command('saved-baskets:remove-old')->cron('0 * * * *');
        //saved baskets end

        // ebay start
        $schedule->command('ebay-orders:assign-ebay-sku')->cron('*/15 * * * *');
        $schedule->command('ebay:orderhub-update-stock-status')->cron('*/15 * * * *');
        $schedule->command('ebay:orderhub-update-ebay-user-name')->cron('*/60 * * * *');
        $schedule->command('ebay:update-ebay-fee-matched-username')->cron('*/15 * * * *');
        $schedule->command('ebay:update-ebay-fee-matched-auction')->cron('*/15 * * * *');
        $schedule->command('ebay:update-sale-type-auction-order')->cron('*/15 * * * *');
        $schedule->command('ebay:update-ebay-fee-matched-oldest-item-number')->cron('*/15 * * * *');
        $schedule->command('ebay:assign-owner-manually-ebay-fees')->cron('*/15 * * * *');
        $schedule->command('ebay:ebay-fee-matched-to-order-item')->cron('*/15 * * * *');
        $schedule->command('ebay:paypal-fee-calculation')->cron('*/15 * * * *');
        $schedule->command('ebay:assign-sales-record-number')->cron('*/15 * * * *');
        $schedule->command('ebay:assign-owner-to-dpd')->cron('*/15 * * * *');
        $schedule->command('ebay:update-packaging-material-charge')->cron('*/15 * * * *');
        $schedule->command('ebay:update-hermes-charge')->cron('*/15 * * * *');
        $schedule->command('ebay:update-royal-mail-charge')->cron('*/15 * * * *');
        $schedule->command('ebay:refund-ebay-orders')->cron('*/15 * * * *');
        $schedule->command('ebay:refund-update-stock-status')->cron('*/15 * * * *');
        $schedule->command('ebay:send-daily-report-email')->dailyAt('7:00');
        $schedule->command('ebay:update-stock-serial-status')->cron('*/15 * * * *');
        $schedule->command('ebay:api-sync-all-orders')->cron('*/15 * * * *');
        $schedule->command('ebay:get-product-detatils')->daily();
        $schedule->command('ebay:dynamic-price')->dailyAt('2:00');
        $schedule->command('ebay:get-category-id')->dailyAt('12:00');
        $schedule->command('ebay:tablet-dynamic-price')->dailyAt('1:00');
        // ebay end

        // ebay refunds start
        $schedule->command('ebay-refunds:process-refunds')->cron('30 * * * *');
        // ebay refunds end

        // orderhub start
        //$schedule->command('orderhub:update-retail-stock-quantities')->cron('0 10-16 * * 1-5');
        $schedule->command('orderhub:generate-new-sku')->cron('*/30 * * * *');
        $schedule->command('update_profit_true_profit')->daily();
        //  $schedule->command('call-phone-check-imei')->cron('*/30 * * * *');

        //Back Market
        $schedule->command('back-market:sync-all-orders')->cron('*/15 * * * *');

        // $schedule->command('back-market:dynamic-price')->dailyAt('2:00');
        $schedule->command('add:second-dynamic-price')->dailyAt('2:00');
        $schedule->command('back-market:add-max-price')->cron('*/60 * * * *');
        $schedule->command('check:buy-box-price')->cron('*/15 * * * *');

//        $schedule->command('back-market:product-detatils')->dailyAt('12:00');


        $schedule->command('ebay:refresh-token')->cron('*/30 * * * * ');
        // $schedule->command('master:average-price')->dailyAt('3:00');
        $schedule->command('master:second-average-price')->dailyAt('3:00');

        $schedule->command('back-market:raw-data')->cron('*/60 * * * *');
        $schedule->command('product:assigned-null')->cron('*/60 * * * *');
        $schedule->command('dpd:create-shipping')->cron('*/45 * * * *');
        $schedule->command('back-market:status-change')->cron('*/5 * * * *');
        $schedule->command('delete:status-done')->daily();
        $schedule->command('assign:validate-unvalidate')->cron('*/5 * * * *');
        // $schedule->command('phone-check:report')->daily();


        //Mobile Advantage

        $schedule->command('mobile-advantage:sync-order')->cron('*/15 * * * *');
        $schedule->command('imei:check')->daily();


    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
