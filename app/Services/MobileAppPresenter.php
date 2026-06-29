<?php

namespace App\Services;

class MobileAppPresenter
{
    /**
     * @var array<string, array{emoji: string, name: string}>
     */
    private const APPS = [
        'com.example.trainingdiary' => [
            'emoji' => '🏋️',
            'name' => 'Training Diary',
        ],
        'ru.nautbekcustom.nutritionjournal' => [
            'emoji' => '🍽️',
            'name' => 'Nutrition Journal',
        ],
        'ru.nautbek.custom' => [
            'emoji' => '✈️',
            'name' => 'TripSplit',
        ],
        'ru.nautbek_custom.mycar' => [
            'emoji' => '🚗',
            'name' => 'My Car',
        ],
    ];

    public function formatLabel(string $app): string
    {
        $entry = self::APPS[$this->normalizePackage($app)] ?? null;

        if ($entry === null) {
            return $app;
        }

        return "{$entry['emoji']} {$entry['name']}";
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
