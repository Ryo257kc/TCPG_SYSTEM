# コード配置・構成ルール

## 目的

`00_global.md`が計算・データ整合性のルールなのに対し、こちらはファイル配置・CSS・ルーティング
などコード構成のルール。旧`AGENTS.md`（codex作成、英語、長すぎて読まれなくなっていた）から
今も有効な部分だけを移した（2026-08-17）。

## ページ構成

- 通常ページは「そのページのフォルダの`index.blade.php`」「そのページのCSS」
  「必要な時だけ1つの小さい`page_script.blade.php`か`tabs/`等のサブフォルダ」で完結させる。
- `header`/`filter_form`/`content`/`list`/`page_style`のような細切れBlade分割はしない。
- 汎用`partials/`フォルダを新規に作らない。複数ページで本当に共有する時だけ共有部品にする。

## admin_v2 と staff_portal の分離

- `admin_v2`（管理側）と`staff_portal`（スタッフ側）は別プロジェクトとして扱う。
- ルート名、共通レイアウト、ヘッダー、フッター、CSS、Controllerの前提を混ぜない。
- 一方の共通部品をもう一方へそのまま流用しない（明示的な統合依頼がない限り）。

## CSS配置

- 管理側共通CSSは`public/css/admin_v2/`、スタッフ側共通CSSは`public/css/staff_portal/`。
- 両側で共有する`public/css/shared/`のようなフォルダは作らない。
- 全ページ共通の見た目は共通CSSへ。**そのページだけの見た目はBlade内の`<style>`直書きでよい**
  （2026-08-18訂正：以前は「`<style>`直書き禁止、実CSSファイルを作る」がルールだったが、
  だいぶ前に変更済み。ドキュメント更新が追いついていなかった）。
- 複数ページで使う・共通化すべきCSSだけを`public/css/`側の共通CSSファイルへ切り出す。
- 共有部品（shared item）を使うページは、その部品のCSSも共有元1箇所を正とし、
  ページごとに見た目が変わる分岐を作らない。

## ルート・Controller・View

- ルートは業務ドメイン単位でまとめる（`attendance.*`など）。1画面1ルートファイルにしない。
- Controllerも業務ドメイン単位（例: `AttendanceController`）を基本にし、1画面1Controllerにしない。
- Viewフォルダの責務はルート・Controllerのドメインと一致させる。

## 日本語ファイルの編集

- 日本語を含むBlade/PHP/SQLファイルをPowerShellの`Set-Content`やインライン出力で書き換えない
  （文字化けの原因になる）。
- `UTF-8 with BOM`で保存されているファイルはBOM無し`UTF-8`で保存し直す。
- PowerShellの表示結果だけで文字化けを判断しない。実際に壊れているかはエディタ側で確認する。
  ユーザーから文字化けの報告が無い限り、文字化けと決めつけない。

## UI・表示のルール

- 確認されていない仮の値・概算値・検証用の値を、通常の値として画面に出さない。
  一時的に出す必要がある場合は「仮」「概算」「確認用」と明記する。
- ユーザーから明示的に頼まれていないラベル・見出し・メニュー・リンクの文言や配置を、
  自分の判断で変更しない（今回のセッションでも、意図せぬラベル変更が何度か問題になった）。

## 「戻る」リンクに url()->previous() を使わない

編集保存がPOST→同じページへのGETリダイレクトになっているページ（一覧の詳細画面など）で
`url()->previous()`を使うと、保存後は「前回のURL」が遷移元の一覧ではなく詳細ページ自身の
URLに上書きされ、「戻る」ボタンが反応しなくなる（LaravelはGETリクエストの度に
`_previous.url`セッション値を現在のURLで更新するため）。遷移元が一意に決まるページでは
`route()`で明示的にリンク先を組み立てる（2026-08-18、`store_daily_report/daily_summary/detail`と
`staff_portal/admin/shift/basic`で発覚・修正、詳細は`docs/rules/store_daily_report/01_daily_summary.md`）。

## DB・SQL Server依存の最小化

- 新規・修正コードでは、既存のService/Repositoryパターンで済む場合に
  `DB::connection('sqlsrv')`を無闇に増やさない。
- `whereRaw()`や`CONVERT`/`LTRIM`/`RTRIM`/`YEAR`/`MONTH`等のSQL Server依存関数は必要最小限にする。
- 動いている大きな範囲を、抽象化のためだけに書き直さない。

## 外部フォルダアクセス

- このプロジェクト（`C:\dev\tcpg_system_laravel`）の外を読み書きしない。
- 例外: `C:\Users\ryo25\OneDrive\dev\samples`は読み取り専用でアクセス可（書き込み・削除は禁止）。
- 一時ファイルは`C:\dev\tcpg_system_laravel\_tmp_codex`にまとめる（作業後に削除を確認）。
