# 請求関連 共通ルール

## 対象画面

- /staff/office/receipt
- /staff/office/receipt/entry
- /staff/office/receipt/payment-confirmation
- /staff/office/receipt/home-visit-counter
- /staff/office/receipt/insurers
- /staff/office/receipt/high_medical

## 基本方針

- 請求関連は、保険請求明細、保険者、入金確認、往診窓口、高額療養を分けて扱う。
- 金額が後続の売上、月次、仕訳に流れるため、表示側で勝手な補正や別計算をしない。
- Accessから移した条件がある場合は、条件を確認してから変更する。
- 日本語ラベル、エラーメッセージ、CSV取込メッセージは必ず日本語で保持する。

## 主なテーブル

- mx_insurance_claim_details: 請求明細、入金確認、往診窓口の元データ。
- mx_insurers: 保険者マスタ。
- mx_journal_entries: 月次後に売上や入金確認へ連動する仕訳。
- mx_monthly_closings: 月次確定状態。
- mx_departments: 店舗名、店舗略称、部門番号、口座選択の参照。
