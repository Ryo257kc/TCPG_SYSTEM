# システム構成

## 目的

給与・賞与まわりで、どのデータがどこから来て、どの画面へ出るかを迷わないようにする。

AIに修正させる時は、先にこのファイルで全体像を確認してから、対象ページの個別ルールを読む。

## DBの役割

- 給与計算テーブルは、計算済みの給与データを保持する。
- 賞与計算テーブルは、計算済みの賞与データを保持する。
- スタッフマスタは、氏名、所属、雇用区分、給与マスタ、交通費、社保、扶養などの元情報を保持する。
- 勤怠テーブルは、出勤日数、勤務時間、有休、欠勤、遅早、日額交通費などの元情報を保持する。
- 往診売上系テーブルは、往診手当、管理手当、委託報酬帳票などの元情報を保持する。
- 帳票は原則として、計算済みDBの値を表示する。帳票用に別計算しない。

## Controller一覧

- `PayrollV2Controller`
  - 給与計算、賞与計算、賃金台帳、振込一覧、明細、会社負担一覧、往診系帳票を扱う中心。
- `AttendanceV2Controller`
  - 勤怠反映、有休反映、勤怠確定状態に関係する。
- `StaffV2Controller`
  - スタッフマスタ、給与マスタ、雇用区分、交通費、社保、扶養に関係する。

## Service一覧

- `PayrollV2UpdateService`
  - 保存時の合計再計算、手入力値の保存、給与・賞与共通の集計ルールに関係する。
- `PayrollV2RecalculateService`
  - 再計算ボタンの処理に関係する。
- `PayrollV2CalculationFlowService`
  - ボタンごとの計算順序、給与マスタ再取込の抑制に関係する。
- `PayrollV2SocialInsuranceAmountService`
  - 社保合計、子ども支援金を含む社会保険系の共通計算に関係する。
- `PayrollV2IncomeTaxService`
  - 給与の所得税計算に関係する。
- `PayrollV2BonusIncomeTaxCalcService`
  - 賞与の所得税計算に関係する。
- `PayrollV2BonusSocialInsuranceService`
  - 賞与の社会保険計算に関係する。
- `PayrollV2EmploymentInsuranceService`
  - 雇用保険計算に関係する。
- `PayrollV2HomeVisitAllowanceService`
  - 往診手当、管理手当に関係する。
- `PayrollV2SalesImportService`
  - 給与計算へ売上系データを反映する処理に関係する。

## Trait一覧

現時点で給与・賞与専用の Trait は中心にしない。

Trait を追加する場合は、ControllerやServiceより先に触らない。共通化の最後の手段として扱う。

## View一覧

- `resources/views/admin_v2/work/payroll/index.blade.php`
  - 給与計算メイン画面。
- `resources/views/admin_v2/work/payroll/page_script.blade.php`
  - 給与計算画面のJS。
- `resources/views/admin_v2/work/payroll/page_state_runtime.php`
  - 給与計算画面の表示状態、集計状態。
- `resources/views/admin_v2/work/bonus/index.blade.php`
  - 賞与計算メイン画面。
- `resources/views/admin_v2/work/bonus/page_script.blade.php`
  - 賞与計算画面のJS。
- `resources/views/admin_v2/work/wage_ledger/index.blade.php`
  - 賃金台帳。
- `resources/views/admin_v2/work/wage_ledger/personal.blade.php`
  - 個人賃金台帳。
- `resources/views/admin_v2/work/transfer_list/index.blade.php`
  - 振込一覧。
- `resources/views/shared/payroll/payslip_item.blade.php`
  - 明細の共通部品。

## CSS一覧

- `public/css/admin_v2/app-frame.css`
  - 管理画面全体の枠、ナビ、共通フレーム。
- `public/css/admin_v2/app-ui.css`
  - 全ページで使えるボタン、入力、テーブルなどの共通UI。
- `public/css/admin_v2/payroll.css`
  - 給与・賞与・賃金台帳・明細など、給与系に閉じた共通UI。
- Blade内の `<style>`
  - そのページでしか使わない微調整だけ。

## 各画面のデータの流れ

### 給与計算

1. 対象年月、スタッフ、雇用区分を取得する。
2. スタッフマスタ、給与マスタ、勤怠、売上、住民税、社保、扶養を参照する。
3. ボタンごとの計算処理で給与DBへ保存する。
4. 保存ボタンでは、手入力値を保持したまま合計を再計算する。
5. 帳票は給与DBの保存値を表示する。

### 賞与計算

1. 対象支給日、スタッフ、雇用区分を取得する。
2. 賞与額、社保、所得税を計算する。
3. 給与と同じ意味の合計は共通Serviceを使う。
4. 帳票は賞与DBの保存値を表示する。

### 賃金台帳

1. 給与DB、賞与DBの保存値を取得する。
2. 表示用に別計算しない。
3. DBの小数値や金額を勝手に丸めない。

### 明細・振込一覧

1. 給与DB、賞与DBの保存値を取得する。
2. 表示に必要な整形だけ行う。
3. 支給、控除、差引支給額の再計算を帳票側で行わない。
