<?php

namespace App\Services;

use App\Models\Agent;
use Illuminate\Support\Facades\Log;

class AgentEmailService
{
    /**
     * Send password assignment email to agent
     *
     * @param  \App\Models\Agent  $agent
     * @param  string  $token
     * @return bool
     */
    public function sendPasswordAssignmentEmail(Agent $agent, string $token): bool
    {
        try {
            $url = rtrim(env('APP_URL'), '/') . '/set_password/agents/?token=' . $token;
            $name = $agent->name ?? '';
            $subject = 'تعيين كلمة المرور لحساب المندوب - Leader for Trans';
            $to = $agent->email;

            if (empty($to)) {
                Log::warning('AgentEmailService: Agent email is empty', [
                    'agent_id' => $agent->id,
                ]);
                return false;
            }

            $html = $this->getEmailTemplate($name, $url);

            // Headers للبريد
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: " . config('mail.from.name') . " <" . config('mail.from.address') . ">\r\n";
            $headers .= "Reply-To: " . config('mail.from.address') . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
            $headers .= "X-Priority: 1 (Highest)\r\n";
            $headers .= "Importance: High\r\n";

            // Check if mail() function is available
            if (!function_exists('mail')) {
                throw new \Exception('PHP mail() function is not available on this server');
            }

            // Check sendmail path (for debugging)
            $sendmailPath = ini_get('sendmail_path');

            Log::info('AgentEmailService: Sending email using PHP mail()', [
                'agent_id' => $agent->id,
                'email' => $to,
                'subject' => $subject,
                'method' => 'PHP mail()',
                'sendmail_path' => $sendmailPath,
                'php_version' => phpversion(),
                'headers_length' => strlen($headers),
                'html_length' => strlen($html),
            ]);

            // إرسال البريد باستخدام PHP mail()
            $result = @mail($to, $subject, $html, $headers);

            // Get last error if any
            $lastError = error_get_last();

            if ($result) {
                Log::info('AgentEmailService: Email sent successfully using PHP mail()', [
                    'agent_id' => $agent->id,
                    'email' => $to,
                    'time' => now()->toDateTimeString(),
                    'method' => 'PHP mail()',
                    'note' => 'mail() returned TRUE, but actual delivery depends on server configuration',
                ]);

                return true;
            } else {
                $errorMsg = $lastError ? $lastError['message'] : 'PHP mail() returned FALSE';
                throw new \Exception('PHP mail() failed: ' . $errorMsg);
            }
        } catch (\Exception $e) {
            Log::error('AgentEmailService: Failed to send agent password email', [
                'agent_id' => $agent->id ?? null,
                'email' => $to ?? null,
                'error' => $e->getMessage(),
                'method' => 'PHP mail()',
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Get email HTML template
     *
     * @param  string  $name
     * @param  string  $url
     * @return string
     */
    private function getEmailTemplate(string $name, string $url): string
    {
        return view('email.set-password', [
            'name' => $name,
            'url' => $url,
            'accountLabel' => 'حساب المندوب',
            'intro' => 'تم إنشاء حساب لك كمندوب على منظومة Leader for Trans. اضغط على الزر أدناه لتعيين كلمة المرور وإكمال تفعيل الحساب.',
        ])->render();
    }

    /**
     * Test email sending with custom data
     *
     * @param  string  $email
     * @param  string  $name
     * @param  string  $token
     * @return bool
     */
    public function testEmail(string $email, string $name = 'Test Agent', string $token = 'test_token_123'): bool
    {
        try {
            $url = rtrim(env('APP_URL'), '/') . '/set_password/agents/?token=' . $token;
            $subject = 'اختبار - تعيين كلمة المرور لحساب المندوب - Leader for Trans';
            $html = $this->getEmailTemplate($name, $url);

            // Headers للبريد
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: " . config('mail.from.name') . " <" . config('mail.from.address') . ">\r\n";
            $headers .= "Reply-To: " . config('mail.from.address') . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
            $headers .= "X-Priority: 1 (Highest)\r\n";
            $headers .= "Importance: High\r\n";

            Log::info('AgentEmailService: Testing email sending using PHP mail()', [
                'email' => $email,
                'name' => $name,
                'method' => 'PHP mail()',
            ]);

            // إرسال البريد باستخدام PHP mail()
            $result = mail($email, $subject, $html, $headers);

            if ($result) {
                Log::info('AgentEmailService: Test email sent successfully using PHP mail()', [
                    'email' => $email,
                    'time' => now()->toDateTimeString(),
                    'method' => 'PHP mail()',
                ]);

                return true;
            } else {
                throw new \Exception('PHP mail() returned FALSE');
            }
        } catch (\Exception $e) {
            Log::error('AgentEmailService: Failed to send test email', [
                'email' => $email,
                'error' => $e->getMessage(),
                'method' => 'PHP mail()',
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}
