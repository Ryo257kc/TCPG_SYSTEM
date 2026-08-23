# 管理マスタ 変更履歴

## 2026-08-15 mx_staffs 本番→DEV全件同期と同期除外列の確定

`tests/DB同期有無一覧.xlsx`(「テーブル同期」シート)にあった検証済みSQLを使い、本番`TCPGSYSTEM.dbo.staff`から開発`TCPGSYSTEM_DEV.dbo.mx_staffs`へ全件同期を実施。実行前に`dbo.mx_staffs_backup_20260815`へ同期前の全件をバックアップ済み。検証済みの同期SQLは`database/sql/2026_08_sync_mx_staffs_from_tcpgsystem.sql`としてリポジトリに保存済み(Excelは手動更新でズレるため、以後はこのSQLファイルを正とする)。

**同期してはいけない列(今後の同期でも必ず除外):**
- `is_admin`・`mail`: Laravel専用、本番に対応列なし
- `addressee_no`/`submission`: 住民税の宛名番号・提出先。年度履歴管理のため`mx_resident`へ移設済み(`docs/rules/year_end_adjustment/12_change_log.md`参照)
- **権限8列**(`oushin_staff`・`is_accounting_user`・`is_payment_check_user`・`is_visit_management_user`・`is_view_only_user`・`is_store_management_user`・`is_daily_report_user`・`front_staff`): `StaffV2Service::permissionColumns()`でLaravel側の権限編集画面から直接更新される現役の項目。**初回同期でこれを含めてしまい、Laravel側で設定済みだった4名分の権限変更を本番の古い値で上書きする事故が発生**(バックアップから復旧済み)。

**教訓:** 「本番から同期」は住所・氏名等の基本情報には正しいが、`mx_staffs`は既にLaravel側が権限管理の主体になっている列を抱えているため、テーブル単位ではなく「どちらが正(source of truth)か」を列ごとに判断する必要がある。
