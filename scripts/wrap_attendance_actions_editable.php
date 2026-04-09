<?php

$path = __DIR__ . '/../resources/views/admin_v2/work/attendance/daily_table_item.blade.php';
$source = file_get_contents($path);
if ($source === false) {
    fwrite(STDERR, "read failed\n");
    exit(1);
}

$lines = preg_split("/\r\n|\n|\r/", $source);
if ($lines === false) {
    fwrite(STDERR, "split failed\n");
    exit(1);
}

$startIndex = 175; // 1-based line 176
$length = 17;      // through line 192

$replacement = [
    '                            <td>',
    '                                @if ($isEditable)',
    '                                    <div class="daily-actions daily-view">',
    '                                        <button class="btn btn-small daily-btn-muted" type="button" data-action="edit">邱ｨ髮・/button>',
    '                                    </div>',
    '                                    <div class="daily-actions daily-edit">',
    '                                        <button class="btn btn-small" type="submit" form="{{ $formId }}">菫晏ｭ・/button>',
    '                                        <button class="btn btn-small daily-btn-muted" type="button" data-action="cancel">蜿匁ｶ・/button>',
    '                                    </div>',
    "                                    <form id=\"{{ \$formId }}\" method=\"post\" action=\"{{ route('admin.attendance.update-daily') }}\">",
    '                                        @csrf',
    '                                        <input type="hidden" name="month" value="{{ $selectedMonth }}">',
    '                                        <input type="hidden" name="company_id" value="{{ $selectedCompanyId }}">',
    '                                        <input type="hidden" name="staff_id" value="{{ $selectedStaffId }}">',
    "                                        <input type=\"hidden\" name=\"time_card_key\" value=\"{{ \$row['time_card_key'] }}\">",
    "                                        <input type=\"hidden\" name=\"work_date\" value=\"{{ \$row['work_date'] }}\">",
    '                                    </form>',
    '                                @endif',
    '                            </td>',
];

array_splice($lines, $startIndex, $length, $replacement);

$updated = implode(PHP_EOL, $lines);

if (file_put_contents($path, $updated) === false) {
    fwrite(STDERR, "write failed\n");
    exit(1);
}

echo "wrapped attendance action block.\n";
