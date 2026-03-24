# Table Mapping Overview

最終更新: 2026-03-23

この資料は、旧DBと現DBのテーブル対応と同期方針を整理するための一覧です。

列の意味:
- `同期あり`: 旧DBから現DBへ同期する想定
- `同期なし`: 現DBを正とする、または旧DBから同期しない想定
- `不要`: 現行運用では使わない想定
- `未確認`: まだ詳細比較をしていない

| 系統 | 旧DB | 旧テーブル | 現DB | 現テーブル | 用途 | 同期方針 | 備考 |
|---|---|---|---|---|---|---|---|
| Payroll | Payroll | `t_allowance` | Payroll_DEV | `mx_allowance` | 手当設定 | 同期なし | 新DB独自拡張あり |
| Payroll | Payroll | `t_atena` | Payroll_DEV | `mx_atena` | 宛名 | 未確認 | カラム比較済 |
| Payroll | Payroll | `t_deduction_shou` | Payroll_DEV | `mx_deduction_shou` | 年調控除 | 未確認 | カラム比較済 |
| Payroll | Payroll | `t_fuyo` | Payroll_DEV | `mx_fuyo` | 扶養一覧 | 未確認 | カラム比較済 |
| Payroll | Payroll | `t_hoken` | Payroll_DEV | `mx_hoken` | 年調保険控除 | 同期なし | カラム比較済 |
| Payroll | Payroll | `t_holiday` | Payroll_DEV | `mx_holiday` | 休日区分 | 不要 | カレンダー用途で作成したが現状不要想定 |
| Payroll | Payroll | `t_kihon` | Payroll_DEV | `mx_kihon` | 給与マスタ | 未確認 | カラム比較済 |
| Payroll | Payroll | `t_kyuyo_shou` | Payroll_DEV | `mx_kyuyo_shou` | 給与・賞与 | 未確認 | カラム比較済、PKあり |
| Payroll | Payroll | `t_mayor` | Payroll_DEV | `mx_mayor` | 住民税 | 同期なし | カラム比較済、PKあり |
| Payroll | Payroll | `t_nen_tyo` | Payroll_DEV | `mx_nen_tyo` | 年末調整 | 同期なし | カラム比較済 |
| Payroll | Payroll | `t_resident` | Payroll_DEV | `mx_resident` | スタッフ住民税 | 未確認 | カラム比較済 |
| Payroll | Payroll | `t_rouho` | Payroll_DEV | `mx_rouho` | 労保料率 | 同期なし | カラム比較済 |
| Payroll | Payroll | `t_staff_shou` | Payroll_DEV | `mx_staff_shou` | スタッフ社保 | 未確認 | カラム比較済 |
| Payroll | Payroll | `t_syaho` | Payroll_DEV | `mx_syaho` | 社保料率 | 同期なし | カラム比較済 |
| Payroll | Payroll | `t_yukyu` | Payroll_DEV | `mx_yukyu` | 有休 | 未確認 | Access運用を元に現DBで管理 |
| TCPGSYSTEM | TCPGSYSTEM | `staff` | TCPGSYSTEM_DEV | `mx_staffs` | スタッフ詳細 | 未確認 | カラム比較済、PKあり |
| TCPGSYSTEM | TCPGSYSTEM | `t_time_card` | TCPGSYSTEM_DEV | `mx_time_cards` | タイムカード | 同期あり | 2026/2以降で同期確認あり |
| TCPGSYSTEM | TCPGSYSTEM | `T_法人店舗` | TCPGSYSTEM_DEV | `mx_stores` | 店舗 | 同期なし | 旧表記は日本語 |
| TCPGSYSTEM | TCPGSYSTEM | `T_法人店舗` | TCPGSYSTEM_DEV | `mx_companies` | 会社 | 同期なし | 旧表記は日本語、要再確認 |

## 先に確認済みの主なテーブル

- `mx_time_cards`
- `mx_yukyu`
- `mx_staffs`
- `mx_fuyo`
- `mx_kihon`
- `mx_kyuyo_shou`
- `mx_mayor`
- `mx_resident`
- `mx_rouho`
- `mx_staff_shou`
- `mx_syaho`
- `mx_allowance`
- `mx_atena`
- `mx_deduction_shou`
- `mx_hoken`
- `mx_nen_tyo`
- `mx_calendar`
- `mx_stores`
- `mx_companies`

## メモ

- `Payroll_DEV` は旧DB構造をかなり引き継いでいるが、PK未設定テーブルが多い
- `mx_allowance` は旧DBベースに新DB独自拡張を足している
- `mx_holiday` は現時点で不要候補
- `TCPGSYSTEM_DEV` は `mx_staffs`, `mx_time_cards`, `mx_calendar`, `mx_stores`, `mx_companies` が主要利用テーブル
