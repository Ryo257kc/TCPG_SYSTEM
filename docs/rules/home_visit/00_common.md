# 往診 共通ルール

## 対象画面

- `/staff/home_visit/daily_report`
- `/staff/home_visit/monthly_report`
- `/staff/home_visit/monthly_visit`
- `/staff/home_visit/patients`
- `/staff/home_visit/receipts`
- `/staff/home_visit/receipt-summary`
- `/staff/home_visit/sales`
- `/staff/hv_office/*`

## 基本方針

往診は日々の往診日報を入力し、月次、給与、売上、距離、未収金、帳票へつなげる。
同じ数値を複数箇所で別計算しない。

## データの扱い

日報の保存値を基準にする。
月次や帳票は日報の保存値、または確定済みの月次保存値を表示する。
給与や売上側で往診日報を独自に作り直さない。

## 未収金

未収金は `uncollected_amount` を使う。
別日の未収金を無視しない。
間違って複数日に入っている場合は、集計から隠さず見える状態にして原因確認できるようにする。

## 触らないこと

- 一度一致確認済みの売上・距離・人数ロジックを別件で変更しない。
- 日付が違う往診データを勝手に重複扱いしない。
- `unpaid_amo` など別用途のカラムを未収金として使わない。
