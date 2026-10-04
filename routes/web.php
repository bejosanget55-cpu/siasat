<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\RecapController;

Route::get('/', function () {
    return redirect('/presensi');
});

// 1. Modul Registrasi Wajah & Antrean Impor Siswa
Route::get('/registrasi', [StudentController::class, 'index']);
Route::get('/registrasi/template', [StudentController::class, 'downloadTemplate']);
Route::post('/registrasi/import', [StudentController::class, 'importCsv']);
Route::get('/registrasi/students/{classId}', [StudentController::class, 'getStudentsByClass']);
Route::post('/registrasi/simpan', [StudentController::class, 'store']);
Route::post('/registrasi/update', [StudentController::class, 'updateStudent']);
Route::post('/registrasi/delete/{id}', [StudentController::class, 'deleteStudent']);
Route::post('/registrasi/reset-face/{id}', [StudentController::class, 'resetFace']);

// 2. Modul Pemindai Presensi Wajah
Route::get('/presensi', [AttendanceController::class, 'index']);
Route::get('/presensi/descriptors/{classId?}', [AttendanceController::class, 'getDescriptors']);
Route::post('/presensi/scan', [AttendanceController::class, 'store']);

// 3. Modul Rekapitulasi, WhatsApp & Ekspor Excel
Route::get('/rekap', [RecapController::class, 'index']);
Route::post('/rekap/update', [RecapController::class, 'updateStatus']);
Route::get('/rekap/export', [RecapController::class, 'exportExcel']);