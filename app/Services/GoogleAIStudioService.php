<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GoogleAIStudioService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services_google.ai_studio.api_key');
        $this->baseUrl = config('services_google.ai_studio.base_url');
        $this->model = config('services_google.ai_studio.model');
    }

    /**
     * Generate a video using Google AI Studio (Veo API)
     *
     * @param string $prompt The text prompt describing the video
     * @param array $options Additional options (aspectRatio, personGeneration, etc.)
     * @return array Response with operation name for polling
     */
    public function generateVideo(string $prompt, array $options = []): array
    {
        $url = "{$this->baseUrl}/v1beta/models/{$this->model}:predictLongRunning";

        $payload = [
            'instances' => [
                [
                    'prompt' => $prompt,
                ],
            ],
            'parameters' => [
                'aspectRatio' => $options['aspect_ratio'] ?? '16:9',
                'sampleCount' => $options['sample_count'] ?? 1,
                'durationSeconds' => $options['duration'] ?? 8,
                'resolution' => $options['resolution'] ?? '720p',
            ],
        ];

        // If an image is provided for image-to-video
        if (!empty($options['image_base64'])) {
            $payload['instances'][0]['image'] = [
                'bytesBase64Encoded' => $options['image_base64'],
                'mimeType' => $options['image_mime_type'] ?? 'image/png',
            ];
        }

        try {
            $response = Http::timeout(120)
                ->withQueryParameters(['key' => $this->apiKey])
                ->post($url, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'operation_name' => $response->json('name'),
                    'data' => $response->json(),
                ];
            }

            Log::error('Google AI Studio API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('error.message', 'Lỗi không xác định từ API'),
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Google AI Studio Exception', ['message' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check the status of a video generation operation
     *
     * @param string $operationName The operation name returned from generateVideo
     * @return array Status and video data if completed
     */
    public function checkOperationStatus(string $operationName): array
    {
        $url = "{$this->baseUrl}/v1beta/{$operationName}";

        try {
            $response = Http::timeout(30)
                ->withQueryParameters(['key' => $this->apiKey])
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $done = $data['done'] ?? false;

                if ($done && isset($data['response'])) {
                    return [
                        'success' => true,
                        'done' => true,
                        'videos' => $data['response']['generateVideoResponse']['generatedSamples'] ?? [],
                        'data' => $data,
                    ];
                }

                return [
                    'success' => true,
                    'done' => false,
                    'data' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('error.message', 'Lỗi kiểm tra trạng thái'),
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Google AI Studio Status Check Exception', ['message' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Download and save a generated video from a URI
     *
     * @param string $videoUri The video URI returned by the API
     * @param string $filename The filename to save as
     * @return string|null The storage path or null on failure
     */
    public function saveVideoFromUri(string $videoUri, string $filename): ?string
    {
        try {
            // Append API key to the video URI
            $separator = str_contains($videoUri, '?') ? '&' : '?';
            $downloadUrl = "{$videoUri}{$separator}key={$this->apiKey}";

            $response = Http::timeout(300)->withOptions(['stream' => false])->get($downloadUrl);

            if ($response->successful()) {
                $path = "videos/{$filename}";
                Storage::disk('public')->put($path, $response->body());
                return $path;
            }

            Log::error('Failed to download video from URI', [
                'uri' => $videoUri,
                'status' => $response->status(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Failed to save video from URI', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Download and save a generated video from base64 data (legacy fallback)
     *
     * @param string $base64Data The base64 encoded video data
     * @param string $filename The filename to save as
     * @return string|null The storage path or null on failure
     */
    public function saveVideo(string $base64Data, string $filename): ?string
    {
        try {
            $videoData = base64_decode($base64Data);
            $path = "videos/{$filename}";
            Storage::disk('public')->put($path, $videoData);
            return $path;
        } catch (\Exception $e) {
            Log::error('Failed to save video', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * List available models
     */
    public function listModels(): array
    {
        $url = "{$this->baseUrl}/v1beta/models";

        try {
            $response = Http::timeout(30)
                ->withQueryParameters(['key' => $this->apiKey])
                ->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'models' => $response->json('models', []),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('error.message', 'Lỗi lấy danh sách models'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
