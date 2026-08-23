# 店舗日報 共通ルール

## 対象画面

- /staff/office/store_daily_report
- /staff/office/store_daily_report/daily_summary
- /staff/office/store_daily_report/daily_summary/detail
- /staff/office/store_daily_report/request_history
- /staff/office/store_daily_report/return_note
- /staff/office/store_daily_report/other_list
- /staff/office/store_daily_report/item_summary

## 基本方針

- 店舗日報は、月間日報、月報、月次処理、売上仕訳に連動する。
- 表示側で売上を勝手に再計算しない。正しい数字は月間日報で集計し、月次テーブルへ保存し、その数字を仕訳へ反映する。
- 売上一覧や売上PDFは仕訳を表示するだけ。店舗日報側と別ロジックで数字を合わせない。
- 月次済みのデータは編集不可。変更が必要な場合は月次解除してから編集する。
- 店舗未選択時は、店舗別に確認する帳票や印刷を無理に出さない。

## 月次の流れ

1. 月間日報で正しい集計数字を確認する。
2. レセ負担金のさくら、ひなた分を入力する。
3. 保険負担は月間日報から自動集計する。
4. 保存後、月次確定で mx_monthly_closings に確定値を保存する。
5. 月次確定時に mx_journal_entries へ売上仕訳を作成する。

## 金額ルール

- レセ負担金の下枠は、mx_monthly_closings の保存値を使う。
- 保険負担は月間日報の集計値。入力欄ではなく自動表示。
- 店舗経費は月間日報の集計に含める。
- 月間日報と売上一覧の数字が違う場合、売上一覧で補正せず、月次テーブルと仕訳作成元を確認する。

## 禁止

- Accessで一致確認済みの集計条件を、確認なしに変更しない。
- 月間日報と月報を同じ帳票扱いにしない。別PDFとして扱う。
- 月次解除時に、関係ない往診月次やレセ月次の仕訳を削除しない。
- 仕訳表示側で店舗日報の数字を再計算しない。
- 店舗日報権限をダッシュボード表示条件に使わない。ダッシュボード上は事務所メニュー配下に置く。

## 保存の確認（2026-08-23、サイレント失敗の一斉修正）

`StoreDailyReportController`の保存系メソッドが、対象行の実在確認や`update()`の影響行数を
一切見ずに、常に「保存しました。」を返していた（041の実運用でこのパターンの不具合が
複数見つかったことを受けて全保存系メソッドを監査）。DEVは店舗日報テーブルに関しては
開発専用で本番移行時にテーブルごと切り替わるため、この時点でまとめて修正した。

- `saveDailySummaryDetail()`：`T_患者名日報`の実在確認を保存前に追加。`T_先生別日報`の
  `update()`が0件なら`\RuntimeException`を投げ、呼び出し元でerrorMessageに変換する構成に変更。
- `saveReturnNote()`（返戻ノート）・`saveRequestHistory()`（依頼履歴）：`update()`の影響行数を
  見て、0件ならerrorMessageを返すよう修正。
- `saveDailySummaryExpense()`（経費行）：`T_レジ詳細`の`update()`/`delete()`が0件の行があれば
  即エラーで返すよう修正。以前は対象行が見つからなくても`$savedCount`が無条件で加算され、
  金額編集が実際には保存されていないのに「経費を保存しました。」と表示されていた
  （このパターンが一番実害が大きい）。
- `bulkCheckDailySummaryDetail()`（一括ch更新）：影響件数をメッセージに含め、0件なら
  「更新対象がありませんでした。」と表示するよう修正（一括処理の性質上エラー扱いにはしない）。

**How to apply**: この控えめ機能追加パターン（対象実在チェック＋`update()`/`delete()`戻り値の
影響行数チェック）は、店舗日報のこのコントローラーに限らず、StaffPortal側で新しい保存処理を
書くとき・既存の保存処理を触るときは必ず確認する。0件更新を成功として返さない。
