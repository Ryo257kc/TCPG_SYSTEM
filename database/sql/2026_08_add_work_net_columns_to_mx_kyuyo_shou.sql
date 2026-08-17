SET NOCOUNT ON;
GO

/*
目的:
- work_in_num（出勤日数）・work_time（実働時間）はAccess時代から「休日出勤分も含めた
  総勤務日数・総実働時間」として明細・賃金台帳に出力され続けており、休日出勤分は
  work_horiday_num（休日出勤日数）・work_time_num（休日出勤時間）として別行で内訳表示
  される設計。既存の合算の意味を変えると過去の明細と数字の意味が変わってしまうため、
  work_in_num/work_timeはそのまま維持する（2026-08-17）。
- 一方で「休日出勤を除いた出勤日数・実働時間」だけを見たい場面のために、既存列とは別に
  新規列を追加する。既存データを一切書き換えない、純粋な追加。

対象: Payroll_DEV.dbo.mx_kyuyo_shou（本番公開前のため、本番Payrollにも同様に追加予定）

追加理由:
- work_in_num_net: 出勤日数（休日出勤除く）。work_in_numからwork_horiday_num相当を
  差し引いた値に相当。
- work_time_net: 実働時間（休日出勤除く）。work_timeからwork_time_num相当を
  差し引いた値に相当。
- 型はwork_in_num/work_timeと同じreal, NULL許容。
*/

ALTER TABLE dbo.mx_kyuyo_shou ADD work_in_num_net real NULL;
GO
ALTER TABLE dbo.mx_kyuyo_shou ADD work_time_net real NULL;
GO
