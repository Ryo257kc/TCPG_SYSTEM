# 店舗日報 触らないルール

## 禁止

- Accessで一致確認済みの集計条件を、確認なしに変更しない。
- 月間日報と月報を同じ帳票にしない。
- 月次解除でレセ、往診、小口現金、入金確認の仕訳を削除しない。
- 店舗日報の数字を売上一覧や売上PDF側で再計算しない。
- 店舗名、部門名、会社名を推測で補完しない。
- 0円になった既存仕訳を勝手に削除する補強を入れない。
- 既存DBの値を表示用に丸めて保存しない。
- 月次済みの店舗日報データは編集不可（`docs/rules/store_daily_report/00_common.md`）。
  - 実例（2026-08-16）: `StoreDailyReportController`の7つの保存系メソッドのうち5つ
    （`saveDailySummaryHeader`／`saveDailySummaryExpense`（月次チェックの条件が経理権限の
    有無で逆になっていた）／`addDailySummaryPatient`／`bulkCheckDailySummaryDetail`／
    `saveDailySummaryDetail`（保存アクションのみで削除アクションは無防備だった））で
    月次締めチェックが抜けていた。Blade側の表示制御（ボタン非表示）だけでは守られない。
    新しい保存・削除メソッドを追加する時は、`dailySummaryMonthlyClosingRow()`を必ず呼ぶ。

## 数字が違うとき

表示側を先に直さない。
まず月間日報の集計、mx_monthly_closings、mx_journal_entries の順で確認する。

## 先生別外

先生別外を理由に自費治療金、本日売上金、窓口合計から除外しない。
先生別外は先生別集計から外すための条件。
通常日報と修正日報の差は teacher.ch 条件で分ける。
