<?php


use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminSessionController;
use App\Http\Controllers\Coach\CoachScheduleController;
use App\Http\Controllers\Coach\CoachSelectionController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\ClubPage\ClubActivityController;
use App\Http\Controllers\ClubPage\EventController;
use App\Http\Controllers\User\UserAuthController;
use App\Http\Controllers\Payment\SubscriptionController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\ClubPage\GymHallController;
use App\Http\Controllers\Admin\ManagementCoachController;
use App\Http\Controllers\Admin\EmailController;
use App\Http\Controllers\Admin\SalaryController;
use Illuminate\Http\Request;
use Spatie\Permission\Traits\HasRoles;  

use App\Http\Controllers\Admin\AdminReceptionistController;

use Illuminate\Support\Facades\Route;


    // مسارات عامة لا تحتاج توكن
    Route::post('/dashboard/login', [AdminAuthController::class, 'login']); 
    // 1. شاشة إدخال الإيميل (Forgot Password)
    Route::post('dashboard/password/email', [ForgotPasswordController::class, 'sendResetOtp']);
    Route::post('dashboard/password/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
    Route::post('dashboard/password/reset', [ForgotPasswordController::class, 'resetPassword']);


    Route::middleware('auth:sanctum,admin-api')->group( function () {
        Route::post('logout', [AdminAuthController::class,'logout']) ;
    });

    //...................................................

    Route::prefix('dashboard')->middleware(['auth:sanctum', 'role:admin|reception'])->group(function () {

        Route::get('/profile', [ProfileController::class, 'show']);
        Route::post('/profile/update', [ProfileController::class, 'update']);

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

        //gym-halls
        Route::get('gym-halls/all', [GymHallController::class, 'index']);
        Route::get('gym-halls/{id}/details', [GymHallController::class, 'show']);

        // الحضور للكوتش
        Route::post('/attendance/coach', [ManagementCoachController::class, 'coachCheckIn']);
        Route::get('/attendance/employee/{userId}', [ManagementCoachController::class, 'employeeAttendanceRecords']);
        Route::get('/coaches/attendance', [ManagementCoachController::class, 'allCoachesAttendance']);
        Route::get('coachs/all', [ManagementCoachController::class, 'index']);
        Route::get('coaches/{id}', [ManagementCoachController::class, 'show']);

        //club page
        Route::get('/club/details', [ClubActivityController::class, 'getClubDetails']);
        Route::get('/services', [ClubActivityController::class, 'servicesIndex']);
        Route::get('/events', [EventController::class, 'eventsIndex']);
        Route::get('/events/{id}/details', [EventController::class, 'showEvent']);

        //session
        Route::get('admin/sessions/statistics', [AdminSessionController::class, 'statistics']); 
        Route::get('admin/sessions/', [AdminSessionController::class, 'indexSessions']);           
        Route::get('admin/sessions/{id}', [AdminSessionController::class, 'showSession']); 

        //dashboard 
        Route::get('reception/dashboard', [AdminReceptionistController::class, 'dashboard']);
        Route::get('admin/dashboard', [AdminAuthController::class, 'dashboard']);

    });

    //admin only
    Route::prefix('dashboard')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
        
        Route::prefix('schedules')->group( function () {
        // إدارة جداول العمل: عرض الخطط
            Route::get('/all', [CoachScheduleController::class, 'index']);
            Route::post('/add', [CoachScheduleController::class, 'store']);
            Route::post('/update/{id}', [CoachScheduleController::class, 'update']);
            Route::delete('/{schedule_id}', [CoachScheduleController::class, 'destroy']);

            //....................................
            // تعيين جداول عمل لكوتش
            Route::post('/Staff/{user_id}', [CoachScheduleController::class, 'assignSchedule']);
            Route::get('/Staff/{user_id}', [CoachScheduleController::class, 'showStaffSchedules']);
            Route::post('/Staff/update/{user_id}', [CoachScheduleController::class, 'updateStaffSchedules']);
            Route::get('/staff/schedules-report', [CoachScheduleController::class, 'getAllStaffWithSchedules']);
        });

        // ادارة  الصالات
        Route::prefix('gym-halls')->group(function () {
            Route::post('/add', [GymHallController::class, 'store']);
            Route::post('/{id}/update', [GymHallController::class, 'update']);
            Route::delete('/{id}/delete', [GymHallController::class, 'destroy']);
        });
        // ادارة  الموظفين
        Route::middleware(['auth:sanctum'])->prefix('admin/receptionists')->group(function () {
            Route::get('/statistics', [AdminReceptionistController::class, 'staticsData']);
            Route::get('/', [AdminReceptionistController::class, 'index']);          
            Route::post('/', [AdminReceptionistController::class, 'store']);         
            Route::get('/{id}', [AdminReceptionistController::class, 'show']);       
            Route::put('/{id}', [AdminReceptionistController::class, 'update']);     
            Route::delete('/{id}', [AdminReceptionistController::class, 'destroy']); 
            Route::post('/{id}/status', [AdminReceptionistController::class, 'toggleStatus']); 
            Route::get('/{id}/activity-logs', [AdminReceptionistController::class, 'activity']); 
            Route::get('/{id}/subscriptions', [AdminReceptionistController::class, 'receptionistSubscriptions']);
            Route::get('/{id}/summary', [AdminReceptionistController::class, 'receptionistSummary']);
        });

        Route::middleware(['auth:sanctum'])->prefix('admin/sessions')->group(function () {
            Route::post('/', [AdminSessionController::class, 'storeSession']);          
            Route::post('/{id}/update', [AdminSessionController::class, 'updateSession']);      
            Route::post('/{id}/cancel', [AdminSessionController::class, 'cancelSession']); 
        });
         

        // معالجة طلبات الكوتش للتوظيف بالنادي
        Route::get('coaches/pending', [ManagementCoachController::class, 'getPendingCoaches']);
        Route::post('coaches/{id}/update-status', [ManagementCoachController::class, 'updateCoachStatus']); 
        Route::get('coaches/rejected', [ManagementCoachController::class, 'getRejectedCoaches']);        
        Route::post('coaches/{id}/reactivate', [ManagementCoachController::class, 'reactivateCoach']);

        Route::get('coaches/sent-emails', [EmailController::class, 'sentEmails']);
        Route::get('coaches/sent-emails/{id}', [EmailController::class, 'showSentEmail']);
        Route::post('/coaches/{id}/send-email', [EmailController::class, 'sendEmailToCoach']);
        Route::post('/admin/send-email/user', [EmailController::class, 'sendEmailToUser']);

        Route::get('/coach/{id}/trainees', [ManagementCoachController::class, 'getTraineesByCoach']);  
    
        // تعيين راتب جماعي للكوتش أو الاستقبال بناءً على خطة العمل والدور والشهر
        Route::post('/coach/salaries', [SalaryController::class, 'storeOrUpdateSalary']);
        Route::get('/coach/{id}/salaries', [SalaryController::class, 'getEmployeeSalaries']);
        Route::post('/salaries/assign', [SalaryController::class, 'assignSalaryByWorkSchedule']);
        Route::post('/salaries/{salaryId}/pay', [SalaryController::class, 'paySalary']);
        Route::get('/salaries/employees', [SalaryController::class, 'getAllEmployeesSalaries']);


        //add Products 
        Route::post('products/add', [ProductController::class, 'storeProduct']);
        Route::post('products/update/{id}', [ProductController::class, 'updateProduct']);
        Route::delete('products/delete/{id}', [ProductController::class, 'deleteProduct']);

        //club page
        Route::post('/admin/club/details', [ClubActivityController::class, 'updateClubDetails']);
        // خدمات
        Route::post('/services', [ClubActivityController::class, 'servicesStore']);
        Route::post('/services/{id}/update', [ClubActivityController::class, 'servicesUpdate']); 
        Route::delete('/services/{id}/delete', [ClubActivityController::class, 'servicesDestroy']);
        // فعاليات
        Route::post('/events', [EventController::class, 'eventsStore']);
        Route::post('/events/{id}/update', [EventController::class, 'eventsUpdate']);
        Route::delete('/events/{id}/delete', [EventController::class, 'eventsDestroy']);






    });




    
