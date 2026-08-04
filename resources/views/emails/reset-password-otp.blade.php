<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إعادة تعيين كلمة المرور - Smart Gym</title>
</head>
<body style="font-family: 'Tahoma', Arial, sans-serif; background-color: #f4f0ea; margin: 0; padding: 40px 0; direction: rtl; text-align: right;">
    
    <!-- الحاوية الرئيسية -->
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #fdfbf7; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #e8e1d5;">
        
        <!-- البار العلوي الأسود (نفس حجم البار السفلي وبدلاً من الشعار كلمة سمارت جيم بالذهبي في المنتصف) -->
        <tr>
            <td align="center" style="background-color: #1a1a1a; padding: 20px 30px; border-bottom: 1px solid #2a2a2a;">
                <p style="color: #b8860b; font-size: 25px; font-weight: bold; margin: 0; text-align: center;">
                   SMART GYM
                </p>
            </td>
        </tr>

        <!-- الصورة أسفل البار العلوي الأسود مباشرة -->
        <tr>
            <td align="center" style="padding: 25px 20px 10px 20px; background-color: #fdfbf7;">
                <img src="{{ $message->embed(public_path('images/logo.png')) }}" alt="Smart Gym Logo" width="130" style="display: block; border: 0; outline: none;">
            </td>
        </tr>

        <!-- محتوى الرسالة (باللون البيج فوق الكتابة ومن اليمين لليسار) -->
        <tr>
            <td style="padding: 20px 30px 40px 30px; color: #333333; direction: rtl; text-align: right;">
                <h2 style="color: #1a1a1a; font-size: 20px; margin-top: 0; font-weight: bold;">مرحباً بك..</h2>
                <p style="font-size: 15px; line-height: 1.8; color: #555555;">
                    تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك في <strong style="color: #b8860b;">SMART GYM</strong>. يمكنك استخدام رمز التحقق (OTP) أدناه لإتمام العملية:
                </p>
                
                <!-- صندوق رمز الـ OTP -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 30px 0;">
                    <tr>
                        <td align="center">
                            <span style="background-color: #f4f0ea; color: #1a1a1a; font-size: 32px; font-weight: bold; padding: 15px 35px; border-radius: 8px; letter-spacing: 8px; display: inline-block; border: 2px dashed #d4af37; box-shadow: inset 0 2px 4px rgba(0,0,0,0.03);">
                                {{ $otpCode }}
                            </span>
                        </td>
                    </tr>
                </table>

                <p style="font-size: 14px; color: #777777; line-height: 1.6;">
                    هذا الرمز صالح لفترة محدودة. إذا لم تقم بطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذه الرسالة بأمان.
                </p>
            </td>
        </tr>

        <!-- البار السفلي -->
        <tr>
            <td style="background-color: #1a1a1a; padding: 20px 30px; text-align: center; border-top: 1px solid #2a2a2a;">
                <p style="color: #999999; font-size: 12px; margin: 0;">
                    جميع الحقوق محفوظة &copy; {{ date('Y') }} <strong style="color: #b8860b;">SMART GYM</strong>
                </p>
            </td>
        </tr>

    </table>
</body>
</html>