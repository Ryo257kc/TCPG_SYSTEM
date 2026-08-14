# 年末調整 DB役割

## mx_nen_tyo

年調計算結果テーブル（Access時代の年調テーブルに相当、帳票や源泉徴収簿へ表示する計算結果を
保存する）と、年調申請・対象者管理（申請の状態、本人回答、変更有無、提出日時、確認日時など）を
兼ねる。2026年8月に`staff_year_end_applications`を廃止して統合した（旧テーブルは実データ上は
`x_staff_year_end_applications`にリネーム済みで参照されていない。物理削除はまだしていない）。

申請管理まわりの主な列:

- `application_status` / `submitted_at` / `confirmed_at` / `reflected_at` / `return_note`
- `personal_info_changed` / `dependents_changed` / `insurance_deduction_changed` /
  `housing_loan_changed` / `previous_job_withholding_changed` / `spouse_changed` /
  `special_collection_requested` / `previous_job_already_submitted`
- `new_address` / `new_address_furi` / `new_staff_name` / `new_staff_name_furi` / `new_birthday`
  （氏名・住所等の変更申告。mx_staffsへの反映は「反映」操作を経由するステージング）
- `address_change_certificate_*` / `name_change_certificate_*`（証憑ファイル、3列×2種）

計算結果まわりの主な列（従来通り）は、`nen_tyo_no`（PK・唯一の識別子）、`staff_id`、`year_end`、
`edit_lock`（処理済ロック）、その他約80の計算用カラム。

## mx_hoken

保険料控除の入力・確認テーブル。
証明書添付パスもここへ持つ。
保険年度は対象年で自動設定する。

## mx_fuyo

扶養家族テーブル。
年度ごとに行を持つ。
削除ではなく、控除対象チェックや年度行で判断する運用が基本。
ただし年調保険と違い、扶養情報はスタッフマスタでも使うため、勝手に削除しない。

## mx_staffs

スタッフの基本情報、就業状況、入退社日を参照する。
業務委託は年調対象外。