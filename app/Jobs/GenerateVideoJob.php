<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\GoogleAIStudioService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 10;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 180;

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Rate limit constants
     */
    const RPM_LIMIT = 2;       // Requests per minute
    const RPD_LIMIT = 10;      // Requests per day
    const RPM_DELAY = 35;      // Seconds to wait when RPM exceeded (35s buffer)
    const CACHE_KEY_RPM = 'google_api_rpm';
    const CACHE_KEY_RPD = 'google_api_rpd';

    public function __construct(
        public Video $video,
        public array $options = [],
    ) {
        $this->onQueue('video-generate');
    }

    /**
     * Execute the job.
     */
    public function handle(GoogleAIStudioService $aiService): void
    {
        // Skip if video is no longer pending/queued
        if (!in_array($this->video->status, ['pending', 'queued'])) {
            Log::info('GenerateVideoJob: Skipping - video status is ' . $this->video->status, [
                'video_id' => $this->video->id,
            ]);
            return;
        }

        // Check daily rate limit
        $dailyCalls = (int) Cache::get(self::CACHE_KEY_RPD . ':' . date('Y-m-d'), 0);
        if ($dailyCalls >= self::RPD_LIMIT) {
            Log::warning('GenerateVideoJob: Daily rate limit reached', [
                'video_id' => $this->video->id,
                'daily_calls' => $dailyCalls,
                'limit' => self::RPD_LIMIT,
            ]);

            $this->video->update([
                'status' => 'failed',
                'error_message' => 'Đã đạt giới hạn ' . self::RPD_LIMIT . ' video/ngày. Vui lòng thử lại vào ngày mai.',
            ]);
            return;
        }

        // Check per-minute rate limit
        $minuteCalls = (int) Cache::get(self::CACHE_KEY_RPM, 0);
        if ($minuteCalls >= self::RPM_LIMIT) {
            $retryAfter = self::RPM_DELAY;
            Log::info('GenerateVideoJob: RPM limit reached, releasing back to queue', [
                'video_id' => $this->video->id,
                'minute_calls' => $minuteCalls,
                'retry_after_seconds' => $retryAfter,
                'attempt' => $this->attempts(),
            ]);

            $this->release($retryAfter);
            return;
        }

        // Track API call BEFORE making the request (optimistic lock)
        Cache::increment(self::CACHE_KEY_RPM);
        Cache::put(self::CACHE_KEY_RPM, (int) Cache::get(self::CACHE_KEY_RPM, 1), now()->addSeconds(60));

        $dailyKey = self::CACHE_KEY_RPD . ':' . date('Y-m-d');
        Cache::increment($dailyKey);
        Cache::put($dailyKey, (int) Cache::get($dailyKey, 1), now()->endOfDay());

        // Update status to processing
        $this->video->update(['status' => 'processing']);

        Log::info('GenerateVideoJob: Calling API', [
            'video_id' => $this->video->id,
            'title' => $this->video->title,
            'attempt' => $this->attempts(),
            'rpm_count' => $minuteCalls + 1,
            'rpd_count' => $dailyCalls + 1,
        ]);

        // Load reference image from storage if saved during upload
        $options = $this->options;
        if (!empty($options['reference_image_path'])) {
            $imagePath = $options['reference_image_path'];
            if (Storage::disk('public')->exists($imagePath)) {
                $options['image_base64'] = base64_encode(Storage::disk('public')->get($imagePath));
                // Clean up temp file
                Storage::disk('public')->delete($imagePath);
            }
            unset($options['reference_image_path']);
        }

        // Call AI Studio API - use full prompt with master character if available
        $prompt = $this->video->metadata['full_prompt'] ?? $this->video->prompt;

        $result = $aiService->generateVideo($prompt, $options);

        if ($result['success']) {
            $this->video->update([
                'status' => 'processing',
                'google_operation_name' => $result['operation_name'],
            ]);

            Log::info('GenerateVideoJob: API call successful', [
                'video_id' => $this->video->id,
                'operation_name' => $result['operation_name'],
            ]);
        } else {
            $errorMessage = $result['error'] ?? 'Lỗi không xác định';
            $statusCode = $result['status_code'] ?? null;

            // If rate limited by Google (429), release back to queue
            if ($statusCode === 429) {
                Log::warning('GenerateVideoJob: Google API returned 429, releasing to queue', [
                    'video_id' => $this->video->id,
                    'error' => $errorMessage,
                ]);

                $this->video->update(['status' => 'queued']);
                $this->release(self::RPM_DELAY * 2);
                return;
            }

            $this->video->update([
                'status' => 'failed',
                'error_message' => $errorMessage,
            ]);

            Log::error('GenerateVideoJob: API call failed', [
                'video_id' => $this->video->id,
                'error' => $errorMessage,
                'status_code' => $statusCode,
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('GenerateVideoJob: Job failed permanently', [
            'video_id' => $this->video->id,
            'error' => $exception?->getMessage(),
        ]);

        $this->video->update([
            'status' => 'failed',
            'error_message' => 'Lỗi hàng đợi: ' . ($exception?->getMessage() ?? 'Không xác định'),
        ]);
    }

    /**
     * Get current rate limit status (static helper)
     */
    public static function getRateLimitStatus(): array
    {
        $minuteCalls = (int) Cache::get(self::CACHE_KEY_RPM, 0);
        $dailyCalls = (int) Cache::get(self::CACHE_KEY_RPD . ':' . date('Y-m-d'), 0);

        return [
            'rpm_used' => $minuteCalls,
            'rpm_limit' => self::RPM_LIMIT,
            'rpm_remaining' => max(0, self::RPM_LIMIT - $minuteCalls),
            'rpd_used' => $dailyCalls,
            'rpd_limit' => self::RPD_LIMIT,
            'rpd_remaining' => max(0, self::RPD_LIMIT - $dailyCalls),
            'can_generate' => $dailyCalls < self::RPD_LIMIT,
        ];
    }
}
