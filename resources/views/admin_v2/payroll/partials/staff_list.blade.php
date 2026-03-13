<aside class="staff-list" style="width:260px;min-width:260px;">
@php
    $currentStaffId = $selectedRow['staff_id'] ?? null;
@endphp

@forelse ($rows as $row)
    @php
        $isActive = $currentStaffId !== null && $currentStaffId === ($row['staff_id'] ?? null);
        $attendanceChecked = ((int) (($row['summary']['attendance_checked'] ?? 0))) === 1;
        $payrollConfirmed = ((int) (($row['summary']['edit_lock'] ?? 0))) === 1;
    @endphp
    <a class="staff-item {{ $isActive ? 'active' : '' }} {{ $payrollConfirmed ? 'payroll-confirmed' : 'payroll-unconfirmed' }}" href="{{ route('admin.payroll.index', ['payment_date' => $selectedPaymentDate, 'company_id' => $selectedCompanyId, 'staff_id' => $row['staff_id']]) }}">
        <p class="staff-name">{{ $row['staff_name'] }}</p>
        <div class="staff-meta">
            <span>{{ $row['staff_id'] }}</span>
            <span>-</span>
            <span>{{ $row['division'] !== '' ? $row['division'] : 'N/A' }}</span>
            <span class="pill {{ $attendanceChecked ? 'confirmed' : 'unconfirmed' }}">
                {{ $attendanceChecked ? '勤怠済' : '勤怠未' }}
            </span>
        </div>
    </a>
@empty
    <div class="empty">No staff found.</div>
@endforelse
</aside>
