<?php


use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Coach\CoachScheduleController;
use App\Http\Controllers\Coach\CoachSelectionController;
use App\Http\Controllers\Auth\ForgotPasswordController;

use App\Http\Controllers\User\UserAuthController;
use App\Http\Controllers\Payment\SubscriptionController;
use App\Http\Controllers\Product\ProductController;

use App\Http\Controllers\Admin\ManagementCoachController;
use App\Http\Controllers\Admin\SalaryController;
use Illuminate\Http\Request;
use Spatie\Permission\Traits\HasRoles;  //for role and permission

use App\Http\Controllers\Admin\AdminReceptionistController;

use Illuminate\Support\Facades\Route;


    // مسارات عامة لا تحتاج توكن
    Route::post('/dashboard/login', [AdminAuthController::class, 'login']); 
    // 1. شاشة إدخال الإيميل (Forgot Password)
    Route::post('dashboard/password/email', [ForgotPasswordController::class, 'sendResetOtp']);
    Route::post('dashboard/password/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
    Route::post('dashboard/password/reset', [ForgotPasswordController::class, 'resetPassword']);



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
            Route::get('/staff/schedules-report', [CoachScheduleController::class, 'getAllStaffWithSchedules']);
        });

        Route::middleware(['auth:sanctum'])->prefix('admin/receptionists')->group(function () {
            Route::get('/', [AdminReceptionistController::class, 'index']);          // عرض كل موظفي الاستقبال (مع بحث و Paginate)
            Route::post('/', [AdminReceptionistController::class, 'store']);         // إضافة موظف استقبال جديد
            Route::get('/{id}', [AdminReceptionistController::class, 'show']);       // عرض تفاصيل موظف استقبال محدد
            Route::put('/{id}', [AdminReceptionistController::class, 'update']);     // تعديل بيانات موظف استقبال
            Route::delete('/{id}', [AdminReceptionistController::class, 'destroy']); // حذف موظف استقبال
            Route::post('/{id}/status', [AdminReceptionistController::class, 'toggleStatus']); // تفعيل أو حظر حساب موظف الاستقبال
            Route::get('/{id}/activity-logs', [AdminReceptionistController::class, 'receptionistActivity']); // عرض سجل نشاطات موظف الاستقبال
        });
         

        //عرض طلبات الكوتش المعلقة
        Route::get('coaches/pending', [ManagementCoachController::class, 'getPendingCoaches']);
        //قبول أو رفض طلبات الكوتش
        Route::post('coaches/{id}/update-status', [ManagementCoachController::class, 'updateCoachStatus']);
        // عرض المدربين المرفوضين
        Route::get('coaches/rejected', [ManagementCoachController::class, 'getRejectedCoaches']);
        // إعادة تفعيل حساب المدرب المرفوض
        Route::post('coaches/{id}/reactivate', [ManagementCoachController::class, 'reactivateCoach']);


        Route::get('coachs/all', [CoachSelectionController::class, 'index']);
        // عرض تفاصيل كوتش محدد
        Route::get('coaches/{id}', [CoachSelectionController::class, 'show']);
        // عرض المتدربين التابعين لكوتش معين (بإرسال الـ coach_id في الـ URL)
        Route::get('/coach/{id}/trainees', [ManagementCoachController::class, 'getTraineesByCoach']);
    

        // 1. إضافة أو تعديل راتب الموظف/الكوتش (مع احتساب المكافآت والخصومات)
        Route::post('/coach/salaries', [SalaryController::class, 'storeOrUpdateSalary']);
        Route::get('/coach/{id}/salaries', [SalaryController::class, 'getEmployeeSalaries']);
        // تعيين راتب جماعي للكوتش أو الاستقبال بناءً على خطة العمل والدور والشهر
        Route::post('/salaries/assign', [SalaryController::class, 'assignSalaryByWorkSchedule']);
        Route::post('/salaries/{salaryId}/pay', [SalaryController::class, 'paySalary']);
        // مسار عرض رواتب الموظفين مع الفلترة
        Route::get('/salaries/employees', [SalaryController::class, 'getAllEmployeesSalaries']);


        //add Products 
        Route::post('products/add', [ProductController::class, 'storeProduct']);
        Route::post('products/update/{id}', [ProductController::class, 'updateProduct']);
        Route::delete('products/delete/{id}', [ProductController::class, 'deleteProduct']);

    });




    
