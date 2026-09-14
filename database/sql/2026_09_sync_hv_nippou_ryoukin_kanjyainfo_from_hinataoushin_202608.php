<?php

/*
目的:
- 往診(home_visit)のレガシー本番DB「HINATAoushin」(TCPGSYSTEM等と同一サーバー・同一アカウント、
  docs/rules/home_visit/12_change_log.mdで言及されている`tests/hinata_oushin/`の本番相当先)から、
  DEV(TCPGSYSTEM_DEV)のhv_nippou/hv_ryoukin/hv_kanjya_infoへ2026年8月分を同期する。
- 往診は本番(legacy)が正。まだシステムマスタ以外に公開してない機能のため、DEV側の
  is_confirmed/is_management_fixed等の確定フラグも気にせず無条件に上書きしてよい
  （2026-09-14、ユーザー確認。同じ理由でmx_time_cards等・実際に運用中の機能とは扱いが違う）。
- 列対応はHINATAoushin.INFORMATION_SCHEMA.COLUMNSとSchema::getColumnListing()の並び順を
  突き合わせて確認済み（nippou/hv_nippouは50列、ryoukin/hv_ryoukinは17列、kanjya_info/
  hv_kanjya_infoは26列。新システム側だけの追加列は末尾のみ）。

実行結果(2026-09-14): nippou 745→1968件、ryoukin 0→295件、kanjya_info 687→692件（いずれも
legacy側の件数と一致、DEV独自の6件(legacy側に存在しない未確定データ)はそのまま保持）。

使い方: `php database/sql/2026_09_sync_hv_nippou_ryoukin_kanjyainfo_from_hinataoushin_202608.php`
（対象月・パスは実行時のハードコードなので、次回使う場合は日付・年月を書き換えてから実行する）。
IDENTITY_INSERT ON/INSERT/OFFは同一のstatement()呼び出し内でバッチ実行する必要がある
（別々のstatement()呼び出しに分けるとIDENTITY_INSERT状態が引き継がれずエラーになる、
ODBC Driver 18のセッション/コネクションプーリング起因と推測、2026-09-14に実際に確認）。
*/

require 'C:/dev/tcpg_system_laravel/vendor/autoload.php';
$app = require 'C:/dev/tcpg_system_laravel/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

DB::connection('sqlsrv')->beginTransaction();
try {
    $countNippouBefore = DB::connection('sqlsrv')->table('hv_nippou')->where('treatment_date', '>=', '2026-08-01')->where('treatment_date', '<', '2026-09-01')->count();
    $countRyoukinBefore = DB::connection('sqlsrv')->table('hv_ryoukin')->where('target_month', '>=', '2026-08-01')->where('target_month', '<', '2026-09-01')->count();
    $countKanjyaBefore = DB::connection('sqlsrv')->table('hv_kanjya_info')->count();

    // ===== 1. hv_nippou =====
    DB::connection('sqlsrv')->update("
        UPDATE dst SET
            dst.treatment_date = src.[施術日], dst.target_month = src.[月度], dst.patient_id = src.[患者選択],
            dst.start_time = src.[開始], dst.end_time = src.[終了], dst.is_home_visit = src.[往診チェック],
            dst.distance = src.[距離], dst.remarks = src.[備考], dst.staff_name = src.[担当],
            dst.is_no_treatment = src.[施術なし], dst.private_fee = src.[自費], dst.insurance_amount = src.[保険金額],
            dst.copayment_amount = src.[負担金], dst.treatment_details = src.[施術内容], dst.initial_fee = src.[初診料],
            dst.added_at = src.[追加日], dst.ma_applied_at = src.[ma申請日],
            dst.is_deformity_manual_therapy = src.[変形徒手], dst.is_treatment_report_submitted = src.[施術報告書],
            dst.is_receipt_home_visit = src.[レセ往療], dst.receipt_home_visit_distance = src.[レセ往療距離],
            dst.receipt_home_visit_distance_ma = src.[レセ往療距離ma], dst.is_management_fixed = src.[管理確定],
            dst.is_confirmed = src.[確定], dst.confirmed_at = src.[確定日], dst.home_visit_start_point = src.[往療の起点],
            dst.count_sheet_remarks = src.[回数表備考], dst.start_point_address = src.[起点住所],
            dst.sales_amount = src.[売上金額], dst.is_same_day_duplicate = src.[同日重複],
            dst.uncollected_amount = src.[未収入金], dst.holiday_category = src.[休日区分],
            dst.category_hours = src.[区分時間], dst.attendance_applied_at = src.[勤怠申請日],
            dst.manager_approved_at = src.[管理者承認日], dst.is_manager_approved = src.[管理者承認],
            dst.work_change_date = src.[勤務変更日], dst.before_work_change_start_time = src.[勤務変更前開始],
            dst.before_work_change_end_time = src.[勤務変更前終了], dst.after_work_change_start_time = src.[勤務変更後開始],
            dst.after_work_change_end_time = src.[勤務変更後終了], dst.work_change_reason = src.[勤務変更理由],
            dst.daily_report_store_name = src.[日報店舗], dst.is_ma_treatment = src.[ma施術],
            dst.daily_report_facility_name = src.[日報施設], dst.daily_report_town_name = src.[日報町名],
            dst.daily_report_address = src.[日報住所], dst.daily_report_billing_staff = src.[日報請求担当],
            dst.massage_store_name = src.[マッサージ店舗]
        FROM TCPGSYSTEM_DEV.dbo.hv_nippou dst
        JOIN HINATAoushin.dbo.nippou src ON dst.daily_report_id = src.[日報No]
        WHERE src.[施術日] >= '2026-08-01' AND src.[施術日] < '2026-09-01'
    ");

    DB::connection('sqlsrv')->statement("
        SET IDENTITY_INSERT TCPGSYSTEM_DEV.dbo.hv_nippou ON;
        INSERT INTO TCPGSYSTEM_DEV.dbo.hv_nippou (
            daily_report_id, treatment_date, target_month, patient_id, start_time, end_time,
            is_home_visit, distance, remarks, staff_name, is_no_treatment, private_fee,
            insurance_amount, copayment_amount, treatment_details, initial_fee, added_at,
            ma_applied_at, is_deformity_manual_therapy, is_treatment_report_submitted,
            is_receipt_home_visit, receipt_home_visit_distance, receipt_home_visit_distance_ma,
            is_management_fixed, is_confirmed, confirmed_at, home_visit_start_point,
            count_sheet_remarks, start_point_address, sales_amount, is_same_day_duplicate,
            uncollected_amount, holiday_category, category_hours, attendance_applied_at,
            manager_approved_at, is_manager_approved, work_change_date,
            before_work_change_start_time, before_work_change_end_time,
            after_work_change_start_time, after_work_change_end_time, work_change_reason,
            daily_report_store_name, is_ma_treatment, daily_report_facility_name,
            daily_report_town_name, daily_report_address, daily_report_billing_staff,
            massage_store_name
        )
        SELECT
            src.[日報No], src.[施術日], src.[月度], src.[患者選択], src.[開始], src.[終了],
            src.[往診チェック], src.[距離], src.[備考], src.[担当], src.[施術なし], src.[自費],
            src.[保険金額], src.[負担金], src.[施術内容], src.[初診料], src.[追加日],
            src.[ma申請日], src.[変形徒手], src.[施術報告書], src.[レセ往療], src.[レセ往療距離], src.[レセ往療距離ma],
            src.[管理確定], src.[確定], src.[確定日], src.[往療の起点], src.[回数表備考], src.[起点住所],
            src.[売上金額], src.[同日重複], src.[未収入金], src.[休日区分], src.[区分時間], src.[勤怠申請日],
            src.[管理者承認日], src.[管理者承認], src.[勤務変更日], src.[勤務変更前開始], src.[勤務変更前終了],
            src.[勤務変更後開始], src.[勤務変更後終了], src.[勤務変更理由], src.[日報店舗], src.[ma施術],
            src.[日報施設], src.[日報町名], src.[日報住所], src.[日報請求担当], src.[マッサージ店舗]
        FROM HINATAoushin.dbo.nippou src
        WHERE src.[施術日] >= '2026-08-01' AND src.[施術日] < '2026-09-01'
          AND NOT EXISTS (SELECT 1 FROM TCPGSYSTEM_DEV.dbo.hv_nippou dst WHERE dst.daily_report_id = src.[日報No]);
        SET IDENTITY_INSERT TCPGSYSTEM_DEV.dbo.hv_nippou OFF;
    ");

    // ===== 2. hv_ryoukin =====
    DB::connection('sqlsrv')->update("
        UPDATE dst SET
            dst.patient_id = src.[患者名], dst.target_month = src.[月度], dst.collection_date = src.[集金日],
            dst.collected_amount = src.[集金額], dst.unit_price = src.[単価], dst.billing_count = src.[請求回数],
            dst.adjustment_amount = src.[過不足金額], dst.is_bank_transfer = src.[振込], dst.description = src.[内容],
            dst.treatment_count = src.[施術回数], dst.payment_remarks = src.[入金備考], dst.is_private_charge = src.[自費ch],
            dst.is_payment_confirmed = src.[入金確定], dst.payment_confirmed_at = src.[入金確定日],
            dst.payment_staff = src.[入金担当], dst.payment_store_name = src.[入金店舗]
        FROM TCPGSYSTEM_DEV.dbo.hv_ryoukin dst
        JOIN HINATAoushin.dbo.ryoukin src ON dst.charge_id = src.[料金No]
        WHERE src.[月度] >= '2026-08-01' AND src.[月度] < '2026-09-01'
    ");

    DB::connection('sqlsrv')->statement("
        SET IDENTITY_INSERT TCPGSYSTEM_DEV.dbo.hv_ryoukin ON;
        INSERT INTO TCPGSYSTEM_DEV.dbo.hv_ryoukin (
            charge_id, patient_id, target_month, collection_date, collected_amount, unit_price,
            billing_count, adjustment_amount, is_bank_transfer, description, treatment_count,
            payment_remarks, is_private_charge, is_payment_confirmed, payment_confirmed_at,
            payment_staff, payment_store_name
        )
        SELECT
            src.[料金No], src.[患者名], src.[月度], src.[集金日], src.[集金額], src.[単価],
            src.[請求回数], src.[過不足金額], src.[振込], src.[内容], src.[施術回数],
            src.[入金備考], src.[自費ch], src.[入金確定], src.[入金確定日], src.[入金担当], src.[入金店舗]
        FROM HINATAoushin.dbo.ryoukin src
        WHERE src.[月度] >= '2026-08-01' AND src.[月度] < '2026-09-01'
          AND NOT EXISTS (SELECT 1 FROM TCPGSYSTEM_DEV.dbo.hv_ryoukin dst WHERE dst.charge_id = src.[料金No]);
        SET IDENTITY_INSERT TCPGSYSTEM_DEV.dbo.hv_ryoukin OFF;
    ");

    // ===== 3. hv_kanjya_info (全件) =====
    DB::connection('sqlsrv')->update("
        UPDATE dst SET
            dst.patient_name = src.[患者名], dst.visit_town_name = src.[往診町名], dst.full_address = src.[正式住所],
            dst.facility_name = src.[施設名], dst.visit_store_name = src.[往診店舗], dst.massage_store_name = src.[マッサージ店舗],
            dst.is_excluded_from_count = src.[カウント除外], dst.standard_distance = src.[標準距離],
            dst.burden_ratio = src.[負担割合], dst.standard_burden_amount = src.[標準負担金],
            dst.subsidy_limit_count = src.[助成上限回数], dst.subsidy_burden_amount = src.[助成負担金],
            dst.window_distance = src.[窓口距離], dst.common_id = src.[共通ID], dst.massage_id = src.[マッサージID],
            dst.patient_notes = src.[患者備考], dst.collection_staff = src.[集金担当], dst.billing_staff = src.[請求担当],
            dst.treatment_fee = src.[施術料金], dst.consent_category = src.[同意区分], dst.consent_date = src.[同意日],
            dst.is_massage_target = src.[マッサージ対象者], dst.is_receipt_import_excluded = src.[レセ取込除外],
            dst.visit_distance = src.[往療距離], dst.is_store_channel_excluded = src.[店舗ch除外]
        FROM TCPGSYSTEM_DEV.dbo.hv_kanjya_info dst
        JOIN HINATAoushin.dbo.kanjya_info src ON dst.patient_id = src.[患者No]
    ");

    DB::connection('sqlsrv')->statement("
        SET IDENTITY_INSERT TCPGSYSTEM_DEV.dbo.hv_kanjya_info ON;
        INSERT INTO TCPGSYSTEM_DEV.dbo.hv_kanjya_info (
            patient_id, patient_name, visit_town_name, full_address, facility_name, visit_store_name,
            massage_store_name, is_excluded_from_count, standard_distance, burden_ratio,
            standard_burden_amount, subsidy_limit_count, subsidy_burden_amount, window_distance,
            common_id, massage_id, patient_notes, collection_staff, billing_staff, treatment_fee,
            consent_category, consent_date, is_massage_target, is_receipt_import_excluded,
            visit_distance, is_store_channel_excluded
        )
        SELECT
            src.[患者No], src.[患者名], src.[往診町名], src.[正式住所], src.[施設名], src.[往診店舗],
            src.[マッサージ店舗], src.[カウント除外], src.[標準距離], src.[負担割合],
            src.[標準負担金], src.[助成上限回数], src.[助成負担金], src.[窓口距離],
            src.[共通ID], src.[マッサージID], src.[患者備考], src.[集金担当], src.[請求担当], src.[施術料金],
            src.[同意区分], src.[同意日], src.[マッサージ対象者], src.[レセ取込除外],
            src.[往療距離], src.[店舗ch除外]
        FROM HINATAoushin.dbo.kanjya_info src
        WHERE NOT EXISTS (SELECT 1 FROM TCPGSYSTEM_DEV.dbo.hv_kanjya_info dst WHERE dst.patient_id = src.[患者No]);
        SET IDENTITY_INSERT TCPGSYSTEM_DEV.dbo.hv_kanjya_info OFF;
    ");

    $countNippouAfter = DB::connection('sqlsrv')->table('hv_nippou')->where('treatment_date', '>=', '2026-08-01')->where('treatment_date', '<', '2026-09-01')->count();
    $countRyoukinAfter = DB::connection('sqlsrv')->table('hv_ryoukin')->where('target_month', '>=', '2026-08-01')->where('target_month', '<', '2026-09-01')->count();
    $countKanjyaAfter = DB::connection('sqlsrv')->table('hv_kanjya_info')->count();

    echo "nippou: before=$countNippouBefore after=$countNippouAfter\n";
    echo "ryoukin: before=$countRyoukinBefore after=$countRyoukinAfter\n";
    echo "kanjya_info: before=$countKanjyaBefore after=$countKanjyaAfter\n";

    DB::connection('sqlsrv')->commit();
    echo "COMMITTED\n";
} catch (\Throwable $e) {
    DB::connection('sqlsrv')->rollBack();
    echo "ROLLED BACK: " . $e->getMessage() . "\n";
}
