<?php

use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\MasterController;
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
        Route::post('/payroll/sync-attendance', [PayrollController::class, 'syncAttendance'])->name('admin.payroll.sync-attendance');
        Route::post('/payroll/sync-attendance-bulk', [PayrollController::class, 'syncAttendanceBulk'])->name('admin.payroll.sync-attendance-bulk');
        Route::get('/attendance', [AttendanceManageController::class, 'index'])->name('admin.attendance.index');
        Route::post('/attendance/check', [AttendanceManageController::class, 'check'])->name('admin.attendance.check');
        Route::post('/attendance/day-update', [AttendanceManageController::class, 'updateDay'])->name('admin.attendance.day-update');
        Route::post('/attendance/shift-bulk-create', [AttendanceManageController::class, 'shiftBulkCreate'])->name('admin.attendance.shift-bulk-create');
        Route::post('/attendance/shift-bulk-delete', [AttendanceManageController::class, 'shiftBulkDelete'])->name('admin.attendance.shift-bulk-delete');
        Route::get('/master/company', [MasterController::class, 'company'])->name('admin.master.company');
        Route::get('/master/staff', [MasterController::class, 'staff'])->name('admin.master.staff');
        Route::get('/master/store', [MasterController::class, 'store'])->name('admin.master.store');
        Route::get('/master/allowance', [MasterController::class, 'allowance'])->name('admin.master.allowance');
        Route::post('/master/allowance', [MasterController::class, 'allowanceUpdate'])->name('admin.master.allowance.update');
        Route::post('/master/allowance/ensure-slots', [MasterController::class, 'allowanceEnsureSlots'])->name('admin.master.allowance.ensure-slots');
        Route::get('/', [LoginController::class, 'dashboard'])->name('admin.dashboard');
    });
});
