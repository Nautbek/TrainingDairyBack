<?php

namespace Tests\Unit;

use App\Services\MobileAppPresenter;
use PHPUnit\Framework\TestCase;

class MobileAppPresenterTest extends TestCase
{
    public function test_formats_known_apps_with_emoji(): void
    {
        $presenter = new MobileAppPresenter;

        $this->assertSame('🏋️ Training Diary', $presenter->formatLabel('com.example.trainingdiary'));
        $this->assertSame('🍽️ Nutrition Journal', $presenter->formatLabel('ru.nautbekcustom.nutritionjournal'));
        $this->assertSame('🍽️ Nutrition Journal', $presenter->formatLabel('ru.nautbekcustom.nutritionjournal.debug'));
        $this->assertSame('✈️ TripSplit', $presenter->formatLabel('ru.nautbek.custom'));
        $this->assertSame('🚗 My Car', $presenter->formatLabel('ru.nautbek_custom.mycar'));
    }

    public function test_returns_raw_package_for_unknown_app(): void
    {
        $presenter = new MobileAppPresenter;

        $this->assertSame('unknown.app', $presenter->formatLabel('unknown.app'));
    }
}
