SET NOCOUNT ON;
GO

/*
目的:
- 仕訳帳CSV取込の作り直し（2026-08-15）で journal_breakdown/occurred_at が
  グループ突合の主キーになったこと、金額検索（debit_amount/credit_amount）に
  インデックスが無く全件スキャンになっていたことを確認したため、不足していた
  インデックスを追加する。

対象: TCPGSYSTEM_DEV.dbo.mx_journal_entries

追加理由の内訳:
- (company_name_short, occurred_at): CSV取込のグループ突合で使う
  一括取得クエリ（company_name_short一致 + occurred_at範囲）に対応
- journal_breakdown: グループ突合の完全一致検索、および仕訳内訳の
  オートコンプリート候補取得（GROUP BY）に対応
- debit_amount / credit_amount: 一覧の金額検索フィルター（完全一致）、
  および金額のオートコンプリート候補取得（GROUP BY）に対応
- debit_department_name: credit_department_name に対応する索引が
  既にあったのに借方側だけ無かった非対称を解消（部門のオートコンプリート候補用）
- debit_account_title / debit_counterparty / credit_counterparty /
  management_number: それぞれのオートコンプリート候補取得（GROUP BY）に対応
  （credit_account_titleは既存のcredit_item_name複合索引でカバー済み）

注意:
- CSV取込は数百行単位でINSERT/UPDATEするため、索引が増えた分だけ書き込みは
  わずかに遅くなる。読み取り（一覧表示・検索・取込時の突合）を優先した判断。
*/

CREATE NONCLUSTERED INDEX IX_mx_journal_entries_company_occurred
    ON dbo.mx_journal_entries (company_name_short, occurred_at);

CREATE NONCLUSTERED INDEX IX_mx_journal_entries_journal_breakdown
    ON dbo.mx_journal_entries (journal_breakdown);

CREATE NONCLUSTERED INDEX IX_mx_journal_entries_debit_amount
    ON dbo.mx_journal_entries (debit_amount);

CREATE NONCLUSTERED INDEX IX_mx_journal_entries_credit_amount
    ON dbo.mx_journal_entries (credit_amount);

CREATE NONCLUSTERED INDEX IX_mx_journal_entries_debit_department_name
    ON dbo.mx_journal_entries (debit_department_name, month_date);

CREATE NONCLUSTERED INDEX IX_mx_journal_entries_debit_account_title
    ON dbo.mx_journal_entries (debit_account_title);

CREATE NONCLUSTERED INDEX IX_mx_journal_entries_debit_counterparty
    ON dbo.mx_journal_entries (debit_counterparty);

CREATE NONCLUSTERED INDEX IX_mx_journal_entries_credit_counterparty
    ON dbo.mx_journal_entries (credit_counterparty);

CREATE NONCLUSTERED INDEX IX_mx_journal_entries_management_number
    ON dbo.mx_journal_entries (management_number);
GO
