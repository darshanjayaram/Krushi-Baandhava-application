<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Crop;
use App\Models\CuratedVideo;
use App\Models\NewsArticle;
use App\Models\Scheme;
use Illuminate\Database\Seeder;

class AgriculturalCmsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tomato = Crop::where('slug', 'tomato')->first();
        $arecanut = Crop::where('slug', 'arecanut')->first();

        // 1. Government Schemes
        $schemes = [
            [
                'title' => 'Farm Mechanization Scheme (Tractor & Power Tiller Subsidy)',
                'title_kn' => 'ಕೃಷಿ ಯಾಂತ್ರೀಕರಣ ಯೋಜನೆ (ಟ್ರಾಕ್ಟರ್ ಮತ್ತು ಪವರ್ ಟಿಲ್ಲರ್ ಸಬ್ಸಿಡಿ)',
                'slug' => 'farm-mechanization-subsidy',
                'category' => 'machinery',
                'sponsoring_agency' => 'ಕರ್ನಾಟಕ ಕೃಷಿ ಇಲಾಖೆ (Dept of Agriculture, Karnataka)',
                'benefit_amount' => 'Up to 90% for SC/ST and 50% for General (Max ₹2 Lakhs)',
                'benefit_amount_kn' => 'ಪ.ಜಾ/ಪ.ಪಂ ರೈತರಿಗೆ ಶೇ. 90 ಮತ್ತು ಸಾಮಾನ್ಯ ರೈತರಿಗೆ ಶೇ. 50 ರವರೆಗೆ ಸಬ್ಸಿಡಿ (ಗರಿಷ್ಠ ₹2 ಲಕ್ಷ)',
                'eligibility_criteria' => 'Small and marginal farmers holding verified RTC in Karnataka.',
                'eligibility_criteria_kn' => 'ಕರ್ನಾಟಕದಲ್ಲಿ ಸ್ವಂತ ಜಮೀನು ಹಾಗೂ ಆರ್.ಟಿ.ಸಿ (RTC) ಹೊಂದಿರುವ ಸಣ್ಣ ಮತ್ತು ಅತಿ ಸಣ್ಣ ರೈತರು.',
                'documents_required' => 'ಆಧಾರ್ ಕಾರ್ಡ್, ಆರ್.ಟಿ.ಸಿ / ಪಹಣಿ, ಬ್ಯಾಂಕ್ ಪಾಸ್‌ಬುಕ್, ಜಾತಿ ಪ್ರಮಾಣಪತ್ರ (ಅಗತ್ಯವಿದ್ದರೆ).',
                'official_url' => 'https://raitamitra.karnataka.gov.in/',
                'apply_url' => 'https://k-kisan.karnataka.gov.in/',
                'icon_emoji' => '🚜',
                'display_order' => 1,
            ],
            [
                'title' => 'Pradhan Mantri Krishi Sinchayee Yojana (Drip & Sprinkler)',
                'title_kn' => 'ಪ್ರಧಾನ ಮಂತ್ರಿ ಕೃಷಿ ಸಿಂಚಾಯಿ ಯೋಜನೆ (ಹನಿ ಮತ್ತು ತುಂತುರು ನೀರಾವರಿ)',
                'slug' => 'pmksy-micro-irrigation',
                'category' => 'irrigation',
                'sponsoring_agency' => 'ಕೇಂದ್ರ ಮತ್ತು ಕರ್ನಾಟಕ ಸರ್ಕಾರ (Central & Karnataka Govt)',
                'benefit_amount' => '90% subsidy for SC/ST, 75% for General farmers up to 5 acres',
                'benefit_amount_kn' => 'ಪ.ಜಾ/ಪ.ಪಂ ರೈತರಿಗೆ ಶೇ. 90 ಮತ್ತು ಸಾಮಾನ್ಯ ರೈತರಿಗೆ ಶೇ. 75 ರವರೆಗೆ ಸಹಾಯಧನ (5 ಎಕರೆವರೆಗೆ)',
                'eligibility_criteria' => 'Farmers having confirmed water source (borewell/canal/pond) and land RTC.',
                'eligibility_criteria_kn' => 'ಬೋರ್‌ವೆಲ್, ಬಾವಿ ಅಥವಾ ಕೃಷಿ ಹೊಂಡದ ನೀರಿನ ಮೂಲ ಹೊಂದಿರುವ ಎಲ್ಲ ರೈತರು ಅರ್ಹರು.',
                'documents_required' => 'ಪಹಣಿ (RTC), ವಿದ್ಯುತ್ ಬಿಲ್ / ನೀರಿನ ಮೂಲ ದೃಢೀಕರಣ ಪತ್ರ, ಆಧಾರ್ ಕಾರ್ಡ್, ಬ್ಯಾಂಕ್ ವಿವರ.',
                'official_url' => 'https://pmksy.gov.in/',
                'apply_url' => 'https://raitamitra.karnataka.gov.in/',
                'icon_emoji' => '💧',
                'display_order' => 2,
            ],
            [
                'title' => 'Krishi Bhagya Scheme (Farm Ponds & Polyethylene Lining)',
                'title_kn' => 'ಕೃಷಿ ಭಾಗ್ಯ ಯೋಜನೆ (ಕೃಷಿ ಹೊಂಡ ಹಾಗೂ ಪಾಲಿಥಿನ್ ಹೊದಿಕೆ)',
                'slug' => 'krishi-bhagya-scheme',
                'category' => 'subsidy',
                'sponsoring_agency' => 'ಕರ್ನಾಟಕ ರಾಜ್ಯ ಸರ್ಕಾರ (Govt of Karnataka)',
                'benefit_amount' => '80% to 90% subsidy for farm pond excavation and pump sets',
                'benefit_amount_kn' => 'ಕೃಷಿ ಹೊಂಡ ನಿರ್ಮಾಣ, ಪಾಲಿಥಿನ್ ಹೊದಿಕೆ ಹಾಗೂ ಡೀಸೆಲ್ ಪಂಪ್‌ಸೆಟ್‌ಗೆ ಶೇ. 80-90 ಅನುದಾನ',
                'eligibility_criteria' => 'Dryland farmers in selected rainfed taluks of Karnataka.',
                'eligibility_criteria_kn' => 'ಮಳೆ ಆಶ್ರಿತ ಒಣಭೂಮಿ ಕೃಷಿ ಮಾಡುವ ಕರ್ನಾಟಕದ ರೈತರು.',
                'documents_required' => 'ಆರ್.ಟಿ.ಸಿ, ಜಂಟಿ ಖಾತೆದಾರರ ಒಪ್ಪಿಗೆ ಪತ್ರ, ಆಧಾರ್ ಕಾರ್ಡ್.',
                'official_url' => 'https://raitamitra.karnataka.gov.in/',
                'apply_url' => 'https://raitamitra.karnataka.gov.in/',
                'icon_emoji' => '🛡️',
                'display_order' => 3,
            ],
            [
                'title' => 'PM Kisan Samman Nidhi Yojana',
                'title_kn' => 'ಪ್ರಧಾನ ಮಂತ್ರಿ ಕಿಸಾನ್ ಸಮ್ಮಾನ್ ನಿಧಿ ಯೋಜನೆ',
                'slug' => 'pm-kisan-samman-nidhi',
                'category' => 'subsidy',
                'sponsoring_agency' => 'ಕೇಂದ್ರ ಕೃಷಿ ಸಚಿವಾಲಯ (Govt of India)',
                'benefit_amount' => 'Rs 6,000 per year directly to bank account in 3 installments',
                'benefit_amount_kn' => 'ವರ್ಷಕ್ಕೆ ₹6,000 ರೂ. ನೇರ ನಗದು ವರ್ಗಾವಣೆ (ತಲಾ ₹2,000 ರಂತೆ 3 ಕಂತುಗಳಲ್ಲಿ)',
                'eligibility_criteria' => 'All landholding farmers families having cultivable land in their names.',
                'eligibility_criteria_kn' => 'ತಮ್ಮ ಹೆಸರಿನಲ್ಲಿ ಸಾಗುವಳಿ ಭೂಮಿ ಹೊಂದಿರುವ ಎಲ್ಲಾ ರೈತ ಕುಟುಂಬಗಳು.',
                'documents_required' => 'ಆಧಾರ್ ಜೋಡಣೆಯಾದ ಬ್ಯಾಂಕ್ ಖಾತೆ, ಪಹಣಿ (RTC), ಮೊಬೈಲ್ ಸಂಖ್ಯೆ.',
                'official_url' => 'https://pmkisan.gov.in/',
                'apply_url' => 'https://pmkisan.gov.in/',
                'icon_emoji' => '🌴',
                'display_order' => 4,
            ],
            [
                'title' => 'Pradhan Mantri Fasal Bima Yojana (Crop Insurance)',
                'title_kn' => 'ಪ್ರಧಾನ ಮಂತ್ರಿ ಫಸಲ್ ಬಿಮಾ ಯೋಜನೆ (ಬೆಳೆ ವಿಮೆ)',
                'slug' => 'pm-fasal-bima-yojana',
                'category' => 'insurance',
                'sponsoring_agency' => 'ಕೇಂದ್ರ ಮತ್ತು ರಾಜ್ಯ ಸರ್ಕಾರ',
                'benefit_amount' => 'Comprehensive financial loss protection against drought, flood, pests',
                'benefit_amount_kn' => 'ಅತಿವೃಷ್ಟಿ, ಅನಾವೃಷ್ಟಿ ಹಾಗೂ ಕೀಟ ಬಾಧೆ ನಷ್ಟ ಪರಿಹಾರ (ರೈತರ ಪ್ರೀಮಿಯಂ ಕೇವಲ 1.5% - 2%)',
                'eligibility_criteria' => 'All farmers growing notified crops in notified insurance units.',
                'eligibility_criteria_kn' => 'ಅಧಿಸೂಚಿತ ಬೆಳೆಗಳನ್ನು ಬೆಳೆಯುವ ಎಲ್ಲಾ ರೈತರು (ಸಾಲ ಪಡೆದ ಮತ್ತು ಸಾಲ ಪಡೆಯದ ರೈತರು).',
                'documents_required' => 'ಬೆಳೆ ದೃಢೀಕರಣ ಪತ್ರ, ಆರ್.ಟಿ.ಸಿ, ಬ್ಯಾಂಕ್ ಪಾಸ್‌ಬುಕ್.',
                'official_url' => 'https://pmfby.gov.in/',
                'apply_url' => 'https://samrakshane.karnataka.gov.in/',
                'icon_emoji' => '☂️',
                'display_order' => 5,
            ],
            [
                'title' => 'Soil Health Card Scheme',
                'title_kn' => 'ಮಣ್ಣು ಆರೋಗ್ಯ ಕಾರ್ಡ್ ಯೋಜನೆ',
                'slug' => 'soil-health-card-scheme',
                'category' => 'organic',
                'sponsoring_agency' => 'ಕೃಷಿ ಇಲಾಖೆ ಕರ್ನಾಟಕ',
                'benefit_amount' => '100% Free soil testing and customized nutrient recommendation report',
                'benefit_amount_kn' => 'ಉಚಿತ ಮಣ್ಣು ಪರೀಕ್ಷೆ ಹಾಗೂ ಅಗತ್ಯ ರಸಗೊಬ್ಬರಗಳ ವೈಜ್ಞಾನಿಕ ಶಿಫಾರಸು ಕಾರ್ಡ್',
                'eligibility_criteria' => 'All agricultural landowners in Karnataka.',
                'eligibility_criteria_kn' => 'ಕರ್ನಾಟಕದ ಎಲ್ಲಾ ರೈತರು.',
                'documents_required' => 'ಜಮೀನಿನ ಮಣ್ಣಿನ ಮಾದರಿ, ಪಹಣಿ ಪ್ರತಿ.',
                'official_url' => 'https://soilhealth.dac.gov.in/',
                'apply_url' => 'https://soilhealth.dac.gov.in/',
                'icon_emoji' => '🌱',
                'display_order' => 6,
            ],
        ];

        foreach ($schemes as $s) {
            Scheme::updateOrCreate(['slug' => $s['slug']], $s);
        }

        // 2. Agricultural News
        $news = [
            [
                'headline' => 'Karnataka APMC Mandis Report High Arrivals of Tomato & Onion',
                'headline_kn' => 'ರಾಜ್ಯದ ಪ್ರಮುಖ ಎಪಿಎಂಸಿ ಮಾರುಕಟ್ಟೆಗಳಲ್ಲಿ ಟೊಮೆಟೊ ಮತ್ತು ಈರುಳ್ಳಿ ಆವಕದಲ್ಲಿ ಹೆಚ್ಚಳ',
                'slug' => 'karnataka-mandis-tomato-onion-arrivals-surge',
                'summary' => 'Arrivals in Kolar, Bangalore Binny Mill, and Hubli APMC saw steady influx today with modal prices stabilizing.',
                'summary_kn' => 'ಕೋಲಾರ, ಬೆಂಗಳೂರು ಬಿನ್ನಿ ಮಿಲ್ ಹಾಗೂ ಹುಬ್ಬಳ್ಳಿ ಎಪಿಎಂಸಿ ಮಾರುಕಟ್ಟೆಗಳಿಗೆ ತರಕಾರಿ ಆವಕ ಹೆಚ್ಚಾಗಿದ್ದು ದರಗಳು ಸ್ಥಿರಗೊಂಡಿವೆ.',
                'content' => 'The Karnataka State Agricultural Marketing Board (KSAMB) reported a 15% increase in vegetable arrivals across major mandis. Daily transaction tracking shows consistent demand from interstate traders.',
                'content_kn' => 'ಕರ್ನಾಟಕ ರಾಜ್ಯ ಕೃಷಿ ಮಾರಾಟ ಮಂಡಳಿ ವರದಿಯ ಪ್ರಕಾರ ರಾಜ್ಯದ ಪ್ರಮುಖ ಮಂಡಿಗಳಿಗೆ ತರಕಾರಿ ಆವಕ ಶೇ. 15 ರಷ್ಟು ಏರಿಕೆಯಾಗಿದೆ. ಹೊರರಾಜ್ಯಗಳ ವ್ಯಾಪಾರಿಗಳಿಂದ ಬೇಡಿಕೆ ಸ್ಥಿರವಾಗಿದೆ.',
                'source_name' => 'ಕೃಷಿ ಮಾರಾಟ ಮಂಡಳಿ (KSAMB)',
                'priority' => 'important',
                'published_at' => now()->subHours(2),
            ],
            [
                'headline' => 'Last Date for Kharif Crop Insurance Registration Extended',
                'headline_kn' => 'ಮುಂಗಾರು ಬೆಳೆ ವಿಮೆ ನೊಂದಣಿ ಕೊನೆಯ ದಿನಾಂಕ ವಿಸ್ತರಣೆ — ರೈತರಿಗೆ ಶುಭ ಸುದ್ದಿ',
                'slug' => 'crop-insurance-registration-deadline-extended',
                'summary' => 'Agriculture Department extends PMFBY crop insurance enrolment deadline by 10 days for rain-affected districts.',
                'summary_kn' => 'ಮಳೆ ಕೊರತೆ ಹಾಗೂ ತಾಂತ್ರಿಕ ಕಾರಣಗಳಿಂದಾಗಿ ಪ್ರಧಾನ ಮಂತ್ರಿ ಫಸಲ್ ಬಿಮಾ ಯೋಜನೆ ನೊಂದಣಿ ದಿನಾಂಕವನ್ನು 10 ದಿನಗಳ ಕಾಲ ವಿಸ್ತರಿಸಲಾಗಿದೆ.',
                'content' => 'Farmers who have not yet enrolled for their kharif season crop insurance can visit the nearest Grama One or Raitha Samparka Kendra with their RTC and bank details.',
                'content_kn' => 'ಇನ್ನೂ ಬೆಳೆ ವಿಮೆ ಮಾಡಿಸದ ರೈತರು ತಮ್ಮ ಗ್ರಾಮ ಒನ್ ಅಥವಾ ರೈತ ಸಂಪರ್ಕ ಕೇಂದ್ರಕ್ಕೆ ಭೇಟಿ ನೀಡಿ ಪಹಣಿ ಹಾಗೂ ಆಧಾರ್ ಕಾರ್ಡ್ ಸಲ್ಲಿಸಿ ನೊಂದಾಯಿಸಿಕೊಳ್ಳಬಹುದು.',
                'source_name' => 'ಕೃಷಿ ಇಲಾಖೆ ಕರ್ನಾಟಕ',
                'priority' => 'breaking',
                'published_at' => now()->subHours(5),
            ],
            [
                'headline' => 'Special Incentive Released for Karnataka Millet Cultivators Under Raitha Siri',
                'headline_kn' => 'ರೈತ ಸಿರಿ ಯೋಜನೆಯಡಿ ಸಿರಿಧಾನ್ಯ ಬೆಳೆಗಾರರಿಗೆ ಪ್ರೋತ್ಸಾಹ ಧನ ಬಿಡುಗಡೆ',
                'slug' => 'raitha-siri-millet-incentive-released',
                'summary' => 'Karnataka government disburses ₹10,000 per hectare directly to bank accounts of registered millet farmers.',
                'summary_kn' => 'ಸಿರಿಧಾನ್ಯ (ರಾಗಿ, ಜೋಳ, ಸಜ್ಜೆ, ನವಣೆ) ಬೆಳೆದ ರೈತರಿಗೆ ಪ್ರತಿ ಹೆಕ್ಟೇರ್‌ಗೆ ₹10,000 ಪ್ರೋತ್ಸಾಹ ಧನವನ್ನು ಸರ್ಕಾರ ನೇರವಾಗಿ ಖಾತೆಗೆ ಜಮೆ ಮಾಡಿದೆ.',
                'content' => 'To promote drought-resistant and nutritious millets, the State Government credited the subsidy amount into eligible farmers Aadhaar-linked accounts.',
                'content_kn' => 'ಪೌಷ್ಟಿಕ ಸಿರಿಧಾನ್ಯಗಳ ಬೇಸಾಯವನ್ನು ಪ್ರೋತ್ಸಾಹಿಸಲು ಸರ್ಕಾರ ಅರ್ಹ ರೈತರ ಬ್ಯಾಂಕ್ ಖಾತೆಗಳಿಗೆ ಡಿಬಿಟಿ ಮೂಲಕ ನೇರವಾಗಿ ಸಹಾಯಧನ ಜಮೆ ಮಾಡಿದೆ.',
                'source_name' => 'ಪ್ರಜಾವಾಣಿ ಕೃಷಿ ವರದಿ',
                'priority' => 'standard',
                'published_at' => now()->subDay(),
            ],
        ];

        foreach ($news as $n) {
            NewsArticle::updateOrCreate(['slug' => $n['slug']], $n);
        }

        // 3. Curated Videos
        $videos = [
            [
                'title' => 'Scientific Pest & Disease Management in Tomato',
                'title_kn' => 'ಟೊಮೆಟೊ ಬೆಳೆಯಲ್ಲಿ ಎಲೆ ಮುದುರು ರೋಗ ಹಾಗೂ ಕೀಟಗಳ ನಿಯಂತ್ರಣ ಕ್ರಮಗಳು',
                'youtube_video_id' => 'x8uT4-jX94k',
                'youtube_url' => 'https://www.youtube.com/watch?v=x8uT4-jX94k',
                'crop_id' => $tomato?->id,
                'category' => 'pest_control',
                'channel_name' => 'ಕೃಷಿ ದರ್ಶನ (Krishi Darshana)',
                'duration_text' => '14:20',
                'display_order' => 1,
            ],
            [
                'title' => 'Arecanut Koleroga and Fruit Rot Prevention in Monsoon',
                'title_kn' => 'ಅಡಿಕೆಯಲ್ಲಿ ಕೊಳೆ ರೋಗ ಮತ್ತು ಹನಿ ರೋಗ ತಡೆಗಟ್ಟುವ ಪರಿಣಾಮಕಾರಿ ವಿಧಾನಗಳು',
                'youtube_video_id' => 'uGz51Jz4h_0',
                'youtube_url' => 'https://www.youtube.com/watch?v=uGz51Jz4h_0',
                'crop_id' => $arecanut?->id,
                'category' => 'pest_control',
                'channel_name' => 'ತೋಟಗಾರಿಕೆ ತಜ್ಞರು',
                'duration_text' => '18:05',
                'display_order' => 2,
            ],
            [
                'title' => 'Modern Drip Irrigation Installation and Filter Cleaning',
                'title_kn' => 'ಹನಿ ನೀರಾವರಿ ಪದ್ಧತಿ ಅಳವಡಿಕೆ ಹಾಗೂ ವೆಂಚುರಿ/ಫಿಲ್ಟರ್ ಸ್ವಚ್ಛತೆ ಮಾರ್ಗದರ್ಶಿ',
                'youtube_video_id' => '9mX8UeP4X10',
                'youtube_url' => 'https://www.youtube.com/watch?v=9mX8UeP4X10',
                'crop_id' => null,
                'category' => 'cultivation',
                'channel_name' => 'ಕೃಷಿ ತಂತ್ರಜ್ಞಾನ',
                'duration_text' => '11:45',
                'display_order' => 3,
            ],
        ];

        foreach ($videos as $v) {
            CuratedVideo::updateOrCreate(['youtube_video_id' => $v['youtube_video_id']], $v);
        }

        // 4. Agronomy Articles
        $articles = [
            [
                'title' => 'High Yield Tomato Cultivation & Nutrient Management',
                'title_kn' => 'ಟೊಮೆಟೊ ಅಧಿಕ ಇಳುವರಿಗೆ ವೈಜ್ಞಾನಿಕ ಬೇಸಾಯ ಹಾಗೂ ಪೋಷಕಾಂಶಗಳ ನಿರ್ವಹಣೆ',
                'slug' => 'high-yield-tomato-cultivation-guide',
                'crop_id' => $tomato?->id,
                'category' => 'cultivation',
                'summary' => 'Comprehensive agronomic guide on soil preparation, hybrid seed selection, drip fertigation, and staking.',
                'summary_kn' => 'ಉತ್ತಮ ಮಣ್ಣಿನ ಸಿದ್ಧತೆ, ತಳಿಗಳ ಆಯ್ಕೆ, ಹನಿ ನೀರಾವರಿ ಜೊತೆ ರಸಗೊಬ್ಬರ ಹಾಗೂ ಕಡ್ಡಿ ಕಟ್ಟುವುದು ಕುರಿತಾದ ಸಂಪೂರ್ಣ ಮಾಹಿತಿ.',
                'body' => 'Tomato requires well-drained loamy soil with pH between 6.0 to 7.0. Applying 25 tonnes of well-decomposed FYM per hectare during final ploughing increases water holding capacity.',
                'body_kn' => 'ಟೊಮೆಟೊ ಬೆಳೆಗೆ ಉತ್ತಮ ಬಸಿಗಾಲುವೆ ಸೌಲಭ್ಯವಿರುವ ಫಲವತ್ತಾದ ಗೋಡು ಮಣ್ಣು ಸೂಕ್ತ. ನಾಟಿ ಮಾಡುವ 15 ದಿನಗಳ ಮುನ್ನ ಪ್ರತಿ ಎಕರೆಗೆ 10 ಟನ್ ಕೊಳೆತ ಕೊಟ್ಟಿಗೆ ಗೊಬ್ಬರವನ್ನು ಮಣ್ಣಿಗೆ ಬೆರೆಸಬೇಕು. ಕಾಯಿ ಕಚ್ಚುವ ಹಂತದಲ್ಲಿ 13-0-45 (ಪೊಟ್ಯಾಶಿಯಂ ನೈಟ್ರೇಟ್) ಸಿಂಪಡಿಸುವುದರಿಂದ ಕಾಯಿಯ ತೂಕ ಹಾಗೂ ಹೊಳಪು ಹೆಚ್ಚುತ್ತದೆ.',
                'author_name' => 'ಡಾ. ಮಂಜುನಾಥ್, ಕೃಷಿ ವಿಜ್ಞಾನ ಕೇಂದ್ರ',
                'published_at' => now()->subDays(3),
            ],
            [
                'title' => 'Monsoon Disease Management in Arecanut Plantations',
                'title_kn' => 'ಅಡಿಕೆ ತೋಟಗಳಲ್ಲಿ ಮಳೆಗಾಲದ ಕೊಳೆ ರೋಗ ನಿಯಂತ್ರಣ ಹಾಗೂ ಬಸಿಗಾಲುವೆ ಮಹತ್ವ',
                'slug' => 'arecanut-monsoon-disease-management',
                'crop_id' => $arecanut?->id,
                'category' => 'pest_control',
                'summary' => 'Bordeaux mixture preparation, spraying schedules, and root aeration practices for heavy rainfall zones.',
                'summary_kn' => 'ಮಳೆಗಾಲ ಪ್ರಾರಂಭವಾಗುವ ಮುನ್ನ ಶೇ. 1 ರ ಬೋರ್ಡೋ ದ್ರಾವಣ ಸಿಂಪಡಣೆ ಮತ್ತು ತೋಟದಲ್ಲಿ ನೀರು ನಿಲ್ಲದಂತೆ ಬಸಿಗಾಲುವೆ ನಿರ್ವಹಣೆ.',
                'body' => 'Fruit rot (Koleroga) caused by Phytophthora meadii is severe during southwest monsoon. Preventive spray of 1% Bordeaux mixture before onset of monsoon is mandatory.',
                'body_kn' => 'ಮಳೆಗಾಲದಲ್ಲಿ ಫೈಟೊಫ್ತೊರಾ ಶಿಲೀಂಧ್ರದಿಂದ ಉಂಟಾಗುವ ಕೊಳೆ ರೋಗವು ಅಡಿಕೆ ಬೆಳೆಗೆ ಭಾರಿ ನಷ್ಟ ತರುತ್ತದೆ. ಮುಂಗಾರು ಆರಂಭಕ್ಕೆ ಮುನ್ನ ಶೇ. 1 ರ ಬೋರ್ಡೋ ಮಿಶ್ರಣವನ್ನು ಗೊನೆಗಳಿಗೆ ಸಂಪೂರ್ಣವಾಗಿ ಸಿಂಪಡಿಸಬೇಕು. ತೋಟದಲ್ಲಿ ನೀರು ನಿಲ್ಲದಂತೆ ಎಡೆ ಬಸಿಗಾಲುವೆಗಳನ್ನು ತೋಡುವುದು ಅತ್ಯಗತ್ಯ.',
                'author_name' => 'ತೋಟಗಾರಿಕಾ ತಜ್ಞರು, ಶಿವಮೊಗ್ಗ',
                'published_at' => now()->subDays(7),
            ],
        ];

        foreach ($articles as $a) {
            Article::updateOrCreate(['slug' => $a['slug']], $a);
        }
    }
}
