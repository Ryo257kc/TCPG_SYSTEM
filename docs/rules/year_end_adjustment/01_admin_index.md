# 年末調整 管理一覧

## 画面

- ルート: `/admin/year-end-adjustments`
- Controller: `App\Http\Controllers\Admin\V2\YearEndAdjustmentV2Controller@index`
- View: `resources/views/admin_v2/work/year_end_adjustments/index.blade.php`

## 役割

- 対象年ごとの年調対象者を一覧する。
- 対象者作成を行う。
- ステータスを変更する。
- 下書き対象者は削除できる。

## 対象者作成

対象者作成は `createTargets` で行う。
作成先は `mx_nen_tyo`（`application_status='draft'`で新規行をINSERT）。
既に同じ年・同じスタッフの`mx_nen_tyo`行がある場合はスキップする。

## 対象者条件

- 現在有効なスタッフを対象にする。
- `staff_division` に `業務委託` を含むスタッフは対象外。
- 退職日が空、または現在日以降のスタッフを対象にする。

## ステータス

使用中の状態は以下。

- `draft`: 未提出（`resolveApplicationStatus()`が`application_status`空欄時に`edit_lock`から補完する。
  下書きのうち、`*_changed`系カラムのどれかに値が入っていれば画面表示だけ「入力中」に分ける）
- `submitted`: 提出済
- `returned`: 差戻し
- `confirmed`: 確認済
- `excluded`: 対象外
- `retired`: 退職済

`reflected`（反映済）は2026年8月に廃止。「反映」（氏名・住所等をmx_staffsへ書き込む操作）は
ステータスを進めず、`confirmed`のまま`reflected_at`だけ更新する。確認済なのに未反映の行は、
詳細画面で`needs_reflect`（`confirmed_at`が`reflected_at`より新しい、または`reflected_at`が
空）を見て警告バッジを出す。理由：反映は機械的な処理で確認と区別する意味がなく、かつ
「変更なしスタッフ」に不要な反映操作を強いらないため。

ステータスは申請状況であり、`mx_nen_tyo.edit_lock`（計算処理済ロック）とは別物。ただし
`confirmApplication()`は両方を1回のUPDATEで連動して更新する（`application_status='confirmed'`と
`edit_lock=1`を同時に立てる）。