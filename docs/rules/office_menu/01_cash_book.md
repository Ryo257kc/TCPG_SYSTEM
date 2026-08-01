# 現金出納帳ルール

## 対象

- `/staff/office/office_menu/cash_book`
- `app/Http/Controllers/StaffPortal/office/CashBookController.php`
- `resources/views/staff_portal/office/office_menu/cash_book/index.blade.php`
- `dbo.mx_journal_entries`
- `dbo.mx_monthly_closings`

## 役割

現金出納帳は、小口現金の入金・出金を仕訳テーブルで管理する画面。
対象は金庫ごとに表示し、月次確定後は該当月の編集・削除を止める。

## 表示条件

- `vault_name` が金庫名のデータを対象にする。
- 金庫を選択していない場合は明細を表示しない。
- 対象月の絞り込みは `month_date` を使う。
- 前月繰越は、月初の残高として扱う。

## 入出金ルール

- 入金は `deposit_amount`、借方勘定科目 `小口現金`。
- 出金は `withdrawal_amount`、貸方勘定科目 `小口現金`。
- 金庫名から会社・部門名を解決して保存する。
- 手入力保存では、売上やレセの仕訳を補完しない。

## 月次処理

- 月次確定時に、翌月1日の前月繰越仕訳を金庫ごとに作る。
- 繰越仕訳は `小口現金` / `前月繰越`。
- 月次確定情報は `mx_monthly_closings` に `authority = 経理` で保存する。
- 既に月次確定済の月は再確定しない。

## 月次解除

- 管理者のみ実行できる。
- 解除対象は `mx_monthly_closings` の現金出納帳月次確定行。
- 他の月次処理、売上仕訳、レセ仕訳、往診仕訳は消さない。

## 禁止

- 現金出納帳側で売上や未収入金を再計算しない。
- 月次確定済の月を保存・削除しない。
- 金庫別の数字を勝手に合算しない。
- 返金、売上、レセ、往診の仕訳と同じ削除条件にしない。
- 条件が不明な場合は、変更前に確認する。
