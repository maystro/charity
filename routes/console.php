<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// توليد تنبيهات إعادة التقييم يوميًا
Schedule::command('app:generate-alerts')->dailyAt('02:00')->description('فحص وتوليد تنبيهات إعادة التقييم');

// كشف الزيارات المتأخرة كل ساعة
Schedule::command('app:detect-overdue-visits')->hourly()->description('كشف الزيارات المتأخرة');

// إنهاء عمليات النشر العالقة كل 5 دقائق
Schedule::command('app:cleanup-stale-deployments')->everyFiveMinutes()->description('إنهاء عمليات النشر العالقة');

// معالجة وظائف الطابور تلقائيًا كل دقيقة (بديل عن تشغيل queue:work يدويًا)
Schedule::command('queue:work --stop-when-empty --tries=1 --timeout=0')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->description('معالجة وظائف الطابور');

// نسخة احتياطية يومية لقاعدة البيانات
Schedule::command('app:database-backup')->dailyAt(config('backup.schedule_time', '03:00'))->description('نسخة احتياطية يومية لقاعدة البيانات');
