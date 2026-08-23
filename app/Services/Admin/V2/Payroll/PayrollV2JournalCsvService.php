<?php

namespace App\Services\Admin\V2\Payroll;

use Carbon\CarbonImmutable;

class PayrollV2JournalCsvService
{
    /**
     * 給与確定データ(mx_kyuyo_shou)から、freeeの取引インポート形式(振替伝票ではなく取引仕訳)で
     * 未払計上のCSVを作る。1取引(管理番号1つ)の中に、勘定科目1つ・符号付き金額1つの行を積み上げる形式。
     * 支給側(給料手当・役員報酬・旅費交通費・立替金)はプラス、天引き側(法定福利費・預り金)はマイナス。
     * 未払金の行は出さない(決済期日・決済日を空にした未決済分としてfreee側が自動的に扱う想定)。
     * 部署はスタッフマスタのstore_nameをそのまま使う(店舗↔部署の対応関係は未確定のため)。
     *
     * 金額項目の対応は実物の仕訳(journal_breakdown=6043037)、および2026-06支給分をユーザーが
     * 手で取引仕訳形式に組み直したサンプル(2026.6.20-給与.csv)の両方と完全一致することを検証済み:
     *   給料手当 = supply_sum - yakuin_sum - traffic_addition
     *   役員報酬 = yakuin_sum
     *   旅費交通費(通勤手当) = traffic_addition
     *   立替金 = cost_liquidation
     *   法定福利費/預り金 = kenpo・kaigo・kounen・koyou・child_support_funds・income_tax・resident_tax
     *
     * staff_division='業務委託'は対象外(賃金台帳のPayrollV2Controller::shouldIncludeWageLedgerRow()と同じ判定)。
     * 除外しないとyakuin_sum列に業務委託の契約金額が紛れて役員報酬として誤集計される。
     *
     * 部署はスタッフマスタのstore_nameをそのまま使う(店舗コード配下の個別部署へは分けない)。
     * 6043037の実物でも複数人が同じ店舗の1部署名にまとまっていたため、この粒度で正しい。
     *
     * @param list<array<string, mixed>> $rows PayrollV2SummaryService::mergeRows() の結果
     */
    public function build(array $rows, string $paymentDate): array
    {
        return $this->buildFor($rows, $paymentDate, '業務委託', false, '給料手当');
    }

    /**
     * 業務委託のみを対象に、人ごとに1行で業務委託料を出す版(2026-08-23、ユーザー指示で給与とは
     * 別ロジックにした)。給与のように給料手当/役員報酬/旅費交通費を分けず、supply_sumをそのまま
     * 業務委託料として合算する。取引先列にスタッフ名を入れる。立替金・天引き(法定福利費/預り金、
     * 通常は0)は金額があれば別行のまま人ごとに出す。
     */
    public function buildOutsource(array $rows, string $paymentDate): array
    {
        $occurredAt = $this->accrualDate($paymentDate);
        $managementLabel = $this->managementLabel($paymentDate);

        $lines = [];
        foreach ($rows as $row) {
            $summary = (array) ($row['summary'] ?? []);
            if ($summary === []) {
                continue;
            }
            if (trim((string) ($row['division'] ?? '')) !== '業務委託') {
                continue;
            }

            $staffName = trim((string) ($row['staff_name'] ?? ''));
            $storeName = trim((string) ($row['store_name'] ?? ''));

            $items = [
                ['業務委託料', null, '課対仕入（控80）10%', $this->amount($summary, 'supply_sum') + $this->amount($summary, 'cost_liquidation')],
                ['法定福利費', '健康保険料（預り分）', '対象外', -$this->amount($summary, 'kenpo')],
                ['法定福利費', '介護保険料（預り分）', '対象外', -$this->amount($summary, 'kaigo')],
                ['法定福利費', '厚生年金保険料（預り分）', '対象外', -$this->amount($summary, 'kounen')],
                ['法定福利費', '雇用保険料（預り分）', '対象外', -$this->amount($summary, 'koyou')],
                ['法定福利費', '子ども支援金（預り分）', '対象外', -$this->amount($summary, 'child_support_funds')],
                ['預り金', '源泉所得税', '対象外', -$this->amount($summary, 'income_tax')],
                ['預り金', '住民税', '対象外', -$this->amount($summary, 'resident_tax')],
            ];
            foreach ($items as [$title, $item, $taxCategory, $amount]) {
                if ($amount === 0.0) {
                    continue;
                }
                $lines[] = $this->line($occurredAt, $title, $item, $taxCategory, $amount, $storeName, $staffName);
            }
        }

        if ($lines !== []) {
            $lines[0]['expense_income'] = '支出';
            $lines[0]['management_number'] = $managementLabel;
        }

        return [
            'content' => $this->toCsv($lines),
            'row_count' => count($lines),
        ];
    }

    private function buildFor(array $rows, string $paymentDate, string $division, bool $onlyDivision, string $basicSalaryTitle): array
    {
        $occurredAt = $this->accrualDate($paymentDate);
        $managementLabel = $this->managementLabel($paymentDate);

        /** @var array<string, array<string, mixed>> $groups */
        $groups = [];

        foreach ($rows as $row) {
            $summary = (array) ($row['summary'] ?? []);
            if ($summary === []) {
                continue;
            }
            $isDivision = trim((string) ($row['division'] ?? '')) === $division;
            if ($isDivision !== $onlyDivision) {
                continue;
            }

            $companyName = trim((string) ($row['company_name'] ?? ''));
            $storeName = trim((string) ($row['store_name'] ?? ''));
            $groupKey = $companyName . '|' . $storeName;

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = $this->emptyGroup($storeName);
            }

            $yakuin = $this->amount($summary, 'yakuin_sum');
            $traffic = $this->amount($summary, 'traffic_addition');
            $supply = $this->amount($summary, 'supply_sum');

            $groups[$groupKey]['basic_salary'] += $supply - $yakuin - $traffic;
            $groups[$groupKey]['officer_compensation'] += $yakuin;
            $groups[$groupKey]['traffic_addition'] += $traffic;
            $groups[$groupKey]['cost_liquidation'] += $this->amount($summary, 'cost_liquidation');
            $groups[$groupKey]['kenpo'] += $this->amount($summary, 'kenpo');
            $groups[$groupKey]['kaigo'] += $this->amount($summary, 'kaigo');
            $groups[$groupKey]['kounen'] += $this->amount($summary, 'kounen');
            $groups[$groupKey]['koyou'] += $this->amount($summary, 'koyou');
            $groups[$groupKey]['child_support_funds'] += $this->amount($summary, 'child_support_funds');
            $groups[$groupKey]['income_tax'] += $this->amount($summary, 'income_tax');
            $groups[$groupKey]['resident_tax'] += $this->amount($summary, 'resident_tax');
        }

        $lines = [];
        foreach ($groups as $group) {
            $lines = array_merge($lines, $this->groupLines($group, $occurredAt, $basicSalaryTitle));
        }
        if ($lines !== []) {
            $lines[0]['expense_income'] = '支出';
            $lines[0]['management_number'] = $managementLabel;
        }

        return [
            'content' => $this->toCsv($lines),
            'row_count' => count($lines),
        ];
    }

    /** @return array<string, mixed> */
    private function emptyGroup(string $storeName): array
    {
        return [
            'store_name' => $storeName,
            'basic_salary' => 0.0,
            'officer_compensation' => 0.0,
            'traffic_addition' => 0.0,
            'cost_liquidation' => 0.0,
            'kenpo' => 0.0,
            'kaigo' => 0.0,
            'kounen' => 0.0,
            'koyou' => 0.0,
            'child_support_funds' => 0.0,
            'income_tax' => 0.0,
            'resident_tax' => 0.0,
        ];
    }

    /**
     * @param array<string, mixed> $group
     * @return list<array<string, mixed>>
     */
    private function groupLines(array $group, string $occurredAt, string $basicSalaryTitle): array
    {
        $storeName = (string) $group['store_name'];
        $lines = [];

        $items = [
            [$basicSalaryTitle, null, '対象外', (float) $group['basic_salary']],
            ['役員報酬', null, '対象外', (float) $group['officer_compensation']],
            ['旅費交通費', '通勤手当', '不課税', (float) $group['traffic_addition']],
            ['立替金', null, '対象外', (float) $group['cost_liquidation']],
            ['法定福利費', '健康保険料（預り分）', '対象外', -(float) $group['kenpo']],
            ['法定福利費', '介護保険料（預り分）', '対象外', -(float) $group['kaigo']],
            ['法定福利費', '厚生年金保険料（預り分）', '対象外', -(float) $group['kounen']],
            ['法定福利費', '雇用保険料（預り分）', '対象外', -(float) $group['koyou']],
            ['法定福利費', '子ども支援金（預り分）', '対象外', -(float) $group['child_support_funds']],
            ['預り金', '源泉所得税', '対象外', -(float) $group['income_tax']],
            ['預り金', '住民税', '対象外', -(float) $group['resident_tax']],
        ];
        foreach ($items as [$title, $item, $taxCategory, $amount]) {
            if ($amount === 0.0) {
                continue;
            }
            $lines[] = $this->line($occurredAt, $title, $item, $taxCategory, $amount, $storeName);
        }

        return $lines;
    }

    /** @return array<string, mixed> */
    private function line(
        string $occurredAt,
        string $accountTitle,
        ?string $itemName,
        string $taxCategory,
        float $amount,
        string $departmentName,
        ?string $note = null,
    ): array {
        return [
            'expense_income' => null,
            'management_number' => null,
            'occurred_at' => $occurredAt,
            'due_date' => null,
            'counterparty_code' => null,
            'counterparty' => null,
            'account_title' => $accountTitle,
            'tax_category' => $taxCategory,
            'amount' => $amount,
            'tax_calc_category' => null,
            'tax_amount' => null,
            'note' => $note,
            'item_name' => $itemName,
            'department_name' => $departmentName,
            'memo_tag' => null,
            'segment1' => null,
            'segment2' => null,
            'segment3' => null,
            'settled_at' => null,
            'settled_account' => null,
            'settled_amount' => null,
        ];
    }

    /** @param list<array<string, mixed>> $lines */
    private function toCsv(array $lines): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, [
            '収支区分',
            '管理番号',
            '発生日',
            '決済期日',
            '取引先コード',
            '取引先',
            '勘定科目',
            '税区分',
            '金額',
            '税計算区分',
            '税額',
            '備考',
            '品目',
            '部門',
            'メモタグ（複数指定可、カンマ区切り）',
            'セグメント1',
            'セグメント2',
            'セグメント3',
            '決済日',
            '決済口座',
            '決済金額',
        ]);

        foreach ($lines as $line) {
            fputcsv($handle, [
                $this->csvText($line['expense_income']),
                $this->csvText($line['management_number']),
                $line['occurred_at'],
                $this->csvText($line['due_date']),
                $this->csvText($line['counterparty_code']),
                $this->csvText($line['counterparty']),
                $this->csvText($line['account_title']),
                $this->csvText($line['tax_category']),
                $this->csvMoney((float) $line['amount']),
                $this->csvText($line['tax_calc_category']),
                $this->csvText($line['tax_amount']),
                $this->csvText($line['note']),
                $this->csvText($line['item_name']),
                $this->csvText($line['department_name']),
                $this->csvText($line['memo_tag']),
                $this->csvText($line['segment1']),
                $this->csvText($line['segment2']),
                $this->csvText($line['segment3']),
                $this->csvText($line['settled_at']),
                $this->csvText($line['settled_account']),
                $this->csvText($line['settled_amount']),
            ]);
        }

        rewind($handle);
        $content = (string) stream_get_contents($handle);
        fclose($handle);

        return mb_convert_encoding($content, 'SJIS-win', 'UTF-8');
    }

    private function accrualDate(string $paymentDate): string
    {
        try {
            return CarbonImmutable::parse($paymentDate)
                ->subMonthNoOverflow()
                ->endOfMonth()
                ->format('Y/m/d');
        } catch (\Throwable) {
            return '';
        }
    }

    private function managementLabel(string $paymentDate): string
    {
        try {
            return CarbonImmutable::parse($paymentDate)->format('Y/n') . '月給与';
        } catch (\Throwable) {
            return '';
        }
    }

    private function amount(array $summary, string $key): float
    {
        $value = $summary[$key] ?? 0;
        if ($value === null) {
            return 0.0;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $text = str_replace([',', ' '], '', trim((string) $value));
        return $text !== '' && is_numeric($text) ? (float) $text : 0.0;
    }

    private function csvMoney(float $value): string
    {
        return '\\' . number_format((int) round($value));
    }

    private function csvText(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }
}
