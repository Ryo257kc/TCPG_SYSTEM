# 賞与計算ページ ルール

対象:

- `resources/views/admin_v2/work/bonus/index.blade.php`
- `resources/views/admin_v2/work/bonus/page_script.blade.php`
- `App\Http\Controllers\Admin\V2\PayrollV2Controller`
- `PayrollV2BonusSocialInsuranceService`
- `PayrollV2BonusIncomeTaxCalcService`

## ページの役割

賞与データの表示、手入力、保存、再計算、確定、振込一覧、賃金台帳を扱う。

## 給与との共通点

- 支給合計、社保合計、控除合計は給与と同じ考え方で `PayrollV2UpdateService` を通す。
- 子ども支援金は社保合計に含める。
- 賞与も明細と賃金台帳に表示する。

## 賞与固有の注意

- 賞与の社会保険は標準賞与額を使う。
- 健康保険の年度累計上限、厚生年金の同月上限を確認する。
- 賞与の所得税は前月給与の社保控除後金額、扶養人数、賞与額を基準にする。
- 賞与の再計算も、最終合計は `PayrollV2UpdateService::saveBonus()` に寄せる。

## レイアウト

- 給与と同じ入力幅、同じ表部品を使う。
- 共通部品は `payroll.css`。
- 賞与だけの横並び調整はBlade内styleでよい。
- 基本情報ボックスだけが横幅を吸いすぎないようにする。

## 旧カラム

- 役員報酬として使うのは `allowance_amo_2`。
- `allowance_2` は旧カラムで、現在は `x_allowance_2` に退避済み。
- `officer_com` は旧カラムで、現在は `x_officer_com` に退避済み。