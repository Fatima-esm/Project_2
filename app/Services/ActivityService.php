<?php

namespace App\Services;

use Spatie\Activitylog\Facades\LogBatch;

class ActivityService
{
    /**
     * تسجيل نشاط احترافي عام
     * 
     * @param string $description وصف الحدث (مثال: "قام بإنشاء متدرب جديد")
     * @param mixed|null $subject الكائن المستهدف (مثل نموذج المتدرب أو المنتج)
     * @param array|null $properties بيانات إضافية بصيغة مصفوفة لتخزينها كـ JSON
     * @param string|null $logName تصنيف السجل (مثل: reception, admin, sales)
     */
    
    public static function log(string $description, $subject = null, ?array $properties = [], ?string $logName = 'default')
    {
        $logger = activity($logName)
            ->causedBy(auth()->user()); // يربط النشاط تلقائياً بالمستخدم الحالي (الموظف أو الأدمن)

        if ($subject) {
            $logger->performedOn($subject);
        }

        if (!empty($properties)) {
            $logger->withProperties($properties);
        }

        return $logger->log($description);
    }
}