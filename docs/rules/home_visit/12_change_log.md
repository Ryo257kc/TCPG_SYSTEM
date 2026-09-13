# 往診 変更履歴・注意点

## 2026-09-13 スタッフポータルの一般公開に往診だけ未対応、メニューをシステムマスタ限定に変更

他ドメインはスタッフへの一般公開を進める段階になったが、往診だけ準備が整っていないため、
ダッシュボードのメニューカード「往診」「往診事務」「往診管理」の`visible`条件を
一旦`$isAdmin`（システムマスタ）のみに変更した（`resources/views/staff_portal/dashboard/index.blade.php`）。

**Controller側の権限チェック（`isOushinStaff()`/`isAccounting()`/`isVisitManagement()`等）は
変更していない。** メニューを隠しているだけなので、対象スタッフがURLを直接開けば従来通り
動作する（`00_global.md`の「メニューを隠すだけで満足しない」という原則とは逆方向の変更に
見えるが、ここは「まだ一般公開する準備ができていない機能を隠す」という意図的な暫定対応。
往診の一般公開準備が整ったら、`$isAdmin`単独の条件を元の`|| $isOushinStaff`等へ戻すこと）。

同じ理由で、ダッシュボードの「入金確定が未確定です（◯年◯月）。内容を確認してください。」
バナー（`hv_ryoukin`の自分の未確定月を知らせる、`hv_office.payment_confirmed`へのリンク）も
`AuthController::dashboard()`側でシステムマスタ以外には計算・表示しないよう変更した
（`$isAdminForDashboard`で`unconfirmedPaymentMonths()`の呼び出し自体を分岐）。

## 2026-08-24 フォールバック監査（保存確認漏れ一斉修正と同時に実施）

`ReceiptController.php`の入金確定バナー表示（`is_payment_confirmed`/`payment_confirmed_at`）が、
対象月の全明細ではなく`$items->first()`（先頭行）だけを見て判定していた。一括確定後に
未確定の明細が追加されても、先頭行が確定済みなら画面全体が確定済みに見えてしまう。
`hv_office/DepositManagementController::depositData()`の`isAllConfirmed`判定
（`->every()`で全行確認）と同じロジックに揃えて修正。

`AttendanceV2ConfirmedStateService::mapByStaffIds()`は、`mx_time_cards.attendance_checked`
カラムが存在しない場合に「全スタッフ未確定」を黙って返していた。この判定は往診の
売上ロック（`DailyReportController::isSalesLockedByAttendance()`）だけでなく、給与側の
編集ロック解除判定（`PayrollV2UpdateService`）や管理画面の勤怠確定表示にも使われており、
スキーマが想定外に変化した場合に「未確定」＝ロック解除側に静かに倒れる危険な作り。
DEV上は現在カラムが存在することを確認済み（実害なし）だが、フォールバックをやめて
例外を投げる形に変更し、今後スキーマが崩れた場合に気づけるようにした。

## 2026-08-24 保存確認漏れ一斉修正（041優先範囲の監査に続き、往診ドメインも横断監査）

`PatientController::update()`/`delete()`（患者マスタ、`hv_kanjya_info`）と
`MonthlyVisitController::update()`（月間回数・日報編集、`hv_nippou`）は、対象行の存在確認・
`update()`/`delete()`の影響行数チェックが**一切**無く、常に成功メッセージを返していた。
特に`MonthlyVisitController::update()`は事前の`first()`すら無い、このセッションで見つけた中で
最も無防備な形。標準負担額・治療費・距離等の金額項目も対象に含まれる。
`DailyReportController::confirm()`/`unconfirm()`/`adminConfirm()`・
`PaymentConfirmedController::confirm()`/`unconfirm()`（本人確定・管理確定・入金確定の
一括更新系）も影響行数を見ずに固定メッセージだった。全て影響行数を見て、0件なら
対象なし/見つからない旨を返すよう修正。

## 構造的な壊れ方への警戒(2026-08-12時点)

往診(home-visit)セクションは新システムで最初に作られた部分で、ユーザー曰く「往診を最初に作ってとんでもなくめちゃくちゃにされて、多分２回ぐらい作り変えてる」。このため他のドメインより構造的な破損（コンパイル済みBladeキャッシュがそのままsourceの`.blade.php`として保存されている等）が起きやすい。

- 実例: `resources/views/staff_portal/home_visit/daily_report/index.blade.php`（コンパイル済みキャッシュ状態で発見、クリーンなBlade構文に書き直し済み）、`resources/views/staff_portal/dashboard/index.blade.php`（2026-08-12時点でも同様の状態のまま、動作はするが未修正）。
- home_visit/*、hv_office/*、dashboard、または`HandlesStaffPortalContext`の往診関連権限ヘルパー周りで、死んだコード・関係ないコピペ属性・権限チェック漏れ・列数不一致など構造的に不自然な箇所を見つけたら、単なるtypoよりも過去のリビルドの残骸である可能性を疑い、`tests/hinata_oushin/`のレガシーソースと突き合わせて確認すること。

## 月次締めロックがUI側だけだったバグ(2026-08-15)

`HomeVisitCounterController::save()`/`delete()`は、月次締め判定用のprivateメソッド(`isReceiptMonthlyClosed()`等)は存在しBlade側の表示制御には使われていたが、**controller側の保存・削除処理では一度も呼ばれていなかった**。通常操作では締め後ロックがかかっているように見えるが、直接リクエストや古いページからの送信では素通りしていた。codex実装でUI側の表示制御だけ作り、サーバー側の最終防衛線を付け忘れるパターン(他に`StoreDailyReportController`でも同型の不具合あり、`docs/rules/store_daily_report/99_do_not_touch.md`参照)。

## 往診管理 vs 往診事務の権限区分(2026-08-14確認)

似た名前だが意味が異なる2つのStaffPortalメニューセクション:

- **往診管理**(`staff_portal/dashboard/index.blade.php`の`'visible' => $isVisitManagement || $isAdmin`): 往診管理者のみが編集・閲覧できる。
- **往診事務**(`'visible' => $isAccounting || $isAdmin || $isViewOnly || $isVisitManagement`): スタッフ・管理者が入力済みの内容を、事務スタッフが確認するためのセクション（原則view-only）。往診管理者もここを見られるがview-onlyで、例外は往診売上（`isAccounting`権限保持者のみ編集可）。

メニューカードがどのセクションに「属すべきか」を推測で判断せず、`resources/views/staff_portal/dashboard/index.blade.php`の実際の`visible`条件を必ず確認すること。日本語ラベルだけでは判断できない（「回数表」と「月間回数」のような紛らわしい類似名も同様）。

## hv_nippou/hv_ryoukin のNULLバックフィル(2026-08-13)

ユーザーはレガシーPHPサイト(`tests/hinata_oushin/`)を別の本番DBに対して並行運用中で、`hv_nippou`/`hv_ryoukin`/`hv_kanjya_info`は将来的にレガシー本番から再同期される可能性がある(一回限りの移行ではない)。再同期時は、空欄と数値が混在するとExcelフィルタが重くなる問題が過去に起きているため、以下の列をNULL→0でバックフィルすること(再利用可能な同期スクリプトはリポジトリに存在しない、過去の同期は使い捨ての手動スクリプト):

- `hv_nippou.distance`/`private_fee`/`copayment_amount`/`uncollected_amount`
- `hv_ryoukin.collected_amount`/`unit_price`/`billing_count`/`adjustment_amount`

意図的にバックフィル対象外(正当に空欄になりうる項目): `hv_nippou.initial_fee`(初診時のみ)、`home_visit_start_point`、`category_hours`(ほぼ未使用)、`patient_id`(edge case)。
