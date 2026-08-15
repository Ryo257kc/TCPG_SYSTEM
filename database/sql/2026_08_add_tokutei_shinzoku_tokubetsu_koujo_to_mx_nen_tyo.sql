SET NOCOUNT ON;
GO

/*
目的:
- 特定親族特別控除（国税庁タックスアンサーNo.1177、令和7年分〜の新控除。
  https://www.nta.go.jp/taxes/shiraberu/taxanswer/shotoku/1177.htm ）に対応する列が
  mx_nen_tyoに無かったため追加する。DB・計算ロジック・帳票のいずれにも未実装だった
  （2026-08-15判明）。対象は19〜23歳未満・合計所得123万円以下・控除対象扶養親族に
  非該当の親族等で、既存の「特定扶養親族」（toku_fu列）と対象年齢帯が重なる。

対象: Payroll_DEV.dbo.mx_nen_tyo（本番Payrollも同様に追加が必要）

追加理由:
- 既存のhaigu_deduction（配偶者控除）／haigu_toku_deduction（配偶者特別控除）と
  同じ型（money, NULL許容）で、控除額を保存する列として追加。
- 計算ロジック（所得区分ごとの控除額テーブル）はまだ未実装。対象者が実際にいるか
  確認できてから着手する。
*/

ALTER TABLE dbo.mx_nen_tyo ADD tokutei_shinzoku_tokubetsu_koujo money NULL;
GO
