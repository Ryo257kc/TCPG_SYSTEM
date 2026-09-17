<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Sales\SalesV2Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AccountingV2Controller extends Controller
{
    // 仕訳帳CSV取込の要確認判定・ハイライトで比較する項目。金額・科目が本質的に重要なため
    // ここだけに絞る。摘要は表示はするが一致しなくても確認対象にしない（自由記述で表記ゆれが
    // 普通にあるため）。部門名・税区分・メモ・管理番号等も、CSVの中身が同じでもマッピング元
    // マスタ（mx_departments等）が後から変わると再取込のたびに「違う」判定になり、確認しようが
    // ない差分で埋もれるため比較対象に含めない（2026-08-17）。
    private const REVIEW_COMPARE_FIELDS = [
        'debit_account_title' => true,
        'debit_amount' => true,
        'credit_account_title' => true,
        'credit_amount' => true,
    ];

    // 絞り込み欄に「空白」と入れた時だけ、その項目が借方・貸方どちらか片方でも空欄の明細を
    // 拾う特別扱いにする（2026-09-14、ユーザー要望。フィルタ欄がこれ以上増やせないため、
    // 専用チェックボックスではなく既存の入力欄の中で完結させる。同じ入力欄・
    // 候補一覧(datalist)の両方でこの値を使うため定数化）。
    private const BLANK_FILTER_SENTINEL = '空白';

    public function __construct(
        private readonly SalesV2Service $salesService,
    ) {}

    public function journalEntries(Request $request): View
    {
        // 表示形式：グループ折りたたみ(既定)とフラット(明細1行=1行)を切り替える
        // （2026-09-14、ユーザー要望。Accessでは全行フラットに並べて部門欄等を目視確認
        // できていたが、今のグループ折りたたみではそれができないため）。
        $displayMode = $request->query('display', 'group') === 'flat' ? 'flat' : 'group';
        $dateFrom = trim((string) $request->query('date_from', now()->startOfMonth()->format('Y-m-d')));
        $dateTo = trim((string) $request->query('date_to', now()->endOfMonth()->format('Y-m-d')));
        $selectedCompanyName = trim((string) $request->query('company_name_short', ''));
        $counterparty = trim((string) $request->query('counterparty', ''));
        $amount = trim((string) $request->query('amount', ''));
        $summaryText = trim((string) $request->query('summary_text', ''));
        $accountTitle = trim((string) $request->query('account_title', ''));
        $itemName = trim((string) $request->query('item_name', ''));
        $departmentName = trim((string) $request->query('department_name', ''));
        $vaultName = trim((string) $request->query('vault_name', ''));
        $managementNumber = trim((string) $request->query('management_number', ''));
        $journalBreakdown = trim((string) $request->query('journal_breakdown', ''));
        $parsedAmount = $this->parseMoneyValue($amount);
        // 除外モード：チェックが付いている間、入力済みの全絞り込み欄を「一致するものだけ表示」
        // ではなく「一致するものを除いて表示」に切り替える（2026-09-14、ユーザー要望。
        // 項目ごとの個別トグルは使わない・全体で1つのチェックのみ、混在運用はしない）。
        $excludeMode = $request->boolean('exclude_mode');

        $companyOptions = DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->select('company_name_short')
            ->whereNotNull('company_name_short')
            ->where('company_name_short', '<>', '')
            ->groupBy('company_name_short')
            ->orderBy('company_name_short')
            ->pluck('company_name_short')
            ->map(fn($value): string => trim((string) $value))
            ->filter(fn(string $value): bool => $value !== '')
            ->values()
            ->all();

        $counterpartyOptions = $this->fetchDistinctUnionOptions('debit_counterparty', 'credit_counterparty', false, $dateFrom, $dateTo);
        $amountOptions = $this->fetchDistinctUnionOptions('debit_amount', 'credit_amount', true, $dateFrom, $dateTo);
        $summaryTextOptions = $this->fetchDistinctOptions('summary_text', false, $dateFrom, $dateTo);
        $accountTitleOptions = $this->fetchDistinctUnionOptions('debit_account_title', 'credit_account_title', false, $dateFrom, $dateTo);
        $itemNameOptions = $this->fetchDistinctUnionOptions('debit_item_name', 'credit_item_name', false, $dateFrom, $dateTo);
        // 部門欄には mx_departments.store_short_name（例: T_さくら 店舗）がそのまま保存
        // されており、絞り込み欄に生のコードが出て読みにくかった（2026-09-14、ユーザー
        // 指摘）。候補一覧・入力欄とも store_category（さくら店舗 等）の読める名前だけに
        // 統一し、生のコードは一切表示しない。実際の検索（$departmentNameを使ったLIKE、
        // 下記）は、読める名前から生のコードへ変換してから行う。
        $departmentLabelMap = $this->fetchDepartmentLabelMap();
        $departmentOptions = array_values(array_unique(array_map(
            fn(string $value): string => $departmentLabelMap[$value] ?? $value,
            $this->fetchDistinctUnionOptions('debit_department_name', 'credit_department_name', false, $dateFrom, $dateTo),
        )));
        $departmentSelectOptions = $this->fetchDepartmentSelectOptions();
        // 金庫(通帳)ごとにフィルタして報酬計算の漏れを確認する運用があったが、絞り込み欄に
        // 無く、グループの折りたたみを開かないと金庫名が見えなかった（2026-09-14、ユーザー
        // 指摘）。他の項目と同じ形でフィルタを追加する。
        $vaultNameOptions = $this->fetchDistinctOptions('vault_name', false, $dateFrom, $dateTo);
        $managementNumberOptions = $this->fetchDistinctOptions('management_number', false, $dateFrom, $dateTo);
        $journalBreakdownOptions = $this->fetchDistinctOptions('journal_breakdown', false, $dateFrom, $dateTo);

        $rows = DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries as entry')
            ->select([
                'entry.journal_entry_id',
                'entry.journal_breakdown',
                'entry.management_number',
                'entry.month_date',
                'entry.occurred_at',
                'entry.vault_name',
                'entry.debit_account_title',
                'entry.debit_amount',
                'entry.debit_item_name',
                'entry.debit_counterparty',
                'entry.debit_department_name',
                'entry.debit_memo_tag',
                'entry.credit_account_title',
                'entry.credit_amount',
                'entry.credit_item_name',
                'entry.credit_counterparty',
                'entry.credit_department_name',
                'entry.credit_memo_tag',
                'entry.summary_text',
                'entry.company_name_short',
                'entry.deposit_name',
                'entry.allocation_ratio',
                'entry.is_trial_balance_excluded',
                'entry.is_upload_unnecessary',
                'entry.is_reward_excluded',
                'entry.is_confirmation_checked',
            ])
            // 管理番号（例：2026/5月給与）は特定の仕訳を名指しで探す用途が主で、対象月を
            // 覚えていないことも多い。デフォルトの期間（当月）で絞られたまま検索すると
            // 見つからず不便なため、管理番号を入力した時は期間を無視して全期間から探す
            // （2026-09-16、ユーザー要望。management_numberには索引済みで全件検索も現実的）。
            ->when($dateFrom !== '' && $managementNumber === '', fn($query) => $query->whereDate('entry.occurred_at', '>=', $dateFrom))
            ->when($dateTo !== '' && $managementNumber === '', fn($query) => $query->whereDate('entry.occurred_at', '<=', $dateTo))
            // 社名・摘要・金庫・管理番号・仕訳内訳は1つの仕訳(複合仕訳含む)の中で共通の値
            // （編集フォームでも「共通」欄として扱っている）なので、行単位でそのまま絞り込んでも
            // 複合仕訳の一部だけが消えることはない。
            ->when($selectedCompanyName !== '', fn($query) => $this->applyFieldFilter($query, ['entry.company_name_short'], [$selectedCompanyName], $excludeMode))
            ->when($summaryText !== '', fn($query) => $this->applyFieldFilter($query, ['entry.summary_text'], [$summaryText], $excludeMode))
            ->when($vaultName !== '', fn($query) => $this->applyFieldFilter($query, ['entry.vault_name'], [$vaultName], $excludeMode))
            ->when($managementNumber !== '', fn($query) => $this->applyFieldFilter($query, ['entry.management_number'], [$managementNumber], $excludeMode))
            ->when($journalBreakdown !== '', fn($query) => $this->applyFieldFilter($query, ['entry.journal_breakdown'], [$journalBreakdown], $excludeMode))
            // 取引先・金額・勘定科目・品目・部門は借方/貸方や明細行ごとに値が変わる項目。
            // 行単位でそのままWHEREをかけると、複合仕訳（1つの仕訳の中に給料手当・法定福利費
            // 等、複数の明細がぶら下がっている形）で、条件に一致しない他の明細行だけ一覧から
            // 消えてしまい、グループの中身が欠けて見える不具合があった（2026-09-14、
            // ユーザーが仕訳6052013で発見：借方1行+貸方5行の給与仕訳を勘定科目で絞ったら
            // 一致する1行しか出てこなかった）。「条件に一致する行が1つでもある仕訳は、
            // その仕訳の全行を表示する」に変更する（EXISTSで同じ仕訳内の兄弟行を見る）。
            ->when(
                $counterparty !== '' || $parsedAmount !== null || $accountTitle !== '' || $itemName !== '' || $departmentName !== '',
                function ($query) use ($counterparty, $parsedAmount, $accountTitle, $itemName, $departmentName, $departmentLabelMap, $excludeMode) {
                    $query->whereExists(function ($sub) use ($counterparty, $parsedAmount, $accountTitle, $itemName, $departmentName, $departmentLabelMap, $excludeMode) {
                        $sub->selectRaw('1')
                            ->from('dbo.mx_journal_entries as sibling')
                            ->whereColumn('sibling.company_name_short', 'entry.company_name_short')
                            ->whereColumn('sibling.occurred_at', 'entry.occurred_at')
                            ->whereColumn('sibling.journal_breakdown', 'entry.journal_breakdown')
                            ->when($counterparty !== '', fn($q) => $this->applyFieldFilter($q, ['sibling.debit_counterparty', 'sibling.credit_counterparty'], [$counterparty], $excludeMode))
                            ->when($parsedAmount !== null, function ($q) use ($parsedAmount, $excludeMode) {
                                if (!$excludeMode) {
                                    $q->where(function ($subQuery) use ($parsedAmount) {
                                        $subQuery->where('sibling.debit_amount', $parsedAmount)
                                            ->orWhere('sibling.credit_amount', $parsedAmount);
                                    });
                                    return;
                                }

                                $q->where(function ($subQuery) use ($parsedAmount) {
                                    $subQuery->where(function ($q2) use ($parsedAmount) {
                                        $q2->where('sibling.debit_amount', '<>', $parsedAmount)->orWhereNull('sibling.debit_amount');
                                    })->where(function ($q2) use ($parsedAmount) {
                                        $q2->where('sibling.credit_amount', '<>', $parsedAmount)->orWhereNull('sibling.credit_amount');
                                    });
                                });
                            })
                            ->when($accountTitle !== '', fn($q) => $this->applyFieldFilter($q, ['sibling.debit_account_title', 'sibling.credit_account_title'], [$accountTitle], $excludeMode))
                            ->when($itemName !== '', fn($q) => $this->applyFieldFilter($q, ['sibling.debit_item_name', 'sibling.credit_item_name'], [$itemName], $excludeMode))
                            ->when($departmentName !== '', function ($q) use ($departmentName, $departmentLabelMap, $excludeMode) {
                                // 入力欄には読める名前（さくら店舗 等）が入るが、保存されているのは
                                // 生のコード（T_さくら 店舗 等）のため、検索前に読める名前→コードへ
                                // 変換する（2026-09-14）。一致するコードが複数あればOR、無ければ
                                // 入力値そのままで検索する（コードを直接入力・旧リンク等の保険）。
                                $matchedCodes = array_keys($departmentLabelMap, $departmentName, true);
                                $searchTerms = $matchedCodes !== [] ? $matchedCodes : [$departmentName];

                                $this->applyFieldFilter($q, ['sibling.debit_department_name', 'sibling.credit_department_name'], $searchTerms, $excludeMode);
                            });
                    });
                }
            )
            ->orderByDesc('entry.occurred_at')
            ->orderByDesc('entry.journal_entry_id')
            ->get()
            ->map(fn($row): array => [
                'journal_entry_id' => (int) ($row->journal_entry_id ?? 0),
                'journal_breakdown' => trim((string) ($row->journal_breakdown ?? '')),
                'management_number' => trim((string) ($row->management_number ?? '')),
                'month_date' => $this->formatDateValue($row->month_date, 'Y/m/d'),
                'occurred_at' => $this->formatDateValue($row->occurred_at, 'Y/m/d'),
                'occurred_at_raw' => $this->formatDateValue($row->occurred_at, 'Y-m-d'),
                'vault_name' => trim((string) ($row->vault_name ?? '')),
                'debit_account_title' => trim((string) ($row->debit_account_title ?? '')),
                'debit_amount' => $this->formatMoneyValue($row->debit_amount),
                'debit_item_name' => trim((string) ($row->debit_item_name ?? '')),
                'debit_counterparty' => trim((string) ($row->debit_counterparty ?? '')),
                'debit_department_name' => trim((string) ($row->debit_department_name ?? '')),
                'debit_memo_tag' => trim((string) ($row->debit_memo_tag ?? '')),
                'credit_account_title' => trim((string) ($row->credit_account_title ?? '')),
                'credit_amount' => $this->formatMoneyValue($row->credit_amount),
                'credit_item_name' => trim((string) ($row->credit_item_name ?? '')),
                'credit_counterparty' => trim((string) ($row->credit_counterparty ?? '')),
                'credit_department_name' => trim((string) ($row->credit_department_name ?? '')),
                'credit_memo_tag' => trim((string) ($row->credit_memo_tag ?? '')),
                'summary_text' => trim((string) ($row->summary_text ?? '')),
                'company_name_short' => trim((string) ($row->company_name_short ?? '')),
                'deposit_name' => trim((string) ($row->deposit_name ?? '')),
                'allocation_ratio' => trim((string) ($row->allocation_ratio ?? '')),
                'is_trial_balance_excluded' => (int) ($row->is_trial_balance_excluded ?? 0) !== 0,
                'is_upload_unnecessary' => (int) ($row->is_upload_unnecessary ?? 0) !== 0,
                'is_reward_excluded' => (int) ($row->is_reward_excluded ?? 0) !== 0,
                'is_confirmation_checked' => (int) ($row->is_confirmation_checked ?? 0) !== 0,
            ])
            ->all();

        $journalGroups = collect($rows)
            ->groupBy(fn(array $row): string => ($row['company_name_short'] ?: '-') . "\n" . ($row['occurred_at'] ?: '-') . "\n" . ($row['journal_breakdown'] ?: '-'))
            ->map(function ($items): array {
                $first = $items->first();
                $debitTotal = $items->sum(fn(array $row): float => (float) str_replace(',', '', $row['debit_amount'] ?: '0'));
                $creditTotal = $items->sum(fn(array $row): float => (float) str_replace(',', '', $row['credit_amount'] ?: '0'));

                return [
                    'group_key' => md5(($first['company_name_short'] ?? '') . '|' . ($first['occurred_at'] ?? '') . '|' . ($first['journal_breakdown'] ?? '')),
                    'journal_breakdown' => $first['journal_breakdown'] ?? '',
                    'occurred_at' => $first['occurred_at'] ?? '',
                    'management_number' => $first['management_number'] ?? '',
                    'company_name_short' => $first['company_name_short'] ?? '',
                    'vault_name' => $first['vault_name'] ?? '',
                    'summary_text' => $first['summary_text'] ?? '',
                    'debit_total' => $this->formatMoneyValue($debitTotal),
                    'credit_total' => $this->formatMoneyValue($creditTotal),
                    'detail_count' => $items->count(),
                    'details' => $items->values()->all(),
                ];
            })
            ->values()
            ->all();

        // 要確認：期間を数ヶ月に広げると仕訳グループが1000件を超え、1グループごとに編集フォームを
        // 描画するため、ブラウザが固まる（ぐるぐるしたまま止まらない）レベルまで重くなっていた。
        // クエリ自体は速い（インデックス済み）ので、グループ化した後の表示件数をページングで
        // 区切る（会計システム側も同じ考え方、2026-08-18）。
        $journalGroupsPerPage = 50;

        // フラット表示から「この仕訳を編集」で対象のグループへ飛べるように、group_key →
        // 何ページ目にあるかのマップを作る（2026-09-14、ユーザー要望：フラットで見つけた
        // 修正対象を、いちいちグループ表示側で探し直すのが大変だった）。
        $groupPageByKey = [];
        foreach ($journalGroups as $index => $group) {
            $groupPageByKey[$group['group_key']] = (int) floor($index / $journalGroupsPerPage) + 1;
        }

        $journalGroupsPage = max(1, (int) $request->query('page', 1));
        $journalGroupsTotal = count($journalGroups);
        $journalGroupsLastPage = max(1, (int) ceil($journalGroupsTotal / $journalGroupsPerPage));
        $journalGroupsPage = min($journalGroupsPage, $journalGroupsLastPage);
        $journalGroupsPaged = array_slice($journalGroups, ($journalGroupsPage - 1) * $journalGroupsPerPage, $journalGroupsPerPage);

        return view('admin_v2.work.journal_entries.index', [
            'rows' => $rows,
            'journalGroups' => $journalGroups,
            'journalGroupsPaged' => $journalGroupsPaged,
            'journalGroupsPage' => $journalGroupsPage,
            'journalGroupsLastPage' => $journalGroupsLastPage,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'companyOptions' => $companyOptions,
            'counterpartyOptions' => $counterpartyOptions,
            'amountOptions' => $amountOptions,
            'summaryTextOptions' => $summaryTextOptions,
            'accountTitleOptions' => $accountTitleOptions,
            'itemNameOptions' => $itemNameOptions,
            'departmentOptions' => $departmentOptions,
            'departmentSelectOptions' => $departmentSelectOptions,
            'vaultNameOptions' => $vaultNameOptions,
            'managementNumberOptions' => $managementNumberOptions,
            'journalBreakdownOptions' => $journalBreakdownOptions,
            'selectedCompanyName' => $selectedCompanyName,
            'counterparty' => $counterparty,
            'amount' => $amount,
            'summaryText' => $summaryText,
            'accountTitle' => $accountTitle,
            'itemName' => $itemName,
            'departmentName' => $departmentName,
            'vaultName' => $vaultName,
            'excludeMode' => $excludeMode,
            'blankFilterSentinel' => self::BLANK_FILTER_SENTINEL,
            'displayMode' => $displayMode,
            'departmentLabelMap' => $departmentLabelMap,
            'groupPageByKey' => $groupPageByKey,
            'managementNumber' => $managementNumber,
            'journalBreakdown' => $journalBreakdown,
            'journalImportedThrough' => $this->journalImportedThroughByCompany(),
        ]);
    }

    /**
     * 絞り込み1項目分のWHERE条件を組み立てる。$exclude=falseなら従来通り「いずれかの
     * 列がいずれかの値を含む」(OR)、$exclude=trueなら「除外モード」で「どの列もどの値も
     * 含まない」(AND)にする（2026-09-14、ユーザー要望）。
     *
     * 除外モードはNULLの扱いに注意が必要：`NOT LIKE`はNULLに対してNULL（＝該当なし）を
     * 返すため、そのままだと値が入っていない側の列を持つ行まで除外されてしまう。
     * 「その列に値が無い＝除外対象を含んでいない」として扱うため、列ごとに
     * `NOT LIKE ... OR IS NULL`にする。
     *
     * @param array<int, string> $columns
     * @param array<int, string> $terms
     */
    private function applyFieldFilter($query, array $columns, array $terms, bool $exclude): void
    {
        if ($terms === [] || $columns === []) {
            return;
        }

        // 入力値がそのまま「空白」（BLANK_FILTER_SENTINEL）なら、通常のLIKE検索ではなく
        // 「対象列のどれかが空欄」の特別扱いにする（2026-09-14）。他の候補と混ぜて
        // 「空白 OR 何か」のような複合検索はできない仕様（部門欄だけの要望のため、
        // 単独で入っている時だけ特別扱いする）。
        if ($terms === [self::BLANK_FILTER_SENTINEL]) {
            $this->applyBlankFilter($query, $columns, $exclude);
            return;
        }

        if (!$exclude) {
            $query->where(function ($subQuery) use ($columns, $terms) {
                foreach ($columns as $column) {
                    foreach ($terms as $term) {
                        $subQuery->orWhere($column, 'like', '%' . $term . '%');
                    }
                }
            });
            return;
        }

        $query->where(function ($subQuery) use ($columns, $terms) {
            foreach ($columns as $column) {
                foreach ($terms as $term) {
                    $subQuery->where(function ($columnQuery) use ($column, $term) {
                        $columnQuery->where($column, 'not like', '%' . $term . '%')->orWhereNull($column);
                    });
                }
            }
        });
    }

    /**
     * 「空白」入力時の特別扱い：$exclude=falseなら対象列のどれか1つでも空欄の行を拾う
     * （借方だけ・貸方だけ空欄でも拾う＝OR）。$exclude=trueなら逆に、対象列が全部
     * 埋まっている行だけ拾う（AND）。
     *
     * @param array<int, string> $columns
     */
    private function applyBlankFilter($query, array $columns, bool $exclude): void
    {
        if (!$exclude) {
            $query->where(function ($subQuery) use ($columns) {
                foreach ($columns as $column) {
                    $subQuery->orWhereNull($column)->orWhere($column, '');
                }
            });
            return;
        }

        $query->where(function ($subQuery) use ($columns) {
            foreach ($columns as $column) {
                $subQuery->whereNotNull($column)->where($column, '<>', '');
            }
        });
    }

    // 事務側（PaymentConfirmationController）と同じ表示。取込担当がここまで入れたか確認できるように、
    // こちら（取込側）にも同じものを出す。
    private function journalImportedThroughByCompany(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->select('company_name_short', DB::raw('MAX(occurred_at) as latest_occurred_at'))
            ->where('credit_account_title', '医療未収入金')
            ->whereNull('vault_name')
            ->whereNotNull('company_name_short')
            ->where('company_name_short', '<>', '')
            ->groupBy('company_name_short')
            ->orderBy('company_name_short')
            ->get()
            ->map(fn($row): array => [
                'company_name_short' => trim((string) ($row->company_name_short ?? '')),
                'latest_occurred_at' => $this->formatDateValue($row->latest_occurred_at, 'Y/n'),
            ])
            ->all();
    }

    // さくら報酬計算
    public function sakuraRewardPrint(Request $request): View
    {
        $dateFrom = trim((string) $request->query('date_from', now()->startOfMonth()->format('Y-m-d')));
        $dateTo = trim((string) $request->query('date_to', now()->endOfMonth()->format('Y-m-d')));

        try {
            $from = Carbon::parse($dateFrom)->startOfDay();
            $to = Carbon::parse($dateTo)->endOfDay();
        } catch (\Throwable) {
            $from = now()->startOfMonth()->startOfDay();
            $to = now()->endOfMonth()->endOfDay();
        }

        $targetMonth = $from->format('Y-m');
        $payrollPaymentMonth = $from->copy()->addMonthNoOverflow();
        $payrollLabel = $payrollPaymentMonth->format('Y/n') . '月給与';
        $bonusLabel = $payrollPaymentMonth->format('Y/n') . '月賞与';
        // 要確認：会社を絞らないと「T_さくら 店舗」という同名の部門が㈱プレッジ側にもあるため
        // 売上が混ざる。さくら経費・売上は㈱トータルケア（company_id=2）のさくら店舗のみが対象
        // （2026-08-18、店舗経費側の同じ問題とあわせて修正）。
        $salesSummary = $this->salesService->summary($targetMonth, '2');
        $sakuraSalesRows = array_values(array_filter(
            (array) ($salesSummary['rows'] ?? []),
            fn(array $row): bool => $this->isSakuraStoreSalesRow($row)
        ));
        $sakuraSales = array_sum(array_map(
            static fn(array $row): float => (float) ($row['total_amount'] ?? 0),
            $sakuraSalesRows
        ));

        $rewardSelectColumns = [
            'entry.journal_entry_id',
            'entry.journal_breakdown',
            'entry.management_number',
            'entry.month_date',
            'entry.occurred_at',
            'entry.vault_name',
            'entry.debit_account_title',
            'entry.debit_amount',
            'entry.debit_item_name',
            'entry.debit_counterparty',
            'entry.debit_department_name',
            'entry.debit_memo_tag',
            'entry.credit_account_title',
            'entry.credit_amount',
            'entry.credit_item_name',
            'entry.credit_counterparty',
            'entry.credit_department_name',
            'entry.credit_memo_tag',
            'entry.summary_text',
            'entry.company_name_short',
            'entry.allocation_ratio',
            'account.expense_sort_name',
            'account.sub_category_name',
            'account.middle_category_name',
            'account.major_category_name',
        ];

        $baseRewardQuery = function () use ($rewardSelectColumns) {
            return DB::connection('sqlsrv')
                ->table('dbo.mx_journal_entries as entry')
                ->leftJoin('dbo.mx_account_titles as account', 'entry.debit_account_title', '=', 'account.account_title_name')
                ->where(function ($query): void {
                    $query->whereNull('entry.closing_journal_entry')
                        ->orWhereRaw("LTRIM(RTRIM(CAST(entry.closing_journal_entry AS NVARCHAR(50)))) = ''")
                        ->orWhereRaw("LTRIM(RTRIM(CAST(entry.closing_journal_entry AS NVARCHAR(50)))) = '0'");
                })
                ->where(function ($query): void {
                    $query->whereNull('entry.is_reward_excluded')
                        ->orWhere('entry.is_reward_excluded', 0);
                })
                ->select($rewardSelectColumns);
        };

        $expenseRows = $baseRewardQuery()
            ->whereBetween('entry.occurred_at', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->where(function ($query): void {
                $query->whereNull('entry.management_number')
                    ->orWhere(function ($subQuery): void {
                        $subQuery->where('entry.management_number', 'not like', '%月給与%')
                            ->where('entry.management_number', 'not like', '%月賞与%');
                    });
            })
            ->orderBy('entry.occurred_at')
            ->orderBy('entry.journal_entry_id')
            ->get()
            ->map(fn($row): array => $this->rewardCalculationRow($row, $from, $to, $payrollLabel, $bonusLabel))
            ->filter(fn(array $row): bool => $row['item_category'] !== '除外')
            ->values()
            ->all();

        $salaryRows = $baseRewardQuery()
            ->where('entry.management_number', 'like', '%' . $payrollLabel . '%')
            ->orderBy('entry.occurred_at')
            ->orderBy('entry.journal_entry_id')
            ->get()
            ->map(fn($row): array => $this->rewardCalculationRow($row, $from, $to, $payrollLabel, $bonusLabel))
            ->filter(fn(array $row): bool => $row['item_category'] !== '除外')
            ->values()
            ->all();

        $bonusRows = $baseRewardQuery()
            ->where('entry.management_number', 'like', '%' . $bonusLabel . '%')
            ->orderBy('entry.occurred_at')
            ->orderBy('entry.journal_entry_id')
            ->get()
            ->map(fn($row): array => $this->rewardCalculationRow($row, $from, $to, $payrollLabel, $bonusLabel))
            ->filter(fn(array $row): bool => $row['item_category'] !== '除外')
            ->values()
            ->all();

        $payrollRows = array_values(array_merge($salaryRows, $bonusRows));

        // 要確認：部門名「T_さくら 店舗」は㈱プレッジ側にも同名で存在するため、部門名だけで
        // 判定すると他社のさくら店舗以外のデータまで混ざる。さくら経費（店舗経費）は
        // ㈱トータルケアのさくら店舗のみを対象にする（会社経費の分割（allocation_ratio）が
        // 入ってるものは2社とも対象のまま、company_name_shortでは絞らない）。
        // ただし人件費（給料手当・役員報酬・賞与・業務委託料・法定福利費、is_labor_cost）だけは
        // ㈱プレッジのさくら店舗分も実際に混ざり得るため、会社での絞り込みから除外する
        // （2026-08-18）。
        $storeExpenseRows = array_values(array_filter($expenseRows, function (array $row): bool {
            return $this->containsSakura((string) ($row['debit_department_name'] ?? ''))
                && (($row['is_labor_cost'] ?? false) || str_contains((string) ($row['company_name_short'] ?? ''), 'トータルケア'))
                && !in_array((string) ($row['debit_account_title'] ?? ''), ['医療未収入金', '現金'], true);
        }));

        $storePayrollRows = array_values(array_filter($payrollRows, function (array $row): bool {
            $item = trim((string) ($row['item'] ?? ''));

            return trim((string) ($row['debit_department_name'] ?? '')) === 'T_さくら 店舗'
                && ($item === '' || $item !== 'イノウエマサヤ井上真也');
        }));

        $storeRows = array_values(array_merge($storeExpenseRows, $storePayrollRows));

        $companyExpenseRows = array_values(array_filter($expenseRows, function (array $row): bool {
            return (float) ($row['allocation_ratio_number'] ?? 0) > 0
                && !$this->containsSakura((string) ($row['debit_department_name'] ?? ''));
        }));

        $companyPayrollRows = array_values(array_filter($payrollRows, function (array $row): bool {
            return (float) ($row['allocation_ratio_number'] ?? 0) > 0;
        }));

        $companyRows = array_values(array_merge($companyExpenseRows, $companyPayrollRows));

        $storeGroups = $this->rewardExpenseGroups($storeRows, 'expense_amount');
        $companyGroups = $this->rewardCompanyGroups($companyRows, 'allocated_amount');
        $storeExpenseTotal = (int) floor(array_sum(array_map(
            static fn(array $group): float => (float) ($group['total'] ?? 0),
            $storeGroups
        )));
        $companyExpenseTotal = (int) floor(array_sum(array_map(
            static fn(array $company): float => (float) ($company['total'] ?? 0),
            $companyGroups
        )));

        $profit = (int) floor($sakuraSales - $storeExpenseTotal - $companyExpenseTotal);
        $inoueRate = 0.66;
        $companyRate = 0.34;
        $guaranteeFee = 10000;
        $inoueProfit = (int) round($profit * $inoueRate, 0, PHP_ROUND_HALF_UP);
        $companyProfit = $profit - $inoueProfit;
        $inouePayment = $inoueProfit - $guaranteeFee;

        return view('admin_v2.work.journal_entries.sakura_reward_print', [
            'dateFrom' => $from->format('Y-m-d'),
            'dateTo' => $to->format('Y-m-d'),
            'targetMonth' => $targetMonth,
            'sakuraSalesRows' => $sakuraSalesRows,
            'sakuraSales' => $sakuraSales,
            'storeRows' => $storeRows,
            'companyRows' => $companyRows,
            'storeGroups' => $storeGroups,
            'companyGroups' => $companyGroups,
            'summary' => [
                'sakura_sales' => $sakuraSales,
                'store_expense_total' => $storeExpenseTotal,
                'company_expense_total' => $companyExpenseTotal,
                'profit' => $profit,
                'inoue_rate' => $inoueRate,
                'company_rate' => $companyRate,
                'guarantee_fee' => $guaranteeFee,
                'inoue_profit' => $inoueProfit,
                'company_profit' => $companyProfit,
                'inoue_payment' => $inouePayment,
                'total_payment' => $inouePayment + $companyProfit,
            ],
        ]);
    }

    public function importJournalEntries(Request $request): RedirectResponse
    {
        // 2026-09-16、ユーザーが会社未選択のまま取込を実行して英語のバリデーションエラー
        // （"The company name short field is required."）を踏んだ。日本語メッセージが
        // 一つも無かったのが原因（target_monthの時と同じパターン）。
        $data = $request->validate([
            'csv_file' => ['required', 'file'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date'],
            'company_name_short' => ['required', 'string', 'max:255'],
        ], [
            'csv_file.required' => 'CSVファイルを選択してください。',
            'csv_file.file' => 'CSVファイルを選択してください。',
            'date_from.required' => '取込対象の期間（開始日）を指定してください。',
            'date_from.date' => '取込対象の期間（開始日）の形式が正しくありません。',
            'date_to.required' => '取込対象の期間（終了日）を指定してください。',
            'date_to.date' => '取込対象の期間（終了日）の形式が正しくありません。',
            'company_name_short.required' => '取込先の会社を選択してください。',
        ]);

        $dateFrom = Carbon::parse((string) $data['date_from'])->startOfDay();
        $dateTo = Carbon::parse((string) $data['date_to'])->endOfDay();
        $companyNameShort = trim((string) $data['company_name_short']);
        $csvRows = $this->readCsvAssocRows($request->file('csv_file')->getPathname());

        if ($csvRows === []) {
            return redirect()->route('admin.work.journal_entries', [
                'date_from' => $data['date_from'],
                'date_to' => $data['date_to'],
                'company_name_short' => $companyNameShort,
            ])->with('errorMessage', 'CSVに取り込める行がありませんでした。');
        }

        // 取込判定・金額に使う列は、ヘッダー名が変わると空欄扱いで静かに素通りしてしまう
        // （対象外行として弾かれるどころか、金額が全行nullで取り込まれることもある）ため、
        // 行の処理に入る前にヘッダーの存在だけ先に確認し、欠けていれば取込自体を止める。
        $requiredHeaders = ['取引日', '仕訳番号', '借方勘定科目', '貸方勘定科目', '借方金額', '貸方金額'];
        $missingHeaders = array_diff($requiredHeaders, array_keys($csvRows[0]));
        if ($missingHeaders !== []) {
            return redirect()->route('admin.work.journal_entries', [
                'date_from' => $data['date_from'],
                'date_to' => $data['date_to'],
                'company_name_short' => $companyNameShort,
            ])->with('errorMessage', 'CSVに必要な列が見つかりません（' . implode('、', $missingHeaders) . '）。会計システム側の出力列名をご確認のうえ、取込形式が変わっていないか確認してください。');
        }

        $departmentMap = DB::connection('sqlsrv')
            ->table('dbo.mx_departments')
            ->select(['store_category', 'store_short_name'])
            ->whereNotNull('store_category')
            ->where('store_category', '<>', '')
            ->get()
            ->mapWithKeys(fn($row): array => [
                trim((string) ($row->store_category ?? '')) => trim((string) ($row->store_short_name ?? '')),
            ])
            ->all();

        $journalColumns = array_flip(Schema::connection('sqlsrv')->getColumnListing('mx_journal_entries'));
        $skippedCount = 0;
        $invalidAmountCount = 0;
        $groupedPayloads = [];
        $csvDebitTotals = [];
        $csvCreditTotals = [];

        foreach ($csvRows as $csvRow) {
            $occurredAt = $this->parseCsvDate($this->csvValue($csvRow, '取引日'));
            if ($occurredAt === null || $occurredAt->lt($dateFrom) || $occurredAt->gt($dateTo)) {
                $skippedCount++;
                continue;
            }

            $creditAccountTitle = $this->csvValue($csvRow, '貸方勘定科目');
            if (in_array($creditAccountTitle, ['保険収入', '自費収入', '窓口収入'], true)) {
                $skippedCount++;
                continue;
            }

            $journalBreakdown = $this->journalBreakdownValue($this->csvValue($csvRow, '仕訳番号'));
            if ($journalBreakdown === '') {
                $skippedCount++;
                continue;
            }

            // 手動編集（updateJournalEntry/updateJournalEntryGroup）は金額が数値でないと
            // エラーで弾くのに対し、CSV取込だけは非数値をnullで黙って取り込んでいた。
            // 空欄（貸借どちらかしか使わない行）は許容し、非空かつ非数値の場合のみ弾く。
            $debitAmountRaw = $this->csvValue($csvRow, '借方金額');
            $creditAmountRaw = $this->csvValue($csvRow, '貸方金額');
            if (($debitAmountRaw !== '' && $this->parseMoneyValue($debitAmountRaw) === null)
                || ($creditAmountRaw !== '' && $this->parseMoneyValue($creditAmountRaw) === null)) {
                $invalidAmountCount++;
                continue;
            }

            $payload = [
                'journal_breakdown' => $journalBreakdown,
                'month_date' => $occurredAt->copy()->startOfMonth()->format('Y-m-d'),
                'occurred_at' => $occurredAt->format('Y-m-d'),
                'deposit_name' => $this->depositNameFromCsv($csvRow),
                'debit_account_title' => $this->csvValue($csvRow, '借方勘定科目'),
                'debit_amount' => $this->parseMoneyValue($debitAmountRaw),
                'debit_tax_category' => $this->csvValue($csvRow, '借方税区分'),
                'debit_item_name' => $this->csvValue($csvRow, '借方品目'),
                'debit_department_name' => $departmentMap[$this->csvValue($csvRow, '借方部門')] ?? null,
                'debit_memo_tag' => $this->csvValue($csvRow, '借方メモ', '借方メモタグ'),
                'credit_account_title' => $creditAccountTitle,
                'credit_amount' => $this->parseMoneyValue($creditAmountRaw),
                'credit_tax_category' => $this->csvValue($csvRow, '貸方税区分'),
                'credit_item_name' => $this->csvValue($csvRow, '貸方品目'),
                'credit_department_name' => $departmentMap[$this->csvValue($csvRow, '貸方部門')] ?? null,
                'credit_memo_tag' => $this->csvValue($csvRow, '貸方メモ', '貸方メモタグ'),
                'debit_counterparty' => $this->csvValue($csvRow, '借方取引先名', '借方取引先'),
                'credit_counterparty' => $this->csvValue($csvRow, '貸方取引先名', '貸方取引先'),
                'summary_text' => $this->csvValue($csvRow, '取引内容'),
                'management_number' => $this->csvValue($csvRow, '管理番号'),
                'company_name_short' => $companyNameShort,
                'closing_journal_entry' => $this->csvValue($csvRow, '決算整理仕訳'),
                'journal_note' => $this->csvValue($csvRow, '借方備考') . $this->csvValue($csvRow, '貸方備考'),
                'updated_at' => now(),
            ];
            $payload = array_intersect_key($payload, $journalColumns);

            // 決算時の税理士修正など、こちらの突合ロジックが拾いきれない変更にも気づけるように、
            // CSV側の科目別合計を集計しておき、後でDB側の科目別合計と突き合わせて表示する。
            $debitTitle = trim((string) ($payload['debit_account_title'] ?? ''));
            if ($debitTitle !== '') {
                $csvDebitTotals[$debitTitle] = ($csvDebitTotals[$debitTitle] ?? 0) + (float) ($payload['debit_amount'] ?? 0);
            }
            $creditTitle = trim((string) ($payload['credit_account_title'] ?? ''));
            if ($creditTitle !== '') {
                $csvCreditTotals[$creditTitle] = ($csvCreditTotals[$creditTitle] ?? 0) + (float) ($payload['credit_amount'] ?? 0);
            }

            $groupKey = $companyNameShort . "\x1f" . $payload['occurred_at'] . "\x1f" . $journalBreakdown;
            $groupedPayloads[$groupKey][] = $payload;
        }

        // 仕訳No単位（会社＋取引日＋仕訳No）でグループ化する。DBに同じグループがまだ無ければ
        // 純粋な新規（後述の通りここではまだ書き込まない）。既にある場合は、会計システム側の
        // 再分類（不明→確定 等）や単仕訳→複合仕訳の変化で内容が変わっている可能性があるため
        // 自動上書きせず、確認画面で既存内容とCSVの内容を並べて見せ、人が選んだものだけ適用する。
        // 要確認：ただし「既存グループがある」というだけで無条件に確認対象にすると、内容が
        // 一字一句同じ再取込（＝実質変更なし）まで大量に「要確認」へ積み上がり、本当に内容が
        // 違う行が埋もれて見つからなくなる。判定はREVIEW_COMPARE_FIELDS（借方/貸方科目・金額）
        // だけで行う。摘要は表記ゆれが普通にあるため対象外。部門名・税区分・メモ・管理番号等も、
        // CSVの中身が同じでもマッピング元マスタ（mx_departments等）が後から変わると再取込の
        // たびに「違う」判定になり、確認しようがない差分で埋もれるため比較対象に含めない
        // （2026-08-17）。
        $identicalCount = 0;
        $reviewGroups = [];
        $rowSignature = fn(array $row): string => $this->lineSignature($row);

        // 要確認：取込ボタンを押した瞬間に新規分を問答無用でINSERTしていたのを、確認してから
        // 別ボタンで反映する2段階に変更（2026-08-17）。新規分もここでは書き込まず$newGroupsに
        // 貯めておき、確認画面の「新規追加」ボタンを押した時だけ実際にINSERTする
        // （applyNewJournalEntries()）。
        $newGroups = [];
        $pendingNewCount = 0;

        // 要確認：以前はグループ（仕訳No）ごとに毎回DBへ問い合わせていたため、月をまたいで
        // グループ数が数百〜数千になるとクエリ回数もそれだけ増え、実行時間60秒を超えて
        // Fatal Errorになっていた（N+1問題）。対象期間・会社のデータを最初に1回だけまとめて
        // 取得し、以降はメモリ上のマップから引くだけにする（2026-08-18）。
        $existingRowsByGroupKey = [];
        foreach (
            DB::connection('sqlsrv')
                ->table('dbo.mx_journal_entries')
                ->where('company_name_short', $companyNameShort)
                ->whereDate('occurred_at', '>=', $dateFrom)
                ->whereDate('occurred_at', '<=', $dateTo)
                ->orderBy('journal_entry_id')
                ->get() as $existingRow
        ) {
            $existingOccurredAt = $this->formatDateValue($existingRow->occurred_at, 'Y-m-d');
            $existingKey = $companyNameShort . "\x1f" . $existingOccurredAt . "\x1f" . trim((string) ($existingRow->journal_breakdown ?? ''));
            $existingRowsByGroupKey[$existingKey][] = (array) $existingRow;
        }

        foreach ($groupedPayloads as $groupKey => $payloads) {
            [$groupCompany, $groupOccurredAt, $groupJournalBreakdown] = explode("\x1f", $groupKey);

            $existingArrays = $existingRowsByGroupKey[$groupKey] ?? [];

            if ($existingArrays === []) {
                $newGroups[$groupKey] = [
                    'company_name_short' => $groupCompany,
                    'occurred_at' => $groupOccurredAt,
                    'journal_breakdown' => $groupJournalBreakdown,
                    'payloads' => $payloads,
                ];
                $pendingNewCount += count($payloads);
                continue;
            }

            $existingSignatures = array_map($rowSignature, $existingArrays);
            $newSignatures = array_map($rowSignature, $payloads);
            sort($existingSignatures);
            sort($newSignatures);

            if ($existingSignatures === $newSignatures) {
                $identicalCount += count($payloads);
                continue;
            }

            $reviewGroups[$groupKey] = [
                'company_name_short' => $groupCompany,
                'occurred_at' => $groupOccurredAt,
                'journal_breakdown' => $groupJournalBreakdown,
                'existing' => $existingArrays,
                'new' => $payloads,
            ];
        }

        // 要確認：以前は「対象外」に$skippedCount（対象期間外・保険収入等の除外科目・仕訳No空欄）
        // しか含めておらず、内容が完全一致していて確認不要だった件数($identicalCount)が
        // 表示から漏れていた。件数の内訳が全部見えるよう分けて表示する（2026-08-18）。
        $reviewLineCount = array_sum(array_map(fn(array $group): int => count($group['new']), $reviewGroups));
        $summaryMessage = 'CSVを確認しました。新規追加候補: ' . $pendingNewCount . '件'
            . ' / 要確認: ' . $reviewLineCount . '件'
            . ' / 既存と完全一致（対応不要）: ' . $identicalCount . '件'
            . ' / 対象期間外・除外科目等: ' . $skippedCount . '件'
            . ($invalidAmountCount > 0 ? ' / 金額が数値でないため未取込: ' . $invalidAmountCount . '件' : '')
            . '（まだ何も書き込んでいません。内容を確認してから反映してください）';

        // 要確認：科目ごとのCSV合計とDB合計の突き合わせ（決算時に税理士が直接修正した場合等に
        // 気づくための保険）は、新規分をまだ書き込んでいないこの時点では正しく計算できない
        // （新規分がDB側にまだ無いので必ず不一致に見える）。この画面では計算せず、
        // 「新規追加」ボタンを押してDBへ実際に書き込んだ後、確認画面を再表示する時に
        // buildJournalReconciliation()で毎回そのDBの現在値から計算し直す（2026-08-17）。
        $token = (string) Str::uuid();
        Cache::put('journal_import_review:' . $token, [
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'company_name_short' => $companyNameShort,
            'summary_message' => $summaryMessage,
            'groups' => $reviewGroups,
            'new_groups' => $newGroups,
            'csv_debit_totals' => $csvDebitTotals,
            'csv_credit_totals' => $csvCreditTotals,
        ], now()->addMinutes(60));

        return redirect()->route('admin.work.journal_entries.import_review', ['token' => $token]);
    }

    /**
     * 仕訳No単位の突合だけでは、決算時に税理士がこちらの知らない形で仕訳を直接修正した場合などに
     * 気づけない。そこで科目ごとにCSVの合計とDBの合計（同じ会社・期間）を突き合わせて表示し、
     * 差があれば一目で分かるようにする。
     *
     * 要確認：この突き合わせはCSV側に元々含まれない仕訳を除外しないと常に赤字（差額あり）になり、
     * 本当に確認すべき差額が埋もれてしまう。除外は2種類。
     * (1) 保険収入・自費収入・窓口収入は、CSV取込処理自体が貸方勘定科目でこの3科目を弾いている
     *     （importJournalEntries()内、旧AccessのfreeeCSV取込クエリと同じ除外条件）。売上は
     *     店舗日報月次で別途mx_journal_entriesへ作成されるため、CSV側と二重計上しないよう
     *     最初から取り込まない。ペアになる医療未収入金（借方）もこの行ごと除外されCSV合計に
     *     乗らないため、DB側も同じ条件（貸方勘定科目が3科目）で除外する。
     * (2) 店舗日報月次の経費仕訳（消耗品費/現金など、journal_breakdownが「年月+経費〜」）と、
     *     現金出納帳（vault_nameが入っている）も同様にCSVに含まれない別経路のため除外する
     *     （2026-08-17、店舗日報の経費仕訳が消耗品費/現金の差額に丸ごと乗っていた実例で発覚）。
     *
     * @param array<string,float> $csvDebitTotals
     * @param array<string,float> $csvCreditTotals
     * @return list<array{side:string,account_title:string,csv_total:float,db_total:float,diff:float}>
     */
    private function buildJournalReconciliation(
        string $companyNameShort,
        Carbon $dateFrom,
        Carbon $dateTo,
        array $csvDebitTotals,
        array $csvCreditTotals
    ): array {
        $internalJournalBreakdownPrefixes = [];
        $monthCursor = $dateFrom->copy()->startOfMonth();
        while ($monthCursor->lte($dateTo)) {
            $internalJournalBreakdownPrefixes[] = $monthCursor->format('Ym') . '経費';
            $monthCursor->addMonthNoOverflow();
        }

        $excludeInternalEntries = function ($query) use ($internalJournalBreakdownPrefixes): void {
            $query->whereRaw("ISNULL(vault_name, '') = ''");
            // 要確認：whereNotInはcredit_account_titleがNULLの行（借方だけの片側仕訳など、
            // 銀行利息のように多い）を、SQLの三値論理でまるごと除外してしまうバグがあった
            // （NULL NOT IN (...)はNULL＝不明になりWHEREを満たさない）。NULLは明示的に許可する
            // （2026-08-17、播州信金の利息19円が丸ごとDB合計から消えていた実例で発覚）。
            $query->where(function ($subQuery): void {
                $subQuery->whereNull('credit_account_title')
                    ->orWhereNotIn('credit_account_title', ['保険収入', '自費収入', '窓口収入']);
            });
            // journal_breakdownがNULLの行（journal_breakdown列を使わない手入力仕訳）も、
            // credit_account_titleと同じNULL三値論理バグで丸ごと除外されていた
            // （2026-08-18、レビューで発覚。DEVに65件該当）。NULLは除外対象にしない。
            foreach ($internalJournalBreakdownPrefixes as $prefix) {
                $query->where(function ($subQuery) use ($prefix): void {
                    $subQuery->whereNull('journal_breakdown')
                        ->orWhere('journal_breakdown', 'not like', $prefix . '%');
                });
            }
        };

        $dbDebitTotals = DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->select('debit_account_title', DB::raw('SUM(debit_amount) as total'))
            ->where('company_name_short', $companyNameShort)
            ->whereDate('occurred_at', '>=', $dateFrom)
            ->whereDate('occurred_at', '<=', $dateTo)
            ->where($excludeInternalEntries)
            ->groupBy('debit_account_title')
            ->pluck('total', 'debit_account_title');

        $dbCreditTotals = DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->select('credit_account_title', DB::raw('SUM(credit_amount) as total'))
            ->where('company_name_short', $companyNameShort)
            ->whereDate('occurred_at', '>=', $dateFrom)
            ->whereDate('occurred_at', '<=', $dateTo)
            ->where($excludeInternalEntries)
            ->groupBy('credit_account_title')
            ->pluck('total', 'credit_account_title');

        $reconciliation = [];
        foreach (['借方' => [$csvDebitTotals, $dbDebitTotals], '貸方' => [$csvCreditTotals, $dbCreditTotals]] as $sideLabel => $totalsPair) {
            [$csvTotals, $dbTotals] = $totalsPair;
            $accountTitles = array_unique(array_merge(array_keys($csvTotals), $dbTotals->keys()->all()));
            foreach ($accountTitles as $accountTitle) {
                $accountTitle = trim((string) $accountTitle);
                if ($accountTitle === '') {
                    continue;
                }
                $csvTotal = (float) ($csvTotals[$accountTitle] ?? 0);
                $dbTotal = (float) ($dbTotals[$accountTitle] ?? 0);
                $reconciliation[] = [
                    'side' => $sideLabel,
                    'account_title' => $accountTitle,
                    'csv_total' => $csvTotal,
                    'db_total' => $dbTotal,
                    'diff' => $csvTotal - $dbTotal,
                ];
            }
        }

        usort(
            $reconciliation,
            fn(array $a, array $b): int => (abs($b['diff']) <=> abs($a['diff']))
                ?: ($a['side'] <=> $b['side'])
                ?: ($a['account_title'] <=> $b['account_title'])
        );

        return $reconciliation;
    }

    public function importJournalEntriesReview(string $token): View|RedirectResponse
    {
        $staged = Cache::get('journal_import_review:' . $token);
        if ($staged === null) {
            return redirect()->route('admin.work.journal_entries')
                ->with('errorMessage', '確認内容の有効期限が切れました。CSVを取込みし直してください。');
        }

        $groups = [];
        foreach ($staged['groups'] as $groupKey => $group) {
            $groups[] = [
                'group_key' => $groupKey,
                'company_name_short' => $group['company_name_short'],
                'occurred_at' => $this->formatDateValue($group['occurred_at'], 'Y/m/d'),
                'journal_breakdown' => $group['journal_breakdown'],
                // 要確認：既存とCSVを別々の表で左右に並べると行がズレて比較しづらいという指摘のため、
                // 同じ行番号の既存・CSVを1行にまとめ、項目ごとに既存値/CSV値を隣り合わせで
                // 出す形にした（2026-08-17）。対応づけは生の行データに対して行う
                // （buildReviewLinePairsが内部でreviewLineDisplay()にかけて表示用に整形する）。
                'line_pairs' => $this->buildReviewLinePairs($group['existing'], $group['new']),
            ];
        }

        $newGroups = $staged['new_groups'] ?? [];
        $pendingNewCount = array_sum(array_map(fn(array $group): int => count($group['payloads']), $newGroups));

        // 要確認：新規分がまだ書き込まれていない間は一致確認を計算しない（新規追加ボタンを
        // 押してDBへ書き込んだ後、この画面を再表示した時だけ計算する。2026-08-17）。
        $reconciliation = [];
        if ($pendingNewCount === 0) {
            $rawReconciliation = $this->buildJournalReconciliation(
                $staged['company_name_short'],
                Carbon::parse($staged['date_from'])->startOfDay(),
                Carbon::parse($staged['date_to'])->endOfDay(),
                $staged['csv_debit_totals'] ?? [],
                $staged['csv_credit_totals'] ?? []
            );
            $reconciliation = array_map(fn(array $row): array => [
                'side' => $row['side'],
                'account_title' => $row['account_title'],
                'csv_total' => $this->formatMoneyValue($row['csv_total']),
                'db_total' => $this->formatMoneyValue($row['db_total']),
                'diff' => $this->formatMoneyValue($row['diff']),
                'has_diff' => $row['diff'] != 0.0,
            ], $rawReconciliation);
        }

        return view('admin_v2.work.journal_entries.import_review', [
            'token' => $token,
            'groups' => $groups,
            'summaryMessage' => $staged['summary_message'],
            'reconciliation' => $reconciliation,
            'pendingNewCount' => $pendingNewCount,
        ]);
    }

    /**
     * CSV取込確認画面の「新規追加」ボタン。要確認扱いになっていない、DBにまだ無い純粋な
     * 新規分だけをここで実際にINSERTする。要確認の仕訳（applyJournalEntriesReview）とは
     * 別ボタン・別処理にして、既存とぶつかる分は必ず人の選択を経由するようにする（2026-08-17）。
     */
    public function applyNewJournalEntries(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => ['required', 'string']]);

        $cacheKey = 'journal_import_review:' . $data['token'];
        $staged = Cache::get($cacheKey);
        if ($staged === null) {
            return redirect()->route('admin.work.journal_entries')
                ->with('errorMessage', '確認内容の有効期限が切れました。CSVを取込みし直してください。');
        }

        $newGroups = $staged['new_groups'] ?? [];
        $insertedCount = 0;
        foreach ($newGroups as $group) {
            DB::connection('sqlsrv')->table('dbo.mx_journal_entries')->insert($group['payloads']);
            $insertedCount += count($group['payloads']);
        }

        $staged['new_groups'] = [];
        $staged['summary_message'] = $insertedCount > 0
            ? '新規分を' . $insertedCount . '件取り込みました。'
            : '新規追加候補はありませんでした。';
        Cache::put($cacheKey, $staged, now()->addMinutes(60));

        return redirect()->route('admin.work.journal_entries.import_review', ['token' => $data['token']]);
    }

    public function applyJournalEntriesReview(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'group_keys' => ['array'],
            'group_keys.*' => ['string'],
        ]);

        $cacheKey = 'journal_import_review:' . $data['token'];
        $staged = Cache::get($cacheKey);
        if ($staged === null) {
            return redirect()->route('admin.work.journal_entries')
                ->with('errorMessage', '確認内容の有効期限が切れました。CSVを取込みし直してください。');
        }

        $selectedKeys = array_flip($data['group_keys'] ?? []);
        $updatedCount = 0;
        $importedCount = 0;
        $deletedCount = 0;
        $skippedGroupCount = 0;
        $reviewNotes = [];
        $missingNotes = [];

        DB::connection('sqlsrv')->transaction(function () use ($staged, $selectedKeys, &$updatedCount, &$importedCount, &$deletedCount, &$skippedGroupCount, &$reviewNotes, &$missingNotes): void {
            foreach ($staged['groups'] as $groupKey => $group) {
                if (!isset($selectedKeys[$groupKey])) {
                    $skippedGroupCount++;
                    continue;
                }

                // 要確認：以前は既存行とCSV行を出現順（0番目同士…）で対応づけてUPDATEしていたため、
                // DB保存順とCSV内の行順が違うだけで別の行に上書きする恐れがあった（表示側の
                // buildReviewLinePairsは2026-08-17に内容一致で対応づける方式へ直したが、実際に
                // 書き込むこちらは直っていなかった）。同じpairJournalLines()で内容一致ペアを
                // 先に対応づけてから書き込む（2026-08-18）。
                foreach ($this->pairJournalLines($group['existing'], $group['new']) as $pair) {
                    $existingRow = $pair['existing'];
                    $payload = $pair['new'];

                    if ($existingRow !== null && $payload !== null) {
                        // journal_entry_idを維持したままUPDATEすることで、入金確認
                        // （PaymentConfirmationController等）や分割割合などCSVに無い列が
                        // このIDに紐づいたまま残るようにする。
                        $affected = DB::connection('sqlsrv')
                            ->table('dbo.mx_journal_entries')
                            ->where('journal_entry_id', (int) $existingRow['journal_entry_id'])
                            ->update($payload);

                        if ($affected === 0) {
                            $missingNotes[] = $group['company_name_short'] . ' ' . $group['occurred_at'] . ' No.' . $group['journal_breakdown']
                                . '（ID:' . $existingRow['journal_entry_id'] . '・更新対象が見つかりませんでした）';
                            continue;
                        }

                        $updatedCount++;
                        continue;
                    }

                    if ($payload !== null) {
                        DB::connection('sqlsrv')->table('dbo.mx_journal_entries')->insert($payload);
                        $importedCount++;
                        continue;
                    }

                    $manualLabels = $this->manualOnlyColumnLabels($existingRow);
                    if ($manualLabels === []) {
                        $affected = DB::connection('sqlsrv')
                            ->table('dbo.mx_journal_entries')
                            ->where('journal_entry_id', (int) $existingRow['journal_entry_id'])
                            ->delete();

                        if ($affected === 0) {
                            $missingNotes[] = $group['company_name_short'] . ' ' . $group['occurred_at'] . ' No.' . $group['journal_breakdown']
                                . '（ID:' . $existingRow['journal_entry_id'] . '・削除対象が見つかりませんでした）';
                            continue;
                        }

                        $deletedCount++;
                    } else {
                        $reviewNotes[] = $group['company_name_short'] . ' ' . $group['occurred_at'] . ' No.' . $group['journal_breakdown']
                            . '（ID:' . $existingRow['journal_entry_id'] . ' / ' . implode('・', $manualLabels) . '）';
                    }
                }
            }
        });

        // 要確認：以前はここで無条件にCache::forgetしていたため、「新規追加」ボタンをまだ
        // 押していない未反映のnew_groups（CSVにあってDBにまだ無い純粋な新規仕訳）が
        // 一緒に消えてしまっていた（要確認の仕訳を先に適用すると、後から新規追加を押しても
        // 何も入らない）。new_groupsが残っている間はトークンを維持する（2026-08-18）。
        $remainingNewGroups = $staged['new_groups'] ?? [];
        if ($remainingNewGroups === []) {
            Cache::forget($cacheKey);
        } else {
            Cache::put($cacheKey, $staged, now()->addMinutes(60));
        }

        $pendingNewCount = array_sum(array_map(fn(array $group): int => count($group['payloads']), $remainingNewGroups));
        $message = '確認分の取込が完了しました。更新: ' . $updatedCount . '件 / 追加: ' . $importedCount . '件 / 削除: ' . $deletedCount . '件'
            . ($skippedGroupCount > 0 ? ' / 見送り（未チェック）: ' . $skippedGroupCount . '件' : '')
            . ($reviewNotes !== [] ? ' / 手動入力値が残っているため削除せず保持した仕訳: ' . implode('、', $reviewNotes) : '')
            . ($missingNotes !== [] ? ' / 確認時から状態が変わり反映できなかった仕訳（再確認してください）: ' . implode('、', $missingNotes) : '')
            . ($pendingNewCount > 0 ? ' / まだ新規追加候補' . $pendingNewCount . '件が残っています。続けて反映してください' : '');

        if ($pendingNewCount > 0) {
            return redirect()->route('admin.work.journal_entries.import_review', ['token' => $data['token']])
                ->with('statusMessage', $message);
        }

        return redirect()->route('admin.work.journal_entries', [
            'date_from' => $staged['date_from'],
            'date_to' => $staged['date_to'],
            'company_name_short' => $staged['company_name_short'],
        ])->with('statusMessage', $message);
    }

    /**
     * 要確認の仕訳で、既存とCSVを別々の表で左右に並べると行がズレて比較しづらいという
     * 指摘のため、既存・CSVを1行にまとめ、項目ごとに既存値/CSV値を隣り合わせで出せる形に
     * 変換する（2026-08-17）。
     *
     * 要確認：複合仕訳（1つの仕訳Noに複数行）は、DB保存順とCSV内の行順が一致するとは
     * 限らない。単純に行番号（0番目同士、1番目同士…）で対応づけると、中身は全く同じでも
     * 並び順が違うだけで全行「差分あり」に見えてしまう（実例：6042016、給料手当の
     * 複合仕訳で発覚）。そこで先に中身（REVIEW_COMPARE_FIELDS）が完全一致する行同士を
     * 1対1で対応づけて除外し、余った行（＝本当に中身が違う行）だけを残りの数で
     * 突き合わせる（2026-08-17）。
     *
     * @param list<array<string,mixed>> $existingLines
     * @param list<array<string,mixed>> $newLines
     * @return list<array{existing:array<string,mixed>,new:array<string,mixed>,diff:array<string,bool>}>
     */
    /**
     * REVIEW_COMPARE_FIELDS（借方/貸方科目・金額）だけを見た、行の内容比較用シグネチャ。
     * 「既存グループと完全一致か」の判定（importJournalEntries）と「既存行とCSV行を
     * 内容で対応づける」処理（pairJournalLines、表示・実適用の両方で共有）の、
     * 唯一の正本にする。以前は2箇所に別々の実装（丸め方も違う）があり、表示上は
     * 一致して見えるのに判定がズレる余地があった（2026-08-18、レビューで発覚）。
     *
     * @param array<string,mixed> $row 生のDB/CSV行（フォーマット前の値）
     */
    private function lineSignature(array $row): string
    {
        $row = array_intersect_key($row, self::REVIEW_COMPARE_FIELDS);
        ksort($row);
        foreach ($row as $key => $value) {
            $row[$key] = is_numeric($value) ? (string) round((float) $value, 4) : trim((string) ($value ?? ''));
        }

        return json_encode($row);
    }

    /**
     * 既存行とCSV/新規行を、REVIEW_COMPARE_FIELDSの内容が完全一致するもの同士で先に
     * 1対1に対応づけ、余った行（＝本当に中身が違う／新規／削除候補の行）だけを残りの
     * 数で突き合わせる。複合仕訳（1つの仕訳Noに複数行）はDB保存順とCSV内の行順が
     * 一致するとは限らないため、単純な位置合わせ（0番目同士…）はしない（2026-08-17、
     * 実例：6042016）。
     *
     * 表示用（importJournalEntriesReview）と実適用（applyJournalEntriesReview）の
     * 両方から、生の行データに対して呼ぶ（フォーマット後の文字列に対して呼ばない）。
     * 以前は表示用に別実装があり、実適用側は対応づけ自体をしていなかった
     * （出現順のまま更新していた）ため、行順がズレていると違う行に上書きする
     * リスクがあった（2026-08-18、レビューで発覚）。
     *
     * @param list<array<string,mixed>> $existingLines
     * @param list<array<string,mixed>> $newLines
     * @return list<array{existing:?array<string,mixed>,new:?array<string,mixed>}>
     */
    private function pairJournalLines(array $existingLines, array $newLines): array
    {
        $remainingExisting = [];
        foreach ($existingLines as $existing) {
            $remainingExisting[$this->lineSignature($existing)][] = $existing;
        }

        $matchedPairs = [];
        $remainingNew = [];
        foreach ($newLines as $new) {
            $signature = $this->lineSignature($new);
            if (!empty($remainingExisting[$signature])) {
                $matchedPairs[] = ['existing' => array_shift($remainingExisting[$signature]), 'new' => $new];
                continue;
            }
            $remainingNew[] = $new;
        }

        $leftoverExisting = $remainingExisting === [] ? [] : array_merge(...array_values($remainingExisting));
        $rowCount = max(count($leftoverExisting), count($remainingNew));
        for ($i = 0; $i < $rowCount; $i++) {
            $matchedPairs[] = ['existing' => $leftoverExisting[$i] ?? null, 'new' => $remainingNew[$i] ?? null];
        }

        return $matchedPairs;
    }

    /**
     * @param list<array<string,mixed>> $existingLines 生の既存行
     * @param list<array<string,mixed>> $newLines 生のCSV行
     * @return list<array{existing:?array<string,mixed>,new:?array<string,mixed>,diff:array<string,bool>}>
     */
    private function buildReviewLinePairs(array $existingLines, array $newLines): array
    {
        $compareFields = array_keys(self::REVIEW_COMPARE_FIELDS);
        $pairs = [];
        foreach ($this->pairJournalLines($existingLines, $newLines) as $pair) {
            $existing = $pair['existing'];
            $new = $pair['new'];
            $diff = [];
            foreach ($compareFields as $field) {
                $diff[$field] = ($existing === null || $new === null)
                    || (($existing[$field] ?? '') !== ($new[$field] ?? ''));
            }
            $pairs[] = [
                'existing' => $existing === null ? null : $this->reviewLineDisplay($existing),
                'new' => $new === null ? null : $this->reviewLineDisplay($new),
                'diff' => $diff,
            ];
        }

        return $pairs;
    }

    private function reviewLineDisplay(array $row): array
    {
        return [
            'journal_entry_id' => $row['journal_entry_id'] ?? null,
            'debit_account_title' => trim((string) ($row['debit_account_title'] ?? '')),
            'debit_amount' => $this->formatMoneyValue($row['debit_amount'] ?? null),
            'debit_item_name' => trim((string) ($row['debit_item_name'] ?? '')),
            'credit_account_title' => trim((string) ($row['credit_account_title'] ?? '')),
            'credit_amount' => $this->formatMoneyValue($row['credit_amount'] ?? null),
            'credit_item_name' => trim((string) ($row['credit_item_name'] ?? '')),
            'summary_text' => trim((string) ($row['summary_text'] ?? '')),
            'management_number' => trim((string) ($row['management_number'] ?? '')),
        ];
    }

    private function manualOnlyColumnLabels(array $row): array
    {
        $labels = [];
        if (trim((string) ($row['allocation_ratio'] ?? '')) !== '') {
            $labels[] = '分割割合';
        }
        if (trim((string) ($row['vault_name'] ?? '')) !== '') {
            $labels[] = '金庫名';
        }
        if ((int) ($row['is_trial_balance_excluded'] ?? 0) !== 0) {
            $labels[] = '試算表除外';
        }
        if ((int) ($row['is_upload_unnecessary'] ?? 0) !== 0) {
            $labels[] = 'アップロード不要';
        }
        if ((int) ($row['is_reward_excluded'] ?? 0) !== 0) {
            $labels[] = '報酬除外';
        }
        if ((int) ($row['is_confirmation_checked'] ?? 0) !== 0) {
            $labels[] = '確認チェック';
        }
        if ((float) ($row['received_amount'] ?? 0) != 0.0) {
            $labels[] = '入金確認済み金額';
        }
        if (trim((string) ($row['deposit_confirmation_note'] ?? '')) !== '') {
            $labels[] = '入金確認メモ';
        }

        return $labels;
    }

    public function updateJournalEntry(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'journal_entry_id' => ['required', 'integer'],
            'journal_breakdown' => ['nullable', 'string', 'max:255'],
            'vault_name' => ['nullable', 'string', 'max:255'],
            'management_number' => ['nullable', 'string', 'max:255'],
            'occurred_at' => ['nullable', 'date'],
            'debit_account_title' => ['nullable', 'string', 'max:255'],
            'debit_counterparty' => ['nullable', 'string', 'max:255'],
            'debit_item_name' => ['nullable', 'string', 'max:255'],
            'debit_department_name' => ['nullable', 'string', 'max:255'],
            'debit_amount' => ['nullable', 'string', 'max:255'],
            'debit_memo_tag' => ['nullable', 'string', 'max:255'],
            'allocation_ratio' => ['nullable', 'string', 'max:255'],
            'credit_account_title' => ['nullable', 'string', 'max:255'],
            'credit_counterparty' => ['nullable', 'string', 'max:255'],
            'credit_item_name' => ['nullable', 'string', 'max:255'],
            'credit_department_name' => ['nullable', 'string', 'max:255'],
            'credit_amount' => ['nullable', 'string', 'max:255'],
            'credit_memo_tag' => ['nullable', 'string', 'max:255'],
            'deposit_name' => ['nullable', 'string', 'max:255'],
            'summary_text' => ['nullable', 'string'],
            'company_name_short' => ['nullable', 'string', 'max:255'],
        ]);

        $debitAmount = $this->parseEditableMoney($request->input('debit_amount'));
        $creditAmount = $this->parseEditableMoney($request->input('credit_amount'));
        if ($debitAmount === false || $creditAmount === false) {
            return redirect()->route('admin.work.journal_entries', $this->journalEntriesRedirectParams($request))
                ->with('errorMessage', '金額は数値で入力してください。');
        }

        $payload = [
            'journal_breakdown' => $this->nullableTrim($data['journal_breakdown'] ?? null),
            'vault_name' => $this->nullableTrim($data['vault_name'] ?? null),
            'management_number' => $this->nullableTrim($data['management_number'] ?? null),
            'occurred_at' => ($data['occurred_at'] ?? '') !== '' ? Carbon::parse((string) $data['occurred_at'])->format('Y-m-d') : null,
            'debit_account_title' => $this->nullableTrim($data['debit_account_title'] ?? null),
            'debit_counterparty' => $this->nullableTrim($data['debit_counterparty'] ?? null),
            'debit_item_name' => $this->nullableTrim($data['debit_item_name'] ?? null),
            'debit_department_name' => $this->nullableTrim($data['debit_department_name'] ?? null),
            'debit_amount' => $debitAmount,
            'debit_memo_tag' => $this->nullableTrim($data['debit_memo_tag'] ?? null),
            'allocation_ratio' => $this->nullableTrim($data['allocation_ratio'] ?? null),
            'credit_account_title' => $this->nullableTrim($data['credit_account_title'] ?? null),
            'credit_counterparty' => $this->nullableTrim($data['credit_counterparty'] ?? null),
            'credit_item_name' => $this->nullableTrim($data['credit_item_name'] ?? null),
            'credit_department_name' => $this->nullableTrim($data['credit_department_name'] ?? null),
            'credit_amount' => $creditAmount,
            'credit_memo_tag' => $this->nullableTrim($data['credit_memo_tag'] ?? null),
            'deposit_name' => $this->nullableTrim($data['deposit_name'] ?? null),
            'summary_text' => $this->nullableTrim($data['summary_text'] ?? null),
            'company_name_short' => $this->nullableTrim($data['company_name_short'] ?? null),
            'is_trial_balance_excluded' => $request->boolean('is_trial_balance_excluded') ? 1 : 0,
            'is_upload_unnecessary' => $request->boolean('is_upload_unnecessary') ? 1 : 0,
            'is_reward_excluded' => $request->boolean('is_reward_excluded') ? 1 : 0,
        ];

        if (($payload['occurred_at'] ?? null) !== null) {
            $payload['month_date'] = Carbon::parse((string) $payload['occurred_at'])->startOfMonth()->format('Y-m-d');
        }

        $columns = array_flip(Schema::connection('sqlsrv')->getColumnListing('mx_journal_entries'));
        if (isset($columns['updated_at'])) {
            $payload['updated_at'] = now();
        }
        $payload = array_intersect_key($payload, $columns);

        $affected = DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->where('journal_entry_id', (int) $data['journal_entry_id'])
            ->update($payload);

        if ($affected === 0) {
            return redirect()->route('admin.work.journal_entries', $this->journalEntriesRedirectParams($request))
                ->with('errorMessage', '保存対象の仕訳が見つかりません。画面を更新してから再度お試しください。');
        }

        return redirect()->route('admin.work.journal_entries', $this->journalEntriesRedirectParams($request))
            ->with('statusMessage', '仕訳帳を保存しました。');
    }

    public function updateJournalEntryGroup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'common' => ['required', 'array'],
            'common.journal_breakdown' => ['nullable', 'string', 'max:255'],
            'common.vault_name' => ['nullable', 'string', 'max:255'],
            'common.management_number' => ['nullable', 'string', 'max:255'],
            'common.occurred_at' => ['nullable', 'date'],
            'common.counterparty' => ['nullable', 'string', 'max:255'],
            'common.summary_text' => ['nullable', 'string'],
            'common.company_name_short' => ['nullable', 'string', 'max:255'],
            'rows' => ['required', 'array'],
            'rows.*.debit_account_title' => ['nullable', 'string', 'max:255'],
            'rows.*.debit_item_name' => ['nullable', 'string', 'max:255'],
            'rows.*.debit_department_name' => ['nullable', 'string', 'max:255'],
            'rows.*.debit_amount' => ['nullable', 'string', 'max:255'],
            'rows.*.debit_memo_tag' => ['nullable', 'string', 'max:255'],
            'rows.*.allocation_ratio' => ['nullable', 'string', 'max:255'],
            'rows.*.credit_account_title' => ['nullable', 'string', 'max:255'],
            'rows.*.credit_item_name' => ['nullable', 'string', 'max:255'],
            'rows.*.credit_department_name' => ['nullable', 'string', 'max:255'],
            'rows.*.credit_amount' => ['nullable', 'string', 'max:255'],
            'rows.*.credit_memo_tag' => ['nullable', 'string', 'max:255'],
        ]);

        $common = $data['common'];
        $occurredAt = ($common['occurred_at'] ?? '') !== ''
            ? Carbon::parse((string) $common['occurred_at'])->format('Y-m-d')
            : null;
        $counterparty = $this->nullableTrim($common['counterparty'] ?? null);

        $commonPayload = [
            'journal_breakdown' => $this->nullableTrim($common['journal_breakdown'] ?? null),
            'vault_name' => $this->nullableTrim($common['vault_name'] ?? null),
            'management_number' => $this->nullableTrim($common['management_number'] ?? null),
            'occurred_at' => $occurredAt,
            'month_date' => $occurredAt !== null ? Carbon::parse($occurredAt)->startOfMonth()->format('Y-m-d') : null,
            'debit_counterparty' => $counterparty,
            'credit_counterparty' => $counterparty,
            'summary_text' => $this->nullableTrim($common['summary_text'] ?? null),
            'company_name_short' => $this->nullableTrim($common['company_name_short'] ?? null),
            'is_trial_balance_excluded' => $request->boolean('common.is_trial_balance_excluded') ? 1 : 0,
            'is_upload_unnecessary' => $request->boolean('common.is_upload_unnecessary') ? 1 : 0,
            'is_reward_excluded' => $request->boolean('common.is_reward_excluded') ? 1 : 0,
        ];

        $columns = array_flip(Schema::connection('sqlsrv')->getColumnListing('mx_journal_entries'));
        if (isset($columns['updated_at'])) {
            $commonPayload['updated_at'] = now();
        }
        $commonPayload = array_intersect_key($commonPayload, $columns);

        try {
            DB::connection('sqlsrv')->transaction(function () use ($data, $commonPayload, $columns): void {
                foreach ($data['rows'] as $journalEntryId => $row) {
                    $debitAmount = $this->parseEditableMoney($row['debit_amount'] ?? null);
                    $creditAmount = $this->parseEditableMoney($row['credit_amount'] ?? null);
                    if ($debitAmount === false || $creditAmount === false) {
                        throw new \InvalidArgumentException('amount');
                    }

                    $payload = array_merge($commonPayload, [
                        'debit_account_title' => $this->nullableTrim($row['debit_account_title'] ?? null),
                        'debit_item_name' => $this->nullableTrim($row['debit_item_name'] ?? null),
                        'debit_department_name' => $this->nullableTrim($row['debit_department_name'] ?? null),
                        'debit_amount' => $debitAmount,
                        'debit_memo_tag' => $this->nullableTrim($row['debit_memo_tag'] ?? null),
                        'allocation_ratio' => $this->nullableTrim($row['allocation_ratio'] ?? null),
                        'credit_account_title' => $this->nullableTrim($row['credit_account_title'] ?? null),
                        'credit_item_name' => $this->nullableTrim($row['credit_item_name'] ?? null),
                        'credit_department_name' => $this->nullableTrim($row['credit_department_name'] ?? null),
                        'credit_amount' => $creditAmount,
                        'credit_memo_tag' => $this->nullableTrim($row['credit_memo_tag'] ?? null),
                    ]);
                    $payload = array_intersect_key($payload, $columns);

                    $affected = DB::connection('sqlsrv')
                        ->table('dbo.mx_journal_entries')
                        ->where('journal_entry_id', (int) $journalEntryId)
                        ->update($payload);

                    if ($affected === 0) {
                        throw new \RuntimeException('journal_entry_not_found:' . $journalEntryId);
                    }
                }
            });
        } catch (\InvalidArgumentException) {
            return redirect()->route('admin.work.journal_entries', $this->journalEntriesRedirectParams($request))
                ->with('errorMessage', '金額は数値で入力してください。');
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.work.journal_entries', $this->journalEntriesRedirectParams($request))
                ->with('errorMessage', '保存対象の仕訳が見つかりません。画面を更新してから再度お試しください。（' . $e->getMessage() . '）');
        }

        return redirect()->route('admin.work.journal_entries', $this->journalEntriesRedirectParams($request))
            ->with('statusMessage', '仕訳帳を保存しました。');
    }

    public function deleteJournalEntry(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'journal_entry_id' => ['required', 'integer'],
        ]);

        $affected = DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->where('journal_entry_id', (int) $data['journal_entry_id'])
            ->delete();

        if ($affected === 0) {
            return redirect()->route('admin.work.journal_entries', $this->journalEntriesRedirectParams($request))
                ->with('errorMessage', '削除対象の仕訳が見つかりません。画面を更新してから再度お試しください。');
        }

        return redirect()->route('admin.work.journal_entries', $this->journalEntriesRedirectParams($request))
            ->with('statusMessage', '仕訳帳を削除しました。');
    }

    /**
     * 複合仕訳のグループを丸ごと削除する。行単位の削除だと1行消すごとにページがリロードされて
     * 最初から探し直しになり、複数行あるグループを消すのが大変だったため追加
     * (2026-09-16、ユーザー要望)。対象行はgroup_key(会社+発生日+journal_breakdown)から
     * 再検索せず、画面に描画済みのjournal_entry_idを明示的に渡してもらってそれだけを消す
     * （表示後にデータが変わっていても、意図しない行まで巻き込まない）。
     */
    public function deleteJournalEntryGroup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'journal_entry_ids' => ['required', 'array', 'min:1'],
            'journal_entry_ids.*' => ['integer'],
        ]);

        $ids = array_map('intval', $data['journal_entry_ids']);

        $affected = DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->whereIn('journal_entry_id', $ids)
            ->delete();

        if ($affected === 0) {
            return redirect()->route('admin.work.journal_entries', $this->journalEntriesRedirectParams($request))
                ->with('errorMessage', '削除対象の仕訳が見つかりません。画面を更新してから再度お試しください。');
        }

        return redirect()->route('admin.work.journal_entries', $this->journalEntriesRedirectParams($request))
            ->with('statusMessage', "仕訳グループを削除しました（{$affected}行）。");
    }

    /**
     * 行単位でstr_getcsv()していた旧実装は、取引内容等のセル内に改行を含む行が来ると
     * そこで1レコードが分断され、以降の列がすべてズレて取り込まれていた。fgetcsv()は
     * クォート内の改行をレコードの区切りと見なさないため、この問題が起きない。
     */
    private function readCsvAssocRows(string $path): array
    {
        $contents = file_get_contents($path);
        if ($contents === false || $contents === '') {
            return [];
        }

        $contents = mb_convert_encoding($contents, 'UTF-8', 'SJIS-win,UTF-8');
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        $headers = null;
        $rows = [];
        while (($values = fgetcsv($stream)) !== false) {
            if ($headers === null) {
                $headers = array_map(fn(?string $header): string => trim((string) $header), $values);
                continue;
            }

            if ($values === [null] || $values === ['']) {
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }
                $row[$header] = trim((string) ($values[$index] ?? ''));
            }
            $rows[] = $row;
        }
        fclose($stream);

        return $rows;
    }

    private function csvValue(array $row, string ...$keys): string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return trim((string) $row[$key]);
            }
        }

        return '';
    }

    private function parseCsvDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse(str_replace('/', '-', $value))->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    // 検索フィルター・CSV取込どちらも「空欄/不正値は黙ってnull」という同じ契約だったため統合。
    // parseEditableMoneyだけは「空」nullと「不正値」falseを区別してエラー判定に使っており、
    // 挙動を変えると保存時のバリデーションに影響するため、意図的に分けたまま残している。
    private function parseMoneyValue(string $value): ?float
    {
        // PHPのtrim()は半角空白しか除去しないため、全角スペースが混ざったCSV・手入力値は
        // is_numeric()がfalseを返し不正値扱いになっていた。カンマ等と同様に先に除去する。
        $value = trim(str_replace([',', '￥', '\\', '　'], '', $value));
        if ($value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function journalBreakdownValue(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return ctype_digit($value) ? str_pad($value, 7, '0', STR_PAD_LEFT) : $value;
    }

    private function depositNameFromCsv(array $row): ?string
    {
        if ($this->csvValue($row, '貸方勘定科目') !== '医療未収入金') {
            return null;
        }

        $value = str_replace(['　', '振込入金＊', '振込入金'], '', $this->csvValue($row, '取引内容'))
            . $this->csvValue($row, '借方備考')
            . $this->csvValue($row, '貸方備考');
        $value = mb_convert_kana(trim($value), 'as', 'UTF-8');

        return $value === '' ? null : $value;
    }

    private function fetchDepartmentSelectOptions(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_departments')
            ->select(['store_short_name', 'store_category'])
            ->whereNull('closed_on')
            ->whereNotNull('store_short_name')
            ->where('store_short_name', '<>', '')
            ->whereNotNull('store_category')
            ->where('store_category', '<>', '')
            ->orderBy('store_category')
            ->get()
            ->map(function ($row): array {
                $value = trim((string) ($row->store_short_name ?? ''));
                $category = trim((string) ($row->store_category ?? ''));

                return [
                    'value' => $value,
                    'label' => $category,
                ];
            })
            ->filter(fn(array $row): bool => $row['value'] !== '')
            ->values()
            ->all();
    }

    /**
     * store_short_name（保存値、例: T_さくら 店舗）→ store_category（表示名、例: さくら店舗）
     * のマップ。絞り込み候補欄の表示専用のため、fetchDepartmentSelectOptions()と違って
     * 閉鎖済み部門も含める（過去の仕訳を検索する時に閉鎖済み部門も引けないと困るため）。
     *
     * @return array<string, string>
     */
    private function fetchDepartmentLabelMap(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_departments')
            ->select(['store_short_name', 'store_category'])
            ->whereNotNull('store_short_name')
            ->where('store_short_name', '<>', '')
            ->whereNotNull('store_category')
            ->where('store_category', '<>', '')
            ->get()
            ->mapWithKeys(function ($row): array {
                return [trim((string) $row->store_short_name) => trim((string) $row->store_category)];
            })
            ->all();
    }
    // 日付で絞らずテーブル全体をGROUP BYすると、蓄積年数が長いほど毎回のページ表示が
    // 重くなる（特に金額・摘要は値の種類が多い）。オートコンプリート候補は今表示している
    // 期間に関係する値だけで十分なので、date_from/date_toが渡された時はそこで絞り込む。
    private function fetchDistinctOptions(string $column, bool $formatMoney = false, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->select($column)
            ->whereNotNull($column)
            ->when($dateFrom !== null && $dateFrom !== '', fn($query) => $query->whereDate('occurred_at', '>=', $dateFrom))
            ->when($dateTo !== null && $dateTo !== '', fn($query) => $query->whereDate('occurred_at', '<=', $dateTo))
            ->groupBy($column)
            ->orderBy($column)
            ->pluck($column)
            ->map(function ($value) use ($formatMoney): string {
                if ($formatMoney && is_numeric($value)) {
                    return number_format((float) $value, 0, '.', '');
                }

                return trim((string) $value);
            })
            ->filter(fn(string $value): bool => $value !== '')
            ->values()
            ->all();
    }

    private function fetchDistinctUnionOptions(string $leftColumn, string $rightColumn, bool $formatMoney = false, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $mergedValues = array_values(array_unique(array_merge(
            $this->fetchDistinctOptions($leftColumn, $formatMoney, $dateFrom, $dateTo),
            $this->fetchDistinctOptions($rightColumn, $formatMoney, $dateFrom, $dateTo),
        )));

        natcasesort($mergedValues);

        return array_values($mergedValues);
    }

    private function parseEditableMoney(mixed $value): float|int|null|false
    {
        // parseMoneyValue()と同じ理由（全角スペースはtrim()で除去されない）で、手入力欄でも
        // 全角スペースが混ざっただけで「金額は数値で入力してください」と弾かれていた。
        $value = trim(str_replace([',', '　'], '', (string) ($value ?? '')));
        if ($value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return false;
        }

        return str_contains($value, '.') ? (float) $value : (int) $value;
    }

    private function nullableTrim(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function journalEntriesRedirectParams(Request $request): array
    {
        return array_filter([
            'date_from' => $request->input('filter_date_from'),
            'date_to' => $request->input('filter_date_to'),
            'company_name_short' => $request->input('filter_company_name_short'),
            'counterparty' => $request->input('filter_counterparty'),
            'amount' => $request->input('filter_amount'),
            'summary_text' => $request->input('filter_summary_text'),
            'account_title' => $request->input('filter_account_title'),
            'item_name' => $request->input('filter_item_name'),
            'department_name' => $request->input('filter_department_name'),
            'management_number' => $request->input('filter_management_number'),
            'journal_breakdown' => $request->input('filter_journal_breakdown'),
        ], fn($value): bool => $value !== null && $value !== '');
    }

    private function rewardCalculationRow(object $row, Carbon $from, Carbon $to, string $payrollLabel, string $bonusLabel): array
    {
        $debitAmount = (float) ($row->debit_amount ?? 0);
        $creditAmount = (float) ($row->credit_amount ?? 0);
        $summaryText = trim((string) ($row->summary_text ?? ''));
        $expenseAmount = str_contains($summaryText, '減免') ? $creditAmount : $debitAmount;
        $allocationRatio = $this->parseRatioValue($row->allocation_ratio ?? null);
        $managementNumber = trim((string) ($row->management_number ?? ''));
        $expenseSortName = trim((string) ($row->expense_sort_name ?? ''));
        $debitAccountTitle = trim((string) ($row->debit_account_title ?? ''));
        $isPayrollManagementNumber = str_contains($managementNumber, '月給与')
            || str_contains($managementNumber, '月賞与');
        $isLaborCost = $isPayrollManagementNumber
            || $expenseSortName === '人件費'
            || in_array($debitAccountTitle, ['給料手当', '役員報酬', '賞与', '業務委託料', '法定福利費'], true);
        $hasTargetPayrollLabel = str_contains($managementNumber, $payrollLabel)
            || str_contains($managementNumber, $bonusLabel);
        $occurredAt = $this->toCarbon($row->occurred_at ?? null);
        $isCurrentMonthOccurred = $occurredAt !== null && $occurredAt->betweenIncluded($from, $to);

        return [
            'journal_entry_id' => (int) ($row->journal_entry_id ?? 0),
            'occurred_at' => $this->formatDateValue($row->occurred_at, 'Y/m/d'),
            'journal_breakdown' => trim((string) ($row->journal_breakdown ?? '')),
            'management_number' => $managementNumber,
            'item' => $this->rewardItemName($row),
            'item_category' => $this->rewardItemCategory($row),
            'expense_sort_name' => $expenseSortName,
            'debit_account_title' => $debitAccountTitle,
            'debit_item_name' => trim((string) ($row->debit_item_name ?? '')),
            'debit_department_name' => trim((string) ($row->debit_department_name ?? '')),
            'debit_counterparty' => trim((string) ($row->debit_counterparty ?? '')),
            'credit_account_title' => trim((string) ($row->credit_account_title ?? '')),
            'credit_item_name' => trim((string) ($row->credit_item_name ?? '')),
            'credit_department_name' => trim((string) ($row->credit_department_name ?? '')),
            'credit_counterparty' => trim((string) ($row->credit_counterparty ?? '')),
            'summary_text' => $summaryText,
            'company_name_short' => trim((string) ($row->company_name_short ?? '')),
            'expense_amount' => $expenseAmount,
            'allocation_ratio' => trim((string) ($row->allocation_ratio ?? '')),
            'allocation_ratio_number' => $allocationRatio,
            'allocated_amount' => $allocationRatio > 0 ? $expenseAmount / $allocationRatio : 0.0,
            'is_labor_cost' => $isLaborCost,
            'is_target_reward_period' => $isLaborCost ? $hasTargetPayrollLabel : $isCurrentMonthOccurred,
        ];
    }

    private function rewardExpenseGroups(array $rows, string $amountKey): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $groupName = trim((string) ($row['expense_sort_name'] ?? ''));
            if ($groupName === '') {
                $groupName = '未分類';
            }
            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [
                    'name' => $groupName,
                    'rows' => [],
                    'total' => 0.0,
                    'count' => 0,
                ];
            }

            $amount = (float) ($row[$amountKey] ?? 0);
            $groups[$groupName]['rows'][] = $row;
            $groups[$groupName]['total'] += $amount;
            $groups[$groupName]['count']++;
        }

        foreach ($groups as $groupName => $group) {
            $groups[$groupName]['summary_rows'] = $this->rewardSummaryRows($group['rows'], $amountKey);
        }

        return array_values($groups);
    }

    private function rewardSummaryRows(array $rows, string $amountKey): array
    {
        $summaryRows = [];
        foreach ($rows as $row) {
            $itemCategory = trim((string) ($row['item_category'] ?? ''));
            $item = trim((string) ($row['item'] ?? ''));
            $allocationRatio = trim((string) ($row['allocation_ratio'] ?? ''));
            $occurredAt = trim((string) ($row['occurred_at'] ?? ''));
            $key = implode('|', [$occurredAt, $itemCategory, $item, $allocationRatio]);

            if (!isset($summaryRows[$key])) {
                $summaryRows[$key] = [
                    'occurred_at' => trim((string) ($row['occurred_at'] ?? '')),
                    'item_category' => $itemCategory,
                    'item' => $item,
                    'allocation_ratio' => $allocationRatio,
                    'expense_amount' => 0.0,
                    'allocated_amount' => 0.0,
                    'count' => 0,
                ];
            }

            $summaryRows[$key]['expense_amount'] += (float) ($row['expense_amount'] ?? 0);
            $summaryRows[$key]['allocated_amount'] += (float) ($row['allocated_amount'] ?? 0);
            $summaryRows[$key]['count']++;
        }

        return array_values($summaryRows);
    }

    private function rewardCompanyGroups(array $rows, string $amountKey): array
    {
        $companies = [];
        foreach ($rows as $row) {
            $companyName = trim((string) ($row['company_name_short'] ?? ''));
            if ($companyName === '') {
                $companyName = '会社未設定';
            }

            if (!isset($companies[$companyName])) {
                $companies[$companyName] = [
                    'company_name' => $companyName,
                    'groups' => [],
                    'total' => 0.0,
                    'count' => 0,
                ];
            }

            $amount = (float) ($row[$amountKey] ?? 0);
            $companies[$companyName]['groups'][] = $row;
            $companies[$companyName]['total'] += $amount;
            $companies[$companyName]['count']++;
        }

        foreach ($companies as $companyName => $company) {
            $companies[$companyName]['groups'] = $this->rewardExpenseGroups($company['groups'], $amountKey);
        }

        return array_values($companies);
    }

    private function rewardItemName(object $row): string
    {
        $accountTitle = trim((string) ($row->debit_account_title ?? ''));
        $itemName = trim((string) ($row->debit_item_name ?? ''));
        $counterparty = trim((string) ($row->debit_counterparty ?? ''));

        if ($accountTitle === '旅費交通費' && $itemName === '通勤手当') {
            return '通勤手当';
        }

        if (in_array($accountTitle, ['給料手当', '役員報酬', '賞与', '業務委託料', '旅費交通費'], true)) {
            $summary = trim((string) ($row->summary_text ?? ''));
            $summary = str_replace(
                ['総合振込', 'インタ－ネツト', '　', ' ', 'さくら店舗', '健康保険料（預り分）', 'プレッジ', '雇用保険料（預り分）', 'トータルケア'],
                '',
                $summary
            );

            return trim($summary . $counterparty);
        }

        return $counterparty;
    }

    private function rewardItemCategory(object $row): string
    {
        $counterparty = trim((string) ($row->debit_counterparty ?? ''));
        $managementNumber = trim((string) ($row->management_number ?? ''));
        $majorCategory = trim((string) ($row->major_category_name ?? ''));
        $middleCategory = trim((string) ($row->middle_category_name ?? ''));
        $debitItem = trim((string) ($row->debit_item_name ?? ''));
        $debitAccount = trim((string) ($row->debit_account_title ?? ''));

        if ($counterparty === '手技療法専門通販') {
            return '医療消耗品費';
        }
        if (str_contains($managementNumber, '月店舗経費')) {
            return '除外';
        }
        if ($majorCategory === '資産' && $middleCategory !== '固定資産') {
            return trim((string) ($row->credit_account_title ?? ''));
        }

        return $debitItem !== '' ? $debitItem : $debitAccount;
    }

    private function parseRatioValue(mixed $value): float
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return 0.0;
        }

        $text = str_replace(',', '', $text);
        return is_numeric($text) ? (float) $text : 0.0;
    }

    private function containsSakura(string $value): bool
    {
        return str_contains($value, 'さくら');
    }

    private function isSakuraStoreSalesRow(array $row): bool
    {
        $departmentName = trim((string) ($row['department_name'] ?? ''));
        $storeName = trim((string) ($row['store_name'] ?? ''));

        return str_contains($departmentName, 'さくら店舗')
            || str_contains($departmentName, 'T_さくら 店舗')
            || str_contains($storeName, 'さくら店舗')
            || str_contains($storeName, 'T_さくら 店舗');
    }

    private function toCarbon(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatDateValue(mixed $value, string $format = 'Y/m/d'): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return \Illuminate\Support\Carbon::parse((string) $value)->format($format);
        } catch (\Throwable) {
            return trim((string) $value);
        }
    }

    private function formatMoneyValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (!is_numeric($value)) {
            return trim((string) $value);
        }

        return number_format((float) $value);
    }
}
