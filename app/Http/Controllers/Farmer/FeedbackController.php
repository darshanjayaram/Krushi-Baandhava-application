<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Admin\FeedbackController as AdminFeedbackController;
use App\Http\Controllers\Controller;
use App\Models\Crop;
use App\Models\District;
use App\Models\FarmerFeedback;
use App\Models\Market;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    /**
     * Show the Report Issues & Farmer Feedback form.
     */
    public function create(Request $request): View
    {
        $mode = $request->query('mode', 'issue');
        if (!in_array($mode, ['issue', 'feedback'], true)) {
            $mode = 'issue';
        }

        $prefillCategory = $request->query('category', '');
        $prefillCrop = $request->query('crop', '');
        $prefillMarket = $request->query('market', '');
        $prefillDistrict = $request->query('district', '');

        // Fetch active crops for easy autocomplete / selection
        $crops = Crop::where('is_active', true)
            ->orderBy('is_major', 'desc')
            ->orderBy('name')
            ->get(['id', 'name', 'name_kn', 'slug']);

        // Fetch districts with markets for dropdowns
        $districts = District::with(['markets' => function ($query) {
                $query->where('is_active', true)->orderBy('name');
            }])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'name_kn']);

        // Fetch dynamic admin configured settings & categories
        $settings = AdminFeedbackController::getFeedbackSettings();
        $issueCategories = array_values(array_filter($settings['issue_categories'], fn($c) => !empty($c['is_active'])));
        $feedbackCategories = array_values(array_filter($settings['feedback_categories'], fn($c) => !empty($c['is_active'])));

        return view('farmer.feedback.index', compact(
            'mode',
            'prefillCategory',
            'prefillCrop',
            'prefillMarket',
            'prefillDistrict',
            'crops',
            'districts',
            'settings',
            'issueCategories',
            'feedbackCategories'
        ));
    }

    /**
     * Store submitted feedback or reported issue.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        // 1. Anti-spam honeypot check
        if (!empty($request->input('antispam_website'))) {
            // Fake success for spam bots without storing to DB
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'ticket_no' => 'KB-' . date('y') . '-' . rand(1000, 9999),
                    'message' => 'Thank you for your submission.',
                ]);
            }
            return redirect()->route('farmer.feedback.create')
                ->with('success_ticket', 'KB-' . date('y') . '-' . rand(1000, 9999));
        }

        // 2. Validate input
        $validated = $request->validate([
            'type' => 'required|in:issue,feedback',
            'category' => 'required|string|max:100',
            'rating' => 'nullable|integer|min:1|max:5',
            'crop_name' => 'nullable|string|max:120',
            'district' => 'nullable|string|max:120',
            'market_name' => 'nullable|string|max:150',
            'message' => 'nullable|string|max:5000',
            'farmer_name' => 'nullable|string|max:120',
            'farmer_phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\s\-]{10,15}$/'],
            'farmer_email' => 'nullable|email|max:120',
            'photo' => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:10240', // max 10MB
            'voice' => 'nullable|file|max:20480', // max 20MB (audio/webm, audio/wav, audio/mp3, audio/ogg, audio/mp4, audio/aac)
            'voice_duration' => 'nullable|integer',
        ], [
            'farmer_phone.required' => 'ದಯವಿಟ್ಟು ನಿಮ್ಮ ಮೊಬೈಲ್ ಸಂಖ್ಯೆಯನ್ನು ನಮೂದಿಸಿ (Please enter your mobile phone number).',
            'farmer_phone.regex' => 'ದಯವಿಟ್ಟು ಮಾನ್ಯವಾದ ಮೊಬೈಲ್ ಸಂಖ್ಯೆಯನ್ನು ನಮೂದಿಸಿ (Please enter a valid 10-digit mobile number).',
            'farmer_email.email' => 'ದಯವಿಟ್ಟು ಮಾನ್ಯವಾದ ಇಮೇಲ್ ವಿಳಾಸವನ್ನು ನಮೂದಿಸಿ (Please enter a valid email address).',
            'photo.image' => 'ಫೋಟೋ ಮಾನ್ಯವಾದ ಚಿತ್ರವಾಗಿರಬೇಕು (The uploaded file must be an image: JPG, PNG, or WebP).',
            'photo.max' => 'ಫೋಟೋ ಗಾತ್ರ 10MB ಗಿಂತ ಕಡಿಮೆಯಿರಬೇಕು (Photo size must not exceed 10MB).',
            'voice.max' => 'ಆಡಿಯೋ ರೆಕಾರ್ಡಿಂಗ್ ಗಾತ್ರ 20MB ಗಿಂತ ಕಡಿಮೆಯಿರಬೇಕು (Voice recording must not exceed 20MB).',
        ]);

        // Require at least a text message OR a voice recording
        if (empty($validated['message']) && !$request->hasFile('voice')) {
            $errorMsg = 'ದಯವಿಟ್ಟು ಸಮಸ್ಯೆಯ ವಿವರ ಬರೆಯಿರಿ ಅಥವಾ ಧ್ವನಿ ಸಂದೇಶ ರೆಕಾರ್ಡ್ ಮಾಡಿ (Please provide either a message or record a voice note).';
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                    'errors' => ['message' => [$errorMsg]],
                ], 422);
            }
            return back()->withErrors(['message' => $errorMsg])->withInput();
        }

        // 3. Process photo upload
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoFile = $request->file('photo');
            $filename = 'photo_' . time() . '_' . Str::random(8) . '.' . $photoFile->getClientOriginalExtension();
            $photoPath = $photoFile->storeAs('uploads/feedback/photos', $filename, 'public');
        }

        // 4. Process voice note upload
        $voicePath = null;
        if ($request->hasFile('voice')) {
            $voiceFile = $request->file('voice');
            $extension = $voiceFile->getClientOriginalExtension();
            if (empty($extension)) {
                $mime = $voiceFile->getClientMimeType();
                $extension = str_contains($mime, 'wav') ? 'wav' : (str_contains($mime, 'mp4') ? 'm4a' : (str_contains($mime, 'ogg') ? 'ogg' : 'webm'));
            }
            $voiceFilename = 'voice_' . time() . '_' . Str::random(8) . '.' . $extension;
            $voicePath = $voiceFile->storeAs('uploads/feedback/voice', $voiceFilename, 'public');
        }

        // 5. Create feedback record
        $feedback = FarmerFeedback::create([
            'type' => $validated['type'],
            'category' => $validated['category'],
            'rating' => $validated['rating'] ?? null,
            'crop_name' => $validated['crop_name'] ?? null,
            'district' => $validated['district'] ?? null,
            'market_name' => $validated['market_name'] ?? null,
            'message' => $validated['message'] ?? null,
            'voice_path' => $voicePath,
            'voice_duration' => $validated['voice_duration'] ?? null,
            'photo_path' => $photoPath,
            'farmer_name' => $validated['farmer_name'] ?? null,
            'farmer_phone' => $validated['farmer_phone'],
            'farmer_email' => $validated['farmer_email'] ?? null,
            'status' => FarmerFeedback::STATUS_NEW,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Support WhatsApp URL to message Krushi Baandhava desk directly
        $supportPhone = preg_replace('/[^0-9]/', '', SystemSetting::get('feedback_support_whatsapp', '919876543210'));
        $typeLabel = $feedback->type === FarmerFeedback::TYPE_ISSUE ? 'ಸಮಸ್ಯೆ ವರದಿ' : 'ಸಲಹೆ/ಪ್ರತಿಕ್ರಿಯೆ';
        $categoryLabel = $feedback->category_label;
        $farmerName = $feedback->farmer_name ? " ನನ್ನ ಹೆಸರು {$feedback->farmer_name}." : "";
        $text = "ನಮಸ್ಕಾರ ಕೃಷಿ ಬಾಂಧವ,{$farmerName} ನಾನು ನನ್ನ ಟಿಕೆಟ್ ಸಂಖ್ಯೆ *{$feedback->ticket_no}* ({$typeLabel} - {$categoryLabel}) ಕುರಿತು ವಿಚಾರಿಸಲು ಸಂಪರ್ಕಿಸುತ್ತಿದ್ದೇನೆ.";
        $whatsAppUrl = "https://wa.me/{$supportPhone}?text=" . rawurlencode($text);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'ticket_no' => $feedback->ticket_no,
                'type' => $feedback->type,
                'whatsapp_url' => $whatsAppUrl,
                'message' => 'ನಿಮ್ಮ ವರದಿಯನ್ನು ಯಶಸ್ವಿಯಾಗಿ ಸ್ವೀಕರಿಸಲಾಗಿದೆ. ಟಿಕೆಟ್ ಸಂಖ್ಯೆ: ' . $feedback->ticket_no,
            ]);
        }

        return redirect()->route('farmer.feedback.create')
            ->with('feedback_success', [
                'ticket_no' => $feedback->ticket_no,
                'type' => $feedback->type,
                'category_label' => $feedback->category_label,
                'whatsapp_url' => $whatsAppUrl,
                'farmer_name' => $feedback->farmer_name,
            ]);
    }
}
