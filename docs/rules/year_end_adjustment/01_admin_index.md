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
作成先は `staff_year_end_applications`。
既に同じ年・同じスタッフの対象者がある場合はスキップする。

## 対象者条件

- 現在有効なスタッフを対象にする。
- `staff_division` に `業務委託` を含むスタッフは対象外。
- 退職日が空、または現在日以降のスタッフを対象にする。

## ステータス

使用中の状態は以下。

- `draft`: 下書き
- `submitted`: 提出済
- `returned`: 差戻し
- `confirmed`: 確認済
- `reflected`: 反映済
- `excluded`: 対象外
- `retired`: 退職済

ステータスは申請状況であり、`mx_nen_tyo.edit_lock` の処理済とは別物。