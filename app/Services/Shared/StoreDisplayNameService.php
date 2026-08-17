<?php

namespace App\Services\Shared;

/**
 * 店舗の表示名解決を1箇所にまとめる。
 *
 * mx_stores.store_short_name（無ければstore_name）を表示名にする、という同じ
 * フォールバックが勤怠系・スタッフマスタ等で個別に複製されていたため、ここへ集約する
 * （2026-08-17）。admin_v2・staff_portal どちらからも参照してよい、純粋なマスタ表示名の解決のみで
 * 業務ロジックは持たない。
 */
class StoreDisplayNameService
{
    public function resolve(?string $storeName, ?string $storeShortName): string
    {
        $shortName = trim((string) $storeShortName);
        if ($shortName !== '') {
            return $shortName;
        }

        return trim((string) $storeName);
    }
}
