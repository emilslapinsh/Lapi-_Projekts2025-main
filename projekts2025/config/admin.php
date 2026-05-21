<?php

return [
    'usernames' => collect(explode(',', (string) env('ADMIN_USERNAMES', 'admin')))
        ->map(fn ($u) => strtolower(trim($u)))
        ->filter()
        ->unique()
        ->values()
        ->all(),
];
