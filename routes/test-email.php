<?php

/**
 * Email Testing Routes
 *
 * هذا الملف يحتوي على routes للاختبار فقط
 * احذفها في production أو استخدم middleware للحماية
 */

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Agent;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AssignAgentPasswordNotification;

// ⚠️ هذا للاختبار فقط - احذفها أو أضف middleware للحماية في production
Route::get('/test-email', function(Request $request) {
    $email = $request->get('email', 'test@example.com');

    $config = [
        'MAIL_MAILER' => config('mail.default'),
        'MAIL_HOST' => config('mail.mailers.smtp.host'),
        'MAIL_PORT' => config('mail.mailers.smtp.port'),
        'MAIL_ENCRYPTION' => config('mail.mailers.smtp.encryption'),
        'MAIL_USERNAME' => config('mail.mailers.smtp.username') ? '***' : 'not set',
        'MAIL_FROM' => config('mail.from.address'),
    ];

    return view('test-email', [
        'email' => $email,
        'config' => $config,
    ]);
})->name('test.email.form');

Route::post('/test-email/send', function(Request $request) {
    $email = $request->input('email');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return back()->with('error', 'البريد الإلكتروني غير صحيح');
    }

    try {
        // اختبار 1: إرسال بسيط
        Mail::raw('هذا اختبار بسيط لإرسال الإيميلات من نظام Leader for Trans', function($msg) use ($email) {
            $msg->to($email)
                ->subject('اختبار إرسال بريد إلكتروني - Leader')
                ->from(config('mail.from.address'), config('mail.from.name'));
        });

        Log::info('Test email sent successfully', ['to' => $email]);

        return back()->with('success', "✓ تم إرسال إيميل اختبار بنجاح إلى: {$email}. تحقق من صندوق الوارد ومجلد Spam");

    } catch (\Exception $e) {
        Log::error('Test email exception', [
            'email' => $email,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return back()->with('error', 'خطأ في إرسال الإيميل: ' . $e->getMessage());
    }
})->name('test.email.send');

Route::post('/test-email/send-agent', function(Request $request) {
    $email = $request->input('email');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return back()->with('error', 'البريد الإلكتروني غير صحيح');
    }

    try {
        // Create a test agent object (not saved to DB)
        $testAgent = new Agent();
        $testAgent->email = $email;
        $testAgent->name = 'مندوب تجريبي';
        $testAgent->phone = '01000000000';
        $testAgent->session_id = 'test_token_' . time();

        // Send using notification
        Notification::route('mail', $email)
            ->notify(new AssignAgentPasswordNotification($testAgent));

        Log::info('Test agent notification sent', ['to' => $email]);

        return back()->with('success', "✓ تم إرسال إشعار المندوب بنجاح إلى: {$email}. تحقق من صندوق الوارد ومجلد Spam");

    } catch (\Exception $e) {
        Log::error('Test agent notification exception', [
            'email' => $email,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return back()->with('error', 'خطأ في إرسال الإشعار: ' . $e->getMessage());
    }
})->name('test.email.send-agent');

Route::get('/test-email/check-smtp', function() {
    $config = [
        'host' => config('mail.mailers.smtp.host'),
        'port' => config('mail.mailers.smtp.port'),
        'encryption' => config('mail.mailers.smtp.encryption'),
        'username' => config('mail.mailers.smtp.username'),
        'from' => config('mail.from.address'),
    ];

    $results = [
        'config' => $config,
        'tests' => [],
    ];

    // Test 1: Check if host is reachable
    try {
        $host = $config['host'];
        $port = $config['port'];

        $connection = @fsockopen($host, $port, $errno, $errstr, 5);

        if ($connection) {
            $results['tests'][] = [
                'name' => 'اتصال SMTP',
                'status' => 'success',
                'message' => "✓ نجح الاتصال بـ {$host}:{$port}",
            ];
            fclose($connection);
        } else {
            $results['tests'][] = [
                'name' => 'اتصال SMTP',
                'status' => 'error',
                'message' => "✗ فشل الاتصال: {$errstr} ({$errno})",
            ];
        }
    } catch (\Exception $e) {
        $results['tests'][] = [
            'name' => 'اتصال SMTP',
            'status' => 'error',
            'message' => "✗ خطأ: " . $e->getMessage(),
        ];
    }

    // Test 2: Try to send test email with detailed logging
    try {
        $testEmail = 'test@example.com';

        // Enable Swift Mailer logging
        Log::info('Testing SMTP send with full details', $config);

        Mail::raw('Test email from Leader SMTP checker', function($msg) use ($testEmail) {
            $msg->to($testEmail)
                ->subject('SMTP Test - Leader')
                ->from(config('mail.from.address'), config('mail.from.name'));
        });

        $results['tests'][] = [
            'name' => 'إرسال تجريبي',
            'status' => 'success',
            'message' => "✓ تم إرسال الأمر إلى SMTP server بنجاح",
        ];

    } catch (\Exception $e) {
        $results['tests'][] = [
            'name' => 'إرسال تجريبي',
            'status' => 'error',
            'message' => "✗ فشل الإرسال: " . $e->getMessage(),
        ];
    }

    return response()->json($results, 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
})->name('test.email.check-smtp');

