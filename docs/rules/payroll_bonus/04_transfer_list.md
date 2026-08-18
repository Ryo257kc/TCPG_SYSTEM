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

## 振込残額（口座2への分割振込）

`mx_kyuyo_shou.transfer_balance`（振込残額）が設定されているスタッフは、第2口座
（`bank_name_2`等）へその分だけ分けて振り込む。両口座の登録がある場合のみ、表示上だけ2行に
割る（`buildTransferListView()`の`secondary_account`、2026-08-18）。集計用の1スタッフ1行は
そのまま維持し（ソート・人数カウント・住民税/課税対象額/所得税の二重計上を避けるため）、
blade側で口座1行の直後に口座2の内訳行（氏名・部署は空欄、網掛け、`(メイン)`等の
`transfer_purpose`を口座番号の横に表示）を追加描画する。口座1の表示額は
`transfer_amount - secondary_account.amount`。`transfer_amount`自体は口座分割前の総額のまま
保存されている（`PayrollV2SummaryService::transferAmount()`はtransfer_balanceを引かない）。

## 業務委託の扱い（会社合計サマリーのみ除外）

「会社合計」ブロックの住民税・所得税・課税対象額合計は、委託を除いた社員・パートの納税
（住民税特別徴収・源泉所得税の納付）で使う数字。業務委託は事業所得扱いで対象外のため、
このサマリーだけ除外する。振込自体は行うため個別行・振込支払額合計・市町村別サマリーには
業務委託も含めたまま（`buildTransferListView()`の`$isOutsourceRow`判定、2026-08-18）。
実データ確認：2026/8/20支払・㈱トータルケアで業務委託3名分(770,745円)を含んだ2,945,491円が
表示されていたのを2,174,746円に修正。