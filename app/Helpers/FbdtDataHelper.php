<?php

namespace App\Helpers;

use App\Models\Fbdt;
use Illuminate\Support\Collection;

class FbdtDataHelper
{
    public static function transformYearSummary(object $row): array
    {
        return [
            'year' => (int) $row->tahun,
            'area_id' => $row->area_id !== null ? (string) $row->area_id : null,
            'area_name' => $row->area_name ?? null,
            'months_count' => (int) $row->months_count,
        ];
    }

    public static function transformYearDetail(Collection $rows): array
    {
        /** @var Fbdt $firstRow */
        $firstRow = $rows->first();

        return [
            'year' => (int) $firstRow->tahun,
            'area_id' => $firstRow->area_id !== null ? (string) $firstRow->area_id : null,
            'area_name' => $firstRow->area?->name,
            'months' => $rows->sortBy('bulan')->values()->map(fn (Fbdt $row) => [
                'month' => (int) $row->bulan,
                'fb' => $row->fb !== null ? (float) $row->fb : null,
                'dt' => $row->dt !== null ? (float) $row->dt : null,
                'mtbf' => $row->mtbf !== null ? (float) $row->mtbf : null,
                'mttr' => $row->mttr !== null ? (float) $row->mttr : null,
            ])->all(),
        ];
    }
}
