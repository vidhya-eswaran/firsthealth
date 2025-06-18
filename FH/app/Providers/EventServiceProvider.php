<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use App\Models\ActionLog;

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
            \App\Events\UserActionLogged::class => [
            \App\Listeners\LogUserAction::class,
        ],
            \App\Events\LocationUpdate::class => [
            \App\Listeners\LocationUpdateListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();

        Event::listen('router.matched', function ($route) {
            if (Auth::check()) {
                ActionLog::create([
                    'user_id' => Auth::id(),
                    'action' => Request::method() . ' ' . Request::path(),
                    'details' => json_encode(Request::except(['password', 'password_confirmation'])),
                    'ip_address' => Request::ip(),
                ]);
            }
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
