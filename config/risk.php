<?php

return [
    'weights' => [
        'delay' => 0.25,
        'blocked' => 0.20,
        'progress_gap' => 0.25,
        'deadline_pressure' => 0.20,
        'workload' => 0.10,
    ],

    'priority_modifier' => [
        'critical' => 8,
        'high' => 4,
        'medium' => 0,
        'low' => -4,
    ],

    'delay_days_saturation' => 14,

    'progress_gap_multiplier' => 1.25,

    'deadline_pressure_factor' => 5,

    'employee_max_recommended_tasks' => 5,

    'levels' => [
        'low' => 0,
        'medium' => 31,
        'high' => 61,
        'critical' => 81,
    ],
];
