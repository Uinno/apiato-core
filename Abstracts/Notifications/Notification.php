<?php

declare(strict_types=1);

namespace Apiato\Core\Abstracts\Notifications;

use Illuminate\Notifications\Notification as LaravelNotification;
use Illuminate\Support\Facades\Config;

abstract class Notification extends LaravelNotification
{
    public function via($notifiable): array
    {
        return Config::get('notification.channels');
    }
}
