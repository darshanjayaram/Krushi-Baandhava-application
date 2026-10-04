<?php

namespace App\Console\Commands;

use App\Models\NotificationBroadcast;
use App\Models\PushSubscription;
use App\Models\SystemSetting;
use App\Services\Notification\PwaPushService;
use Illuminate\Console\Command;

class SendPwaWeatherNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pwa:send-weather-alert {--force : Force send even if disabled in settings}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Broadcast morning weather forecast & agronomic advisory to all PWA subscribers';

    /**
     * Execute the console command.
     */
    public function handle(PwaPushService $pushService): int
    {
        $enabled = $this->option('force') || (
            SystemSetting::get('pwa_push_enabled', true) &&
            SystemSetting::get('pwa_auto_weather_enabled', true)
        );

        if (!$enabled) {
            $this->info('Weather PWA notification is currently disabled in system settings. Skipped.');
            return Command::SUCCESS;
        }

        $activeSubscribersCount = PushSubscription::active()->count();
        if ($activeSubscribersCount === 0) {
            $this->info('No active PWA push subscribers found. Skipped.');
            return Command::SUCCESS;
        }

        $titleKn = "🌤️ ಇಂದಿನ ಹವಾಮಾನ ವರದಿ & ಕೃಷಿ ಸಲಹೆ";
        $titleEn = "🌤️ Today's Weather Advisory for Farmers";

        $bodyKn = "ನಿಮ್ಮ ಭಾಗದ ಇಂದಿನ ಮಳೆ, ತೇವಾಂಶ ಮತ್ತು ಕೃಷಿ ಸಿಂಪಡಣೆಗೆ ಸೂಕ್ತ ಹವಾಮಾನ ಮುನ್ಸೂಚನೆ ಪರಿಶೀಲಿಸಿ.";
        $bodyEn = "Check today's precipitation outlook, humidity, and optimal crop spraying conditions.";

        $broadcast = NotificationBroadcast::create([
            'created_by' => null, // automated system
            'type' => 'weather',
            'title_kn' => $titleKn,
            'title_en' => $titleEn,
            'body_kn' => $bodyKn,
            'body_en' => $bodyEn,
            'target_url' => '/weather',
            'status' => 'pending',
            'total_recipients' => $activeSubscribersCount,
        ]);

        $this->info("Broadcasting morning weather alert to {$activeSubscribersCount} subscribers...");
        $result = $pushService->broadcast($broadcast);

        $this->info("Completed! Sent: {$result['sent']}, Failed: {$result['failed']}.");

        return Command::SUCCESS;
    }
}
