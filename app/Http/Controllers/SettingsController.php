<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleAIStudioService;

class SettingsController extends Controller
{
    /**
     * Show settings page
     */
    public function index()
    {
        $apiKeyConfigured = !empty(config('services_google.ai_studio.api_key'));
        return view('settings.index', compact('apiKeyConfigured'));
    }

    /**
     * Test API connection
     */
    public function testConnection()
    {
        $service = new GoogleAIStudioService();
        $result = $service->listModels();

        if ($result['success']) {
            $videoModels = collect($result['models'])->filter(function ($model) {
                return str_contains($model['name'] ?? '', 'veo') ||
                       str_contains($model['description'] ?? '', 'video');
            })->values();

            return response()->json([
                'success' => true,
                'message' => 'Kết nối thành công!',
                'models_count' => count($result['models']),
                'video_models' => $videoModels,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Kết nối thất bại: ' . ($result['error'] ?? 'Lỗi không xác định'),
        ]);
    }
}
