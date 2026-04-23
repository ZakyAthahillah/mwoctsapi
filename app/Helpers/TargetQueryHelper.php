<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class TargetQueryHelper
{
    public static function summaryQuery(?int $areaId)
    {
        return DB::table('target_models')
            ->leftJoin('parts', 'parts.id', '=', 'target_models.part_id')
            ->when($areaId !== null, fn ($query) => $query->where('target_models.area_id', $areaId), fn ($query) => $query->whereNull('target_models.area_id'))
            ->groupBy('target_models.part_id', 'target_models.tahun', 'parts.name')
            ->select([
                'target_models.part_id',
                'target_models.tahun',
                'parts.name as part_name',
            ])
            ->selectRaw('COUNT(target_models.id) as total_month')
            ->selectRaw('AVG(target_models.mtbf) as average_mtbf')
            ->selectRaw('AVG(target_models.mttr) as average_mttr');
    }

    public static function summary(?int $areaId, int $partId, int $year): ?object
    {
        return self::summaryQuery($areaId)
            ->where('target_models.part_id', $partId)
            ->where('target_models.tahun', $year)
            ->first();
    }

    public static function months(?int $areaId, int $partId, int $year): array
    {
        return DB::table('target_models')
            ->when($areaId !== null, fn ($query) => $query->where('area_id', $areaId), fn ($query) => $query->whereNull('area_id'))
            ->where('part_id', $partId)
            ->where('tahun', $year)
            ->orderBy('bulan')
            ->get()
            ->map(fn ($row) => TargetDataHelper::transformMonth($row))
            ->all();
    }

    public static function replaceMonths(?int $areaId, int $partId, int $year, array $targets): void
    {
        DB::table('target_models')
            ->when($areaId !== null, fn ($query) => $query->where('area_id', $areaId), fn ($query) => $query->whereNull('area_id'))
            ->where('part_id', $partId)
            ->where('tahun', $year)
            ->delete();

        DB::table('target_models')->insert(collect($targets)->map(fn ($target) => [
            'area_id' => $areaId,
            'part_id' => $partId,
            'tahun' => $year,
            'bulan' => (int) $target['month'],
            'mtbf' => $target['mtbf'] ?? null,
            'mttr' => $target['mttr'] ?? null,
        ])->all());
    }
}
