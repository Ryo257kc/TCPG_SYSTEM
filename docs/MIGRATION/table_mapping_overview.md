# Table Mapping Overview

最終更新: 2026-03-23

この表は、旧DBと現DBのテーブル対応を整理するための親表です。  
同期方針の意味は次の通りです。

- `同期あり`: 旧DBから新DBへ同期対象
- `同期無し`: 新DBを正とする。同期すると困る
- `不要`: 現行運用では同期不要
- `未確認`: まだ確認中

| 区分 | 旧DB | 旧テーブル | 現DB | 現テーブル | 用途 | 同期方針 | 備考 |
|---|---|---|---|---|---|---|---|
| Payroll | Payroll | `t_allowance` | Payroll_DEV | `mx_allowance` | 手当設定 | 同期無し | |
| Payroll | Payroll | `t_atena` | Payroll_DEV | `mx_atena` | 宛名 | 同期無し | |
| Payroll | Payroll | `t_deduction_shou` | Payroll_DEV | `mx_deduction_shou` | 年調控除 | 同期無し | |
| Payroll | Payroll | `t_fuyo` | Payroll_DEV | `mx_fuyo` | 扶養一覧 | 未確認 | |
| Payroll | Payroll | `t_hoken` | Payroll_DEV | `mx_hoken` | 年調保険控除 | 同期無し | |
| Payroll | Payroll | `t_holiday` | Payroll_DEV | `mx_holiday` | 休日区分 | 不要 | |
| Payroll | Payroll | `t_kihon` | Payroll_DEV | `mx_kihon` | 給与マスタ | 未確認 | |
| Payroll | Payroll | `t_kyuyo_shou` | Payroll_DEV | `mx_kyuyo_shou` | 給与 / 賞与 | 未確認 | |
| Payroll | Payroll | `t_mayor` | Payroll_DEV | `mx_mayor` | 住民税 | 同期無し | |
| Payroll | Payroll | `t_nen_tyo` | Payroll_DEV | `mx_nen_tyo` | 年末調整 | 同期無し | |
| Payroll | Payroll | `t_resident` | Payroll_DEV | `mx_resident` | スタッフ住民税 | 未確認 | |
| Payroll | Payroll | `t_rouho` | Payroll_DEV | `mx_rouho` | 労保料率 | 同期無し | |
| Payroll | Payroll | `t_staff_shou` | Payroll_DEV | `mx_staff_shou` | スタッフ社保 | 未確認 | |
| Payroll | Payroll | `t_syaho` | Payroll_DEV | `mx_syaho` | 社保料率 | 同期無し | |
| Payroll | Payroll | `t_yukyu` | Payroll_DEV | `mx_yukyu` | 有休 | 未確認 | Access運用を元に整理予定 |
| TCPGSYSTEM | TCPGSYSTEM | `T_勘定科目` | TCPGSYSTEM_DEV | `mx_account_titles` | 勘定科目 | 同期無し | |
| TCPGSYSTEM | TCPGSYSTEM | `T_宛名` | TCPGSYSTEM_DEV | `mx_addressees` | 宛名 | 未確認 | |
| TCPGSYSTEM | TCPGSYSTEM | `t_calendar` | TCPGSYSTEM_DEV | `mx_calendar` | カレンダー | 同期無し | |
| TCPGSYSTEM | TCPGSYSTEM | `T_修正` | TCPGSYSTEM_DEV | `mx_corrections` | 修正 | 未確認 | |
| TCPGSYSTEM | TCPGSYSTEM | `T_部門` | TCPGSYSTEM_DEV | `mx_departments` | 部門 | 未確認 | |
| TCPGSYSTEM | TCPGSYSTEM | `T_保険請求内訳` | TCPGSYSTEM_DEV | `mx_insurance_claim_details` | 保険請求内訳 | 未確認 | |
| TCPGSYSTEM | TCPGSYSTEM | `T_保険者` | TCPGSYSTEM_DEV | `mx_insurers` | 保険者 | 未確認 | |
| TCPGSYSTEM | TCPGSYSTEM | `T_請求明細` | TCPGSYSTEM_DEV | `mx_invoice_details` | 請求明細 | 未確認 | |
| TCPGSYSTEM | TCPGSYSTEM | `T_請求書` | TCPGSYSTEM_DEV | `mx_invoices` | 請求書 | 未確認 | |
| TCPGSYSTEM | TCPGSYSTEM | `T_仕訳帳` | TCPGSYSTEM_DEV | `mx_journal_entries` | 仕訳帳 | 未確認 | |
| TCPGSYSTEM | TCPGSYSTEM | `t_kihon_shift` | TCPGSYSTEM_DEV | `mx_kihon_shifts` | 基本シフト | 同期無し | |
| TCPGSYSTEM | TCPGSYSTEM | `T_借入返済詳細` | TCPGSYSTEM_DEV | `mx_loan_repayment_details` | 借入返済詳細 | 同期無し | |
| TCPGSYSTEM | TCPGSYSTEM | `T_借入返済` | TCPGSYSTEM_DEV | `mx_loan_repayments` | 借入返済 | 同期無し | |
| TCPGSYSTEM | TCPGSYSTEM | `t_message` | TCPGSYSTEM_DEV | `mx_message` | メッセージ | 同期無し | |
| TCPGSYSTEM | TCPGSYSTEM | `T_月次` | TCPGSYSTEM_DEV | `mx_monthly_closings` | 月次 | 同期無し | |
| TCPGSYSTEM | TCPGSYSTEM | `staff` | TCPGSYSTEM_DEV | `mx_staffs` | スタッフ詳細 | 未確認 | 現行の正本候補 |
| TCPGSYSTEM | TCPGSYSTEM | `t_time_card` | TCPGSYSTEM_DEV | `mx_time_cards` | タイムカード | 同期あり | 2026/2 以降で確認実績あり |
| TCPGSYSTEM | TCPGSYSTEM | `T_法人店舗` | TCPGSYSTEM_DEV | `mx_stores` | 店舗 | 同期無し | |
| TCPGSYSTEM | TCPGSYSTEM | `T_法人店舗` | TCPGSYSTEM_DEV | `mx_companies` | 会社 | 同期無し | |

## 次に作るもの

- `mx_yukyu` カラム比較表
- `mx_staffs` カラム比較表
- 勤怠側の有休使用元テーブル比較
