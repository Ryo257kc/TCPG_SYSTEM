<?php

/*
目的:
- 店舗日報の本番DB「DailyReport」から、検証用の「DailyReport_DEV」へ
  2026/08/04〜2026/09/30 の4テーブルを同期する。

背景:
- 日報集計の「先生別計」「差額」がLaravel側で一度も再計算されておらず、レセコンや
  患者明細を編集するとズレたまま残る問題の、再計算処理を検証するため（2026-10-08）。
- DEVは2026-08-03で止まっており（T_日報集計の最新日付）、再現対象である9月のズレ
  （9/4・9/5・9/9・9/12の6件）が1件も入っていないため、この期間の同期が必要。
- 10月分は入れない。当日作成中のデータが混ざると本番/DEVどちらを見ているか
  分からなくなるため（2026-10-08、ユーザー指示）。

方針:
- 本番(DailyReport)が正。既存行はUPDATE、無い行だけINSERT。
- IDENTITY列（No・先生別No・レジＮｏ）は本番と同じ値を入れる。Laravel側が
  No / 先生別No を更新キーに使っているため、ズレると検証にならない。
- T_先生別日報は日付列を持たないので、対象期間のT_患者名日報.患者Noに
  ぶら下がる行だけを対象にする。
- 本番側へは一切書き込まない。UPDATE/INSERTの対象はDailyReport_DEVのみ。

注意:
- IDENTITY_INSERT ON/INSERT/OFFは同一のstatement()呼び出し内でバッチ実行すること
  （別々のstatement()に分けるとIDENTITY_INSERT状態が引き継がれずエラーになる。
  ODBC Driver 18のセッション/コネクションプーリング起因、2026-09-14の往診同期で確認済み）。
- 接続は sqlsrv_dailyreport を使う。.envがDailyReport（本番）を向いている状態で実行する
  （DB名を明示しているのでDEVへ切り替える前に実行してよい）。

使い方:
  php database/sql/2026_10_sync_dailyreport_dev_from_dailyreport_20260804_20260930.php

実行結果(2026-10-08): ※実行後に追記する
*/

require 'C:/dev/tcpg_system_laravel/vendor/autoload.php';
$app = require 'C:/dev/tcpg_system_laravel/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$from = '2026-08-04';
$to = '2026-10-01'; // 上限は含まない（9/30まで）

$conn = DB::connection('sqlsrv_dailyreport');

$countByDate = static function (string $db, string $table) use ($conn, $from, $to): int {
    return (int) $conn->selectOne(
        "SELECT COUNT(*) AS c FROM {$db}.dbo.[{$table}] WHERE 日付 >= ? AND 日付 < ?",
        [$from, $to]
    )->c;
};

$countTeacher = static function (string $db) use ($conn, $from, $to): int {
    return (int) $conn->selectOne(
        "SELECT COUNT(*) AS c
           FROM {$db}.dbo.T_先生別日報 t
          WHERE t.患者No_t IN (
                SELECT 患者No FROM {$db}.dbo.T_患者名日報 WHERE 日付 >= ? AND 日付 < ?
          )",
        [$from, $to]
    )->c;
};

$conn->beginTransaction();
try {
    $before = [
        '日報集計' => $countByDate('DailyReport_DEV', 'T_日報集計'),
        '患者名日報' => $countByDate('DailyReport_DEV', 'T_患者名日報'),
        '先生別日報' => $countTeacher('DailyReport_DEV'),
        'レジ詳細' => $countByDate('DailyReport_DEV', 'T_レジ詳細'),
    ];

    // ===== 1. T_日報集計 =====
    $conn->update("
        UPDATE dst SET
            dst.日報集計No = src.日報集計No, dst.日付 = src.日付, dst.レセコン = src.レセコン,
            dst.先生別計 = src.先生別計, dst.差額 = src.差額, dst.交通事故 = src.交通事故,
            dst.備考 = src.備考, dst.レジ = src.レジ, dst.銀行預入金額 = src.銀行預入金額,
            dst.来院人数 = src.来院人数, dst.予約人数 = src.予約人数, dst.院内人数 = src.院内人数,
            dst.日報集計店舗 = src.日報集計店舗, dst.確定 = src.確定, dst.確定日 = src.確定日,
            dst.誤差チェック = src.誤差チェック, dst.カード = src.カード, dst.チャージ残金 = src.チャージ残金
        FROM DailyReport_DEV.dbo.T_日報集計 dst
        JOIN DailyReport.dbo.T_日報集計 src ON dst.No = src.No
        WHERE src.日付 >= ? AND src.日付 < ?
    ", [$from, $to]);

    $conn->statement("
        SET IDENTITY_INSERT DailyReport_DEV.dbo.T_日報集計 ON;
        INSERT INTO DailyReport_DEV.dbo.T_日報集計 (
            No, 日報集計No, 日付, レセコン, 先生別計, 差額, 交通事故, 備考, レジ,
            銀行預入金額, 来院人数, 予約人数, 院内人数, 日報集計店舗, 確定, 確定日,
            誤差チェック, カード, チャージ残金
        )
        SELECT src.No, src.日報集計No, src.日付, src.レセコン, src.先生別計, src.差額,
               src.交通事故, src.備考, src.レジ, src.銀行預入金額, src.来院人数,
               src.予約人数, src.院内人数, src.日報集計店舗, src.確定, src.確定日,
               src.誤差チェック, src.カード, src.チャージ残金
        FROM DailyReport.dbo.T_日報集計 src
        WHERE src.日付 >= '{$from}' AND src.日付 < '{$to}'
          AND NOT EXISTS (SELECT 1 FROM DailyReport_DEV.dbo.T_日報集計 dst WHERE dst.No = src.No);
        SET IDENTITY_INSERT DailyReport_DEV.dbo.T_日報集計 OFF;
    ");

    // ===== 2. T_患者名日報 =====
    $conn->update("
        UPDATE dst SET
            dst.患者No = src.患者No, dst.日付 = src.日付, dst.時刻 = src.時刻,
            dst.患者名 = src.患者名, dst.日別順 = src.日別順, dst.割合 = src.割合,
            dst.レセ負担金 = src.レセ負担金, dst.保険請求 = src.保険請求, dst.差額 = src.差額,
            dst.保険証 = src.保険証, dst.回収日 = src.回収日, dst.新患 = src.新患,
            dst.店舗 = src.店舗, dst.日報集計No_t = src.日報集計No_t,
            dst.日報備考 = src.日報備考, dst.負担金ch = src.負担金ch
        FROM DailyReport_DEV.dbo.T_患者名日報 dst
        JOIN DailyReport.dbo.T_患者名日報 src ON dst.No = src.No
        WHERE src.日付 >= ? AND src.日付 < ?
    ", [$from, $to]);

    $conn->statement("
        SET IDENTITY_INSERT DailyReport_DEV.dbo.T_患者名日報 ON;
        INSERT INTO DailyReport_DEV.dbo.T_患者名日報 (
            No, 患者No, 日付, 時刻, 患者名, 日別順, 割合, レセ負担金, 保険請求, 差額,
            保険証, 回収日, 新患, 店舗, 日報集計No_t, 日報備考, 負担金ch
        )
        SELECT src.No, src.患者No, src.日付, src.時刻, src.患者名, src.日別順, src.割合,
               src.レセ負担金, src.保険請求, src.差額, src.保険証, src.回収日, src.新患,
               src.店舗, src.日報集計No_t, src.日報備考, src.負担金ch
        FROM DailyReport.dbo.T_患者名日報 src
        WHERE src.日付 >= '{$from}' AND src.日付 < '{$to}'
          AND NOT EXISTS (SELECT 1 FROM DailyReport_DEV.dbo.T_患者名日報 dst WHERE dst.No = src.No);
        SET IDENTITY_INSERT DailyReport_DEV.dbo.T_患者名日報 OFF;
    ");

    // ===== 3. T_先生別日報（日付列が無いので患者名日報の対象期間にぶら下がる行だけ）=====
    $conn->update("
        UPDATE dst SET
            dst.患者No_t = src.患者No_t, dst.メニュー = src.メニュー, dst.回数 = src.回数,
            dst.備考 = src.備考, dst.担当者 = src.担当者, dst.自費 = src.自費,
            dst.保険負担 = src.保険負担, dst.請求金額 = src.請求金額, dst.計算外 = src.計算外,
            dst.項目 = src.項目, dst.一括 = src.一括, dst.先生別外 = src.先生別外,
            dst.ch = src.ch, dst.担当者ID = src.担当者ID, dst.レセ差額 = src.レセ差額,
            dst.カード手数料 = src.カード手数料, dst.charge = src.charge
        FROM DailyReport_DEV.dbo.T_先生別日報 dst
        JOIN DailyReport.dbo.T_先生別日報 src ON dst.先生別No = src.先生別No
        WHERE src.患者No_t IN (
            SELECT 患者No FROM DailyReport.dbo.T_患者名日報 WHERE 日付 >= ? AND 日付 < ?
        )
    ", [$from, $to]);

    $conn->statement("
        SET IDENTITY_INSERT DailyReport_DEV.dbo.T_先生別日報 ON;
        INSERT INTO DailyReport_DEV.dbo.T_先生別日報 (
            先生別No, 患者No_t, メニュー, 回数, 備考, 担当者, 自費, 保険負担, 請求金額,
            計算外, 項目, 一括, 先生別外, ch, 担当者ID, レセ差額, カード手数料, charge
        )
        SELECT src.先生別No, src.患者No_t, src.メニュー, src.回数, src.備考, src.担当者,
               src.自費, src.保険負担, src.請求金額, src.計算外, src.項目, src.一括,
               src.先生別外, src.ch, src.担当者ID, src.レセ差額, src.カード手数料, src.charge
        FROM DailyReport.dbo.T_先生別日報 src
        WHERE src.患者No_t IN (
            SELECT 患者No FROM DailyReport.dbo.T_患者名日報 WHERE 日付 >= '{$from}' AND 日付 < '{$to}'
        )
          AND NOT EXISTS (SELECT 1 FROM DailyReport_DEV.dbo.T_先生別日報 dst WHERE dst.先生別No = src.先生別No);
        SET IDENTITY_INSERT DailyReport_DEV.dbo.T_先生別日報 OFF;
    ");

    // ===== 4. T_レジ詳細 =====
    $conn->update("
        UPDATE dst SET
            dst.項目 = src.項目, dst.品名 = src.品名, dst.金額 = src.金額,
            dst.日付 = src.日付, dst.レジ店舗 = src.レジ店舗, dst.金額1 = src.金額1,
            dst.非表示ch = src.非表示ch
        FROM DailyReport_DEV.dbo.T_レジ詳細 dst
        JOIN DailyReport.dbo.T_レジ詳細 src ON dst.レジＮｏ = src.レジＮｏ
        WHERE src.日付 >= ? AND src.日付 < ?
    ", [$from, $to]);

    $conn->statement("
        SET IDENTITY_INSERT DailyReport_DEV.dbo.T_レジ詳細 ON;
        INSERT INTO DailyReport_DEV.dbo.T_レジ詳細 (
            レジＮｏ, 項目, 品名, 金額, 日付, レジ店舗, 金額1, 非表示ch
        )
        SELECT src.レジＮｏ, src.項目, src.品名, src.金額, src.日付, src.レジ店舗,
               src.金額1, src.非表示ch
        FROM DailyReport.dbo.T_レジ詳細 src
        WHERE src.日付 >= '{$from}' AND src.日付 < '{$to}'
          AND NOT EXISTS (SELECT 1 FROM DailyReport_DEV.dbo.T_レジ詳細 dst WHERE dst.レジＮｏ = src.レジＮｏ);
        SET IDENTITY_INSERT DailyReport_DEV.dbo.T_レジ詳細 OFF;
    ");

    $after = [
        '日報集計' => $countByDate('DailyReport_DEV', 'T_日報集計'),
        '患者名日報' => $countByDate('DailyReport_DEV', 'T_患者名日報'),
        '先生別日報' => $countTeacher('DailyReport_DEV'),
        'レジ詳細' => $countByDate('DailyReport_DEV', 'T_レジ詳細'),
    ];
    $prod = [
        '日報集計' => $countByDate('DailyReport', 'T_日報集計'),
        '患者名日報' => $countByDate('DailyReport', 'T_患者名日報'),
        '先生別日報' => $countTeacher('DailyReport'),
        'レジ詳細' => $countByDate('DailyReport', 'T_レジ詳細'),
    ];

    echo "期間: {$from} 〜 {$to}（上限含まず）\n";
    foreach ($after as $name => $count) {
        printf("%-12s DEV before=%6d after=%6d / 本番=%6d %s\n",
            $name, $before[$name], $count, $prod[$name],
            $count === $prod[$name] ? 'OK' : '★不一致');
    }

    $conn->commit();
    echo "COMMITTED\n";
} catch (\Throwable $e) {
    $conn->rollBack();
    echo "ROLLED BACK: " . $e->getMessage() . "\n";
}
