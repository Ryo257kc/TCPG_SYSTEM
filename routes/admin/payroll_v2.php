<?php

use App\Http\Controllers\Admin\V2\PayrollV2Controller;
use Illuminate\Support\Facades\Route;

Route::get('/payroll', [PayrollV2Controller::class, 'index'])->name('admin.payroll.index');
Route::get('/payroll-v2', [PayrollV2Controller::class, 'index'])->name('admin.payroll-v2.index');
Route::get('/payroll/transfer-list', [PayrollV2Controller::class, 'transferList'])->name('admin.payroll.transfer-list');
Route::get('/payroll/wage-ledger', [PayrollV2Controller::class, 'wageLedger'])->name('admin.payroll.wage-ledger');
Route::post('/payroll/update', [PayrollV2Controller::class, 'update'])->name('admin.payroll.update');
Route::post('/payroll/attendance-reflect', [PayrollV2Controller::class, 'reflectAttendance'])->name('admin.payroll.attendance-reflect');
Route::post('/payroll/recalculate', [PayrollV2Controller::class, 'recalculate'])->name('admin.payroll.recalculate');
Route::post('/payroll/calc-koyou', [PayrollV2Controller::class, 'calcKoyou'])->name('admin.payroll.calc-koyou');
Route::post('/payroll/calc-overtime-deduction', [PayrollV2Controller::class, 'calcOvertimeDeduction'])->name('admin.payroll.calc-overtime-deduction');
Route::post('/payroll/calc-income-tax', [PayrollV2Controller::class, 'calcIncomeTax'])->name('admin.payroll.calc-income-tax');
Route::post('/payroll/confirm', [PayrollV2Controller::class, 'confirm'])->name('admin.payroll.confirm');
Route::get('/payroll/create-candidates', [PayrollV2Controller::class, 'createCandidates'])->name('admin.payroll.create-candidates');
Route::post('/payroll/create', [PayrollV2Controller::class, 'create'])->name('admin.payroll.create');
Route::post('/payroll/delete', [PayrollV2Controller::class, 'delete'])->name('admin.payroll.delete');
