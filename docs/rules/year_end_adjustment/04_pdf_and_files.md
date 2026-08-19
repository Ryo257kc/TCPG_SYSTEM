# 年末調整 PDF・ファイル

## 【未着手・要件検討中】電子的控除証明書（XML）の自動入力

2026-08-19、ユーザーから「電子的控除証明書のXMLをアップロードしたら該当項目に自動入力できるように
したい」というアイデア。保険会社等が発行する国税庁標準フォーマットのXMLを読み込んで、
`updateInsurance()`等の保険料控除申告フォームに値を自動反映するイメージ。まだ要件（対象証憑の
種類、UI上のアップロード導線、XML署名検証をするか等）は詰めていない。着手前に必ずユーザーと
スコープを確認すること。

**現状**：`CertificateFileService`はPDF/画像の保存のみで、XMLの中身を解析する機能は無い。

**国税庁公式スキーマを取得・保存済み**：`docs/reference/nta_certificate_xsd/`に、e-Taxが
公開しているXMLスキーマ（XSD）を保存してある（2026-08-19、`https://www.e-tax.nta.go.jp/shiyo/download/kojo04.CAB`
から取得・展開）。証憑の種類ごとに別ファイル（すべて`kyotsu/`配下）：

- `TEG800-001.xsd`：生命保険料控除証明書（最優先で対応するならこれ）
- `TEG810-001.xsd`：地震保険料控除証明書
- `TEG820/821/822-001.xsd`：寄附金受領証明書（複数寄附対応版含む）
- `TEG830-001.xsd`：寄附金控除に関する証明書
- `TEG840-001.xsd`：国民年金保険料等控除証明書
- `TEG850-001.xsd`：小規模企業共済等掛金控除証明書
- `general/`：共通の型定義（`gen:kingaku`＝金額、`gen:yyyymmdd`等）。各TEGファイルがimportする。

**TEG800（生命保険料控除証明書）の主要フィールド**（要素コード→意味）：

- `WCA00000` 保険会社名 / `WCC00000` 証明日 / `WCD00000` 契約者
- `WCE00000`（明細、最大100回繰り返し）の中に：
  - `WCE00040` 証券番号 / `WCE00050` 保険種類 / `WCE00080` 被保険者
  - `WCE00030`（kubun_CD: 1/2/3、適用制度＝一般/介護医療/個人年金の区分と思われる、要確認）
  - 新旧制度・一般/介護医療/年金それぞれの「保険料・配当金・差引保険料等合計額」
    （`WCE00220`旧制度〜`WCE00310`新制度配下、金額は`gen:kingaku`型）

各TEGファイルの冒頭コメント（`<xsd:documentation>`）に様式名・versionが書いてあるので、
実装時はそこから読み始めるとよい。

**次にやること**：実装着手前に、ユーザーと一緒に (1) 対象証憑の種類（生命保険料だけで
始めるか）、(2) スタッフ側アップロード画面のどこに導線を置くか、(3) XMLの電子署名検証を
するか（`XMLDSIG050.xsd`が署名スキーマとして同梱されている）を決める。実際の保険会社から
発行されたサンプルXMLが手に入ればスキーマとの突き合わせがより確実。

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

**未実装（2026年8月時点、`writeGensenHyouPreview()`に座標が無い）:**

- 受給者生年月日欄（`staff.birthday`）
- 中途就・退職欄（`就職`/`退職`の丸印、年月日）— 退職源泉のスタッフで特に必要
- 摘要欄
- 支払者（会社）欄の住所・電話番号（現状は氏名又は名称のみ）

次にこの帳票を触るときは、まず退職者（`mx_staffs.tai_date`が入っているスタッフ）の実データで
プレビューを出し、上記4点を優先して座標を合わせる。
## 基礎控除申告書プレビュー

基礎控除申告書は `mx_nen_tyo` の保存済み値を表示する。
帳票側で基礎控除、配偶者控除、所得金額調整控除を再計算しない。

主要出力値は以下。

- `kyuyo_teate_sum`
- `shotoku_deduction`
- `bonus_kyuyo_sum`
- `kiso_bunrui`
- `kiso_koujyo`
- `haigu_shotoku`（配偶者の所得金額の見積額。実運用では配偶者に他の所得があっても合算して
  この1列に手入力するので、給与所得の所得金額欄・合計所得金額の見積額欄の両方にこの値を使う。
  `haigu_shotoku_sum`という別カラムが存在したが、全レコードでゼロ＝一度も使われていなかった
  ため2026年8月に削除した）
- `haigu_bunrui`
- `haigu_deduction`（配偶者控除）
- `haigu_toku_deduction`（配偶者特別控除。2026年8月に物理カラム名変更：旧`haigu_toku_deduction`→`haigu_deduction`、旧`haigu_toku_deduction_amo`→`haigu_toku_deduction`）
- `tyosei_koujyo_select`
- `tyosei_koujyo`
## 扶養控除申告書プレビュー

扶養控除申告書は `mx_fuyo` の保存値を表示する。
帳票側で扶養判定や控除対象判定を再計算しない。

主要出力値は以下。

- `fuyo_name`
- `fuyo_name_furi`
- `fuyo_relationship`
- `fuyo_birthday`
- `fuyo_shunyu`
- `deduction_target`
- `failure_judgment`
- `kyojyu`
- `fuyo_address`

## PDF座標調整ルール

扶養控除申告書などのPDF帳票は、原本PDFの上に文字を重ねて表示する。
帳票用に別計算を作らず、保存済みDB値を表示する。

座標調整中の印字行は、座標・文字サイズ・最大幅を同じ行で確認できる書き方にする。
サイズだけの変数を別行に分けない。

例:

```php
$this->writePdfTextSized($pdf, 142, 17, $staffName, 7, 28);
```

意味:

- `142`: X座標
- `17`: Y座標
- `$staffName`: 表示文字
- `7`: 文字サイズ
- `28`: 最大幅

数字を1文字ずつ枠に入れる場合は以下の順番にする。

```php
$this->writePdfDigitsSized($pdf, 136, 26, $myNumber, 4.5, 7);
```

意味:

- `136`: X座標
- `26`: Y座標
- `$myNumber`: 表示文字
- `4.5`: 文字間隔
- `7`: 文字サイズ

折り返し文字は以下の順番にする。

```php
$this->writePdfWrappedTextSized($pdf, 142, 35, $address, 58, 4.0, 2, 7);
```

意味:

- `142`: X座標
- `35`: Y座標
- `$address`: 表示文字
- `58`: 最大幅
- `4.0`: 行間
- `2`: 最大行数
- `7`: 文字サイズ

原本PDFに印字済みの文字を隠す場合は、`fillPdfRect()` で上から枠を重ねる。
調整中は色付きにして位置を確認し、確定後に白 `[255, 255, 255]` へ変更する。

```php
$birthdayEraseColor = [255, 240, 120];
$this->fillPdfRect($pdf, 209.0, 10.0, 30.0, 4.8, $birthdayEraseColor);
```

文字色は `SetTextColor()` または近くの `$textColor = [0, 0, 180];` で管理する。
黒は `[0, 0, 0]`、白は `[255, 255, 255]`。

PDF座標調整中は、ユーザーが合わせた座標を勝手に戻さない。
座標を変更する場合は、変更前に理由を明確にする。
## 扶養控除申告書の追加ルール

扶養控除申告書の文字色は、Controller先頭の定数で統一する。
扶養控除申告書内で個別に `SetTextColor()` の色値をばらばらに書かない。

```php
private const FUYO_PDF_TEXT_COLOR = [255, 0, 0];
```

フリガナや氏名のように、枠内で文字サイズを変えず文字間だけ詰めたい項目は `writePdfTrackedTextSized()` を使う。
自動で文字サイズを下げる処理は使わない。

```php
$this->writePdfTrackedTextSized($pdf, 40.0, 70.5 + $rowOffset, $furi, 7, 1.4, 24);
```

意味:

- `40.0`: X座標
- `70.5 + $rowOffset`: Y座標
- `$furi`: 表示文字
- `7`: 文字サイズ
- `1.4`: 文字間隔
- `24`: 最大文字数

生年月日の短縮表示が必要な欄は `formatPdfJapaneseDateShort()` を使う。
現在は16歳未満欄のみ `R2/12/5` 形式にする。
本人欄、A欄、B欄は通常の和暦表示を使う。

C欄の障害者情報は `mx_fuyo` から取得する。
対象カラムは以下。

- `fuyo_name`: 名前
- `failure_notebook`: 障害手帳
- `failure_judgment`: 等級・判定
- `kyojyu`: 同居判定

C欄へ表示する内容は以下。

- 障害者チェック
- 障害者人数（同居特別、特別、その他）
- 名前、障害手帳、等級・判定

障害者C欄は `writeFuyoDisabilitySection()` にまとめる。
扶養者個別行のA欄・B欄に障害者情報を重複表示しない。

PDF座標はユーザーが実帳票を見ながら調整する前提。
一度「一致した」と言われた座標は、別件修正で勝手に戻さない。