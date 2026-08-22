<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class CustomResetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token)
    {
    }

    /**
     * دریافت کانال‌های ارسال نوتیفیکیشن.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * ارسال ایمیل بازیابی رمز عبور.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = $this->resetUrl($notifiable);

        // در محیط توسعه یا تست، لینک بازیابی را در فایل لاگ ثبت می‌کنیم
        // تا بدون نیاز به SMTP واقعی قابل مشاهده باشد.
        if (app()->environment('local', 'testing', 'development')) {
            Log::channel('password_reset')->info('Password reset link generated', [
                'email' => $notifiable->getEmailForPasswordReset(),
                'url' => $resetUrl,
            ]);
        }

        return (new MailMessage)
            ->subject('بازیابی رمز عبور CoCAI')
            ->greeting('سلام فرمانده!')
            ->line('درخواست بازیابی رمز عبور برای حساب کاربری شما در CoCAI ثبت شده است.')
            ->action('تنظیم رمز عبور جدید', $resetUrl)
            ->line('این لینک تا ۶۰ دقیقه معتبر است.')
            ->line('اگر شما این درخواست را نداده‌اید، لطفاً این ایمیل را نادیده بگیرید.');
    }

    /**
     * ساخت URL بازیابی رمز عبور.
     */
    protected function resetUrl(object $notifiable): string
    {
        return URL::route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], true);
    }
}
