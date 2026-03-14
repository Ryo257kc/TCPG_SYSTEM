<?php

use App\Http\Controllers\Admin\V2\DashboardV2Controller;
use App\Http\Controllers\Admin\V2\LoginV2Controller;
use App\Http\Controllers\Admin\V2\Master\AllowanceV2Controller;
use App\Http\Controllers\Admin\V2\Master\CalendarV2Controller;
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

        require __DIR__ . '/admin/attendance_v2.php';
        require __DIR__ . '/admin/payroll_v2.php';

        Route::get('/master/company', [CompanyV2Controller::class, 'index'])->name('admin.master.company');
        Route::post('/master/company/update', [CompanyV2Controller::class, 'update'])->name('admin.master.company.update');
        Route::post('/master/company/syaho', [CompanyV2Controller::class, 'storeShaho'])->name('admin.master.company.syaho.store');
        Route::post('/master/company/syaho/update', [CompanyV2Controller::class, 'updateShaho'])->name('admin.master.company.syaho.update');
        Route::post('/master/company/syaho/delete', [CompanyV2Controller::class, 'deleteShaho'])->name('admin.master.company.syaho.delete');
        Route::post('/master/company/rouho', [CompanyV2Controller::class, 'storeRouho'])->name('admin.master.company.rouho.store');
        Route::post('/master/company/rouho/update', [CompanyV2Controller::class, 'updateRouho'])->name('admin.master.company.rouho.update');
        Route::post('/master/company/rouho/delete', [CompanyV2Controller::class, 'deleteRouho'])->name('admin.master.company.rouho.delete');
        Route::get('/master/staff', [StaffV2Controller::class, 'index'])->name('admin.master.staff');
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
