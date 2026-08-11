<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mobile app labels
    |--------------------------------------------------------------------------
    |
    | Maps a mobile app package name to a human-readable label used in
    | Telegram notifications (feedback, donations, ...). Apps that own a
    | backend module contribute their own entry from their module service
    | provider (see Modules/*\/Providers) instead of being listed here, so
    | deleting a module also removes its label automatically. Apps with no
    | dedicated backend module yet are listed here directly.
    |
    */
    'apps' => [
        'com.example.trainingdiary' => [
            'emoji' => '🏋️',
            'name' => 'Training Diary',
        ],
    ],
];
