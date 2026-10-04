<?php

return [
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:contact@krushibaandhava.in'),
        'public_key' => env('VAPID_PUBLIC_KEY', 'BHQ7JNFx2IRv46UUSOBWIpDOv4t8fYtYXBz6kofw8okad1NH18T8TSKf5DoPJrEcNqmdvDrsEG7Zzo-fgr-XfGw'),
        'private_key' => env('VAPID_PRIVATE_KEY', 'gdvsE-WHW7_hR0gQdRNbWbFnChNxhGgcJmZijahw3MM'),
    ],
    'defaults' => [
        'ttl' => 86400, // 24 hours
        'urgency' => 'normal',
    ],
];
