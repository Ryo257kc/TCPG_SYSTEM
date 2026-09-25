/*
目的:
- 給与仕訳CSV・会社負担一覧CSV等、freeeインポート用CSVの「部門」列を今まで手作業で
  修正していた問題への対応（2026-09-22、ユーザー相談）。
- mx_stores.store_code と mx_departments.official_store_no は1対1にならないケースがある
  （例: store_code=001は6件のmx_departments、005は4件、009は2件のdepartmentsに対応）。
  往診系スタッフが個人ごとに別部門を持つ店舗があり、単純なJOINでは部門を一意に決定できない
  ことを実データで確認済み。
- そのため、店舗ごとに「freeeインポート用の部門名」を明示的に1つ設定できる列を
  mx_stores に追加する。値はmx_departments.store_category（読める部門名）と同じ形式・
  同じ制約（nvarchar(20)）に揃える。既存のstore_code↔official_store_noの対応関係は
  変更せず、あくまで店舗マスタ側に「CSV表示用の確定値」を持たせるだけ。

注意:
- 対象DB: TCPGSYSTEM_DEV（2026-09-22実行）
*/

ALTER TABLE dbo.mx_stores
ADD freee_department_name NVARCHAR(20) NULL;

-- 2026-09-22: 「半分の長さ」がフォームの表示幅の話だったのを列の文字数と誤解し、
-- 一時的に10へ変更してしまった。20に戻し、画面側は入力欄の表示幅(detail-field-wide→
-- 通常幅)を修正して対応。
