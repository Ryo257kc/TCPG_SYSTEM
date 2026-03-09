<?php

use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Master\AllowanceController;
use App\Http\Controllers\Admin\Master\CompanyController;
use App\Http\Controllers\Admin\Master\StaffController;
use App\Http\Controllers\Admin\Master\StoreController;
use App\Http\Controllers\Admin\Attendance\AttendanceManageController;
use App\Http\Controllers\Admin\PayrollController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::prefix('admin')->group(function (): void {
    Route::get('/login', [LoginController::class, 'show'])->name('admin.login');
    Route::post('/login', [LoginController::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [LoginController::class, 'logout'])->name('admin.logout');

    Route::middleware('admin.auth')->group(function (): void {
        Route::get('/payroll', [PayrollController::class, 'index'])->name('admin.payroll.index');
        Route::get('/payroll/csv', [PayrollController::class, 'exportCsv'])->name('admin.payroll.csv');
        Route::post('/payroll/toggle-lock', [PayrollController::class, 'toggleLock'])->name('admin.payroll.toggle-lock');
        Route::post('/payroll/lock', [PayrollController::class, 'lock'])->name('admin.payroll.lock');
        Route::post('/payroll/unlock', [PayrollController::class, 'unlock'])->name('admin.payroll.unlock');
        Route::post('/payroll/save', [PayrollController::class, 'save'])->name('admin.payroll.save');
        Route::post('/payroll/recalc-koyou', [PayrollController::class, 'recalcKoyou'])->name('admin.payroll.recalc-koyou');
        Route::post('/payroll/recalc-income-tax', [PayrollController::class, 'recalcIncomeTax'])->name('admin.payroll.recalc-income-tax');
        Route::post('/payroll/sync-attendance', [PayrollController::class, 'syncAttendance'])->name('admin.payroll.sync-attendance');
        Route::post('/payroll/sync-attendance-bulk', [PayrollController::class, 'syncAttendanceBulk'])->name('admin.payroll.sync-attendance-bulk');
        Route::get('/attendance', [AttendanceManageController::class, 'index'])->name('admin.attendance.index');
        Route::post('/attendance/check', [AttendanceManageController::class, 'check'])->name('admin.attendance.check');
        Route::post('/attendance/day-update', [AttendanceManageController::class, 'updateDay'])->name('admin.attendance.day-update');
        Route::post('/attendance/shift-bulk-create', [AttendanceManageController::class, 'shiftBulkCreate'])->name('admin.attendance.shift-bulk-create');
        Route::post('/attendance/shift-bulk-delete', [AttendanceManageController::class, 'shiftBulkDelete'])->name('admin.attendance.shift-bulk-delete');
        Route::get('/master/company', [CompanyController::class, 'index'])->name('admin.master.company');
        Route::get('/master/staff', [StaffController::class, 'index'])->name('admin.master.staff');
        Route::get('/master/store', [StoreController::class, 'index'])->name('admin.master.store');
        Route::get('/master/allowance', [AllowanceController::class, 'index'])->name('admin.master.allowance');
        Route::post('/master/allowance', [AllowanceController::class, 'update'])->name('admin.master.allowance.update');
        Route::post('/master/allowance/ensure-slots', [AllowanceController::class, 'ensureSlots'])->name('admin.master.allowance.ensure-slots');
        Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    });
});
