<?php

use App\Http\Controllers\Admin\V2\AttendanceV2Controller;
use App\Http\Controllers\Admin\V2\DashboardV2Controller;
use App\Http\Controllers\Admin\V2\LoginV2Controller;
use App\Http\Controllers\Admin\V2\Master\AllowanceV2Controller;
use App\Http\Controllers\Admin\V2\Master\CompanyV2Controller;
use App\Http\Controllers\Admin\V2\Master\StaffV2Controller;
use App\Http\Controllers\Admin\V2\Master\StoreV2Controller;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::prefix('admin')->group(function (): void {
    Route::get('/login', [LoginV2Controller::class, 'show'])->name('admin.login');
    Route::post('/login', [LoginV2Controller::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [LoginV2Controller::class, 'logout'])->name('admin.logout');

    Route::middleware('admin.auth')->group(function (): void {
        Route::get('/', [DashboardV2Controller::class, 'index'])->name('admin.dashboard');

        Route::get('/attendance', [AttendanceV2Controller::class, 'index'])->name('admin.attendance.index');
        require __DIR__ . '/admin/payroll_v2.php';

        Route::get('/master/company', [CompanyV2Controller::class, 'index'])->name('admin.master.company');
        Route::post('/master/company/update', [CompanyV2Controller::class, 'update'])->name('admin.master.company.update');
        Route::get('/master/staff', [StaffV2Controller::class, 'index'])->name('admin.master.staff');
        Route::get('/master/store', [StoreV2Controller::class, 'index'])->name('admin.master.store');
        Route::post('/master/store/update', [StoreV2Controller::class, 'update'])->name('admin.master.store.update');
        Route::get('/master/allowance', [AllowanceV2Controller::class, 'index'])->name('admin.master.allowance');
        Route::post('/master/allowance', [AllowanceV2Controller::class, 'update'])->name('admin.master.allowance.update');
    });
});


