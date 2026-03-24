# Column Mapping: TCPGSYSTEM_DEV Checked Tables

最終更新: 2026-03-23

この資料は、`TCPGSYSTEM_DEV` でここまで確認できたテーブルの要点メモです。
詳細比較は必要に応じて個別ファイルへ分けていきます。

## `staff` -> `mx_staffs`

- 主キー: `staff_id` あり
- UNIQUE: `staff_id`
- 追加インデックス:
  - `section`
  - `is_store_management_user`
- 差分:
  - 日本語列名の英語化
  - `staff_id` 長さ拡張 `nvarchar(4)` -> `nvarchar(50)`
  - `section` 長さ拡張 `nvarchar(3)` -> `nvarchar(50)`
- 備考: 現DB側の英語化はかなり整理されている

## `t_time_card` -> `mx_time_cards`

- 1〜35列目までは旧DBを英語化して継承
- 主キー: なし
- インデックス:
  - `(staff_name, work_date)`
- 主キー候補: `time_no`
- 現DB独自列:
  - `holiday_category`
  - `attendance_checked`
  - `attendance_checked_at`
  - `attendance_checked_by`
- 備考: 旧DBベースに現DB独自管理列を追加

## `t_calendar` -> `mx_calendar`

- 新旧カラム: 完全一致
- 順番: 一致
- 主キー: なし
- インデックス:
  - `calendar_day`
- 主キー候補: `calendar_day`
- 備考: カレンダーテーブルとしては素直な構造

## `T_法人店舗` -> `mx_stores`

- 主キー: `store_code` あり
- UNIQUE: `store_code`
- 旧 `T_法人店舗` を店舗単位情報として分割した側
- 主な対応:
  - `法人店舗No` -> `store_code`
  - `法人店舗名` -> `store_name`
  - `業種` -> `business_type`
  - `往診エリア` -> `visit_area`
  - `店舗略称` -> `store_short_name`
  - `郵便番号` -> `postal_code`
  - `住所ふりがな` -> `address_kana`
  - `住所` -> `store_address`
  - `電話番号` -> `phone`
  - `閉店` -> `is_closed`
- 備考:
  - `company_id` で `mx_companies` を参照
  - `category`, `external_store_code` は現DB側の管理用拡張とみなす

## `T_法人店舗` -> `mx_companies`

- 主キー: `company_id` あり
- 自然キーUNIQUE:
  - `company_name`
  - `company_address`
  - `office_number`
  - `health_office_code`
- 旧 `T_法人店舗` を会社単位情報として分割した側
- 主な対応:
  - `社名` -> `company_name`
  - `会社住所` -> `company_address`
  - `役職名` -> `ceo_title`
  - `代表者名ふりがな` -> `ceo_name_kana`
  - `代表者名` -> `ceo_name`
  - `事業所番号` -> `office_number`
  - `事業所整理番号健保` -> `health_office_code`
  - `事業所整理番号厚年` -> `pension_office_code`
  - `法人マイナンバー` -> `corporate_number`
  - `保険者番号` -> `insurer_number`
  - `保険者名称` -> `insurer_name`
  - `保険者所在地` -> `insurer_address`
  - `雇用保険事業所番号` -> `employment_office_number`
  - `労働保険番号` -> `labor_insurance_number`
  - `労働設置区分` -> `labor_install_category`
  - `労働設置年月日` -> `labor_install_date`
  - `労働事業所区分` -> `labor_office_category`
  - `労働産業分類` -> `labor_industry_category`
- 備考: 旧1テーブルを店舗・会社へ正規化した良い分割

## 補足

- `mx_account_titles` は未使用で、列名が `legacy_t_*` になっているため作り直し候補
- `mx_stores` と `mx_companies` の分割は、旧構造より現DB側の方が整理されている
