SET NOCOUNT ON;
GO

/*
目的:
- 3DB（TCPGSYSTEM_DEV/Payroll_DEV/DailyReport_DEV）のインデックス総点検（2026-08-15）で、
  Payroll_DEV.mx_hoken / mx_nen_tyo にPK以外の索引が無いことが判明したため追加する。

対象: Payroll_DEV.dbo.mx_hoken, Payroll_DEV.dbo.mx_nen_tyo

追加理由:
- mx_hoken: `insurance_staff_no` + `insurance_year` で絞る問い合わせが
  YearEndAdjustmentV2Controller・YearEndApplicationController（年調保険控除の
  一覧・登録・削除・確認済みフラグ更新等）に多数あり、PKのみだと毎回全件スキャンになる。
- mx_nen_tyo: `staff_id` + `year_end` で「今年度の行を取得、無ければ作成」という
  パターンが管理・スタッフポータル双方で繰り返し使われている。年末調整の中心テーブル。
*/

CREATE NONCLUSTERED INDEX IX_mx_hoken_staff_year
    ON dbo.mx_hoken (insurance_staff_no, insurance_year);

CREATE NONCLUSTERED INDEX IX_mx_nen_tyo_staff_year_end
    ON dbo.mx_nen_tyo (staff_id, year_end);
GO
