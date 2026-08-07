# 年末調整 詳細・保険

## 画面

- ルート: `/admin/year-end-adjustments/{applicationId}`
- Controller: `YearEndAdjustmentV2Controller@show`
- View: `resources/views/admin_v2/work/year_end_adjustments/show.blade.php`

## 詳細画面の役割

- 申請状況を確認する。
- スタッフ情報を確認する。
- `mx_nen_tyo` の計算結果を確認する。
- `mx_fuyo` の扶養情報を確認する。
- `mx_hoken` の保険情報を追加・編集・削除する。
- 保険料控除申告書PDFをプレビューする。

## 保険情報

保険情報は `mx_hoken` に保存する。
スタッフ本人が入力する想定の項目でも、管理側で修正できるようにしておく。
前年コピーや本人確認の運用を考慮し、添付証明書は毎年必須扱いにする。

## 保険証明書添付

保存先は `public/uploads/year_end/{targetYear}/{staffId}/insurance/`。
DBには `certificate_file_path`、`certificate_original_name`、`certificate_uploaded_at` を保存する。

許可する形式は PDF、JPG、JPEG、PNG。
HEICは受け付けない。
画像は読める範囲で圧縮する。

## 削除

保険情報は不要になったら削除でよい。
解約済みの保険を残すと翌年コピーや計算に混ざるリスクがあるため、不要な行は残さない。