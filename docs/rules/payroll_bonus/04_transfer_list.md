# 振込一覧 ルール

対象:

- `resources/views/admin_v2/work/transfer_list/index.blade.php`
- `PayrollV2Controller::transferList()`
- `PayrollV2Controller::bonusTransferList()`

## 基本方針

- 給与と賞与で共通帳票を使う。
- 振込額は保存済みの支給合計、控除合計、振込残額をもとに表示する。
- 住民税、市区町村、指定番号の表示はスタッフマスタ・市区町村マスタを確認する。

## 注意

- 紹介派遣など、集計対象外の扱いはAccess運用時の条件に合わせる。
- 銀行情報は第1口座を基本にし、必要時に第2口座を使う既存ルールを守る。
- 給与と賞与で違う集計にしない。