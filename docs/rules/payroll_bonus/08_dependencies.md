# 依存関係

## 目的

修正時に「どこを触ればいいか」を迷わないようにする。

このファイルは、画面ごとの関係ファイルと触る順番を固定するためのもの。

## 基本の触る順番

1. View
2. Controller
3. Service
4. Trait
5. DBカラム

DBカラム変更は最後に検討する。既存カラム名を変える場合は、先に `06_db_columns.md` を確認する。

## 給与計算

```text
給与計算
├ PayrollV2Controller
├ PayrollV2UpdateService
├ PayrollV2CalculationFlowService
├ PayrollV2RecalculateService
├ PayrollV2SocialInsuranceAmountService
├ PayrollV2IncomeTaxService
├ PayrollV2EmploymentInsuranceService
├ PayrollV2HomeVisitAllowanceService
├ payroll/index.blade.php
├ payroll/page_script.blade.php
├ payroll/page_state_runtime.php
└ public/css/admin_v2/payroll.css
```

### 注意

- 保存ボタンは手入力値を守る。
- 雇用保険、所得税、社保、往診手当などの個別ボタンで給与マスタを勝手に再取込しない。
- 業務委託は税、社保、控除系を計算しない。
- 日額交通費、課税通勤費、非課税通勤費は交通費ルールを確認してから触る。

## 賞与計算

```text
賞与計算
├ PayrollV2Controller
├ PayrollV2UpdateService
├ PayrollV2CalculationFlowService
├ PayrollV2BonusSocialInsuranceService
├ PayrollV2BonusIncomeTaxCalcService
├ PayrollV2SocialInsuranceAmountService
├ bonus/index.blade.php
├ bonus/page_script.blade.php
└ public/css/admin_v2/payroll.css
```

### 注意

- 給与と同じ意味の合計は共通Serviceを使う。
- 子ども支援金を社保合計から漏らさない。
- 賞与だけ別式にしない。

## 賃金台帳

```text
賃金台帳
├ PayrollV2Controller
├ wage_ledger/index.blade.php
├ wage_ledger/personal.blade.php
├ shared/payroll/payslip_item.blade.php
└ public/css/admin_v2/payroll.css
```

### 注意

- DB値をそのまま表示する。
- 賃金台帳用データを作らない。
- 表示だけの理由で小数を丸めない。
- 差引支給合計に立替経費を漏らさない。

## 振込一覧

```text
振込一覧
├ PayrollV2Controller
├ transfer_list/index.blade.php
└ public/css/admin_v2/payroll.css
```

### 注意

- 差引支給額を基準にする。
- 手入力後の保存値を表示する。
- 帳票側で支給、控除を再計算しない。

## 明細

```text
明細
├ PayrollV2Controller
├ shared/payroll/payslip_item.blade.php
└ public/css/admin_v2/payroll.css
```

### 注意

- 給与、賞与で同じ表示部品を使える場合は共通化する。
- ただし表示条件が違う場合は無理にまとめない。
- 役員報酬は現在ラベル扱いで、旧カラムを復活させない。

## 勤怠反映との関係

```text
勤怠反映
├ AttendanceV2Controller
├ AttendanceV2BulkReflectService
├ AttendanceV2PaidLeaveUsageService
├ AttendanceV2ConfirmedStateService
└ PayrollV2Controller
```

### 注意

- 有休残、使用日数は勤怠反映のルートでそろえる。
- 再計算ボタンに有休反映ロジックを増やさない。
- 一括反映、個別反映で同じ結果になることを確認する。

## スタッフマスタとの関係

```text
スタッフマスタ
├ StaffV2Controller
├ StaffV2Service
├ staff/index.blade.php
├ staff/page_script.blade.php
└ staff/tabs/payroll_master.blade.php
```

### 注意

- 給与マスタの交通費は `traffic_pay` が基本。
- `traffic_day` は日額交通費。
- `hourly_salary` は時給。
- `hourly_pay` は時給換算。
- ラベルだけの問題ならDBカラム名を変えない。
