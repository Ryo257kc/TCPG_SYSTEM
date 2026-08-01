# ルール一覧

## 目的

このフォルダは、Laravel移行後の現在の正しい運用ルールを残す場所。

`AGENTS.md` はAI向けの強制停止ルールが混ざっていて長いため、業務上の判断や画面修正時に見るルールはここへ分ける。

## 読む順番

1. `00_global.md`
   - 全ページ共通のルール。
   - どの画面を触る時も先に確認する。

2. 対象ページのフォルダ
   - 例: `payroll_bonus/`
   - 業務ごとの細かいルール、計算根拠、触ってはいけないものを書く。

3. `change_log`
   - なぜ変更したかを短く確認する。

## 置き場所のルール

- 全ページ共通: `docs/rules/00_global.md`
- 給与・賞与: `docs/rules/payroll_bonus/`
- 勤怠: `docs/rules/attendance/`
- 売上・仕訳: `docs/rules/sales_accounting/`
- レセ請求: `docs/rules/receipt/`
- 年末調整: `docs/rules/year_end_adjustment/`
- スタッフポータル: `docs/rules/staff_portal/`

## 書き方のルール

- 本文には現在の正しいルールだけを書く。
- 間違っていた古いルールは本文に残さず、正しい内容へ上書きする。
- 変更理由だけを各フォルダの `change_log` に短く残す。
- 長い説明より、触る前に判断できる短いルールを優先する。
- 迷った時は、変更前に止まって確認する。