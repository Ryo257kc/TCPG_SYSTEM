# Column Mapping: Payroll_DEV Checked Tables

最終更新: 2026-03-23

この資料は、`Payroll_DEV` でここまで確認できたテーブルの要点メモです。
詳細比較は必要に応じて個別ファイルへ分けていきます。

## `t_fuyo` -> `mx_fuyo`

- 新旧カラム: 完全一致
- 順番: 一致
- 主キー: なし
- 主キー候補: `fuyo_no`
- 備考: 現時点では構造差分なし

## `t_kihon` -> `mx_kihon`

- 新旧カラム: 完全一致
- 順番: 一致
- 主キー: なし
- 主キー候補: `kihon_no`
- 備考: 給与マスタの基本テーブル

## `t_kyuyo_shou` -> `mx_kyuyo_shou`

- 主キー: `kyuyo_sho_no` あり
- インデックス:
  - `(bonus, supply_month)`
  - `(kyuyo_staff_id, supply_month)`
- 差分:
  - `振込残額` -> `transfer_balance`
  - `会社立替費用` -> `company_advance_cost`
  - 現DBのみ `attendance_checked`

## `t_mayor` -> `mx_mayor`

- 主キー: `mayor_no` あり
- 差分:
  - `office_name` -> `office_no`
- 備考: この差分は意図して変更したもの

## `t_resident` -> `mx_resident`

- 新旧差分:
  - `宛名番号` -> `addressee_no`
- 主キー: なし
- 主キー候補: `resident_no`

## `t_rouho` -> `mx_rouho`

- 主キー: なし
- 主キー候補: `rou_no`
- 差分:
  - `office_no` 型変更
  - 旧: `nvarchar(3)`
  - 新: `int`

## `t_staff_shou` -> `mx_staff_shou`

- 新旧カラム: 完全一致
- 順番: 一致
- 主キー: なし
- 主キー候補: `staff_shou_no`

## `t_syaho` -> `mx_syaho`

- 主キー: なし
- 主キー候補: `syaho_no`
- 差分:
  - `office_no` 型変更
  - 旧: `nvarchar(3)`
  - 新: `int`

## `t_allowance` -> `mx_allowance`

- 主キー: なし
- UNIQUE:
  - `(office_name, allowance_no)`
- 旧DBとの差分:
  - 現DBのみ `amount_column_key`
  - 現DBのみ `display_order`
  - 現DBのみ `slot_no`
  - `office_name` 型変更
  - 順番も変更あり
- 備考: 旧DBをそのまま移したのではなく、新DB側で拡張している

## `t_atena` -> `mx_atena`

- 新旧カラム: 完全一致
- 順番: 一致
- 主キー: なし
- 主キー候補: `atena_no`

## `t_deduction_shou` -> `mx_deduction_shou`

- 新旧カラム: 完全一致
- 順番: 一致
- 主キー: なし
- 主キー候補: `deduction_shou_no`

## `t_hoken` -> `mx_hoken`

- 順番: 一致
- 型: 一致
- 差分: 日本語列名の英語化
  - `保険会社` -> `insurance_company`
  - `区分` -> `category`
  - `適用制度` -> `applied_system`
  - `申告額` -> `declared_amount`
  - `保険労働者No` -> `insurance_staff_no`
  - `保険年度` -> `insurance_year`
  - `保険種類` -> `insurance_type`
  - `保険期間` -> `insurance_period`
  - `契約者` -> `policy_holder_name`
  - `受取人` -> `beneficiary_name`
  - `受取人続柄` -> `beneficiary_relationship`
  - `年金支払開始日` -> `pension_payment_start_date`
  - `確認済チェック` -> `checked_flag`
  - `年調保険備考` -> `year_end_insurance_note`
- 主キー: なし
- 主キー候補: `hoken_no`

## `t_nen_tyo` -> `mx_nen_tyo`

- ほぼ完全一致
- 差分:
  - `16miman_fuyo` -> `dependent_under_16`
- 主キー: なし
- 主キー候補: `nen_tyo_no`

## 補足

- `mx_holiday` は不要候補として一旦比較対象外
- `mx_yukyu` の詳細比較は `column_mapping_mx_yukyu.md` を参照
