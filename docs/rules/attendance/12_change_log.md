# 勤怠 変更履歴

## 2026-08-17 総勤務日数・総実働時間の整理、休みの日の除外、検算アラート追加

- `work_in_num`/`work_time`（出勤日数・実働時間）はAccess時代から「休日出勤分も含む総勤務日数・総実働時間」として明細・賃金台帳に出力され続けているため、意味を変えず維持する方針に確定。
- 新規に`work_in_num_net`/`work_time_net`列を`mx_kyuyo_shou`へ追加（`database/sql/2026_08_add_work_net_columns_to_mx_kyuyo_shou.sql`）。休日出勤・残業を除いた「所定時間」等を別カラムに保存し、既存列は書き換えない。
- 有休・有半・振休・欠勤は「休みの日」として出勤日数・実働時間の集計から除外（`AttendanceV2MonthlySummaryService::isRestCategory()`）。ただし有半は半日だけ休みのため、実際に働いた半分は出勤扱いのまま残す。
- `holiday_work_time`(work_time_num、休日出勤時間)は元々`work_type_time`という手入力欄（空のことが多い）から取っていたため、`change_scheduled`ベースの実働時間と食い違うことがあった。総実働時間から所定時間を引いた値と必ず一致するよう`changeScheduled`ベースに統一。
- 勤怠一覧に検算アラートを追加（`AttendanceV2MonthlySummaryService::reconciliationDiff()`）。シフト予定−休みの日の予定時間−遅早+残業+休日出勤の実働時間が、実働時間(change_scheduled_total)と一致するかを見る。業務委託は対象外。
- ラベルを明細(`shared/payroll/payslip_item.blade.php`)・legacy側と同じ「出勤」（日数）「出勤」（時間、旧「実働」から統一）に揃えた。総出勤日数／平日出勤日数／休日出勤日数、総出勤時間／所定時間／休日出勤時間。
- 日別詳細(`daily_table.blade.php`)の日付編集ボタンが、勤怠確定済みでも常にクリックできてしまい、保存を押してもサーバー側(`AttendanceV2Controller::updateDaily()`)で弾かれるだけで気づきにくかった。ボタン自体を`$isAttendanceChecked`で無効化するよう修正。あわせて`.btn:disabled`の見た目（グレーアウト）をapp-ui.cssに追加（今まで未定義だったため、他の無効化ボタンにも共通で効く）。
- 休出/法出は実働時間をchange_scheduled（シフト変更側の実績値）から計算する方式になったため、区分の横の時間欄(`category_time`/`work_type_time`)は休出/法出では使われなくなっている。それを知らずこの欄に時間を入力しても計算に反映されず、`work_horiday_num`（休出日数）だけカウントされて`work_in_num`等に実働時間が乗らない不一致が起きる（staff069・2026年7月で実例：change_scheduledが0のまま保存され、旧欄のwork_type_timeだけ7.5が残っていた）。紛らわしいので休出/法出を選んでいる間はこの時間欄をグレーアウトするJSを追加。

## 2026-08-01 初版作成

- 管理側勤怠、スタッフ月間勤怠、スタッフポータル管理用勤怠のルールを分ける方針にした。
- 月次集計は `AttendanceV2MonthlySummaryService` を正とする。
- `/staff/attendance/monthly` の月合計を共通Serviceへ寄せた。
- `staff_portal/admin/attendance/management_detail.blade.php` の上部勤怠集計を共通Service表示へ寄せた。
- 参照がない旧日別Service、旧日別表Service、旧出勤日数Serviceを削除対象として整理した。
- staff_portal/admin/attendance/management_detail.blade.php の日別行変換をControllerへ移し、Viewは表示専用に近づけた。
- AttendanceV2DailyTableItemBuilder::summary() を削除し、月次集計は AttendanceV2MonthlySummaryService のみに寄せた。
- AttendanceV2ListSummaryService を削除し、勤怠一覧の月次集計は AttendanceV2MetricService から AttendanceV2MonthlySummaryService を使う形に統一した。
- 打刻ルール 03_punch.md を追加し、打刻と打刻一覧を勤怠カテゴリで管理する方針にした。
