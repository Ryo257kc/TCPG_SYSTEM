<?php
$path='app/Http/Controllers/Admin/PayrollController.php';
$s=file_get_contents($path);
$pattern='/private function applyComputedTotals\(array &\$payload, string \$staffId = \'\', string \$payrollMonthDate = \'\'\): void\s*\{.*?\n\s*\}\n\s*\n\s*private function applyEmploymentInsurance/s';
$replacement=<<<'PHP'
private function applyComputedTotals(array &$payload, string $staffId = '', string $payrollMonthDate = ''): void
    {
        $syahoSum = $this->payloadNumber($payload, 'kenpo')
            + $this->payloadNumber($payload, 'kaigo')
            + $this->payloadNumber($payload, 'kounen')
            + $this->payloadNumber($payload, 'koyou');
        $payload['syaho_sum'] = $this->normalizeNumericInput($syahoSum);

        $supplySum = 0.0;
        $supplySum += $this->payloadNumber($payload, 'basic_salary');
        $supplySum += $this->payloadNumber($payload, 'officer_com');
        for ($i = 2; $i <= 17; $i++) {
            $supplySum += $this->payloadNumber($payload, 'allowance_amo_' . $i);
        }
        $supplySum += $this->payloadNumber($payload, 'traffic_addition');
        $supplySum += $this->payloadNumber($payload, 'leave_allowance');
        $supplySum += $this->payloadNumber($payload, 'late_deduction');
        $supplySum += $this->payloadNumber($payload, 'absence_deduction');

        $targets = $this->computeTargetsFromAllowanceSettings($payload, $staffId, $supplySum);
        $taxable = $targets['taxable'];
        $rouhoTarget = $targets['rouho_target'];
        $syahoTarget = $targets['syaho_target'];
        $nontaxable = max(0.0, $supplySum - $taxable);
        $payload['taxation_sum'] = $this->normalizeNumericInput($taxable);
        $payload['not_taxation_sum'] = $this->normalizeNumericInput($nontaxable);

        $payload['supply_sum'] = $this->normalizeNumericInput($supplySum);
        $payload['pay_total'] = $this->normalizeNumericInput($supplySum);

        $payload['rouho_target_sum'] = $this->normalizeNumericInput($rouhoTarget);
        $payload['syaho_target_sum'] = $this->normalizeNumericInput($syahoTarget);

        $deductionSum = $syahoSum
            + $this->payloadNumber($payload, 'income_tax')
            + $this->payloadNumber($payload, 'resident_tax')
            + $this->payloadNumber($payload, 'rent_cost')
            + $this->payloadNumber($payload, 'adjustment_cost');
        $payload['deduction_sum'] = $this->normalizeNumericInput($deductionSum);
        $payload['koujo_total'] = $this->normalizeNumericInput($deductionSum);

        $otherTotal = $this->payloadNumber($payload, 'adjustment_year_end')
            + $this->payloadNumber($payload, 'cost_liquidation')
            + $this->payloadNumber($payload, 'not_supply');
        $payload['other_total'] = $this->normalizeNumericInput($otherTotal);

        $afterSocial = $supplySum - $syahoSum;
        $payload['syaho_deduction_sum'] = $this->normalizeNumericInput($afterSocial);

        $netPay = $supplySum - $deductionSum + $otherTotal;
        $payload['supply_deduction_sum'] = $this->normalizeNumericInput($netPay);
        $payload['salary_total'] = $this->normalizeNumericInput($netPay);
        $payload['transfer_amo'] = $this->normalizeNumericInput($netPay);
    }

    private function applyEmploymentInsurance
PHP;
$n=preg_replace($pattern,$replacement,$s,1,$count);
if($count!==1){echo "replace failed count={$count}\n"; exit(2);} 
file_put_contents($path,$n);
echo "replaced\n";
