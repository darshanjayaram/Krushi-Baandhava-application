<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FarmerFeedback;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    /**
     * Default categories for Issues (ಸಮಸ್ಯೆ ವರದಿ).
     */
    public static function defaultIssueCategories(): array
    {
        return [
            ['id' => 'price_discrepancy', 'icon' => '💰', 'label_en' => 'Price Discrepancy', 'label_kn' => 'ದರ ವ್ಯತ್ಯಾಸ', 'is_active' => true],
            ['id' => 'weighing_issue', 'icon' => '⚖️', 'label_en' => 'Mandi Weighing / Commission Fee', 'label_kn' => 'ತೂಕ / ದಲ್ಲಾಳಿ ಸಮಸ್ಯೆ', 'is_active' => true],
            ['id' => 'sell_produce', 'icon' => '🚜', 'label_en' => 'Sell Produce Assistance', 'label_kn' => 'ಬೆಳೆ ಮಾರಾಟ ಸಹಾಯ', 'is_active' => true],
            ['id' => 'new_market', 'icon' => '🏢', 'label_en' => 'Request New Mandi', 'label_kn' => 'ಹೊಸ ಮಾರುಕಟ್ಟೆ ಕೋರಿಕೆ', 'is_active' => true],
            ['id' => 'crop_disease', 'icon' => '🐛', 'label_en' => 'Crop Disease / Pest', 'label_kn' => 'ಬೆಳೆ ರೋಗ / ಕೀಟ', 'is_active' => true],
            ['id' => 'govt_scheme', 'icon' => '📜', 'label_en' => 'Govt Scheme Query', 'label_kn' => 'ಸರ್ಕಾರಿ ಯೋಜನೆ ಸಹಾಯ', 'is_active' => true],
            ['id' => 'bug', 'icon' => '📱', 'label_en' => 'Technical App Bug', 'label_kn' => 'ಆ್ಯಪ್ ದೋಷ', 'is_active' => true],
            ['id' => 'other_issue', 'icon' => '❓', 'label_en' => 'Other Issue', 'label_kn' => 'ಇತರೆ ಸಮಸ್ಯೆ', 'is_active' => true],
        ];
    }

    /**
     * Default categories for Feedback (ಸಲಹೆ & ಅಭಿಪ್ರಾಯ).
     */
    public static function defaultFeedbackCategories(): array
    {
        return [
            ['id' => 'price_accuracy', 'icon' => '🎯', 'label_en' => 'Price Accuracy & Quality', 'label_kn' => 'ದರ ಮಾಹಿತಿ ನಿಖರತೆ', 'is_active' => true],
            ['id' => 'feature_request', 'icon' => '✨', 'label_en' => 'New Feature Request', 'label_kn' => 'ಹೊಸ ವೈಶಿಷ್ಟ್ಯ ಕೋರಿಕೆ', 'is_active' => true],
            ['id' => 'app_design', 'icon' => '🎨', 'label_en' => 'App Usability & Design', 'label_kn' => 'ವಿನ್ಯಾಸ & ಬಳಕೆ ಸುಲಭತೆ', 'is_active' => true],
            ['id' => 'appreciation', 'icon' => '❤️', 'label_en' => 'Appreciation', 'label_kn' => 'ಪ್ರಶಂಸೆ & ಅಭಿನಂದನೆ', 'is_active' => true],
            ['id' => 'other_feedback', 'icon' => '💬', 'label_en' => 'Other Suggestion', 'label_kn' => 'ಇತರೆ ಸಲಹೆ', 'is_active' => true],
        ];
    }

    /**
     * Get all active feedback settings.
     */
    public static function getFeedbackSettings(): array
    {
        return [
            'enable_voice' => (bool) SystemSetting::get('feedback_enable_voice', true),
            'max_voice_seconds' => (int) SystemSetting::get('feedback_max_voice_seconds', 180),
            'enable_photos' => (bool) SystemSetting::get('feedback_enable_photos', true),
            'enable_email' => (bool) SystemSetting::get('feedback_enable_email', true),
            'support_whatsapp' => (string) SystemSetting::get('feedback_support_whatsapp', '919876543210'),
            'notification_email' => (string) SystemSetting::get('feedback_notification_email', ''),
            'issue_categories' => SystemSetting::get('feedback_issue_categories', self::defaultIssueCategories()),
            'feedback_categories' => SystemSetting::get('feedback_feedback_categories', self::defaultFeedbackCategories()),
        ];
    }

    /**
     * Display a listing of farmer feedback and reported issues.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $type = $request->query('type');
        $category = $request->query('category');
        $search = $request->query('search');
        $tab = $request->query('tab', 'inbox'); // 'inbox' or 'settings'

        // Query builder
        $query = FarmerFeedback::query()->latest();

        if ($status && in_array($status, [FarmerFeedback::STATUS_NEW, FarmerFeedback::STATUS_IN_REVIEW, FarmerFeedback::STATUS_RESOLVED, FarmerFeedback::STATUS_REJECTED], true)) {
            $query->where('status', $status);
        }

        if ($type && in_array($type, [FarmerFeedback::TYPE_ISSUE, FarmerFeedback::TYPE_FEEDBACK], true)) {
            $query->where('type', $type);
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($search) {
            $query->search($search);
        }

        $feedbacks = $query->paginate(20)->withQueryString();

        // Metrics for Quick KPI Counters
        $stats = [
            'total' => FarmerFeedback::count(),
            'new' => FarmerFeedback::where('status', FarmerFeedback::STATUS_NEW)->count(),
            'in_review' => FarmerFeedback::where('status', FarmerFeedback::STATUS_IN_REVIEW)->count(),
            'resolved' => FarmerFeedback::where('status', FarmerFeedback::STATUS_RESOLVED)->count(),
            'rejected' => FarmerFeedback::where('status', FarmerFeedback::STATUS_REJECTED)->count(),
            'issues_count' => FarmerFeedback::where('type', FarmerFeedback::TYPE_ISSUE)->count(),
            'feedback_count' => FarmerFeedback::where('type', FarmerFeedback::TYPE_FEEDBACK)->count(),
        ];

        $settings = self::getFeedbackSettings();

        return view('admin.feedback.index', compact('feedbacks', 'stats', 'status', 'type', 'category', 'search', 'settings', 'tab'));
    }

    /**
     * Return details for a single grievance / feedback.
     */
    public function show(FarmerFeedback $feedback): JsonResponse
    {
        $feedback->load('resolvedBy:id,name');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $feedback->id,
                'ticket_no' => $feedback->ticket_no,
                'type' => $feedback->type,
                'category' => $feedback->category,
                'category_label' => $feedback->category_label,
                'rating' => $feedback->rating,
                'crop_name' => $feedback->crop_name,
                'district' => $feedback->district,
                'market_name' => $feedback->market_name,
                'message' => $feedback->message,
                'voice_url' => $feedback->voice_url,
                'voice_duration' => $feedback->voice_duration,
                'photo_url' => $feedback->photo_url,
                'farmer_name' => $feedback->farmer_name,
                'farmer_phone' => $feedback->farmer_phone,
                'farmer_email' => $feedback->farmer_email,
                'status' => $feedback->status,
                'admin_notes' => $feedback->admin_notes,
                'resolved_at' => $feedback->resolved_at?->format('M d, Y H:i'),
                'resolved_by' => $feedback->resolvedBy?->name,
                'created_at' => $feedback->created_at->format('M d, Y H:i'),
                'whatsapp_url' => $feedback->getWhatsAppDirectUrl(),
            ]
        ]);
    }

    /**
     * Update the status and admin notes for a feedback/issue.
     */
    public function update(Request $request, FarmerFeedback $feedback): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:new,in_review,resolved,rejected',
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        $feedback->status = $validated['status'];
        $feedback->admin_notes = $validated['admin_notes'];

        if ($validated['status'] === FarmerFeedback::STATUS_RESOLVED) {
            $feedback->resolved_at = now();
            $feedback->resolved_by = auth()->id();
        } elseif ($validated['status'] === FarmerFeedback::STATUS_NEW) {
            $feedback->resolved_at = null;
            $feedback->resolved_by = null;
        }

        $feedback->save();

        return redirect()->back()->with('success', "Ticket #{$feedback->ticket_no} updated successfully.");
    }

    /**
     * Update form configuration settings and categories.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enable_voice' => 'nullable|boolean',
            'max_voice_seconds' => 'required|integer|in:60,120,180,300',
            'enable_photos' => 'nullable|boolean',
            'enable_email' => 'nullable|boolean',
            'support_whatsapp' => 'required|string|max:20',
            'notification_email' => 'nullable|email|max:120',
            'issue_categories' => 'required|array',
            'issue_categories.*.id' => 'required|string|max:50',
            'issue_categories.*.icon' => 'required|string|max:10',
            'issue_categories.*.label_en' => 'required|string|max:100',
            'issue_categories.*.label_kn' => 'required|string|max:100',
            'issue_categories.*.is_active' => 'nullable|boolean',
            'feedback_categories' => 'required|array',
            'feedback_categories.*.id' => 'required|string|max:50',
            'feedback_categories.*.icon' => 'required|string|max:10',
            'feedback_categories.*.label_en' => 'required|string|max:100',
            'feedback_categories.*.label_kn' => 'required|string|max:100',
            'feedback_categories.*.is_active' => 'nullable|boolean',
        ]);

        SystemSetting::set('feedback_enable_voice', (bool)($request->boolean('enable_voice')), 'boolean', 'feedback', 'Allow voice note recording in feedback & issue forms');
        SystemSetting::set('feedback_max_voice_seconds', (int)$validated['max_voice_seconds'], 'integer', 'feedback', 'Maximum voice recording seconds');
        SystemSetting::set('feedback_enable_photos', (bool)($request->boolean('enable_photos')), 'boolean', 'feedback', 'Allow photo/receipt upload');
        SystemSetting::set('feedback_enable_email', (bool)($request->boolean('enable_email')), 'boolean', 'feedback', 'Show email input on farmer forms');
        SystemSetting::set('feedback_support_whatsapp', $validated['support_whatsapp'], 'string', 'feedback', 'Admin Support WhatsApp number');
        SystemSetting::set('feedback_notification_email', $validated['notification_email'] ?? '', 'string', 'feedback', 'Notification email for new grievances');

        // Clean & format categories
        $issueCats = array_map(function ($cat) {
            return [
                'id' => trim($cat['id']),
                'icon' => trim($cat['icon']),
                'label_en' => trim($cat['label_en']),
                'label_kn' => trim($cat['label_kn']),
                'is_active' => !empty($cat['is_active']),
            ];
        }, array_values($validated['issue_categories']));

        $feedbackCats = array_map(function ($cat) {
            return [
                'id' => trim($cat['id']),
                'icon' => trim($cat['icon']),
                'label_en' => trim($cat['label_en']),
                'label_kn' => trim($cat['label_kn']),
                'is_active' => !empty($cat['is_active']),
            ];
        }, array_values($validated['feedback_categories']));

        SystemSetting::set('feedback_issue_categories', $issueCats, 'json', 'feedback', 'Configured Issue categories');
        SystemSetting::set('feedback_feedback_categories', $feedbackCats, 'json', 'feedback', 'Configured Feedback categories');

        return redirect()->route('admin.feedback.index', ['tab' => 'settings'])
            ->with('success', 'Feedback & Report Issues form settings updated successfully!');
    }

    /**
     * Reset form settings to defaults.
     */
    public function resetSettings(): RedirectResponse
    {
        SystemSetting::set('feedback_enable_voice', true, 'boolean', 'feedback');
        SystemSetting::set('feedback_max_voice_seconds', 180, 'integer', 'feedback');
        SystemSetting::set('feedback_enable_photos', true, 'boolean', 'feedback');
        SystemSetting::set('feedback_enable_email', true, 'boolean', 'feedback');
        SystemSetting::set('feedback_support_whatsapp', '919876543210', 'string', 'feedback');
        SystemSetting::set('feedback_notification_email', '', 'string', 'feedback');
        SystemSetting::set('feedback_issue_categories', self::defaultIssueCategories(), 'json', 'feedback');
        SystemSetting::set('feedback_feedback_categories', self::defaultFeedbackCategories(), 'json', 'feedback');

        return redirect()->route('admin.feedback.index', ['tab' => 'settings'])
            ->with('success', 'Form settings and categories reset to system defaults.');
    }

    /**
     * Remove the feedback/issue from storage.
     */
    public function destroy(FarmerFeedback $feedback): RedirectResponse
    {
        $ticketNo = $feedback->ticket_no;

        // Clean up stored voice note if exists
        if ($feedback->voice_path && Storage::disk('public')->exists($feedback->voice_path)) {
            Storage::disk('public')->delete($feedback->voice_path);
        }

        // Clean up stored photo if exists
        if ($feedback->photo_path && Storage::disk('public')->exists($feedback->photo_path)) {
            Storage::disk('public')->delete($feedback->photo_path);
        }

        $feedback->delete();

        return redirect()->back()->with('success', "Ticket #{$ticketNo} and associated files deleted.");
    }
}
