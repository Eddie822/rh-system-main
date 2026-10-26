<?php

return [
    'initial_access' => [
        'employee_number' => env('RH_INITIAL_EMPLOYEE_NUMBER', '0356'),
        // When omitted, the seeder generates a password and displays it once in the console.
        'password' => env('RH_INITIAL_PASSWORD'),
    ],
];
