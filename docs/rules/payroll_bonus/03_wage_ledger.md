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
- 非課税通勤費(`allowance_amo_6`)と非課税通勤費加算(`traffic_addition`)は1行に合算表示する
  （給与明細と同じ扱い）。課税通勤費(`allowance_amo_10`)は賃金台帳では合算せず別行のまま。
  行の生成元は`mx_allowance`マスタ駆動（`PayrollV2AllowanceLabelService::entries()`）なので、
  合算・非表示にする場合は`PayrollV2Controller`側の値合算とblade側`excludedAllowanceKeys`の
  両方を揃える必要がある（2026-08-18）。
  委託報酬台帳（`outsource_reward_ledger_print.blade.php`）は別基準で、課税/非課税を問わず
  `allowance_amo_10`＋`allowance_amo_6`＋`traffic_addition`を「交通費」1行に合算する。
  この合算処理は`PayrollV2Controller::mergeTrafficAdditionIntoNonTaxableCommuting()`に
  1箇所へ統合済み（賃金台帳一覧・個人賃金台帳の両方から呼ぶ。以前は同じ処理が2箇所に
  別々に書かれていた、2026-08-18）。

## 差引支給額（supply_deduction_sum）は保存値、帳票では計算しない

差引支給額の計算式（支給合計 - 控除合計 - 年末調整 + 立替経費精算 + 会社立替費用 - 振込残額）は
`PayrollV2SummaryService::transferAmount()`が正本。ただし**この式を呼ぶのは保存処理
（`PayrollV2UpdateService::rebuildTotals()`／`rebuildBonusTotals()`）だけ**で、計算結果を
`mx_kyuyo_shou.supply_deduction_sum`に保存する。表示側（Controller・帳票・スタッフポータル）は
`transferAmount()`を直接呼ばず、保存済みの`supply_deduction_sum`（`transfer_amount`として
正規化される）を読むだけにする。

一時期、保存を経由せず表示のたびに`transferAmount()`をライブ計算する形になっていて、
`supply_deduction_sum`列への書き込みが失われていた（2026年2月分を最後に、それ以降のレコードで
一度も保存されていなかった）。誰にも確認されないまま「帳票側で再計算しない」というこの
ルール自体に反する状態になっていたため、2026年8月に保存する形へ戻した。過去データ（〜2026年2月）は
本番サーバーからの同期で埋まる想定のため、Laravel側でのバックフィルはしていない。

## 禁止

- DBの値を賃金台帳用に別加工して保存しない。
- 指示なしに小数を整数へ丸めない。
- 使用中のDBカラム名を旧名へ戻さない。