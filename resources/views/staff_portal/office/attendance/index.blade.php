@include('staff_portal.admin.shift.change', [
    'displayName' => $displayName,
    'hidePayrollLinks' => $hidePayrollLinks ?? false,
    'statusMessage' => $statusMessage ?? '',
    'selectedMonth' => $selectedMonth,
    'selectedStaffId' => $selectedStaffId,
    'selectedStaffName' => $selectedStaffName,
    'staffOptions' => $staffOptions ?? [],
    'rows' => $rows ?? [],
    'rowCount' => $rowCount ?? 0,
    'showPunchColumns' => $showPunchColumns ?? true,
    'isSelfOnly' => $isSelfOnly ?? true,
    'updateRouteName' => $updateRouteName ?? 'office.attendance.update',
])
