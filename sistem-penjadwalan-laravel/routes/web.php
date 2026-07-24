<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DosenController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\KajurController;
use App\Http\Controllers\KaprodiController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\MataKuliahController;
use App\Http\Controllers\ProdiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\RuangController;
use App\Http\Controllers\UploadExcelController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'index'])->name('welcome');
Route::get('/jadwal/export-excel', [JadwalController::class, 'exportExcel'])->name('jadwal.export.excel');
Route::get('/jadwal/export-pdf', [JadwalController::class, 'exportPdf'])->name('jadwal.export.pdf');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    Route::get('/jadwal/matrix', [JadwalController::class, 'matrixjadwal'])->name('jadwal.matrix');
    
    // 2. GRUP ROUTE KHUSUS SEKRETARIS JURUSAN (SEKJUR
    Route::group([
        'prefix' => 'sekjur',
        'middleware' => function ($request, $next) {
        if (auth()->user()->role !== 'sekretaris') {
            abort(403, 'Akses ditolak! Halaman ini hanya untuk Sekretaris Jurusan.');
            }
            return $next($request);
            }], function () {
            
        Route::get('/dashboard', [DashboardController::class, 'dashboardSekjur'])->name('sekjur.dashboard');

        Route::get('/upload-data', [UploadExcelController::class, 'uploadForm'])->name('upload.form');
        Route::post('/upload-data/process', [UploadExcelController::class, 'process'])->name('upload.proses');
        Route::get('/cleansing', [UploadExcelController::class, 'cleansingView'])->name('cleansing.view');
        Route::post('/cleansing/proses', [UploadExcelController::class, 'process'])->name('cleansing.process');
        Route::post('/cleansing/store', [UploadExcelController::class, 'storeDatabase'])->name('cleansing.store');
        Route::post('/cleansing/reset', [UploadExcelController::class, 'resetData'])->name('cleansing.reset');
        
        Route::get('/jadwal/generate', [JadwalController::class, 'index'])->name('jadwal.index');
        Route::post('/jadwal/generate/process', [JadwalController::class, 'generate'])->name('jadwal.generate');
        Route::delete('/jadwal/delete', [JadwalController::class, 'deleteByTahunAjar'])->name('jadwal.delete');
        Route::post('/jadwal/proses-ubah', [JadwalController::class, 'prosesUbahJadwal'])->name('jadwal.proses-ubah');
        Route::get('/request-hari-tidak-mengajar', [KaprodiController::class, 'monitorUnavailableDays'])->name('sekjur.unavailable-days');

        Route::resource('users', UserController::class);

        Route::prefix('master-data')->group(function () {
            Route::resource('kelas', KelasController::class);
            Route::resource('dosen', DosenController::class);
            Route::resource('prodi', ProdiController::class);
            Route::resource('ruang', RuangController::class);
            Route::resource('matkul', MataKuliahController::class);
        });
    });

    // 3. GRUP ROUTE KHUSUS KEPALA JURUSAN (KAJUR)
    Route::group(['middleware' => function ($request, $next) {
        if (auth()->user()->role !== 'kajur') {
            abort(403, 'Akses ditolak! Halaman ini hanya untuk Kepala Jurusan.');
        }
        return $next($request);
    }], function () {

        Route::get('/kajur/dashboard', [KajurController::class, 'dashboard'])->name('kajur.dashboard');
        Route::get('/kajur/jadwal', [KajurController::class, 'lihatJadwal'])->name('kajur.jadwal');
    });

    Route::group([
        'prefix' => 'kaprodi',
        'middleware' => function ($request, $next) {
            if (auth()->user()->role !== 'kaprodi') {
                abort(403, 'Akses ditolak! Halaman ini hanya untuk Ketua Program Studi.');
            }
            return $next($request);
        }
    ], function () {

        Route::get('/dashboard', [KaprodiController::class, 'dashboard'])->name('kaprodi.dashboard');
        Route::get('/jadwal', [KaprodiController::class, 'lihatJadwal'])->name('kaprodi.jadwal');
        Route::get('/hari-tidak-mengajar', [KaprodiController::class, 'unavailableDays'])->name('kaprodi.unavailable-days');
        Route::post('/hari-tidak-mengajar', [KaprodiController::class, 'storeUnavailableDays'])->name('kaprodi.unavailable-days.store');
    });
});

require __DIR__ . '/auth.php';
