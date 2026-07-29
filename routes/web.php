<?php

use Illuminate\Support\Facades\Route;

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
    return "welcome";
});


Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    //Route::post('/approve-coach/{id}', [AdminController::class, 'approve']);
});
