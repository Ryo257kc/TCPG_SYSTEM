<?php

use App\Http\Controllers\Admin\V2\DashboardV2Controller;
use App\Http\Controllers\Admin\V2\LoginV2Controller;
use App\Http\Controllers\Admin\V2\Master\AllowanceV2Controller;
use App\Http\Controllers\Admin\V2\Master\CalendarV2Controller;
use App\Http\Controllers\Admin\V2\Master\CompanyV2Controller;
use App\Http\Controllers\Admin\V2\Master\StaffV2Controller;
use App\Http\Controllers\Admin\V2\Master\StoreV2Controller;
use App\Http\Controllers\StaffPortal\AttendanceController;
use App\Http\Controllers\StaffPortal\AuthController;
use App\Http\Controllers\StaffPortal\DocumentsController;
use App\Http\Controllers\StaffPortal\OfficeController;
use App\Http\Controllers\StaffPortal\PayrollController;
use App\Http\Controllers\StaffPortal\ShiftController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

// admin 側の大枠開始
Route::prefix('admin')->group(function (): void {
    Route::get('/login', [LoginV2Controller::class, 'show'])->name('admin.login');
    Route::post('/login', [LoginV2Controller::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [LoginV2Controller::class, 'logout'])->name('admin.logout');

    // admin 側のログイン後だけ
    Route::middleware('admin.auth')->group(function (): void {
        Route::get('/', [DashboardV2Controller::class, 'index'])->name('admin.dashboard');
        Route::get('/sales', [DashboardV2Controller::class, 'sales'])->name('admin.sales');
        Route::get('/sales/pdf', [DashboardV2Controller::class, 'salesPdf'])->name('admin.sales.pdf');

        require __DIR__ . '/admin/attendance_v2.php';
        require __DIR__ . '/admin/payroll_v2.php';
        require __DIR__ . '/admin/paid_leave_v2.php';
        require __DIR__ . '/admin/report_v2.php';

        Route::get('/master/company', [CompanyV2Controller::class, 'index'])->name('admin.master.company');
        Route::post('/master/company/update', [CompanyV2Controller::class, 'update'])->name('admin.master.company.update');
        Route::post('/master/company/syaho', [CompanyV2Controller::class, 'storeShaho'])->name('admin.master.company.syaho.store');
        Route::post('/master/company/syaho/update', [CompanyV2Controller::class, 'updateShaho'])->name('admin.master.company.syaho.update');
        Route::post('/master/company/syaho/delete', [CompanyV2Controller::class, 'deleteShaho'])->name('admin.master.company.syaho.delete');
        Route::post('/master/company/rouho', [CompanyV2Controller::class, 'storeRouho'])->name('admin.master.company.rouho.store');
        Route::post('/master/company/rouho/update', [CompanyV2Controller::class, 'updateRouho'])->name('admin.master.company.rouho.update');
        Route::post('/master/company/rouho/delete', [CompanyV2Controller::class, 'deleteRouho'])->name('admin.master.company.rouho.delete');
        Route::post('/master/company/mayor', [CompanyV2Controller::class, 'storeMayor'])->name('admin.master.company.mayor.store');
        Route::post('/master/company/mayor/update', [CompanyV2Controller::class, 'updateMayor'])->name('admin.master.company.mayor.update');
        Route::post('/master/company/mayor/delete', [CompanyV2Controller::class, 'deleteMayor'])->name('admin.master.company.mayor.delete');
        Route::get('/master/staff', [StaffV2Controller::class, 'index'])->name('admin.master.staff');
        Route::post('/master/staff/update', [StaffV2Controller::class, 'update'])->name('admin.master.staff.update');
        Route::get('/master/store', [StoreV2Controller::class, 'index'])->name('admin.master.store');
        Route::post('/master/store/update', [StoreV2Controller::class, 'update'])->name('admin.master.store.update');
        Route::get('/master/allowance', [AllowanceV2Controller::class, 'index'])->name('admin.master.allowance');
        Route::post('/master/allowance', [AllowanceV2Controller::class, 'update'])->name('admin.master.allowance.update');
        Route::get('/master/calendar', [CalendarV2Controller::class, 'index'])->name('admin.master.calendar');
        Route::post('/master/calendar/update', [CalendarV2Controller::class, 'update'])->name('admin.master.calendar.update');
        Route::post('/master/calendar/delete', [CalendarV2Controller::class, 'delete'])->name('admin.master.calendar.delete');
        Route::post('/master/calendar/import-public-holidays', [CalendarV2Controller::class, 'importPublicHolidays'])->name('admin.master.calendar.import-public-holidays');
    });
});

// staff_portal 側の大枠開始
Route::prefix('staff')->group(function (): void {
    Route::get('/', [AuthController::class, 'dashboard'])->name('staff_portal.dashboard');
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::get('/login', [AuthController::class, 'index'])->name('login.portal');
    Route::post('/login', [AuthController::class, 'login'])->name('login.portal.submit');
    Route::get('/attendance/monthly', [AttendanceController::class, 'attendanceMonthly'])->name('attendance.monthly');
    Route::post('/attendance/monthly/apply', [AttendanceController::class, 'attendanceMonthlyApply'])->name('attendance.monthly.apply');
    Route::get('/attendance/edit/{timeNo}', [AttendanceController::class, 'attendanceEdit'])->name('attendance.edit');
    Route::post('/attendance/edit/{timeNo}', [AttendanceController::class, 'attendanceUpdate'])->name('attendance.update');
    Route::get('/attendance/management', [AttendanceController::class, 'management'])->name('attendance.management');
    Route::get('/attendance/management/{staffId}', [AttendanceController::class, 'managementDetail'])->name('attendance.management.detail');
    Route::post('/attendance/management/{staffId}/approve', [AttendanceController::class, 'managementApprove'])->name('admin.attendance.manage.approve');
    Route::post('/attendance/management/{staffId}/remand', [AttendanceController::class, 'managementRemand'])->name('admin.attendance.manage.remand');
    Route::get('/attendance/punch-list', [AttendanceController::class, 'punchList'])->name('attendance.punch-list');
    Route::get('/attendance/paid-leave', [AttendanceController::class, 'paidLeave'])->name('attendance.paid_leave');
    Route::get('/shift/change', [ShiftController::class, 'adminShiftChange'])->name('admin.shift.change');
    Route::get('/shift/change/edit/{timeNo}', [ShiftController::class, 'adminShiftEdit'])->name('admin.shift.edit');
    Route::post('/shift/change/edit/{timeNo}', [ShiftController::class, 'adminShiftUpdate'])->name('admin.shift.update');
    Route::get('/office/attendance', [ShiftController::class, 'officeAttendance'])->name('office.attendance');
    Route::post('/office/attendance/{timeNo}', [ShiftController::class, 'officeAttendanceUpdate'])->name('office.attendance.update');
    Route::get('/shift/basic', [ShiftController::class, 'adminBasicShift'])->name('admin.basic-shift');
    Route::get('/shift/basic/edit/{shiftNo}', [ShiftController::class, 'adminBasicShiftEdit'])->name('admin.basic-shift.edit');
    Route::post('/shift/basic/edit/{shiftNo}', [ShiftController::class, 'adminBasicShiftUpdate'])->name('admin.basic-shift.update');
    Route::get('/office/documents', [DocumentsController::class, 'index'])->name('office.documents');
    Route::get('/office/sales-menu', [OfficeController::class, 'salesMenu'])->name('office.sales.menu');
    Route::get('/office/sales', [OfficeController::class, 'sales'])->name('office.sales');
    Route::get('/office/sales/print', [OfficeController::class, 'salesPrint'])->name('office.sales.print');
    Route::get('/office/receipt', [OfficeController::class, 'receipt'])->name('office.receipt');
    Route::get('/office/receipt/entry', [OfficeController::class, 'receiptEntry'])->name('office.receipt.entry');
    Route::post('/office/receipt/entry/save', [OfficeController::class, 'receiptEntrySave'])->name('office.receipt.entry.save');
    Route::post('/office/receipt/entry/delete', [OfficeController::class, 'receiptEntryDelete'])->name('office.receipt.entry.delete');
    Route::get('/office/payment-confirmation', [OfficeController::class, 'paymentConfirmation'])->name('office.payment_confirmation');
    Route::get('/office/home-visit-counter', [OfficeController::class, 'homeVisitCounter'])->name('office.home_visit_counter');
    Route::get('/office/insurers', [OfficeController::class, 'insurers'])->name('office.insurers');
    Route::post('/office/insurers/save', [OfficeController::class, 'insurersSave'])->name('office.insurers.save');
    Route::post('/office/insurers/delete', [OfficeController::class, 'insurersDelete'])->name('office.insurers.delete');
    Route::get('/payroll/payslip', [PayrollController::class, 'payslip'])->name('payslip');
    Route::get('/payroll/bonus', [PayrollController::class, 'bonus'])->name('bonus');
});
