# 年末調整 PDF・ファイル

## 電子的控除証明書（XML）の自動入力（生命保険料控除、実装済み・実データ検証済み）

2026-08-19、ユーザーから「電子的控除証明書のXMLをアップロードしたら該当項目に自動入力できるように
したい」というアイデアから実装。保険会社等が発行する国税庁標準フォーマットのXML（TEG800＝
生命保険料控除証明書）を読み込んで、`hoken_add`（保険をもう一件追加）のフォームへ自動入力する。

要件（ユーザーとの合意事項）：

- アップロードされた元ファイル（XML）は必ず証憑として保持する。既存の`certificate_file_path`の
  仕組みをそのまま使う（DataTransfer APIで同じFileオブジェクトを各行のcertificate_file inputへ
  複製して割り当てる）。データだけ取り込んでファイル自体を残さない、という状態にはしない。
- 1つのXMLに複数契約（最大100件、`WCE00000`が繰り返し）が入っている場合、契約ごとに
  `hoken_add`の行を自動で複数生成する。1件目だけ自動、2件目以降は手入力、という差は作らない。
- XMLは画像/PDFのようにブラウザでそのままプレビューできないため、admin側で人が読める形の
  整形プレビューを作る（実装済み、下記参照）。

**実装済みのファイル：**

- `app/Services/YearEnd/LifeInsuranceCertificateXmlParser.php`：TEG800 XMLを`DOMDocument`+
  `DOMXPath`（名前空間`http://xml.e-tax.nta.go.jp/XSD/kyotsu`）で読み、`WCE00000`明細ごとに
  「証明額（12月期想定）」（`WCE00440`配下）の「申告額（参考）」がある区分だけを契約として
  抽出する。証明期間中の実額（`WCE00190`配下）ではなく12月期想定額を使うのは、年末調整の
  申告額としてはこちらが国税庁側の案内する参考額のため。証券番号・被保険者名など`mx_hoken`に
  対応カラムが無い項目は`year_end_insurance_note`へ文字列で畳み込む。
- `App\Http\Controllers\StaffPortal\YearEnd\YearEndApplicationController::parseInsuranceCertificateXml()`
  （ルート`year_end_adjustment.insurance.parse_xml`、POST）：アップロードされたXMLを解析して
  JSONで返すだけで、DBには何も書き込まない。実際の保存は既存の`updateInsurance()`が行う。
- `resources/views/staff_portal/year_end/index.blade.php`：保険料控除セクションに
  「電子的控除証明書（XML）から自動入力」というfile inputを追加。選択時にJS（同ファイル末尾の
  `setupInsuranceXmlAutoFill()`）が上記エンドポイントへPOSTし、契約ごとに既存の
  `hoken-add-more-btn`と同じ行追加ロジック（`makeRowAdder()`）を呼んで新しい行を作り、
  返ってきた値をフィールドへ流し込み、証憑ファイル欄へは選択されたXMLファイル自体を
  `DataTransfer`で複製して割り当てる。行の「この欄に保険を追加する」チェックと外側の
  「保険料控除が増えましたか？」チェックも自動でONにする。
- `app/Services/YearEnd/CertificateFileService.php`：`store()`に`.xml`拡張子/`text/xml`・
  `application/xml`のMIMEタイプの分岐を追加（PDFと同様、圧縮せずそのまま保存）。生のXML文字列を
  読み出す`getContents()`も追加（プレビュー生成用）。
- admin側の整形プレビュー：`YearEndAdjustmentV2Controller::hokenCertificatePreview()`で、
  拡張子が`xml`の場合は`LifeInsuranceCertificateXmlParser`で読み直し、
  `certificate_preview.blade.php`側に`isXml`/`xmlParsed`を渡して証憑らしい見た目のテーブルで
  表示する（生のXMLタグはユーザーに見せない）。パース失敗時は「元を開く」からのダウンロードに
  フォールバックする。管理画面からの証憑再アップロード（`validateHokenValues()`）も
  `.xml`を受け付けるよう`mimes`ルールを拡張済み。

**現状の制約・未実装：**

- **フィールド仕様書との突き合わせ、実際の保険会社発行サンプルXMLでの動作確認とも完了。**
  2026-09-13、国税庁が公開している「資料3_帳票フィールド仕様書（源泉徴収票等_Ver1）.xlsx」
  （`storage/app/templates/kojoall/01電子的控除証明書等/`、ユーザーが一括DLしたもの。
  `storage/app`はgitignore対象なのでこのファイル自体はリポジトリには無い）の「TEG800」シートと
  `LifeInsuranceCertificateXmlParser.php`の実装を1項目ずつ突き合わせ、5つの契約区分パス
  （WCE00500/540/590/630/670＝旧制度一般/旧制度年金/新制度一般/新制度介護医療/新制度年金の
  「申告額（参考）」）を含む全フィールドマッピングが仕様書と一致することを確認済み。さらに
  SOMPOひまわり生命保険が実際に発行した電子的控除証明書（介護医療用・一般用の2種）を
  スタッフ037で本番相当のアップロード経路から取り込み、自動入力・国税庁公式XSLTプレビュー
  ともに正しく表示されることを確認済み（同日、日付項目の桁数バグをこのテストで発見・修正）。
- XMLの電子署名（`dsig:Signature`、`XMLDSIG050.xsd`）は`LifeInsuranceCertificateSignatureVerifier`
  で検証する（2026-09-13実装）。ただし「保存内容が署名時点から変わっていないか」の改ざん検知
  のみで、検証鍵はXML自身のKeyInfo（埋め込みX509証明書）から取り出すため、**その証明書を
  発行したのが本当に保険会社/信頼できる認証局か（なりすまし対策）までは見ていない**。
  そこまでの認証局チェックは対象外という判断（2026-09-13、ユーザー確認：「さすがにそこまで
  高度な改ざんをする人はいない」）。
- `WCE00030`（適用制度のkubun_CD: 1/2/3）は未使用。現状は新旧制度×一般/介護医療/年金の
  ブロック構造（値が入っているかどうか）だけで区分を判定しており、実データで整合するか未確認。
- TEG800（生命保険料控除証明書）のみ対応。地震保険料・寄附金・国民年金・小規模企業共済等の
  他の証憑種類は未対応（下記スキーマは保存済みなので、対応する場合はパーサーを追加する形になる）。

## 国税庁公式XSLTによる証憑プレビュー（2026-09-13追加）

国税庁がTEG800（生命保険料控除証明書）向けに公開しているXSLTスタイルシート
（`CMTEG800-001.xsl`）をXMLに適用し、公式レイアウトのままHTML表示できるようにした。

- `docs/reference/nta_certificate_xsd/stylesheet/CMTEG800-001.xsl`：ユーザーが国税庁から
  一括DLした資料一式（`storage/app/templates/kojoall/`、gitignore対象で本体は非コミット）の
  中から、公式スタイルシートだけをここへコピーして保存（他の証憑種類・CSV変換モジュール
  バイナリ等は現状使わないためコピーしていない）。
- `app/Services/YearEnd/LifeInsuranceCertificateXsltRenderer.php`：`XSLTProcessor`で
  上記スタイルシートを適用しHTML文字列を返す。`LifeInsuranceCertificateXmlParser`
  （mx_hokenへの自動入力用の値抽出）とは役割が別。
- `YearEndAdjustmentV2Controller::hokenCertificateXsltPreview()`
  （ルート`admin.work.year_end_adjustments.hoken.certificate_xslt_preview`）：変換結果の
  HTMLをそのまま返す（スタイルシート自体が`<HTML><HEAD>...`を含む1つの文書を生成するため、
  管理画面のレイアウトには組み込まずiframeで埋め込む）。
- `certificate_preview.blade.php`：XML証憑の場合、既存の「自動入力用に読み取った内容」の
  表の上に、このiframeを追加（どちらか一方に置き換えるのではなく、両方見比べられる形）。

**動作要件**：`ext-xsl`（PHPのXSL拡張）が必要。本番環境で入っていない場合は
（GD/mbstring同様）環境側で有効化してもらう必要がある。

**証明日・年金支払開始日の日付項目のバグ修正（同日）**：`WCC00000`（証明日）・
`WCE00180`（年金支払開始日）は、年・月・日がそれぞれ`gen:yyyy`/`gen:mm`/`gen:dd`という
別々の子要素で持つ構造（国税庁のCSV変換モジュール定義`TEG800_1.1_tpl.xml`で
`zeroSuppress="1"`＝月日はゼロ埋めしない仕様と判明）。以前は親要素のtextContent
（子要素値の単純連結）を「西暦4桁+月2桁+日2桁の8桁固定」とみなして正規表現で
切り出していたが、月・日が1桁（1〜9月、1〜9日）の場合は6〜7桁になり、日付が
丸ごと空になっていた（テストデータで再現・修正確認済み）。`dateFromParts()`で
年・月・日を別々に取得しゼロ埋めして組み立てる方式に修正。

**国税庁公式スキーマを取得・保存済み**：`docs/reference/nta_certificate_xsd/`に、e-Taxが
公開しているXMLスキーマ（XSD）を保存してある（2026-08-19、`https://www.e-tax.nta.go.jp/shiyo/download/kojo04.CAB`
から取得・展開）。証憑の種類ごとに別ファイル（すべて`kyotsu/`配下）：

- `TEG800-001.xsd`：生命保険料控除証明書（対応済み）
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
他の証憑種類（地震保険料・寄附金等）に対応する場合はそこから読み始めるとよい。

## 対象帳票

管理側詳細画面から以下を別タブでプレビューする。

- 保険料控除申告書
- 基礎控除申告書
- 扶養控除申告書
- 源泉徴収簿
- 源泉徴収票

## テンプレート取得

PDFテンプレートは `storage/app/templates/year_end/` から取得する。
年度ごとにサブフォルダを分けている（2026-09-06、フラット+ファイル名prefixから変更。
本番への手動アップロード時に「その年のフォルダを丸ごとコピー」で済ませ、ファイルの
取りこぼしを防ぐため）。

優先順は「targetYearから2025年まで年度を1年ずつ遡り、最初に見つかったファイルを使う」。
2025年固定フォールバックにすると、例えば2026年に更新したファイルが2027年も変更無しの
場合に2026年版を飛び越えて2025年版まで戻ってしまうため、遡り方式にしている（2026-09-06）。

つまり、様式に変更が無い年はファイルを複製しなくてよい。前に様式が変わった年のファイルが
そのまま使われる。変更があった年だけ、同じファイル名でその年度のフォルダに新しいPDFを置く。

**重要（2026-09-06発覚）**: `storage/app/`配下は`private/`・`public/`以外Laravel標準で
git管理対象外（`storage/app/.gitignore`）。このPDF原本一式は**gitでは絶対にデプロイされない**
ため、新しい年度のテンプレートを追加した時・新規サーバー構築時は、このフォルダの中身を
手動でコピーする必要がある。忘れると`yearEndPdfTemplatePath()`が404を返す
（本番の源泉徴収票プレビューが404になった実例あり、原因はこの手動コピー漏れだった）。

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

## 会社印（角印）の重ね書き・PNG透過の扱い方（2026-09-06）

`writePdfCompanySeal()`（源泉徴収票へ会社印を重ねる処理）で、PNGの透過を正しく見せる方法に
かなり遠回りした。**この教訓はTCPDF/Fpdiで画像を透過させたい場面全般に使えるので、印影に
限らず流用してよい。**

会社印は `mx_companies.seal_image_path` で会社ごとに切り替え、実ファイルは
`storage/app/templates/seals/` 配下（`storage/app`全体がgitignore対象なので、
テンプレートPDFと同じく本番への配置は手動)。

**やってはいけない・ハマった道**：PNGを`$pdf->Image()`にそのまま渡すと、TCPDFは内部で
GDの`imagecopy()`を使い「半透明部分を黒背景と合成してから色を抜き出す」実装になっている
（`vendor/tecnickcom/tcpdf/tcpdf.php`の`ImagePngAlpha()`付近）。半透明部分が広い画像だと
色が黒っぽく化ける。これを避けようとRGBとアルファを自前で分離し`$ismask`/`$imgmask`
パラメータで渡す方式も試したが、色は正しくなるものの何故か画質が劣化して文字がつぶれる
（原因未特定）。

**実際に効いた方法**：画像自体は何も加工せずシンプルに`$pdf->Image()`へ渡し、
**`setAlpha()`でCSSの`opacity`のように描画全体へ均一な透明度をかける**。

```php
$pdf->setAlpha(0.6); // 0〜1、CSSのopacityと同じ感覚
$pdf->Image($sealPath, $x, $y, $size, $size);
$pdf->setAlpha(1); // 必ず戻す（後続の描画に影響するため）
```

下の文字を透けさせたい、という要件そのものは「PNG側のアルファチャンネルを正しく処理する」
以外の手段（TCPDFの描画時透明度）で満たせる。PNGのアルファチャンネルに頼るのは、
画像自体に部分的な透過（一部だけ透明、一部は不透明、のような複雑な形）が必要な時だけでよい。