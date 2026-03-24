<?php

use App\Http\Controllers\Admin\V2\PaidLeaveV2Controller;
use Illuminate\Support\Facades\Route;

Route::get('/paid-leave', [PaidLeaveV2Controller::class, 'index'])->name('admin.paid-leave.index');
Route::post('/paid-leave', [PaidLeaveV2Controller::class, 'store'])->name('admin.paid-leave.store');
Route::patch('/paid-leave/{yukyuNo}', [PaidLeaveV2Controller::class, 'update'])->name('admin.paid-leave.update');
Route::delete('/paid-leave/{yukyuNo}', [PaidLeaveV2Controller::class, 'destroy'])->name('admin.paid-leave.destroy');
