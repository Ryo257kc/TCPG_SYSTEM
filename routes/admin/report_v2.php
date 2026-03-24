<?php

use App\Http\Controllers\Admin\V2\ReportV2Controller;
use Illuminate\Support\Facades\Route;

Route::get('/reports', [ReportV2Controller::class, 'index'])->name('admin.report.index');
Route::get('/reports/santei', [ReportV2Controller::class, 'santeiIndex'])->name('admin.report.santei.index');
Route::get('/reports/santei/csv', [ReportV2Controller::class, 'santeiCsv'])->name('admin.report.santei.csv');
Route::get('/reports/bonus-payment', [ReportV2Controller::class, 'bonusPaymentIndex'])->name('admin.report.bonus-payment.index');
Route::get('/reports/bonus-payment/csv', [ReportV2Controller::class, 'bonusPaymentCsv'])->name('admin.report.bonus-payment.csv');
Route::get('/reports/labor-insurance', [ReportV2Controller::class, 'laborInsuranceIndex'])->name('admin.report.labor-insurance.index');
