<?php

namespace App\Console\Commands;

use App\Models\Agent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AssignAgentPasswordNotification;
use Tymon\JWTAuth\Facades\JWTAuth;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sending email to a specific address';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->argument('email');

        $this->info("Testing email sending to: {$email}");
        $this->info("Mail Driver: " . config('mail.default'));
        $this->info("Mail Host: " . config('mail.mailers.smtp.host'));
        $this->info("Mail Port: " . config('mail.mailers.smtp.port'));
        $this->info("Mail Username: " . (config('mail.mailers.smtp.username') ? '***' : 'not set'));
        $this->info("Mail From: " . config('mail.from.address'));
        $this->newLine();

        try {
            // Create a temporary agent for testing
            $testAgent = new Agent();
            $testAgent->email = $email;
            $testAgent->name = 'Test Agent';
            $testAgent->phone = '01000000000';
            $testAgent->session_id = 'test_token_' . time();

            // Don't save to database, just use for notification
            $this->info("Sending test email...");
            Notification::route('mail', $email)
                ->notify(new AssignAgentPasswordNotification($testAgent));

            $this->newLine();
            $this->info("✓ Email command executed successfully!");
            $this->warn("⚠ Check the logs at storage/logs/laravel.log for detailed status");
            $this->warn("⚠ Also check spam/junk folder");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("✗ Failed to send email: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}

