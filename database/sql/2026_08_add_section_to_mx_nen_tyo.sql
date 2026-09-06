SET NOCOUNT ON;
GO

/*
目的:
- 年末調整（mx_nen_tyo）の帳票（源泉徴収票・賃金台帳・扶養控除申告書等）が、
  スタッフの「今の」所属（mx_staffs.section）から会社・店舗を解決していた
  （YearEndAdjustmentV2Controller::staffDetail()）。年調は是正で過去年度を
  やり直す前提の機能なのに、対象年度時点の所属を保存する列が無かった。
  転籍した人の過去年度の年調書類が、転籍後の今の会社名で印刷される可能性が
  あった（給与のmx_kyuyo_shou.sectionで既に修正済みの問題と同じパターン、
  2026-08-24判明）。

対象: Payroll_DEV.dbo.mx_nen_tyo（本番Payrollも同様に追加が必要）

追加理由:
- mx_kyuyo_shou.sectionと同じ型・桁数（nvarchar(3)、店舗コード）で、その年調
  対象年度の対象者作成時点（createTargets()実行時点）の所属を保存する列として追加。
- 既存231行（2015〜2026年、目視確認済み）は過去の所属を記録した履歴が
  存在しないため、バックフィルは「その時点の在籍会社」ではなく「今の
  mx_staffs.section」を暫定値として入れる（転籍が稀なため大半は正しい値に
  なる想定、ユーザー確認の上で実施）。転籍歴のあるスタッフの過去分だけ、
  必要なら個別に手動で訂正する。
*/

ALTER TABLE dbo.mx_nen_tyo ADD section nvarchar(3) NULL;
GO
