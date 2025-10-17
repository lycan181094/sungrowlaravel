<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageProxyController;
use App\Http\Controllers\StorageController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Ruta para servir imágenes desde servidor remoto (pública)
// Mantiene la URL local pero sirve la imagen remota
Route::get('/images/{slug}', [ImageProxyController::class, 'show'])->name('images.show');

// Ruta alternativa para servir archivos desde storage (sin enlace simbólico)
Route::get('/storage/{path}', [StorageController::class, 'serve'])->where('path', '.*')->name('storage.serve');
