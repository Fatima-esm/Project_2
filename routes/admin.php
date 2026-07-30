<?php


use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\Coach\CoachScheduleController;
use App\Http\Controllers\User\UserAuthController;
use App\Http\Controllers\Payment\SubscriptionController;
use App\Http\Controllers\Product\ProductController;

use Illuminate\Http\Request;
use Spatie\Permission\Traits\HasRoles;  //for role and permission

use Illuminate\Support\Facades\Route;


    // مسارات عامة لا تحتاج توكن
    Route::post('/dashboard/login', [AdminAuthController::class, 'login']); 

    // مسارات محمية حسب الدور
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::get('/admin/dashboard', [AdminController::class, 'index']);
    });

    Route::middleware(['auth:sanctum', 'role:reception'])->group(function () {
        Route::get('/reception/dashboard', [ReceptionController::class, 'index']);
    });

    Route::middleware('auth:sanctum,admin-api')->group( function () {
        Route::post('logout', [AdminAuthController::class,'logout']) ;
    });

    //...................................................

    Route::prefix('dashboard')->middleware(['auth:sanctum', 'role:admin|reception'])->group(function () {

        Route::post('users/register',[UserAuthController::class,'register']);
        Route::get('users', [UserAuthController::class, 'index']);
        Route::post('users/{id}/update', [UserAuthController::class, 'updateTrainee']);
        Route::post('users/subscription/{id}/update', [SubscriptionController::class, 'renewSubscriptionByAdmin']);
        
        //product
        Route::get('products/all', [ProductController::class, 'indexProducts']);
        Route::get('products/show/{id}', [ProductController::class, 'showProduct']);
        Route::post('/products/sales', [ProductController::class, 'sellProducts']);    
        Route::get('/sales/all', [ProductController::class, 'indexSales']);
        Route::get('/sales/{id}', [ProductController::class, 'showSale']);
    
    });

    //admin only
    Route::prefix('dashboard')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::prefix('schedules')->group( function () {
        // إدارة جداول العمل: عرض الخطط
            Route::get('/all', [CoachScheduleController::class, 'index']);
            //اضافة موعد عمل جديد
            Route::post('/add', [CoachScheduleController::class, 'store']);
            //حذف خطة عمل
            Route::delete('/{schedule_id}', [CoachScheduleController::class, 'destroy']);

            //....................................
            
            // تعيين جداول عمل لكوتش
            Route::post('/Staff/{user_id}', [CoachScheduleController::class, 'assignSchedule']);
            Route::get('/Staff/{user_id}', [CoachScheduleController::class, 'showStaffSchedules']);
            Route::post('/Staff/update/{user_id}', [CoachScheduleController::class, 'updateStaffSchedules']);
        });  

        //
        Route::get('coachs/all', [CoachScheduleController::class, 'getCoachesList']);
        // عرض تفاصيل كوتش محدد
        Route::get('coaches/{id}', [CoachScheduleController::class, 'coachDetails']);

    
        //add Products 
        Route::post('products/add', [ProductController::class, 'storeProduct']);
        Route::post('products/update/{id}', [ProductController::class, 'updateProduct']);
        Route::delete('products/delete/{id}', [ProductController::class, 'deleteProduct']);

    });




    
