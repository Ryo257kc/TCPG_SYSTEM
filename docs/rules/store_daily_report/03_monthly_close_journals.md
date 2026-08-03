# 店舗日報 月次・仕訳ルール

## 対象

- closeDailySummaryMonthly()
- unlockDailySummaryMonthly()
- dailySummaryMonthlyJournalPayloads()
- deleteDailySummaryMonthlyJournalEntries()

## 月次確定

1. 月間日報で数字を確認する。
2. さくら・ひなたのレセ負担金を入力する。
3. 保険負担を月間日報から自動集計する。
4. mx_monthly_closings に店舗日報月次の確定値を保存する。
5. mx_journal_entries に売上仕訳を作成または更新する。

## 作成する仕訳

- 窓口: 現金 / 保険窓口負担 から 窓口収入 / 保険窓口負担
- 自費: 現金 / 自費 から 自費収入 / 自費
- 経費: 消耗品費 / 店舗経費 から 現金 / 店舗経費

発生日は対象月末、month_date は対象月初。
summary_text は n月売上。
journal_breakdown は yyyymm + 種別 + 部門名。

## 解除

月次解除で削除してよいのは店舗日報月次が作成した仕訳だけ。
レセ取込、往診取込、入金確認、小口現金など別経路の仕訳を削除しない。
自費仕訳を削除する場合、journal_breakdown が yyyymmレセ% のものは削除しない。
