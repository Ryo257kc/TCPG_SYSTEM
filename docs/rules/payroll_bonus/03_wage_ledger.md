# 賃金台帳 ルール

対象:

- `resources/views/admin_v2/work/wage_ledger/index.blade.php`
- `resources/views/admin_v2/work/wage_ledger/personal.blade.php`
- `PayrollV2Controller::wageLedger()`
- `PayrollV2Controller::bonusWageLedger()`
- `PayrollV2Controller::personalWageLedger()`

## 基本方針

- 賃金台帳はDBに保存された給与・賞与データを表示する。
- 賃金台帳用に別の計算データを作らない。
- 表示時に勝手に丸めない。小数が必要な項目は小数表示する。
- 給与と賞与で同じ行定義を使える項目は同じ表示ルールにする。
- 賃金台帳の対象者判定は給与・賞与で共通化する。業務委託は給与・賞与どちらの賃金台帳にも表示しない。


## 必須表示

- 役員報酬は `allowance_amo_2` を表示する。
- 子ども支援金を表示する。
- 差引支給額には立替経費、会社立替、振込残額などの扱いを計算根拠に合わせる。
- 欠勤、遅早控除はマイナス値として保存・表示する。

## 禁止

- DBの値を賃金台帳用に別加工して保存しない。
- 指示なしに小数を整数へ丸めない。
- 使用中のDBカラム名を旧名へ戻さない。