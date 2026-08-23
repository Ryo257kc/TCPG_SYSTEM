# 勤怠 変更履歴

## 2026-08-23 スタッフ月間勤怠が開けないエラー修正・休みの日の所定時間フォールバック統一

- `AttendanceController::attendanceMonthly()`（/staff/attendance/monthly、スタッフ自身の月間勤怠）が
  `ArgumentCountError`で開けなくなっていた。`App\Services\StaffPortal\Attendance\AttendanceV2DailyTableItemBuilder`が
  依存を2つ（`AttendanceV2MonthlySummaryService`・`StoreDisplayNameService`）要求するようになったのに、
  呼び出し側は`new`で1つしか渡していなかった。`app(...)`（Laravelコンテナ解決）に変更し、041のデータで
  正常に動くことを確認済み。
- 「休みの日（有休・有半・振休・欠勤）で変更打刻が無い場合、シフト予定時間へフォールバックせず
  空欄にする」ロジックが、実装が3箇所に分かれていて2箇所（スタッフ自身の月間勤怠・管理者が見る
  スタッフ詳細`managementDetail()`）で抜けていた（管理側の日別勤怠`Admin\V2\Attendance\AttendanceV2DailyTableItemBuilder`
  だけ正しかった）。実データ（031さん・2026-07-13・有休・変更打刻なし）で、直す前は誤ってシフト
  予定時間が出ることを確認し、3箇所とも`AttendanceV2MonthlySummaryService::isRestCategory()`判定で
  揃えた。有半（半日休）は通常その働いた半分の変更打刻が入るため、このフォールバック自体には
  影響を受けにくい（`paid_leave_used=0.5`は別項目で元々正しく保存されている）。

## 2026-08-23 保存確認漏れ（管理側・給与反映）修正

`AttendanceV2BulkReflectService::reflect()`（勤怠→給与への一括反映）が、`mx_kyuyo_shou`の
`update()`戻り値を見ず、`$updated`を無条件に加算していた。戻り値を見て0件なら`$missing`
（対象給与行なしと同じ扱い）に振り分けるよう修正。041実運用の監査を機に管理側も横断監査した。

## 2026-08-23 保存の確認漏れ・statusMessage未表示の一斉修正

041実運用の監査で、店舗日報・入金確認と同じ「保存されてないのに成功扱い」パターンが勤怠・シフト側にも見つかった。

- `AttendanceController::attendanceUpdate()`（スタッフ自身の勤怠実績変更）：`AttendanceV2DailyEditService::update()`は影響行数を`int`で返す設計（Admin側の`AttendanceV2Controller::updateDaily()`は元からこの戻り値を見て「変更はありません。」と出し分けていた）だったが、StaffPortal側の呼び出しだけ戻り値を破棄して常に「保存しました」と表示していた。戻り値を見て0件ならエラー表示するよう修正。
- `ShiftController::officeAttendanceUpdate()`（スタッフ自身のシフト変更）・`adminShiftUpdate()`（店長のシフト変更）・`adminBasicShiftUpdate()`（基本シフト、`is_payment_check_user`も含むため041も到達可能）：いずれも対象行の実在確認・`update()`の影響行数チェックが無く、`time_no`/`shift_no`が古い/存在しない場合でも常に成功扱いだった。`isTimeCardConfirmed()`は「行が無ければ確定していない扱い（false）」を返す作りのため、存在確認の代わりにはならない点に注意（別途`exists()`で確認する必要がある）。3メソッドとも実在確認＋影響行数チェックを追加。
- **さらに別の不具合を発見**：`officeAttendanceUpdate()`・`adminShiftUpdate()`が使う画面（`staff_portal/admin/shift/change.blade.php`、シフト変更・スタッフ自身の勤怠変更で共用）は、そもそも`statusMessage`を表示するマークアップ自体が無かった。そのため今回追加したエラーメッセージだけでなく、**以前からあった「勤怠確定済みのため、シフトは編集できません。」も一度も画面に表示されたことがなかった**。`officeAttendance()`/`adminShiftChange()`側で`statusMessage`をセッションから渡すよう修正し、テンプレートに表示ブロックを追加。`admin/shift/basic.blade.php`（基本シフト）は元から正しく表示できていたので対象外。

## 2026-08-17 総勤務日数・総実働時間の整理、休みの日の除外、検算アラート追加

- `work_in_num`/`work_time`（出勤日数・実働時間）はAccess時代から「休日出勤分も含む総勤務日数・総実働時間」として明細・賃金台帳に出力され続けているため、意味を変えず維持する方針に確定。
- 新規に`work_in_num_net`/`work_time_net`列を`mx_kyuyo_shou`へ追加（`database/sql/2026_08_add_work_net_columns_to_mx_kyuyo_shou.sql`）。休日出勤・残業を除いた「所定時間」等を別カラムに保存し、既存列は書き換えない。
- 有休・有半・振休・欠勤は「休みの日」として出勤日数・実働時間の集計から除外（`AttendanceV2MonthlySummaryService::isRestCategory()`）。ただし有半は半日だけ休みのため、実際に働いた半分は出勤扱いのまま残す。
- `holiday_work_time`(work_time_num、休日出勤時間)は元々`work_type_time`という手入力欄（空のことが多い）から取っていたため、`change_scheduled`ベースの実働時間と食い違うことがあった。総実働時間から所定時間を引いた値と必ず一致するよう`changeScheduled`ベースに統一。
- 勤怠一覧に検算アラートを追加（`AttendanceV2MonthlySummaryService::reconciliationDiff()`）。シフト予定−休みの日の予定時間−遅早+残業+休日出勤の実働時間が、実働時間(change_scheduled_total)と一致するかを見る。業務委託は対象外。
- ラベルを明細(`shared/payroll/payslip_item.blade.php`)・legacy側と同じ「出勤」（日数）「出勤」（時間、旧「実働」から統一）に揃えた。総出勤日数／平日出勤日数／休日出勤日数、総出勤時間／所定時間／休日出勤時間。
- 日別詳細(`daily_table.blade.php`)の日付編集ボタンが、勤怠確定済みでも常にクリックできてしまい、保存を押してもサーバー側(`AttendanceV2Controller::updateDaily()`)で弾かれるだけで気づきにくかった。ボタン自体を`$isAttendanceChecked`で無効化するよう修正。あわせて`.btn:disabled`の見た目（グレーアウト）をapp-ui.cssに追加（今まで未定義だったため、他の無効化ボタンにも共通で効く）。
- 休出/法出は実働時間をchange_scheduled（シフト変更側の実績値）から計算する方式になったため、区分の横の時間欄(`category_time`/`work_type_time`)は休出/法出では使われなくなっている。それを知らずこの欄に時間を入力しても計算に反映されず、`work_horiday_num`（休出日数）だけカウントされて`work_in_num`等に実働時間が乗らない不一致が起きる（staff069・2026年7月で実例：change_scheduledが0のまま保存され、旧欄のwork_type_timeだけ7.5が残っていた）。紛らわしいので休出/法出を選んでいる間はこの時間欄をグレーアウトするJSを追加。

## 2026-08-15 基本シフトタブの表示バグ修正・新規登録を7日一括作成に変更

- スタッフ編集画面「基本シフト」タブで時刻(始業・退出・入出・終業)と勤務店舗が全部空欄表示になっていた。原因は2つ重なっていた: ①`basic_shifts.blade.php`が`shift_start`/`shift_in_out`等のキー名を期待していたが、`tableRows()`経由の配列はDB実列名(`shift_in`/`shift_entry`等)のままで一致していなかった。②`shift_in`等はSQL Server側で「時刻だけを意味のない日付とセットで保存」する形式だが、汎用の`normalizeValue()`が日付として扱い和暦文字列に変換し、`<input type="time">`にとって無効な値になっていた。`StaffV2Service::basicShiftRows()`を汎用`tableRows()`経由から専用実装に変更して解決。`tableRows()`/`relatedRows()`のような汎用シリアライズは、日付・時刻混在列やDB列名と画面フィールド名が食い違うテーブルでは使えない点に注意。
- 上記修正後、基本シフトの運用実態(1名につき必ず月〜日7日分をセットで使う。1曜日だけ追加する使い方はない)に合わせて新規登録の作りを変更。既存行が0件の時だけ「7日分を作成」ボタンを表示(曜日選択・時刻入力なし、空の7行を一括作成)。既存行が1件でもあれば新規登録欄は非表示、一覧側の行ごと編集フォームだけで運用。`StaffV2Service::createBasicShiftWeek()`は既存行があれば何もしない(冪等)。古い1曜日ずつ追加する`storeBasicShift`/`createBasicShift`はコード上は残したまま(画面からは到達不可)。

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
