<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class YearEndInsuranceAttachmentDisplayTest extends TestCase
{
    private function renderInsurance(array $rows): string
    {
        $rows = array_map(fn(array $row): array => $row + ['declared_amount' => null], $rows);
        DB::shouldReceive('connection')->never();
        $application = array_fill_keys([
            'personal_info_changed', 'previous_job_withholding_changed', 'spouse_changed',
            'dependents_changed', 'insurance_deduction_changed', 'housing_loan_changed',
        ], 0);
        return view('staff_portal.year_end.index', [
            'errors' => new \Illuminate\Support\ViewErrorBag(),
            'displayName' => 'テスト', 'targetYear' => 2026, 'editable' => true,
            'statusLabel' => '下書き', 'application' => $application, 'currentNenTyo' => [],
            'currentAddress' => '', 'currentAddressFuri' => '', 'currentStaffName' => '',
            'currentStaffNameFuri' => '', 'currentBirthday' => '', 'currentHeadHouse' => '',
            'currentRelationship' => '', 'dependentRows' => [], 'spouseFuyoRow' => null,
            'hokenRows' => $rows, 'hasPreviousYearInsurance' => false,
        ])->render();
    }

    public function test_saved_paths_clear_warnings_even_when_original_filename_is_missing(): void
    {
        $html = $this->renderInsurance([
            ['hoken_no' => 1, 'certificate_file_path' => 'insurance/one.pdf', 'certificate_original_name' => '証明書.pdf'],
            ['hoken_no' => 2, 'certificate_file_path' => 'insurance/two.pdf', 'certificate_original_name' => null],
        ]);
        self::assertStringNotContainsString('証憑未添付あり', $html);
        self::assertStringNotContainsString('証憑ファイルが未添付です', $html);
        self::assertStringContainsString('現在のファイル', $html);
        self::assertStringContainsString('/staff/year-end-adjustment/certificate/insurance/1', $html);
        self::assertStringContainsString('保存済みの添付をプレビュー', $html);
    }

    public function test_one_missing_path_keeps_header_and_row_warning_even_if_filename_exists(): void
    {
        $html = $this->renderInsurance([
            ['hoken_no' => 1, 'certificate_file_path' => 'insurance/one.pdf'],
            ['hoken_no' => 2, 'certificate_file_path' => null, 'certificate_original_name' => '名前だけ.pdf'],
        ]);
        self::assertStringContainsString('証憑未添付あり', $html);
        self::assertSame(1, substr_count($html, '証憑ファイルが未添付です'));
    }

    public function test_no_insurance_rows_do_not_show_attachment_warning(): void
    {
        $html = $this->renderInsurance([]);
        self::assertStringNotContainsString('証憑未添付あり', $html);
        self::assertStringNotContainsString('証憑ファイルが未添付です', $html);
    }

    public function test_draft_button_is_added_only_to_the_insurance_form(): void
    {
        $html = $this->renderInsurance([]);
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($dom);
        $buttons = $xpath->query('//button[@name="insurance_save_mode"]');
        self::assertCount(2, $buttons);
        foreach ($buttons as $button) {
            self::assertStringContainsString('/insurance', $xpath->query('ancestor::form', $button)->item(0)->getAttribute('action'));
        }
        self::assertStringContainsString('value="draft"', $html);
        self::assertStringContainsString('value="complete"', $html);
        self::assertStringNotContainsString('insuranceYes.checked', $html);
    }
}
