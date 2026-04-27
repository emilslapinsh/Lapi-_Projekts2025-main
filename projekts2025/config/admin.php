<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Administratoru lietotājvārdi
    |--------------------------------------------------------------------------
    |
    | Šeit norādi administratoru lietotājvārdus (atdalītus ar komatu), kuri drīkst
    | piekļūt /admin sadaļai.
    |
    | Piemērs .env:
    | ADMIN_USERNAMES=admin
    |
    */
    'usernames' => collect(explode(',', (string) env('ADMIN_USERNAMES', 'admin')))
        ->map(fn ($u) => strtolower(trim($u)))
        ->filter()
        ->unique()
        ->values()
        ->all(),
];
