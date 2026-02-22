<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateVideoJob;
use App\Models\Project;
use App\Models\Video;
use App\Services\GoogleAIStudioService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VideoController extends Controller
{
    protected GoogleAIStudioService $aiService;

    public function __construct(GoogleAIStudioService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Display the dashboard with video listing
     */
    public function index()
    {
        $videos = Video::with('project')->latest()->paginate(12);
        $stats = [
            'total' => Video::count(),
            'completed' => Video::where('status', 'completed')->count(),
            'processing' => Video::whereIn('status', ['pending', 'processing', 'queued'])->count(),
            'failed' => Video::where('status', 'failed')->count(),
        ];
        $rateLimit = GenerateVideoJob::getRateLimitStatus();

        return view('videos.index', compact('videos', 'stats', 'rateLimit'));
    }

    /**
     * Show the create video form
     */
    public function create(Request $request)
    {
        $projects = Project::orderBy('name')->get();
        $selectedProject = $request->get('project');
        $rateLimit = GenerateVideoJob::getRateLimitStatus();
        return view('videos.create', compact('projects', 'selectedProject', 'rateLimit'));
    }

    /**
     * Store a new video generation request
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'prompt' => 'required|string|max:2000',
            'aspect_ratio' => 'required|in:16:9,9:16,1:1',
            'duration' => 'required|integer|in:5,6,7,8',
            'resolution' => 'required|in:720p,1080p',
            'reference_image' => 'nullable|image|max:10240',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        // Check daily rate limit before creating
        $rateLimit = GenerateVideoJob::getRateLimitStatus();
        if (!$rateLimit['can_generate']) {
            return back()
                ->withInput()
                ->with('error', 'Đã đạt giới hạn ' . $rateLimit['rpd_limit'] . ' video/ngày. Vui lòng thử lại vào ngày mai. (Đã dùng: ' . $rateLimit['rpd_used'] . '/' . $rateLimit['rpd_limit'] . ')');
        }

        // Create video record with queued status
        // Build the full prompt: prepend master character from project if available
        $fullPrompt = $validated['prompt'];
        $masterCharacter = null;
        if (!empty($validated['project_id'])) {
            $project = Project::find($validated['project_id']);
            if ($project && !empty($project->master_character)) {
                $masterCharacter = $project->master_character;
                $fullPrompt = "[Nhân vật chính: {$masterCharacter}]\n\n" . $fullPrompt;
            }
        }

        $video = Video::create([
            'title' => $validated['title'],
            'prompt' => $validated['prompt'],
            'status' => 'queued',
            'resolution' => $validated['resolution'],
            'duration' => $validated['duration'],
            'project_id' => $validated['project_id'] ?? null,
            'metadata' => [
                'aspect_ratio' => $validated['aspect_ratio'],
                'resolution' => $validated['resolution'],
                'queued_at' => now()->toISOString(),
                'master_character' => $masterCharacter,
                'full_prompt' => $masterCharacter ? $fullPrompt : null,
            ],
        ]);

        // Prepare options
        $options = [
            'aspect_ratio' => $validated['aspect_ratio'],
            'duration' => (int)$validated['duration'],
            'resolution' => $validated['resolution'],
        ];

        // Handle reference image - save to storage for queue access
        if ($request->hasFile('reference_image')) {
            $imagePath = $request->file('reference_image')->store('temp/reference-images', 'public');
            $options['reference_image_path'] = $imagePath;
            $options['image_mime_type'] = $request->file('reference_image')->getMimeType();
        }

        // Dispatch to queue with smart delay based on current RPM usage
        $delay = 0;
        if ($rateLimit['rpm_remaining'] <= 0) {
            $delay = GenerateVideoJob::MIN_INTERVAL;
        }

        GenerateVideoJob::dispatch($video, $options)
            ->delay(now()->addSeconds($delay));

        $queuedCount = Video::where('status', 'queued')->count();
        $message = 'Video đã được thêm vào hàng đợi!';
        if ($queuedCount > 1) {
            $message .= ' (Vị trí: #' . $queuedCount . ' trong hàng đợi)';
        }
        $message .= ' Còn lại: ' . $rateLimit['rpd_remaining'] . '/' . $rateLimit['rpd_limit'] . ' video hôm nay.';

        return redirect()->route('videos.show', $video)
            ->with('success', $message);
    }

    /**
     * Display a specific video
     */
    public function show(Video $video)
    {
        return view('videos.show', compact('video'));
    }

    /**
     * Show the edit video form
     */
    public function edit(Video $video)
    {
        $projects = Project::orderBy('name')->get();
        return view('videos.edit', compact('video', 'projects'));
    }

    /**
     * Update the video
     */
    public function update(Request $request, Video $video)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'prompt' => 'required|string|max:2000',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $video->update([
            'title' => $validated['title'],
            'prompt' => $validated['prompt'],
            'project_id' => $validated['project_id'] ?? null,
        ]);

        return redirect()->route('videos.show', $video)
            ->with('success', 'Video đã được cập nhật thành công.');
    }

    /**
     * Check video generation status (AJAX)
     */
    public function checkStatus(Video $video)
    {
        if (!$video->google_operation_name || in_array($video->status, ['completed', 'queued', 'pending'])) {
            return response()->json([
                'status' => $video->status,
                'status_label' => $video->status_label,
                'status_badge' => $video->status_badge,
                'video_url' => $video->video_path ? asset('storage/' . $video->video_path) : null,
            ]);
        }

        $result = $this->aiService->checkOperationStatus($video->google_operation_name);

        if ($result['success'] && $result['done']) {
            // Process completed video
            $samples = $result['videos'] ?? [];
            $filename = Str::slug($video->title) . '-' . $video->id . '.mp4';
            $path = null;

            if (!empty($samples)) {
                $sample = $samples[0];

                // Prefer URI-based download (Veo 3.x response format)
                if (isset($sample['video']['uri'])) {
                    $path = $this->aiService->saveVideoFromUri(
                        $sample['video']['uri'],
                        $filename
                    );
                }
                // Fallback to base64 (Veo 2.x response format)
                elseif (isset($sample['video']['bytesBase64Encoded'])) {
                    $path = $this->aiService->saveVideo(
                        $sample['video']['bytesBase64Encoded'],
                        $filename
                    );
                }
            }

            if ($path) {
                $video->update([
                    'status' => 'completed',
                    'video_path' => $path,
                    'metadata' => array_merge($video->metadata ?? [], [
                        'completed_at' => now()->toISOString(),
                    ]),
                ]);
            } else {
                $video->update([
                    'status' => 'failed',
                    'error_message' => 'API trả về nhưng không thể tải video.',
                ]);
            }
        } elseif (!$result['success']) {
            $video->update([
                'status' => 'failed',
                'error_message' => $result['error'] ?? 'Lỗi kiểm tra trạng thái',
            ]);
        }

        return response()->json([
            'status' => $video->status,
            'status_label' => $video->status_label,
            'status_badge' => $video->status_badge,
            'video_url' => $video->video_path ? asset('storage/' . $video->video_path) : null,
            'error_message' => $video->error_message,
        ]);
    }

    /**
     * Retry a failed video generation
     */
    public function retry(Video $video)
    {
        if ($video->status !== 'failed') {
            return back()->with('error', 'Chỉ có thể thử lại video bị lỗi.');
        }

        $rateLimit = GenerateVideoJob::getRateLimitStatus();
        if (!$rateLimit['can_generate']) {
            return back()->with('error', 'Đã đạt giới hạn ' . $rateLimit['rpd_limit'] . ' video/ngày. Vui lòng thử lại vào ngày mai.');
        }

        $options = [
            'aspect_ratio' => $video->metadata['aspect_ratio'] ?? '16:9',
            'duration' => $video->duration ?? 8,
            'resolution' => $video->metadata['resolution'] ?? $video->resolution ?? '720p',
        ];

        // Reset status and dispatch to queue
        $video->update([
            'status' => 'queued',
            'error_message' => null,
        ]);

        $delay = $rateLimit['rpm_remaining'] <= 0 ? GenerateVideoJob::MIN_INTERVAL : 0;
        GenerateVideoJob::dispatch($video, $options)
            ->delay(now()->addSeconds($delay));

        return redirect()->route('videos.show', $video)
            ->with('success', 'Video đã được thêm vào hàng đợi để thử lại! Còn lại: ' . $rateLimit['rpd_remaining'] . '/' . $rateLimit['rpd_limit'] . ' video hôm nay.');
    }

    /**
     * Delete a video
     */
    public function destroy(Video $video)
    {
        if ($video->video_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($video->video_path);
        }
        if ($video->thumbnail_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($video->thumbnail_path);
        }

        $video->delete();

        return redirect()->route('videos.index')
            ->with('success', 'Video đã được xóa thành công.');
    }

    /**
     * Download a video
     */
    public function download(Video $video)
    {
        if (!$video->video_path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($video->video_path)) {
            return back()->with('error', 'File video không tồn tại.');
        }

        $filePath = \Illuminate\Support\Facades\Storage::disk('public')->path($video->video_path);
        return response()->download(
            $filePath,
            Str::slug($video->title) . '.mp4'
        );
    }
}
