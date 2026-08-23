# 往診 変更履歴・注意点

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
