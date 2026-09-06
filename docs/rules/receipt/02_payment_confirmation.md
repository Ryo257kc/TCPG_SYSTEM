# 入金確認

## 画面

- /staff/office/receipt/payment-confirmation

## Controller

- App\\Http\\Controllers\\StaffPortal\\office\\PaymentConfirmationController

## ルール

- 入金確認は mx_journal_entries の医療未収入金と mx_insurance_claim_details を紐づけて確認する。
- payment_date_text は仕訳内訳との紐づけキー。IDを手入力させる設計にしない。
- 複製は元明細をもとに新しい mx_insurance_claim_details 行を作る。
- 複製先の請求金額は必ず `0` にする。元明細の請求金額をコピーしない。
- 複製先の修正額は、複製元の修正額を符号反転して入れる。例: 元が `-62,748` なら複製先は `62,748`。
- 複製時に備考へ追記する金額は、複製元の請求額。文言は `請求額` とし、確認額・残額に変更しない。

- 元の請求金額が編集不可でも、複製側の修正額、返戻額、入金額で調整できること。
- 保存後は同じ仕訳内訳、同じ会社の入金済金額を集計し、mx_journal_entries.received_amount に反映する。
- 保険者検索、入金名称検索は絞り込み補助。候補選択と自由入力を両立させる。

## 保存・削除の確認（2026-08-23追記）

`save()`（既存行更新の分岐）・`delete()`は、対象の`insurance_claim_detail_id`が既に
存在しない場合でも`update()`/`delete()`の影響行数を見ずに処理を続け、`save()`は
存在しない行を前提に組み立てたゼロ埋めのJSONを、`delete()`は常に`{deleted:true}`を
返していた（041実運用の監査で発覚。店舗日報側の同種バグ修正と合わせて一斉点検）。
`update()`/`delete()`が0件ならHTTP 422で`message`付きJSONを返すよう修正。フロント側
（`page_script.blade.php`）は元々`!response.ok`で失敗判定して`alert()`する作りだった
ため、このバックエンド修正だけでフロント改修は不要だった。

## 候補内訳検索でメモリ不足エラー（2026-09-05追記）

`index()`の`candidateRowsQuery`（画面下部「候補内訳」の検索）は、タブ「全て」「未入金全て」の
時だけ`detail_store.company_id`による絞り込みが無く、`mx_insurance_claim_details`テーブル
全体（会社をまたいで約3万行）が対象になっていた（「済」「未入金」タブは元から絞り込み済み）。
入金名称検索でヒット件数が多いと、SQL Server用ドライバの行取得コストも重なって
`Allowed memory size exhausted`で画面が落ちた。会社IDでの絞り込みを全タブ共通の
前提条件に変更（絞り込みなし他タブとの一貫性を取った形）。ただし会社単位でも
最大1万件超えることを実データで確認したため、`MAX_CANDIDATE_ROWS`(500件)で
上限を設け、超えた場合は黙って切り詰めず「検索結果が多いため、一部のみ表示して
います」を表示して絞り込みを促す。
