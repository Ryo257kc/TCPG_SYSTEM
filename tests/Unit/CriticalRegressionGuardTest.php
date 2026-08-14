<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * 2026年8月に見つかった「静かに壊れる」系の重大バグの再発防止チェック。
 * DBにもLaravelの起動にも依存せず、ソースコードの文字列だけを見る。
 * そのためCI(GitHub Actions)でも高速に実行できる。
 *
 * ここでの検出条件は、実際にバグを踏んだ時の原因そのもの
 * （dbo.プレフィックス無しのgetColumnListing()が空配列を返し、そこから連鎖して
 * 保存処理が静かに何もしなくなっていた）を直接見ている。
 */
class CriticalRegressionGuardTest extends TestCase
{
    private static function projectPath(string $relative): string
    {
        return dirname(__DIR__, 2) . '/' . $relative;
    }

    /**
     * PayrollV2UpdateService::updatableColumns()が空になると、給与明細の保存が
     * sanitizePayload()の時点で全カラム除外され、save()がDBを一切更新せずに
     * return 0するだけになる（2026年8月に実際に発生し、気づかれずに残っていた）。
     */
    public function test_payroll_update_service_uses_dbo_prefix_for_column_listing(): void
    {
        $path = self::projectPath('app/Services/Admin/V2/Payroll/PayrollV2UpdateService.php');
        $source = file_get_contents($path);

        $this->assertNotFalse($source, "{$path} が読み込めません。");
        $this->assertMatchesRegularExpression(
            "/getColumnListing\\('dbo\\.mx_kyuyo_shou'\\)/",
            $source,
            "PayrollV2UpdateService::updatableColumns()のgetColumnListing()呼び出しからdbo.プレフィックスが外れています。".
            "このsqlsrv_payroll接続はdbo.プレフィックス無しだと列一覧が0件になり、給与保存が静かに何もしなくなります。"
        );
    }

    /**
     * PayrollV2FuyoService::resolveByPaymentDate() / resolveByPaymentDateBulk()が
     * 空を返すと、給与新規作成時のfuyo_sum（扶養人数）が常に0で保存される。
     */
    public function test_payroll_fuyo_service_uses_dbo_prefix_for_column_listing(): void
    {
        $path = self::projectPath('app/Services/Admin/V2/Payroll/PayrollV2FuyoService.php');
        $source = file_get_contents($path);

        $this->assertNotFalse($source, "{$path} が読み込めません。");
        $this->assertSame(
            2,
            substr_count($source, "getColumnListing('dbo.mx_fuyo')"),
            'PayrollV2FuyoService内のgetColumnListing()呼び出し(2箇所)からdbo.プレフィックスが外れています。'
        );
    }

    /**
     * 年末調整の扶養控除一覧（fuyoRows）が同じ理由で空を返すと、
     * registration_dateでの絞り込み・並び替えが効かなくなる。
     */
    public function test_year_end_adjustment_fuyo_rows_uses_dbo_prefix_for_column_listing(): void
    {
        $path = self::projectPath('app/Http/Controllers/Admin/V2/YearEndAdjustmentV2Controller.php');
        $source = file_get_contents($path);

        $this->assertNotFalse($source, "{$path} が読み込めません。");
        $this->assertMatchesRegularExpression(
            "/getColumnListing\\('dbo\\.mx_fuyo'\\)/",
            $source,
            'YearEndAdjustmentV2Controller::fuyoRows()のgetColumnListing()呼び出しからdbo.プレフィックスが外れています。'
        );
    }

    /**
     * SQL Serverは1クエリあたり最大2100個までしかバインドパラメータを受け付けない。
     * 一括insert()系の処理でarray_chunk()が消えると、件数が多い時に
     * "Tried to bind parameter number 2101" で処理全体が失敗する
     * （実際に踏んだファイル: シフト一括作成／保険請求CSV取込／年調対象者一括作成）。
     */
    public function test_bulk_insert_sites_still_chunk_before_inserting(): void
    {
        $targets = [
            'app/Services/Admin/V2/Attendance/AttendanceV2ShiftCreateService.php',
            'app/Http/Controllers/StaffPortal/office/EntryController.php',
            'app/Http/Controllers/Admin/V2/YearEndAdjustmentV2Controller.php',
        ];

        foreach ($targets as $relativePath) {
            $path = self::projectPath($relativePath);
            $source = file_get_contents($path);

            $this->assertNotFalse($source, "{$path} が読み込めません。");
            $this->assertMatchesRegularExpression(
                '/array_chunk\(/',
                $source,
                "{$relativePath} からarray_chunk()によるバルクinsertの分割が無くなっています。".
                "行数が多い一括処理でSQL Serverのパラメータ上限(2100個)エラーが再発する可能性があります。"
            );
        }
    }
}
