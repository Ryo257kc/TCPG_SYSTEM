# 年末調整 PDF・ファイル

## 対象帳票

管理側詳細画面から以下を別タブでプレビューする。

- 保険料控除申告書
- 基礎控除申告書
- 扶養控除申告書
- 源泉徴収簿
- 源泉徴収票

## テンプレート取得

PDFテンプレートは `storage/app/templates/year_end/` から取得する。

優先順は以下。

1. `storage/app/templates/year_end/{targetYear}-{templateKey}.pdf`
2. `storage/app/templates/year_end/2025-{templateKey}.pdf`

年度ごとに国の様式が変わる可能性があるため、年度別テンプレートを優先する。
該当年度がない場合のみ、確認済みの前年テンプレートを使う。

## プレビュー生成

プレビューPDFは `storage/app/year_end/previews/{targetYear}/{staffId}/` に作る。
同じファイル名へ上書きするため、ボタンを押すたび無限にPDFを増やさない。

## 帳票への文字出力

現時点では基本情報の出力土台まで。

- 年度
- スタッフID
- 氏名
- 社名
- 対象年

本格的な項目配置は、1帳票ずつ、1項目ずつ確認しながら追加する。
位置調整や帳票レイアウトは大きく一括変更しない。

## 保険証明書

保険証明書は `mx_hoken.certificate_file_path` を参照する。
管理画面では別ウィンドウで開く。
画像の場合はプレビュー画面上で左回転、右回転、戻すができる。
回転はプレビュー上だけで、元ファイルは変更しない。

## アップロード保存

証明書アップロードは年末調整用途として保存先を分ける。
保存先の統一ルールは `year_end/{targetYear}/{staffId}/hoken/` 系を基本にする。
画像は読める範囲で圧縮し、サーバー容量を無駄に使わない。
HEICなどブラウザ確認しにくい形式は受け付けない方針。

## 最終保存

最終的には全員分をまとめたPDF、または個人別PDFを `final` 用フォルダへ退避する方針を検討する。
現時点ではプレビュー中心。
## 源泉徴収簿プレビュー

源泉徴収簿は `mx_kyuyo_shou` と `mx_nen_tyo` の保存値を表示する。
帳票側で給与・賞与・年調の再計算をしない。

月別支給行は `mx_kyuyo_shou` から取得する。

- `supply_month`
- `fuyo_sum`
- `bonus_amo`
- `taxation_sum`
- `syaho_sum`
- `syaho_deduction_sum`
- `income_tax`

年調結果欄は `mx_nen_tyo` の計算済み保存値を表示する。
表示位置は帳票確認しながら微調整する。
## 源泉徴収票プレビュー

源泉徴収票は `mx_nen_tyo` の保存済み年調結果を表示する。
帳票側で支払金額、控除後金額、所得控除額、源泉徴収税額を再計算しない。

現時点の主要出力値は以下。

- `bonus_kyuyo_sum`
- `shotoku_deduction`
- `shotoku_deduction_sum`
- `nentyo_nen_tax`
- `kyu_syaho_fee_kou`
- `seimei_fee_kou`
- `jishun_fee_kou`
- `jyu_kari_kou`
- 扶養人数系の保存値

住所や細かい摘要欄は帳票確認しながら追加する。
