<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\GoogleAIStudioService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 20;
    public int $timeout = 300; // 5 phút (bao gồm sleep)
    public bool $deleteWhenMissingModels = true;

    const RPM_LIMIT = 2;
    const RPD_LIMIT = 10;
    const MIN_INTERVAL = 35;       // Tối thiểu 35s giữa 2 API calls
    const LAST_CALL_KEY = 'video_generate_last_api_call';
    const CACHE_KEY_RPM = 'google_api_rpm';
    const CACHE_KEY_RPD = 'google_api_rpd';

    public function __construct(
        public Video $video,
        public array $options = [],
    ) {
        $this->onQueue('video-generate');
    }

    /**
     * Middleware: WithoutOverlapping đảm bảo CHỈ 1 JOB chạy tại 1 thời điểm.
     * Key 'global-video-generate' là chung cho tất cả video → tuần tự tuyệt đối.
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('global-video-generate'))
                ->releaseAfter(self::MIN_INTERVAL + 5) // Release lại queue sau 40s nếu lock busy
                ->expireAfter(300),                      // Lock tự hết hạn sau 5 phút
        ];
    }

    public function handle(GoogleAIStudioService $aiService): void
    {
        // Skip if video is no longer pending/queued
        if (!in_array($this->video->status, ['pending', 'queued'])) {
            Log::info('GenerateVideoJob: Skipping - status is ' . $this->video->status, [
                'video_id' => $this->video->id,
            ]);
            return;
        }

        // === Enforce minimum interval giữa 2 API calls ===
        $this->enforceMinInterval();

        // === Check daily rate limit ===
        $dailyKey = self::CACHE_KEY_RPD . ':' . date('Y-m-d');
        $dailyCalls = (int) Cache::get($dailyKey, 0);

        if ($dailyCalls >= self::RPD_LIMIT) {
            Log::warning('GenerateVideoJob: Daily limit reached', [
                'video_id' => $this->video->id,
                'daily_calls' => $dailyCalls,
            ]);
            $this->video->update([
                'status' => 'failed',
                'error_message' => 'Đã đạt giới hạn ' . self::RPD_LIMIT . ' video/ngày. Vui lòng thử lại vào ngày mai.',
            ]);
            return;
        }

        // === Check per-minute rate limit (sleep thay vì release để giữ lock) ===
        $minuteCalls = (int) Cache::get(self::CACHE_KEY_RPM, 0);
        if ($minuteCalls >= self::RPM_LIMIT) {
            Log::info('GenerateVideoJob: RPM limit, sleeping ' . self::MIN_INTERVAL . 's...', [
                'video_id' => $this->video->id,
                'rpm' => $minuteCalls,
            ]);
            sleep(self::MIN_INTERVAL);
        }

        // === Increment counters (trong WithoutOverlapping lock → an toàn) ===
        $newRpm = Cache::increment(self::CACHE_KEY_RPM);
        Cache::put(self::CACHE_KEY_RPM, $newRpm, now()->addSeconds(60));

        $newDaily = Cache::increment($dailyKey);
        Cache::put($dailyKey, $newDaily, now()->endOfDay());

        // Ghi lại thời điểm gọi API
        Cache::put(self::LAST_CALL_KEY, now()->timestamp, now()->addMinutes(5));

        // Update status
        $this->video->update(['status' => 'processing']);

        Log::info('GenerateVideoJob: === CALLING API ===', [
            'video_id' => $this->video->id,
            'title' => $this->video->title,
            'attempt' => $this->attempts(),
            'rpm' => $newRpm . '/' . self::RPM_LIMIT,
            'rpd' => $newDaily . '/' . self::RPD_LIMIT,
        ]);

        // Load reference image
        $options = $this->options;
        if (!empty($options['reference_image_path'])) {
            $imagePath = $options['reference_image_path'];
            if (Storage::disk('public')->exists($imagePath)) {
                $options['image_base64'] = base64_encode(Storage::disk('public')->get($imagePath));
                Storage::disk('public')->delete($imagePath);
            }
            unset($options['reference_image_path']);
        }

        // Call API
        $prompt = $this->video->metadata['full_prompt'] ?? $this->video->prompt;
        $result = $aiService->generateVideo($prompt, $options);

        if ($result['success']) {
            $this->video->update([
                'status' => 'processing',
                'google_operation_name' => $result['operation_name'],
            ]);

            Log::info('GenerateVideoJob: API success', [
                'video_id' => $this->video->id,
                'operation_name' => $result['operation_name'],
            ]);
        } else {
            $errorMessage = $result['error'] ?? 'Lỗi không xác định';
            $statusCode = $result['status_code'] ?? null;

            if ($statusCode === 429) {
                Log::warning('GenerateVideoJob: Google 429 - rate limited', [
                    'video_id' => $this->video->id,
                ]);

                // Rollback counters
                Cache::decrement(self::CACHE_KEY_RPM);
                Cache::decrement($dailyKey);

                $this->video->update(['status' => 'queued']);
                sleep(self::MIN_INTERVAL * 2);
                $this->release(self::MIN_INTERVAL);
                return;
            }

            $this->video->update([
                'status' => 'failed',
                'error_message' => $errorMessage,
            ]);

            Log::error('GenerateVideoJob: API failed', [
                'video_id' => $this->video->id,
                'error' => $errorMessage,
                'status_code' => $statusCode,
            ]);
        }

        // === Sleep sau khi xong để đảm bảo khoảng cách với job tiếp theo ===
        Log::info('GenerateVideoJob: Done. Sleeping ' . self::MIN_INTERVAL . 's before releasing lock.');
        sleep(self::MIN_INTERVAL);
    }

    /**
     * Đảm bảo tối thiểu MIN_INTERVAL giây kể từ lần gọi API trước.
     */
    protected function enforceMinInterval(): void
    {
        $lastCallTimestamp = Cache::get(self::LAST_CALL_KEY);
        if ($lastCallTimestamp) {
            $elapsed = time() - (int) $lastCallTimestamp;
            $waitTime = self::MIN_INTERVAL - $elapsed;

            if ($waitTime > 0) {
                Log::info('GenerateVideoJob: Enforcing interval, sleeping ' . $waitTime . 's', [
                    'video_id' => $this->video->id,
                ]);
                sleep((int) ceil($waitTime));
            }
        }
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('GenerateVideoJob: Job FAILED permanently', [
            'video_id' => $this->video->id,
            'error' => $exception?->getMessage(),
        ]);

        $this->video->update([
            'status' => 'failed',
            'error_message' => 'Lỗi hàng đợi: ' . ($exception?->getMessage() ?? 'Không xác định'),
        ]);
    }

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
