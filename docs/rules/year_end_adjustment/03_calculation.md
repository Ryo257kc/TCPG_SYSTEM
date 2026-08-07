# 年末調整 計算ルール

## 基本方針

年調計算は `mx_nen_tyo` に結果を保存する。
給与・賞与の過去データを年調計算のために書き換えない。
給与改定や法改定で過去が変わらないよう、給与テーブルと年調計算結果は保存済みの値を参照する。

## 実行

- 1件ごとの再計算ボタンで計算する。
- Controller: `YearEndAdjustmentV2Controller@calculateSingle`
- 計算本体: `calculateYearEndPayload`

## 処理済ロック

`mx_nen_tyo.edit_lock = 1` は処理済。
処理済の場合は再計算しない。
退職源泉などで確定済みの人も処理済として扱う。

## 参照元

- 給与・賞与集計: `mx_kyuyo_shou`
- 扶養集計: `mx_fuyo`
- 年調計算結果: `mx_nen_tyo`
- スタッフ就業状況: `mx_staffs`

## 注意

年調申請ステータスと、計算処理済は別。
`staff_year_end_applications.status` を変えても、`mx_nen_tyo.edit_lock` を勝手に変えない。