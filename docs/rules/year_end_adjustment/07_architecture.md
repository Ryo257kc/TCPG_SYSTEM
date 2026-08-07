# 年末調整 構成

## Controller

- `app/Http/Controllers/Admin/V2/YearEndAdjustmentV2Controller.php`

## View

- `resources/views/admin_v2/work/year_end_adjustments/index.blade.php`
- `resources/views/admin_v2/work/year_end_adjustments/show.blade.php`
- `resources/views/staff_portal/dashboard/index.blade.php`

## ルート

- `GET /admin/year-end-adjustments`
- `POST /admin/year-end-adjustments/create-targets`
- `GET /admin/year-end-adjustments/{applicationId}`
- `POST /admin/year-end-adjustments/update-status`
- `POST /admin/year-end-adjustments/delete-target`
- `POST /admin/year-end-adjustments/{applicationId}/calculate`
- `GET /admin/year-end-adjustments/{applicationId}/hoken-preview`
- `POST /admin/year-end-adjustments/{applicationId}/hoken`
- `POST /admin/year-end-adjustments/{applicationId}/hoken/{hokenNo}`
- `POST /admin/year-end-adjustments/{applicationId}/hoken/{hokenNo}/delete`

## データの流れ

1. 管理一覧で対象年を選ぶ。
2. 対象者作成で `staff_year_end_applications` を作る。
3. 詳細でスタッフ情報、扶養、保険、年調計算テーブルを確認する。
4. 保険情報を管理側で追加・編集・削除する。
5. 必要に応じて保険料控除申告書PDFをプレビューする。
6. 1件再計算で `mx_nen_tyo` へ計算結果を保存する。
7. 処理済ロック後は再計算しない。