<?php

namespace App\Helpers;

class TargetDataHelper
{
    public static function transformSummary(object $row): array
    {
        return [
            'part_id' => $row->part_id !== null ? (string) $row->part_id : null,
            'part_name' => $row->part_name,
            'year' => (int) $row->tahun,
            'total_month' => (int) $row->total_month,
            'average_mtbf' => $row->average_mtbf !== null ? round((float) $row->average_mtbf, 2) : null,
            'average_mttr' => $row->average_mttr !== null ? round((float) $row->average_mttr, 2) : null,
        ];
    }

    public static function transformDetail(object $summary, array $targets): array
    {
        return [
            ...self::transformSummary($summary),
            'targets' => $targets,
        ];
    }

    public static function transformMonth(object $row): array
    {
        return [
            'id' => (string) $row->id,
            'area_id' => $row->area_id !== null ? (string) $row->area_id : null,
            'part_id' => $row->part_id !== null ? (string) $row->part_id : null,
            'year' => (int) $row->tahun,
            'month' => (int) $row->bulan,
            'mtbf' => $row->mtbf !== null ? (float) $row->mtbf : null,
            'mttr' => $row->mttr !== null ? (float) $row->mttr : null,
        ];
    }
}
