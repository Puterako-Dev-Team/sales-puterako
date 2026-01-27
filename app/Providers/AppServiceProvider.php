<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            /** @var \App\Models\User|null $user */
            $user = Auth::user();
            $notifications = $user ? $user->notifications()->limit(5)->get() : collect();
            $unreadCount = $user ? $user->unreadNotifications()->count() : 0;
            $view->with(compact('notifications', 'unreadCount'));
        });
    }
}
