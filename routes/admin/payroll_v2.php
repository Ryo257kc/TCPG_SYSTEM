<?php

use App\Http\Controllers\Admin\V2\PayrollV2Controller;
use Illuminate\Support\Facades\Route;

Route::get('/payroll', [PayrollV2Controller::class, 'index'])->name('admin.payroll.index');
Route::get('/payroll-v2', [PayrollV2Controller::class, 'index'])->name('admin.payroll-v2.index');
Route::post('/payroll/update', [PayrollV2Controller::class, 'update'])->name('admin.payroll.update');
Route::post('/payroll/recalculate', [PayrollV2Controller::class, 'recalculate'])->name('admin.payroll.recalculate');
Route::post('/payroll/calc-koyou', [PayrollV2Controller::class, 'calcKoyou'])->name('admin.payroll.calc-koyou');
Route::post('/payroll/calc-overtime-deduction', [PayrollV2Controller::class, 'calcOvertimeDeduction'])->name('admin.payroll.calc-overtime-deduction');
Route::post('/payroll/calc-income-tax', [PayrollV2Controller::class, 'calcIncomeTax'])->name('admin.payroll.calc-income-tax');
Route::post('/payroll/confirm', [PayrollV2Controller::class, 'confirm'])->name('admin.payroll.confirm');
