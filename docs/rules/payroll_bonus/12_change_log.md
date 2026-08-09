# 給与・賞与 変更履歴

## 2026-08-09

### 賞与の雇用保険 文字化け修正

- `PayrollV2EmploymentInsuranceService::recalculateBonus()`の「保育事業部」「鍼灸整骨院」除外判定が
  文字化けした文字列リテラルで、一度も一致していなかった（同ファイルの月給側`recalculate()`は
  正しい文字列だった。`PayrollV2OvertimeDeductionService`で同種のバグを直したのと同じ日に発覚）。
  `hex2bin()`方式に統一。

### 賞与の標準報酬月額 上限判定を1箇所に統合

- `PayrollV2Controller::attachBonusCalc()`（賞与一覧の表示用）が、保存処理
  `PayrollV2BonusSocialInsuranceService::resolveTargetStandards()`とは別に、同じ計算を
  独自の式（同月内の判定で日付の前後関係を使っていた）で持っていた。ユーザー確認：
  Codexが後から追加したもので、既存を直さず新しく作ってしまったパターン。
  実害は無かった（同一スタッフが同月に2回賞与を受け取ることはない）が、式のズレ・
  将来の乖離リスクがあったため、共通の`computeTargetStandards()`（public static、
  `PayrollV2BonusSocialInsuranceService`側）に統合。表示側は自分の`kyuyo_sho_no`を
  除いた履歴を渡して呼ぶだけにした。

### デッドコード削除

- `PayrollV2RecalculateService`内の`loadSocialInsuranceRates()`／`calculateSocialInsurance()`
  （＋専用ヘルパー`employeeInsuranceAmount()`／`officeInsuranceAmount()`／`shouldApplyKaigo()`）は
  どこからも呼ばれていない、`PayrollV2SocialInsuranceAmountService`とほぼ同内容の重複だった。削除。

### 差引支給額の保存を復活

- `supply_deduction_sum`が2026年2月分を最後に保存されなくなり、表示側が
  `PayrollV2SummaryService::transferAmount()`をライブ計算する形に静かに変わっていた
  （「帳票側で再計算しない」ルール違反の状態）。
- `PayrollV2UpdateService::rebuildTotals()`／`rebuildBonusTotals()`で`transferAmount()`を呼び、
  結果を`supply_deduction_sum`へ保存するよう修正。
- 表示側（`PayrollController`（StaffPortal）・`PayrollV2Controller`・`PayrollV2SummaryService`）は
  `transferAmount()`の直接呼び出しをやめ、保存済み`supply_deduction_sum`を読むだけに変更。
- 過去データ（〜2026年2月）は本番サーバーからの同期で埋める想定のため、バックフィルはしていない。
- 詳細は `03_wage_ledger.md`。

### DB・スキーマの握りつぶし修正

- `StaffPortal\PayrollController`が手当名マスタを存在しない`t_allowance`という名前で参照していた
  （正しくは`mx_allowance`）。`try/catch(\Throwable)`で握りつぶされていたため、会社ごとの
  手当名カスタマイズ機能が何ヶ月も静かに無効化されていた。
- 同様の`hasTable`/`hasColumn`の握りつぶしが`AuthController`（`PayrollController`と全く同じ内容が
  複製されていた）、`HandlesStaffPortalContext::shouldShowPayrollLinks()`、
  `AllowanceV2Service`・`CompanyV2Service`・`ReportV2SanteiCsvService`・
  `PayrollV2BonusSocialInsuranceService`・`PayrollV2FuyoService`・
  `PayrollV2EmploymentInsuranceService`・`PayrollV2StaffMasterService`にも見つかり、
  すべて握りつぶしを削除（本当にテーブル/カラムが無ければ例外がそのまま出るようにした）。
- `useMxStaffTable()`/`useMxPayrollTable()`/`useMxStoreTable()`が`PayrollController`と
  `AuthController`に複製されていて、`commonViewData()`経由で全26コントローラーから
  呼ばれる`shouldShowPayrollLinks()`が実は他の24コントローラーには存在しないメソッドを
  呼んでいた（毎回例外→握りつぶしで「給与リンクが消えるだけ」に見えていた）。
  `HandlesStaffPortalContext`トレイトに1箇所へ統合。
- 詳細は `99_do_not_touch.md`「DB・スキーマのエラーを握りつぶさない」。

### DBカラム整理

- `allowance_1`、`allowance_3`〜`allowance_16`（手当名称の旧テキスト列、`mx_allowance`マスタに
  置き換え済みで未参照）を`x_`退避。
- `mx_nen_tyo.haigu_toku_deduction`（配偶者控除）→`haigu_deduction`、
  `haigu_toku_deduction_amo`（配偶者特別控除）→`haigu_toku_deduction`にリネーム
  （旧名は「特別」でない方に`toku`が付く紛らわしい命名だった）。
- `mx_nen_tyo.haigu_shotoku_sum`（全レコードで未使用）を削除。

## 2026-08-01

### 整理

- 給与・賞与の共通CSSを `payroll.css` に整理。
- 賞与画面の入力欄幅を給与と同じにした。
- 賞与画面の基本情報ボックスが横幅を吸いすぎないように調整。
- 旧 `bonus.css` 参照を削除。

### 計算

- 給与・賞与の社保合計、控除合計を `PayrollV2UpdateService` に寄せた。
- 子ども支援金を社保合計に含めるよう整理。
- 賞与保存を `saveBonus()` に寄せた。

### DB

- `officer_com` を `x_officer_com` に退避。
- `allowance_2` を `x_allowance_2` に退避。
- レセDBの `x_department_no_old`、`x_staff_name_old` は退避済みとして確認。
- `store_name_old` は存在しないことを確認。

### 役員報酬

- 現在の役員報酬は `allowance_amo_2`。
- `allowance_2`、`officer_com` は旧カラム扱い。

## 2026-08-01 ドキュメント構成を追加

- `07_architecture.md` を追加。
- `08_dependencies.md` を追加。
- `09_coding_rules.md` を追加。
- `99_do_not_touch.md` を追加。
- 今後、全ページ共通ルールは薄く保ち、ページ別の業務ルールは対象別docsに分ける方針にした。
