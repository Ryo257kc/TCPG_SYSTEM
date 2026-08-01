# DBカラム整理ルール

## 基本方針

- 使わない候補はすぐ削除しない。
- まずDB上で `x_` を頭につけて退避する。
- Laravelコードからは退避カラムを完全に参照しない。
- 運用でエラーが出ない期間を置いてから削除する。

## 退避済み

給与DB:

- `officer_com` -> `x_officer_com`
- `allowance_2` -> `x_allowance_2`

レセDB:

- `department_no_old` -> `x_department_no_old`
- `staff_name_old` -> `x_staff_name_old`

存在確認済み:

- `store_name_old` は確認時点で存在なし。

## リネーム時のSQL例

```sql
EXEC sp_rename 'dbo.mx_kyuyo_shou.officer_com', 'x_officer_com', 'COLUMN';
EXEC sp_rename 'dbo.mx_kyuyo_shou.allowance_2', 'x_allowance_2', 'COLUMN';
```

## 運用中に出る可能性があるエラー

```text
列名 'officer_com' が無効です
列名 'allowance_2' が無効です
Invalid column name 'officer_com'
Invalid column name 'allowance_2'
```

このエラーが出た場合は、出た画面、操作、対象年月、スタッフを記録してからコード側を確認する。

## 削除前チェック

- `x_` カラム名でコード検索して参照がないこと。
- 運用で該当エラーが出ていないこと。
- 必要なら退避カラムの値を別途バックアップしていること。