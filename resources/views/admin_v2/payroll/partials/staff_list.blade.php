<aside class="staff-list" style="width:260px;min-width:260px;">
@php
    $currentStaffId = $selectedRow['staff_id'] ?? null;
@endphp

@forelse ($rows as $row)
    @php
        $isActive = $currentStaffId !== null && $currentStaffId === ($row['staff_id'] ?? null);
    @endphp
    <a class="staff-item {{ $isActive ? 'active' : '' }}" href="{{ route('admin.payroll.index', ['payment_date' => $selectedPaymentDate, 'company_id' => $selectedCompanyId, 'staff_id' => $row['staff_id']]) }}">
        <p class="staff-name">{{ $row['staff_name'] }}</p>
        <div class="staff-meta">
            <span>{{ $row['staff_id'] }}</span>
            <span>-</span>
            <span>{{ $row['division'] !== '' ? $row['division'] : 'N/A' }}</span>
            <span class="pill">&#21220;&#24608;&#26410;</span>
        </div>
    </a>
@empty
    <div class="empty">No staff found.</div>
@endforelse
</aside>