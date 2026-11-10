<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterJasaController;
use App\Http\Controllers\MasterSparepartController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('pos.login')->group(function () {
    // Catalog reads are shared with the transaction form, so any logged-in user may read them.
    Route::get('master-sparepart', [MasterSparepartController::class, 'index']);
    Route::get('master-jasa', [MasterJasaController::class, 'index']);
    Route::get('profile', [ProfileController::class, 'show']);
    Route::get('profile/logo', [ProfileController::class, 'logo']);
});

Route::middleware('pos.login:Sparepart')->group(function () {
    Route::post('master-sparepart', [MasterSparepartController::class, 'store']);
    Route::put('master-sparepart/{masterSparepart}', [MasterSparepartController::class, 'update']);
    Route::delete('master-sparepart/{masterSparepart}', [MasterSparepartController::class, 'destroy']);
});

Route::middleware('pos.login:Jasa')->group(function () {
    Route::post('master-jasa', [MasterJasaController::class, 'store']);
    Route::put('master-jasa/{masterJasa}', [MasterJasaController::class, 'update']);
    Route::delete('master-jasa/{masterJasa}', [MasterJasaController::class, 'destroy']);
});

Route::middleware('pos.login:Dashboard')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index']);
});

Route::middleware('pos.login:Transaksi')->group(function () {
    Route::get('transaksi', [TransaksiController::class, 'index']);
    Route::post('transaksi', [TransaksiController::class, 'store']);
    Route::get('transaksi/{transaksi}/invoice', [TransaksiController::class, 'invoice']);
    Route::patch('transaksi/{transaksi}/payment-status', [TransaksiController::class, 'updatePaymentStatus']);
    Route::patch('transaksi/{transaksi}/work-status', [TransaksiController::class, 'updateWorkStatus']);
    Route::get('transaksi/{transaksi}', [TransaksiController::class, 'show']);
    Route::put('transaksi/{transaksi}', [TransaksiController::class, 'update']);
});

Route::middleware('pos.login:Pengaturan')->group(function () {
    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::put('users/{user}', [UserController::class, 'update']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);

    Route::get('karyawan', [KaryawanController::class, 'index']);
    Route::post('karyawan', [KaryawanController::class, 'store']);
    Route::put('karyawan/{karyawan}', [KaryawanController::class, 'update']);
    Route::delete('karyawan/{karyawan}', [KaryawanController::class, 'destroy']);

    Route::post('profile', [ProfileController::class, 'store']);
});
