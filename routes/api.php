<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\ExercisesController;
use App\Http\Controllers\ProfileController;

use App\Http\Controllers\Payment\PlanController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Payment\TransactionController;
use App\Http\Controllers\Payment\SubscriptionController;

use App\Http\Controllers\Product\ProductController;

use App\Http\Controllers\Coach\CoachSelectionController;
use App\Http\Controllers\Coach\TraineeController;
use App\Http\Controllers\Coach\WorkoutPlanController;

use App\Http\Controllers\Auth\ForgotPasswordController;

use App\Http\Controllers\User\UserAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MeasurementController;


    Route::post('register',[UserAuthController::class,'register']);
    Route::post('login',[UserAuthController::class,'login']);
    Route::post('logout', [UserAuthController::class,'logout'])->middleware('auth:sanctum,api');



    // 1. شاشة إدخال الإيميل (Forgot Password)
    Route::post('/password/email', [ForgotPasswordController::class, 'sendResetOtp']);
    // 2. شاشة إدخال الرمز والتحقق منه فقط (Verify OTP)
    Route::post('/password/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
    // 3. شاشة كلمة المرور الجديدة وتأكيدها (Reset Password)
    Route::post('/password/reset', [ForgotPasswordController::class, 'resetPassword']);

    //لاجل ادخال الهدف ععند التسجيل لا تحتاج توكين
    Route::get('/goals', [ProfileController::class, 'allGoals']);
    Route::post('goals/select', [ProfileController::class, 'selectGoal']);
    Route::post('/user/measurements/update', [ProfileController::class, 'addMeasurement']);



    require __DIR__ .'/admin.php';

    //----------------------------------------------------------------------------------------
    //'subscription' حماية عند عدم الدفع
    // تم*
    Route::post('/upload-cv', [UserAuthController::class, 'uploadCv']); // coach only

    Route::middleware(['auth:sanctum', 'role:trainee|coach'])->group(function () {
    // باقي خدمات النادي

        Route::middleware('check.subscription')->group(function () {
            Route::get('exercises', [ExercisesController::class, 'index']);

            //عرض الكوتش
            Route::get('/user/coaches', [CoachSelectionController::class, 'index']);
            // عرض تفاصيل كوتش محدد
            Route::get('/user/coaches/{id}', [CoachSelectionController::class, 'show']);
            // اختيار الكوتش (يشترط وجود اشتراك نشط)
            Route::post('/user/select-coach', [CoachSelectionController::class, 'selectCoach']);
            // اختيار الكوتش (يشترط وجود اشتراك نشط)
            Route::post('/user/change-coach', [CoachSelectionController::class, 'requestChangeCoach']);


           // 4. التقدم والقياسات
            Route::get("/user/measurements", [ProfileController::class, 'getMeasurements']);
            Route::get('/user/measurements/history', [ProfileController::class, 'getHistory']);


        });

    // 1. الملف الشخصي + QR Code   تم*
    Route::get('/user/profile', [ProfileController::class, 'show']);
    Route::post('/user/profile/update', [ProfileController::class, 'update']);
    Route::get('/user/qr-code', [ProfileController::class, 'generateQR']);

    //عرض المتدربين عند الكوتش المشرف
    Route::get('/coach/my-trainees/all', [TraineeController::class, 'indexMyTrainees']);
    Route::get('/coach/my-trainees/detailes/{id}', [TraineeController::class, 'showTraineeDetails']);
    //وضع الكوتش خطة لمتدرب 
    Route::post('/coach/trainees/{id}/add-workout-plan', [WorkoutPlanController::class, 'assignWorkoutPlan']);
    Route::get('/coach/trainee/{id}/workout-plans', [WorkoutPlanController::class, 'getTraineeWorkoutPlans']);
    Route::post('/coach/trainees/workout-plan/{planId}/update', [WorkoutPlanController::class, 'updateWorkoutPlan']);

    //.......................................................................................................
    // صفحة التمارين
    //6.show exercies
    Route::get('/exercises', [ExercisesController::class, 'getAllExercises']);
    // اضافة رابط لتصنيف التمارين
    Route::get('/exercises/categories', [ExercisesController::class, 'getCategories']);
    //7.show exercise by category
    Route::get('/exercises/categories/{id}', [ExercisesController::class, 'getExercisesByCategory']);
    //عرض تفاصيل تمرين
    Route::get('/exercises/{id}', [ExercisesController::class, 'getExerciseDetails']);

    //...............................................................................................
    //products
    Route::get('/Home/products', [ProductController::class, 'indexProducts']);
    Route::get('/Home/products/show/{id}', [ProductController::class, 'showProduct']);

    //...........................................

    //payment plan
    Route::get('/plans',[PlanController::class,'index']);
    Route::post('/plans/create',[PlanController::class,'create']);
    Route::get('plan/{id}', [PlanController::class, 'show']);

    Route::get('/subscriptions',[SubscriptionController::class,'currentSubscription']);
    Route::post('/subscriptions/create',[SubscriptionController::class,'create']);

    Route::post('/transactions/create',[TransactionController::class,'create']);
    Route::post('/transactions/verify',[TransactionController::class,'verify']);
    Route::get('/my-transactions', [TransactionController::class, 'myTransactions']);
    Route::get('transactions/show', [TransactionController::class, 'lastTransaction']); // آخر معاملة
    Route::get('transactions/{id}/download', [TransactionController::class, 'downloadInvoice']);

     Route::get('/payments',[PaymentController::class,'index']);
    
});



