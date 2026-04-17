<?php

namespace App\Helpers;

use Carbon\Carbon;

class DashboardDataHelper
{
    public static function transform(array $reporting, Carbon $periodStart, Carbon $periodEnd): array
    {
        return [
            'reporting' => $reporting,
            'period' => [
                'start' => $periodStart->format('d-m-Y'),
                'end' => $periodEnd->format('d-m-Y'),
            ],
        ];
    }
}
