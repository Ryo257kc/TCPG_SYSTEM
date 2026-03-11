<?php

use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Master\AllowanceController;
use App\Http\Controllers\Admin\Master\CompanyController;
use App\Http\Controllers\Admin\Master\StaffController;
use App\Http\Controllers\Admin\Master\StoreController;
use App\Http\Controllers\Admin\Attendance\AttendanceManageController;
use App\Http\Controllers\Admin\BonusController;
use App\Http\Controllers\Admin\PayrollV2Controller;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::prefix('admin')->group(function (): void {
    Route::get('/login', [LoginController::class, 'show'])->name('admin.login');
    Route::post('/login', [LoginController::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [LoginController::class, 'logout'])->name('admin.logout');

    Route::middleware('admin.auth')->group(function (): void {
        Route::get('/payroll', [PayrollV2Controller::class, 'index'])->name('admin.payroll.index');
        Route::get('/payroll-v2', [PayrollV2Controller::class, 'index'])->name('admin.payroll-v2.index');
        Route::post('/payroll/seed-bulk-create', [PayrollV2Controller::class, 'seedBulkCreate'])->name('admin.payroll.seed-bulk-create');
        Route::post('/payroll/seed-bulk-delete', [PayrollV2Controller::class, 'seedBulkDelete'])->name('admin.payroll.seed-bulk-delete');
        Route::post('/payroll/sync-attendance', [PayrollV2Controller::class, 'syncAttendance'])->name('admin.payroll.sync-attendance');
        Route::post('/payroll/sync-attendance-bulk', [PayrollV2Controller::class, 'syncAttendanceBulk'])->name('admin.payroll.sync-attendance-bulk');
        Route::post('/payroll/recalc-defaults', [PayrollV2Controller::class, 'recalcDefaults'])->name('admin.payroll.recalc-defaults');
        Route::post('/payroll/recalc-koyou', [PayrollV2Controller::class, 'recalcKoyou'])->name('admin.payroll.recalc-koyou');
        Route::post('/payroll/recalc-premium-deduction', [PayrollV2Controller::class, 'recalcPremiumDeduction'])->name('admin.payroll.recalc-premium-deduction');
        Route::post('/payroll/recalc-income-tax', [PayrollV2Controller::class, 'recalcIncomeTax'])->name('admin.payroll.recalc-income-tax');
        Route::post('/payroll/save', [PayrollV2Controller::class, 'save'])->name('admin.payroll.save');
        Route::post('/payroll/lock', [PayrollV2Controller::class, 'lock'])->name('admin.payroll.lock');
        Route::post('/payroll/unlock', [PayrollV2Controller::class, 'unlock'])->name('admin.payroll.unlock');
        Route::post('/payroll-v2/seed-bulk-create', [PayrollV2Controller::class, 'seedBulkCreate'])->name('admin.payroll-v2.seed-bulk-create');
        Route::post('/payroll-v2/seed-bulk-delete', [PayrollV2Controller::class, 'seedBulkDelete'])->name('admin.payroll-v2.seed-bulk-delete');
        Route::post('/payroll-v2/sync-attendance', [PayrollV2Controller::class, 'syncAttendance'])->name('admin.payroll-v2.sync-attendance');
        Route::post('/payroll-v2/sync-attendance-bulk', [PayrollV2Controller::class, 'syncAttendanceBulk'])->name('admin.payroll-v2.sync-attendance-bulk');
        Route::post('/payroll-v2/recalc-defaults', [PayrollV2Controller::class, 'recalcDefaults'])->name('admin.payroll-v2.recalc-defaults');
        Route::post('/payroll-v2/recalc-koyou', [PayrollV2Controller::class, 'recalcKoyou'])->name('admin.payroll-v2.recalc-koyou');
        Route::post('/payroll-v2/recalc-premium-deduction', [PayrollV2Controller::class, 'recalcPremiumDeduction'])->name('admin.payroll-v2.recalc-premium-deduction');
        Route::post('/payroll-v2/recalc-income-tax', [PayrollV2Controller::class, 'recalcIncomeTax'])->name('admin.payroll-v2.recalc-income-tax');
        Route::post('/payroll-v2/save', [PayrollV2Controller::class, 'save'])->name('admin.payroll-v2.save');
        Route::post('/payroll-v2/lock', [PayrollV2Controller::class, 'lock'])->name('admin.payroll-v2.lock');
        Route::post('/payroll-v2/unlock', [PayrollV2Controller::class, 'unlock'])->name('admin.payroll-v2.unlock');
        Route::get('/attendance', [AttendanceManageController::class, 'index'])->name('admin.attendance.index');
        Route::post('/attendance/check', [AttendanceManageController::class, 'check'])->name('admin.attendance.check');
        Route::post('/attendance/day-update', [AttendanceManageController::class, 'updateDay'])->name('admin.attendance.day-update');
        Route::post('/attendance/shift-bulk-create', [AttendanceManageController::class, 'shiftBulkCreate'])->name('admin.attendance.shift-bulk-create');
        Route::post('/attendance/shift-bulk-delete', [AttendanceManageController::class, 'shiftBulkDelete'])->name('admin.attendance.shift-bulk-delete');
        Route::get('/bonus', [BonusController::class, 'index'])->name('admin.bonus.index');
        Route::post('/bonus/seed-bulk-create', [BonusController::class, 'seedBulkCreate'])->name('admin.bonus.seed-bulk-create');
        Route::post('/bonus/seed-bulk-delete', [BonusController::class, 'seedBulkDelete'])->name('admin.bonus.seed-bulk-delete');
        Route::post('/bonus/save', [BonusController::class, 'save'])->name('admin.bonus.save');
        Route::post('/bonus/lock', [BonusController::class, 'lock'])->name('admin.bonus.lock');
        Route::post('/bonus/unlock', [BonusController::class, 'unlock'])->name('admin.bonus.unlock');
        Route::get('/master/company', [CompanyController::class, 'index'])->name('admin.master.company');
        Route::post('/master/company/update', [CompanyController::class, 'update'])->name('admin.master.company.update');
        Route::get('/master/staff', [StaffController::class, 'index'])->name('admin.master.staff');
        Route::get('/master/store', [StoreController::class, 'index'])->name('admin.master.store');
        Route::post('/master/store/update', [StoreController::class, 'update'])->name('admin.master.store.update');
        Route::get('/master/allowance', [AllowanceController::class, 'index'])->name('admin.master.allowance');
        Route::post('/master/allowance', [AllowanceController::class, 'update'])->name('admin.master.allowance.update');
        Route::post('/master/allowance/create', [AllowanceController::class, 'create'])->name('admin.master.allowance.create');
        Route::post('/master/allowance/ensure-slots', [AllowanceController::class, 'ensureSlots'])->name('admin.master.allowance.ensure-slots');
        Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    });
});

