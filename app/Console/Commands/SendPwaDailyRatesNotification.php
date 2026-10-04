<?php

namespace App\Console\Commands;

use App\Models\NotificationBroadcast;
use App\Models\PushSubscription;
use App\Models\SystemSetting;
use App\Services\Notification\PwaPushService;
use Illuminate\Console\Command;

class SendPwaDailyRatesNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pwa:send-daily-rates {--force : Force send even if disabled in settings}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Broadcast daily APMC market prices summary to all PWA subscribers';

    /**
     * Execute the console command.
     */
    public function handle(PwaPushService $pushService): int
    {
        $enabled = $this->option('force') || (
            SystemSetting::get('pwa_push_enabled', true) &&
            SystemSetting::get('pwa_auto_rates_enabled', true)
        );

        if (!$enabled) {
            $this->info('Daily rates PWA notification is currently disabled in system settings. Skipped.');
            return Command::SUCCESS;
        }

        $activeSubscribersCount = PushSubscription::active()->count();
        if ($activeSubscribersCount === 0) {
            $this->info('No active PWA push subscribers found. Skipped.');
            return Command::SUCCESS;
        }

        $dateFormatted = now()->format('d-m-Y');

        $titleKn = "🌾 ಇಂದಿನ ಮಂಡಿ ದರಗಳು ಅಪ್ಡೇಟ್ ಆಗಿವೆ ({$dateFormatted})";
        $titleEn = "🌾 Today's APMC Mandi Rates ({$dateFormatted})";

        $bodyKn = "ಕರ್ನಾಟಕದ ಪ್ರಮುಖ APMC ಮಂಡಿಗಳಲ್ಲಿ ಇಂದಿನ ಹರಾಜು ದರಗಳು ಅಪ್ಡೇಟ್ ಆಗಿವೆ. ಅಡಿಕೆ, ತೆಂಗು, ಮೆಕ್ಕೆಜೋಳ ಮತ್ತು ಇತರ ಬೆಳೆಗಳ ಇಂದಿನ ಧಾರಣೆ ವೀಕ್ಷಿಸಿ.";
        $bodyEn = "Closing auction rates updated across Karnataka mandis. Tap to view today's modal prices and market arrivals.";

        $broadcast = NotificationBroadcast::create([
            'created_by' => null, // automated system
            'type' => 'daily_rates',
            'title_kn' => $titleKn,
            'title_en' => $titleEn,
            'body_kn' => $bodyKn,
            'body_en' => $bodyEn,
            'target_url' => '/crops',
            'status' => 'pending',
            'total_recipients' => $activeSubscribersCount,
        ]);

        $this->info("Broadcasting daily rates to {$activeSubscribersCount} subscribers...");
        $result = $pushService->broadcast($broadcast);

        $this->info("Completed! Sent: {$result['sent']}, Failed: {$result['failed']}.");

        return Command::SUCCESS;
    }
}
