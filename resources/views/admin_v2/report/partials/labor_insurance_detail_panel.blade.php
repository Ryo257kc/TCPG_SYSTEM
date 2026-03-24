@php
    $detail = $report['detail'] ?? null;
@endphp

@if ($detail)
    <section class="panel detail-panel">
        <div class="detail-head">
            <div>
                <h2>{{ $detail['label'] }}</h2>
                <p>{{ number_format((int) $detail['total_count']) }}人 / {{ number_format((float) $detail['total_amount']) }}円</p>
            </div>
            <a
                class="btn btn-secondary"
                href="{{ route('admin.report.labor-insurance.index', ['year' => $selectedYear, 'company' => $selectedCompany]) }}"
            >
                閉じる
            </a>
        </div>

        <div class="table-wrap">
            <table class="report-table detail-table">
                <thead>
                <tr>
                    <th>社員ID</th>
                    <th>氏名</th>
                    <th>雇用区分</th>
                    <th>会社</th>
                    <th>店舗</th>
                    <th>金額</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($detail['rows'] as $row)
                    <tr>
                        <td>{{ $row['staff_id'] }}</td>
                        <td>{{ $row['staff_name'] !== '' ? $row['staff_name'] : '-' }}</td>
                        <td>{{ $row['staff_division'] !== '' ? $row['staff_division'] : '-' }}</td>
                        <td>{{ $row['company_name'] !== '' ? $row['company_name'] : '-' }}</td>
                        <td>{{ $row['store_name'] !== '' ? $row['store_name'] : '-' }}</td>
                        <td class="num">{{ number_format((float) $row['amount']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
