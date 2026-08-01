# 給与・賞与 変更履歴

## 2026-08-01

### 整理

- 給与・賞与の共通CSSを `payroll.css` に整理。
- 賞与画面の入力欄幅を給与と同じにした。
- 賞与画面の基本情報ボックスが横幅を吸いすぎないように調整。
- 旧 `bonus.css` 参照を削除。

### 計算

- 給与・賞与の社保合計、控除合計を `PayrollV2UpdateService` に寄せた。
- 子ども支援金を社保合計に含めるよう整理。
- 賞与保存を `saveBonus()` に寄せた。

### DB

- `officer_com` を `x_officer_com` に退避。
- `allowance_2` を `x_allowance_2` に退避。
- レセDBの `x_department_no_old`、`x_staff_name_old` は退避済みとして確認。
- `store_name_old` は存在しないことを確認。

### 役員報酬

- 現在の役員報酬は `allowance_amo_2`。
- `allowance_2`、`officer_com` は旧カラム扱い。