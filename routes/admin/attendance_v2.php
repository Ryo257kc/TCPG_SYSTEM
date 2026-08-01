<?php

use App\Http\Controllers\Admin\V2\AttendanceV2Controller;
use Illuminate\Support\Facades\Route;

Route::get('/attendance', [AttendanceV2Controller::class, 'index'])->name('admin.attendance.index');
Route::get('/attendance/shift-candidates', [AttendanceV2Controller::class, 'shiftCandidates'])->name('admin.attendance.shift-candidates');
Route::post('/attendance/create-shifts', [AttendanceV2Controller::class, 'createShifts'])->name('admin.attendance.create-shifts');
Route::post('/attendance/delete-shifts', [AttendanceV2Controller::class, 'deleteShifts'])->name('admin.attendance.delete-shifts');
Route::post('/attendance/update-daily', [AttendanceV2Controller::class, 'updateDaily'])->name('admin.attendance.update-daily');
Route::post('/attendance/confirm', [AttendanceV2Controller::class, 'confirmAttendance'])->name('admin.attendance.confirm');
Route::post('/attendance/cancel-confirm', [AttendanceV2Controller::class, 'cancelConfirmAttendance'])->name('admin.attendance.cancel-confirm');
Route::post('/attendance/reflect', [AttendanceV2Controller::class, 'reflectAttendance'])->name('admin.attendance.reflect');
