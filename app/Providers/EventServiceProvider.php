<?php

namespace App\Providers;

use App\Listeners\UserSyncToQuickBooks;
use App\Observers\MailObserver;
use App\Observers\SaleObserver;
use App\Observers\StockObserver;
use App\Observers\UserObserver;
use App\Models\Stock;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        Registered::class=>[
            UserSyncToQuickBooks::class

        ]

    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {

        $mailObserver = new MailObserver();
        Event::subscribe($mailObserver);

        $userObserver = new UserObserver;
        Event::subscribe($userObserver);

        $saleSubscriber = new SaleObserver;
        Event::subscribe($saleSubscriber);

        $stockSubscriber = new StockObserver();
        Stock::observe($stockSubscriber);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
