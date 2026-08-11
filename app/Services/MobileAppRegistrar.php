<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;

/**
 * Lets a module contribute its own Telegram label to config('mobile_apps')
 * from its own service provider, without core needing to know about it.
 *
 * Package names contain dots (e.g. "ru.nautbek.custom"), so this deliberately
 * avoids Config::set('mobile_apps.apps.'.$package, ...) — Laravel would parse
 * the dots in $package as nested config segments instead of a literal key.
 */
class MobileAppRegistrar
{
    public static function register(string $package, string $emoji, string $name): void
    {
        $apps = config('mobile_apps.apps', []);
        $apps[$package] = ['emoji' => $emoji, 'name' => $name];

        Config::set('mobile_apps.apps', $apps);
    }
}
