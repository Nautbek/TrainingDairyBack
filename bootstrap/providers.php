<?php

return [
    App\Providers\AppServiceProvider::class,

    // Per-app modules — delete a module's folder and remove its line here to
    // fully remove that app's backend functionality.
    Modules\MyCar\Providers\MyCarServiceProvider::class,
    Modules\TripSplit\Providers\TripSplitServiceProvider::class,
    Modules\Nutrition\Providers\NutritionServiceProvider::class,
];
