<?php

/*
目的:
- 往診のレガシー本番DB「HINATAoushin」から、TCPGSYSTEM_DEV の
  hv_nippou / hv_ryoukin / hv_kanjya_info へ同期する。

方針:
- 3テーブルとも MERGE（既存行はUPDATE、無い行はINSERT）。差分追加だけだと、
  レガシー側で既存行が更新されても反映されないため（2026-10-13、ユーザー指摘）。
- hv_nippou / hv_ryoukin は 2026-06-01 以降に絞る。
- hv_kanjya_info は患者マスタなので全件対象。患者Noを限定したINSERTだと、既存の患者
  （3680〜3683は4件ともDEV側に存在済みだった）で主キー重複になるうえ、レガシー側の
  更新も取り込めない。
- 列対応はHINATAoushinとTCPGSYSTEM_DEVのINFORMATION_SCHEMAを並び順で突き合わせて
  生成（nippou/hv_nippou 50列、ryoukin/hv_ryoukin 17列、kanjya_info/hv_kanjya_info 26列）。
  新システム側だけの追加列（daily_visit_area / massage_visit_area / payment_visit_area）は
  末尾にあり、同期対象外のまま触らない。

注意:
- 読み取り元: HINATAoushin（レガシー本番）
- 書き込み先: TCPGSYSTEM_DEV（※名前はDEVだが本番運用中）
- 主キーは3テーブルともIDENTITYのため IDENTITY_INSERT が必要。
  ON/MERGE/OFF は必ず同一のstatement()呼び出し内でバッチ実行すること
  （別々のstatement()に分けるとIDENTITY_INSERT状態が引き継がれずエラーになる。
  ODBC Driver 18のセッション/コネクションプーリング起因、2026-09-14に確認済み）。
- 件数を表示してからCOMMITする。失敗時は自動でROLLBACK。

使い方:
  php database/sql/2026_10_sync_hv_from_hinataoushin_merge.php

実行結果(2026-10-13): ユーザーがSSMSで実行。対象期間は$fromの既定2026-06-01ではなく
2026-09-01以降（hv_kanjya_infoは全件）。実行後に新旧を照合し、3テーブルとも
件数一致・取りこぼし0・全列の値の相違0を確認済み
（hv_nippou 2,499件 / hv_ryoukin 295件 / hv_kanjya_info 693件）。
照合は主キーで突き合わせ、EXCEPTで全列を比較（NULL同士も同値として扱う方式）。

次回使うときは$fromを対象期間に書き換えてから実行する。
*/

require 'C:/dev/tcpg_system_laravel/vendor/autoload.php';
$app = require 'C:/dev/tcpg_system_laravel/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$from = '2026-06-01';
$conn = DB::connection('sqlsrv');

$counts = static function () use ($conn, $from): array {
    return [
        'hv_nippou' => (int) $conn->selectOne("SELECT COUNT(*) AS c FROM TCPGSYSTEM_DEV.dbo.hv_nippou WHERE treatment_date >= ?", [$from])->c,
        'hv_ryoukin' => (int) $conn->selectOne("SELECT COUNT(*) AS c FROM TCPGSYSTEM_DEV.dbo.hv_ryoukin WHERE target_month >= ?", [$from])->c,
        'hv_kanjya_info' => (int) $conn->selectOne("SELECT COUNT(*) AS c FROM TCPGSYSTEM_DEV.dbo.hv_kanjya_info")->c,
    ];
};

$source = [
    'hv_nippou' => (int) $conn->selectOne("SELECT COUNT(*) AS c FROM HINATAoushin.dbo.nippou WHERE [施術日] >= ?", [$from])->c,
    'hv_ryoukin' => (int) $conn->selectOne("SELECT COUNT(*) AS c FROM HINATAoushin.dbo.ryoukin WHERE [月度] >= ?", [$from])->c,
    'hv_kanjya_info' => (int) $conn->selectOne("SELECT COUNT(*) AS c FROM HINATAoushin.dbo.kanjya_info")->c,
];

$conn->beginTransaction();
try {
    $before = $counts();

    // ===== hv_nippou（2026-06-01 以降）=====
    $conn->statement("
        SET IDENTITY_INSERT TCPGSYSTEM_DEV.dbo.hv_nippou ON;

        MERGE TCPGSYSTEM_DEV.dbo.hv_nippou AS tgt
        USING (SELECT * FROM HINATAoushin.dbo.nippou
                 WHERE [施術日] >= '2026-06-01') AS src
           ON tgt.daily_report_id = src.[日報No]
        WHEN MATCHED THEN
            UPDATE SET
                tgt.treatment_date = src.[施術日],
                tgt.target_month = src.[月度],
                tgt.patient_id = src.[患者選択],
                tgt.start_time = src.[開始],
                tgt.end_time = src.[終了],
                tgt.is_home_visit = src.[往診チェック],
                tgt.distance = src.[距離],
                tgt.remarks = src.[備考],
                tgt.staff_name = src.[担当],
                tgt.is_no_treatment = src.[施術なし],
                tgt.private_fee = src.[自費],
                tgt.insurance_amount = src.[保険金額],
                tgt.copayment_amount = src.[負担金],
                tgt.treatment_details = src.[施術内容],
                tgt.initial_fee = src.[初診料],
                tgt.added_at = src.[追加日],
                tgt.ma_applied_at = src.[ma申請日],
                tgt.is_deformity_manual_therapy = src.[変形徒手],
                tgt.is_treatment_report_submitted = src.[施術報告書],
                tgt.is_receipt_home_visit = src.[レセ往療],
                tgt.receipt_home_visit_distance = src.[レセ往療距離],
                tgt.receipt_home_visit_distance_ma = src.[レセ往療距離ma],
                tgt.is_management_fixed = src.[管理確定],
                tgt.is_confirmed = src.[確定],
                tgt.confirmed_at = src.[確定日],
                tgt.home_visit_start_point = src.[往療の起点],
                tgt.count_sheet_remarks = src.[回数表備考],
                tgt.start_point_address = src.[起点住所],
                tgt.sales_amount = src.[売上金額],
                tgt.is_same_day_duplicate = src.[同日重複],
                tgt.uncollected_amount = src.[未収入金],
                tgt.holiday_category = src.[休日区分],
                tgt.category_hours = src.[区分時間],
                tgt.attendance_applied_at = src.[勤怠申請日],
                tgt.manager_approved_at = src.[管理者承認日],
                tgt.is_manager_approved = src.[管理者承認],
                tgt.work_change_date = src.[勤務変更日],
                tgt.before_work_change_start_time = src.[勤務変更前開始],
                tgt.before_work_change_end_time = src.[勤務変更前終了],
                tgt.after_work_change_start_time = src.[勤務変更後開始],
                tgt.after_work_change_end_time = src.[勤務変更後終了],
                tgt.work_change_reason = src.[勤務変更理由],
                tgt.daily_report_store_name = src.[日報店舗],
                tgt.is_ma_treatment = src.[ma施術],
                tgt.daily_report_facility_name = src.[日報施設],
                tgt.daily_report_town_name = src.[日報町名],
                tgt.daily_report_address = src.[日報住所],
                tgt.daily_report_billing_staff = src.[日報請求担当],
                tgt.massage_store_name = src.[マッサージ店舗]
        WHEN NOT MATCHED THEN
            INSERT (
                daily_report_id,
                treatment_date,
                target_month,
                patient_id,
                start_time,
                end_time,
                is_home_visit,
                distance,
                remarks,
                staff_name,
                is_no_treatment,
                private_fee,
                insurance_amount,
                copayment_amount,
                treatment_details,
                initial_fee,
                added_at,
                ma_applied_at,
                is_deformity_manual_therapy,
                is_treatment_report_submitted,
                is_receipt_home_visit,
                receipt_home_visit_distance,
                receipt_home_visit_distance_ma,
                is_management_fixed,
                is_confirmed,
                confirmed_at,
                home_visit_start_point,
                count_sheet_remarks,
                start_point_address,
                sales_amount,
                is_same_day_duplicate,
                uncollected_amount,
                holiday_category,
                category_hours,
                attendance_applied_at,
                manager_approved_at,
                is_manager_approved,
                work_change_date,
                before_work_change_start_time,
                before_work_change_end_time,
                after_work_change_start_time,
                after_work_change_end_time,
                work_change_reason,
                daily_report_store_name,
                is_ma_treatment,
                daily_report_facility_name,
                daily_report_town_name,
                daily_report_address,
                daily_report_billing_staff,
                massage_store_name
            )
            VALUES (
                src.[日報No],
                src.[施術日],
                src.[月度],
                src.[患者選択],
                src.[開始],
                src.[終了],
                src.[往診チェック],
                src.[距離],
                src.[備考],
                src.[担当],
                src.[施術なし],
                src.[自費],
                src.[保険金額],
                src.[負担金],
                src.[施術内容],
                src.[初診料],
                src.[追加日],
                src.[ma申請日],
                src.[変形徒手],
                src.[施術報告書],
                src.[レセ往療],
                src.[レセ往療距離],
                src.[レセ往療距離ma],
                src.[管理確定],
                src.[確定],
                src.[確定日],
                src.[往療の起点],
                src.[回数表備考],
                src.[起点住所],
                src.[売上金額],
                src.[同日重複],
                src.[未収入金],
                src.[休日区分],
                src.[区分時間],
                src.[勤怠申請日],
                src.[管理者承認日],
                src.[管理者承認],
                src.[勤務変更日],
                src.[勤務変更前開始],
                src.[勤務変更前終了],
                src.[勤務変更後開始],
                src.[勤務変更後終了],
                src.[勤務変更理由],
                src.[日報店舗],
                src.[ma施術],
                src.[日報施設],
                src.[日報町名],
                src.[日報住所],
                src.[日報請求担当],
                src.[マッサージ店舗]
            );

        SET IDENTITY_INSERT TCPGSYSTEM_DEV.dbo.hv_nippou OFF;
    ");

    // ===== hv_ryoukin（2026-06-01 以降）=====
    $conn->statement("
        SET IDENTITY_INSERT TCPGSYSTEM_DEV.dbo.hv_ryoukin ON;

        MERGE TCPGSYSTEM_DEV.dbo.hv_ryoukin AS tgt
        USING (SELECT * FROM HINATAoushin.dbo.ryoukin
                 WHERE [月度] >= '2026-06-01') AS src
           ON tgt.charge_id = src.[料金No]
        WHEN MATCHED THEN
            UPDATE SET
                tgt.patient_id = src.[患者名],
                tgt.target_month = src.[月度],
                tgt.collection_date = src.[集金日],
                tgt.collected_amount = src.[集金額],
                tgt.unit_price = src.[単価],
                tgt.billing_count = src.[請求回数],
                tgt.adjustment_amount = src.[過不足金額],
                tgt.is_bank_transfer = src.[振込],
                tgt.description = src.[内容],
                tgt.treatment_count = src.[施術回数],
                tgt.payment_remarks = src.[入金備考],
                tgt.is_private_charge = src.[自費ch],
                tgt.is_payment_confirmed = src.[入金確定],
                tgt.payment_confirmed_at = src.[入金確定日],
                tgt.payment_staff = src.[入金担当],
                tgt.payment_store_name = src.[入金店舗]
        WHEN NOT MATCHED THEN
            INSERT (
                charge_id,
                patient_id,
                target_month,
                collection_date,
                collected_amount,
                unit_price,
                billing_count,
                adjustment_amount,
                is_bank_transfer,
                description,
                treatment_count,
                payment_remarks,
                is_private_charge,
                is_payment_confirmed,
                payment_confirmed_at,
                payment_staff,
                payment_store_name
            )
            VALUES (
                src.[料金No],
                src.[患者名],
                src.[月度],
                src.[集金日],
                src.[集金額],
                src.[単価],
                src.[請求回数],
                src.[過不足金額],
                src.[振込],
                src.[内容],
                src.[施術回数],
                src.[入金備考],
                src.[自費ch],
                src.[入金確定],
                src.[入金確定日],
                src.[入金担当],
                src.[入金店舗]
            );

        SET IDENTITY_INSERT TCPGSYSTEM_DEV.dbo.hv_ryoukin OFF;
    ");

    // ===== hv_kanjya_info（全件）=====
    $conn->statement("
        SET IDENTITY_INSERT TCPGSYSTEM_DEV.dbo.hv_kanjya_info ON;

        MERGE TCPGSYSTEM_DEV.dbo.hv_kanjya_info AS tgt
        USING (SELECT * FROM HINATAoushin.dbo.kanjya_info) AS src
           ON tgt.patient_id = src.[患者No]
        WHEN MATCHED THEN
            UPDATE SET
                tgt.patient_name = src.[患者名],
                tgt.visit_town_name = src.[往診町名],
                tgt.full_address = src.[正式住所],
                tgt.facility_name = src.[施設名],
                tgt.visit_store_name = src.[往診店舗],
                tgt.massage_store_name = src.[マッサージ店舗],
                tgt.is_excluded_from_count = src.[カウント除外],
                tgt.standard_distance = src.[標準距離],
                tgt.burden_ratio = src.[負担割合],
                tgt.standard_burden_amount = src.[標準負担金],
                tgt.subsidy_limit_count = src.[助成上限回数],
                tgt.subsidy_burden_amount = src.[助成負担金],
                tgt.window_distance = src.[窓口距離],
                tgt.common_id = src.[共通ID],
                tgt.massage_id = src.[マッサージID],
                tgt.patient_notes = src.[患者備考],
                tgt.collection_staff = src.[集金担当],
                tgt.billing_staff = src.[請求担当],
                tgt.treatment_fee = src.[施術料金],
                tgt.consent_category = src.[同意区分],
                tgt.consent_date = src.[同意日],
                tgt.is_massage_target = src.[マッサージ対象者],
                tgt.is_receipt_import_excluded = src.[レセ取込除外],
                tgt.visit_distance = src.[往療距離],
                tgt.is_store_channel_excluded = src.[店舗ch除外]
        WHEN NOT MATCHED THEN
            INSERT (
                patient_id,
                patient_name,
                visit_town_name,
                full_address,
                facility_name,
                visit_store_name,
                massage_store_name,
                is_excluded_from_count,
                standard_distance,
                burden_ratio,
                standard_burden_amount,
                subsidy_limit_count,
                subsidy_burden_amount,
                window_distance,
                common_id,
                massage_id,
                patient_notes,
                collection_staff,
                billing_staff,
                treatment_fee,
                consent_category,
                consent_date,
                is_massage_target,
                is_receipt_import_excluded,
                visit_distance,
                is_store_channel_excluded
            )
            VALUES (
                src.[患者No],
                src.[患者名],
                src.[往診町名],
                src.[正式住所],
                src.[施設名],
                src.[往診店舗],
                src.[マッサージ店舗],
                src.[カウント除外],
                src.[標準距離],
                src.[負担割合],
                src.[標準負担金],
                src.[助成上限回数],
                src.[助成負担金],
                src.[窓口距離],
                src.[共通ID],
                src.[マッサージID],
                src.[患者備考],
                src.[集金担当],
                src.[請求担当],
                src.[施術料金],
                src.[同意区分],
                src.[同意日],
                src.[マッサージ対象者],
                src.[レセ取込除外],
                src.[往療距離],
                src.[店舗ch除外]
            );

        SET IDENTITY_INSERT TCPGSYSTEM_DEV.dbo.hv_kanjya_info OFF;
    ");

    $after = $counts();

    echo "対象: {$from} 以降（hv_kanjya_infoは全件）\n";
    foreach ($after as $table => $count) {
        printf("%-16s DEV before=%6d after=%6d / レガシー=%6d %s\n",
            $table, $before[$table], $count, $source[$table],
            $count === $source[$table] ? 'OK' : '★件数不一致（DEV独自行がある場合は想定内）');
    }

    $conn->commit();
    echo "COMMITTED\n";
} catch (\Throwable $e) {
    $conn->rollBack();
    echo "ROLLED BACK: " . $e->getMessage() . "\n";
}
