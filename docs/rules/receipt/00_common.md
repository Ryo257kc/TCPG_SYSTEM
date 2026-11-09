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

## 未収入金一覧の返戻タブ

- 返戻タブの閲覧・保存は `is_admin` の管理者だけに許可し、直接URL・POSTでも権限確認する。
- 返戻額が0以外の明細を対象とし、回収不可や残額0円で除外しない。月指定は決算月1つに統一し、未処理はその月末までの施術分、処理済みはその月末日が返戻仕訳処理日の明細を表示する。保存・確認シートも同じ決算月を使う。
- 管理者は月次締め後も、選択した返戻明細の `returned_journal_processed_at` だけを指定決算月の末日で保存できる。
- この保存で返戻額・修正額・請求額・回収不可・備考や、売上仕訳を更新しない。確認シート・仕訳CSVの出力でもDBを更新しない。
- 仕訳CSVは会社を指定した処理済み明細から出力し、一覧・確認シートと抽出処理を共用する。回収不可も含め、返戻の符号で借貸を切り替える。既存のインポート用CSVと同じ15列で、Excelの文字化けを防ぐためUTF-8（BOM付き）で出力する。
