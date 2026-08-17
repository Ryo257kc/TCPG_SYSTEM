# 往診 日報ルール

## 対象画面

- `/staff/home_visit/daily_report`
- `/staff/home_visit/daily_report/print`

## 基本方針

往診日報は往診関連集計の入口。
患者、施術日、担当、町名、距離、売上、自費、保険金額、負担金、未収金などを保存する。

## 売上・自費・未収金

売上金額、自費、未収金は後続の月次集計、給与、売上帳票で使う。
表示用に別名で保存しない。
未収金は `uncollected_amount` を使う。

売上金額・未収金は、日報が管理確定（`is_management_fixed`）した後だけ日報一覧から直接
入力できる（`updateSalesAmount`/`updateUncollectedAmount`、往診売上権限＝`isAccounting`のみ）。
通常の日報編集（`update`、確定後は編集不可）とは別のロック条件にして入力の窓を空けている。
ロックがかかるタイミングは項目によって違う。

- 売上金額：担当スタッフの**勤怠が確定**すると編集不可（歩合計算に使うため）。
- 未収金：その日報が取り込まれる**給与が確定（`edit_lock`）**すると編集不可
  （給与計算で未収額を確認しながら入力する運用のため）。取込先の給与年月は
  日報のtreatment_dateの翌月（`PayrollV2SalesImportService::salesYearMonth()`の逆）。
  スタッフごとに給与確定のタイミングが違うので、同じ日でもロックされてる人と
  されてない人が混在するのが正常。

未収金は月ごとの合計額を事務側で計算して渡されることが多く、特定の日付に紐づく値ではない
（一覧で合計しやすいようどこかの行にまとめて入れる）。

実装: `DailyReportController::updateSalesAmount()`, `DailyReportController::updateUncollectedAmount()`,
`DailyReportController::isSalesLockedByAttendance()`, `DailyReportController::isUncollectedLockedByPayroll()`

## 重複

日付が違うデータは同じ患者でも重複とは限らない。
重複判定を入れる場合は、日付、患者、施術内容、同日重複フラグを確認する。

## 触らないこと

- 1週間違いなど、通常ありえる往診を勝手に重複除外しない。
- 未収金を集計から隠さない。
- 給与側や売上側の都合で日報保存値を変更しない。
