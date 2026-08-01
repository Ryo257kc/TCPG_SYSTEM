<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 勤怠管理詳細</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/daily_table_item_plain.css') }}">

    <style>
        .summary-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            grid-template-rows: auto auto;
            gap: 6px;
            align-items: stretch;
        }

        .summary-grid .memo-card {
            grid-column: 1;
            grid-row: 1 / span 2;
        }

        .summary-grid .total-card {
            min-height: 0;
        }

        .summary-box {
            border: 1px solid #d8c4b0;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.92);
            padding: 8px;
        }

        .summary-title {
            margin: 0 0 6px;
            font-size: 13px;
            font-weight: 800;
            color: #6b3f1f;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            padding: 3px 0;
            border-bottom: 1px dashed #e6d3c0;
        }

        .attendance-management-detail-page .data-table tbody tr.daily-row-returned>td {
            background: #fdecec !important;
            color: #9f1f1f !important;
            font-weight: 700;
        }
    </style>
</head>

<body class="attendance-management-detail-page">
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">日別勤怠（{{ $targetStaffName }}）</h2>
            </div>
            @if (!empty($statusMessage))
            <div class="status">{{ $statusMessage }}</div>
            @endif
            <div class="meta">
                <span>対象月: {{ $selectedMonth }}</span>
                <!-- <span>ID: {{ $targetStaffId }}</span>
            <span>名前: {{ $targetStaffName }}</span> -->
            </div>

            @php
            $summary = $dailySummary ?? [];
            $fmtSummary = static function (mixed $value, int $digits = 2): string {
            $number = (float) ($value ?? 0);
            return fmod($number, 1.0) === 0.0 ? (string) ((int) $number) : number_format($number, $digits);
            };
            @endphp
            <div class="summary-grid">
                <section class="summary-box">
                    <h3 class="summary-title">勤怠集計</h3>
                    <div class="summary-row"><span>出勤日数</span><strong>{{ $fmtSummary($summary['work_days'] ?? 0, 1) }}</strong></div>
                    <div class="summary-row"><span>欠勤日数</span><strong>{{ $fmtSummary($summary['absence_days'] ?? 0, 1) }}</strong></div>
                    <div class="summary-row"><span>有休日数</span><strong>{{ $fmtSummary($summary['paid_leave_days'] ?? 0, 1) }}</strong></div>
                </section>
                <section class="summary-box">
                    <h3 class="summary-title">時間集計</h3>
                    <div class="summary-row"><span>休日出勤</span><strong>{{ $fmtSummary($summary['holiday_work_days'] ?? 0, 1) }}</strong>日／<strong>{{ $fmtSummary($summary['holiday_work_time'] ?? 0, 2) }}</strong>時間</div>
                    <div class="summary-row"><span>出勤時間</span><strong>{{ $fmtSummary($summary['work_time_total'] ?? 0, 2) }}</strong></div>
                    <div class="summary-row"><span>残業時間</span><strong>{{ $fmtSummary($summary['overtime_total'] ?? 0, 2) }}</strong></div>
                </section>
                <section class="summary-box memo-card">
                    <h3 class="summary-title">差戻し</h3>

                    @forelse (($returnedSummaries ?? []) as $returnedSummary)
                    <div class="summary-row">
                        <span>{{ $returnedSummary['work_date'] }}</span>
                        <strong>{{ $returnedSummary['return_note'] }}</strong>
                    </div>
                    @empty
                    <div class="summary-row">
                        <span>差戻しはありません。</span>
                        <strong></strong>
                    </div>
                    @endforelse

                </section>
            </div>

            @if ($rowCount === 0)
            <div class="empty">対象データはありません。</div>
            @else
            @include('shared.attendance.daily_table_item_plain', [
            'rows' => $detailRows ?? [],
            'tableClass' => 'data-table',
            'wrapClass' => 'table-wrap',
            'showPaidLeaveUsed' => true,
            'showActualScheduled' => true,
            'showShiftScheduled' => true,
            'showChangeScheduled' => true,
            'showNightOvertime' => true,
            'rowClassResolver' => static function (array $row): string {
            if (!empty($row['is_returned'])) {
            return 'daily-row-returned';
            }
            $holidayType = trim((string) ($row['holiday_category'] ?? ''));
            return in_array($holidayType, ['休日', '祝日', '法休'], true) ? 'holiday' : '';
            },
            ])
            @endif

            @if (!empty($monthlyAppliedAt))
            <div class="apply-row">
                <span class="applied-text">{{ $monthlyAppliedAt }} 本人申請済</span>
                @if (!empty($canAdminApprove))
                <div class="action-buttons">
                    <form method="post" action="{{ route('admin.attendance.manage.approve', ['staffId' => $targetStaffId, 'month' => $selectedMonth]) }}">
                        @csrf
                        <button type="submit" class="btn-approve">管理者承認</button>
                    </form>
                    @if (!empty($canAdminRemand))
                    <form id="remand-form" method="post" action="{{ route('admin.attendance.manage.remand', ['staffId' => $targetStaffId, 'month' => $selectedMonth]) }}">
                        @csrf
                        <button type="submit" class="btn-remand margin_t20">差戻し</button>
                    </form>
                    @endif
                </div>
                @elseif (!empty($adminApprovedAt))
                <div class="applied-text">{{ $adminApprovedAt }} 管理者承認済</div>
                @endif
            </div>
            @endif



            <div>
                <a class="btn btn_back" href="{{ route('attendance.management', ['month' => $selectedMonth]) }}">戻る</a>
            </div>
            <!-- <div class="home-visit-counter-footer">
                <a href="{{ route('dashboard') }}" class="btn_back">戻る</a>
            </div> -->
        </section>
    </main>
</body>

</html>
