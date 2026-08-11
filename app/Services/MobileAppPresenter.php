<?php

namespace App\Services;

class MobileAppPresenter
{
    public function formatLabel(string $app): string
    {
        $entry = $this->apps()[$this->normalizePackage($app)] ?? null;

        if ($entry === null) {
            return $app;
        }

        return "{$entry['emoji']} {$entry['name']}";
    }

    /**
     * @return array<string, array{emoji: string, name: string}>
     */
    private function apps(): array
    {
        /** @var array<string, array{emoji: string, name: string}> $apps */
        $apps = config('mobile_apps.apps', []);

        return $apps;
    }

    private function normalizePackage(string $app): string
    {
        $app = trim($app);

        if (str_ends_with($app, '.debug')) {
            return substr($app, 0, -strlen('.debug'));
        }

        return $app;
    }
}
