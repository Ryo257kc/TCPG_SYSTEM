# Column Mapping: `t_yukyu` -> `mx_yukyu`

最終更新: 2026-03-23

この表は、旧 `Payroll.dbo.t_yukyu` と現 `Payroll_DEV.dbo.mx_yukyu` の順番比較です。  
現時点では、新旧でカラム構成は同一です。

## 方針

- `mx_yukyu` は有休の取得・使用・消滅履歴を持つ
- 勤怠反映時に `date_use` / `days_used` が入る前提
- 残数や消滅日数は、この履歴を元に計算して給与明細へ反映する

## カラム比較

| No | 旧DBカラム | 現DBカラム | 型 | 備考 |
|---|---|---|---|---|
| 1 | `yukyu_no` | `yukyu_no` | `int` | |
| 2 | `staff_id` | `staff_id` | `nvarchar(4)` | |
| 3 | `remaining_day` | `remaining_day` | `real` | 残日数 |
| 4 | `addition_day` | `addition_day` | `datetime` | 加算日 |
| 5 | `date_use` | `date_use` | `datetime` | 使用日 |
| 6 | `days_used` | `days_used` | `real` | 使用日数 |
| 7 | `extinction_day` | `extinction_day` | `datetime` | 消滅予定日 |
| 8 | `lost_num` | `lost_num` | `real` | 消滅日数 |

## 確認メモ

- 新旧カラム順・名称・型は一致
- まずはこの構成を正として `有休管理` を組み立てる
- `mx_yukyu` 単体ではなく、`mx_staffs` と勤怠の有休使用実績をあわせて運用する
