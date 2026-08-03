# 売上一覧ルール

## 対象

- `/staff/office/sales`
- `/staff/office/sales/print`
- `app/Services/StaffPortal/Office/SalesV2Service.php`
- 売上一覧と売上PDFのビュー

## 役割

売上一覧は、月次処理や取込で作成された仕訳を表示する画面。
正しい数字を作る場所ではなく、`mx_journal_entries` の表示・集計が役割。

## 表示元

- 主データは `mx_journal_entries`。
- 店舗名、部門名、会社名は `mx_departments`、`mx_stores`、`mx_companies` を補助的に使う。

## 集計ルール

- 窓口収入、保険窓口負担、自費収入、個人振込、経費は仕訳の勘定科目・品目・部門で判定する。
- 日報、レセ、往診の元データから売上一覧側で再集計しない。
- 店舗未選択の合算表示は原則不要。店舗別確認を優先する。
- `/admin/sales` のCSV DLは、売上一覧に表示する `mx_journal_entries` 由来の売上データを出力する。
- CSVの借方部門・貸方部門は、freee取込用の mx_departments.store_category を優先して出力する。例: ひなた店舗。
- 会社別合計は、店舗・部門に紐づく会社ごとに下部へ表示する。表示側で売上を作り直さない。

- 部門空白や0円行が表示される場合は、仕訳の部門、品目、金額を確認する。

## 注意

- PDFも表示専用。
- 月間日報の確定数字と仕訳表示が一致することを優先する。
- 仕訳にない数字を表示側で補完しない。
