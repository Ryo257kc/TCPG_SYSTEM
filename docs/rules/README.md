# ルール一覧

## 目的

このフォルダは、Laravel移行後の現在の正しい運用ルールを残す場所。

旧`AGENTS.md`（codex作成、英語、長すぎて読まれなくなっていた）は2026-08-17に廃止し、
今も有効な内容は`01_code_structure.md`へ移した。プロジェクト直下の`CLAUDE.md`が
毎回自動で読み込まれる入口になっており、そこからこのフォルダへ誘導する構成にしている。

## 読む順番

1. `00_global.md`
   - 全ページ共通のルール（計算・データ整合性）。
   - どの画面を触る時も先に確認する。

2. `01_code_structure.md`
   - ファイル配置・CSS・ルーティング・Controller分割など、コード構成のルール。

3. 対象ページのフォルダ
   - 例: `payroll_bonus/`
   - 業務ごとの細かいルール、計算根拠（`10_calculation_basis.md`の「実装:」欄でコードへ）、
     触ってはいけないものを書く。

4. `change_log`
   - なぜ変更したかを短く確認する。

## 置き場所のルール

- 全ページ共通（計算・データ整合性）: `docs/rules/00_global.md`
- コード配置・構成: `docs/rules/01_code_structure.md`
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