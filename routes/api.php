<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\LoanController;
use App\Models\Loan;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::prefix('v1/auth/')
    ->controller(AuthController::class)
    ->group(function () {
        Route::post('register', 'register')->name('user-register');
        Route::post('login', 'login')->name('user-login');
        Route::middleware(['auth:sanctum'])->post('logout', 'logout')->name('user-logout');
    });

Route::middleware(['auth:sanctum'])
    ->prefix('v1/loan/')
    ->controller(LoanController::class)
    ->group(function () {
        Route::get('list', 'index')->name('loan-index')->can('view-loan-list');
        Route::post('create', 'create')->name('loan-create')->can('create-loan');
        Route::get('{uuid}', 'show')->name('loan-show')->can('view-loan', 'uuid');
        Route::post('payment', 'makePayment')->name('loan-payment');
        Route::post('approve', 'approve')->name('loan-approve')->can('approve-loan');
    });
