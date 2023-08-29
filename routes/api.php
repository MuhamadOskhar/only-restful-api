<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// START DATA JURUSAN
Route::get('/jurusan', [JurusanController::class, 'get']);
Route::get('/jurusan/untuk-tabel', [JurusanController::class, 'getUntukTabel']);
Route::get('/jurusan/untuk-input-option', [JurusanController::class, 'getUntukInputOption']);
Route::get('/jurusan/find', [JurusanController::class, 'find']);
Route::post('/jurusan', [JurusanController::class, 'create']);
Route::put('/jurusan', [JurusanController::class, 'update']);
Route::put('/jurusan/pulihkan', [JurusanController::class, 'restore']);
Route::delete('/jurusan', [JurusanController::class, 'delete']);
// END DATA JURUSAN