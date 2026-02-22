<?php

namespace App\Http\Controllers;

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
            'processing' => Video::whereIn('status', ['pending', 'processing'])->count(),
            'failed' => Video::where('status', 'failed')->count(),
        ];

        return view('videos.index', compact('videos', 'stats'));
    }

    /**
     * Show the create video form
     */
    public function create(Request $request)
    {
        $projects = Project::orderBy('name')->get();
        $selectedProject = $request->get('project');
        return view('videos.create', compact('projects', 'selectedProject'));
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

        // Create video record
        $video = Video::create([
            'title' => $validated['title'],
            'prompt' => $validated['prompt'],
            'status' => 'pending',
            'resolution' => $validated['resolution'],
            'duration' => $validated['duration'],
            'project_id' => $validated['project_id'] ?? null,
            'metadata' => [
                'aspect_ratio' => $validated['aspect_ratio'],
                'resolution' => $validated['resolution'],
            ],
        ]);

        // Prepare options
        $options = [
            'aspect_ratio' => $validated['aspect_ratio'],
            'duration' => (int)$validated['duration'],
            'resolution' => $validated['resolution'],
        ];

        // Handle reference image
        if ($request->hasFile('reference_image')) {
            $imageContent = file_get_contents($request->file('reference_image')->getRealPath());
            $options['image_base64'] = base64_encode($imageContent);
            $options['image_mime_type'] = $request->file('reference_image')->getMimeType();
        }

        // Call AI Studio API
        $result = $this->aiService->generateVideo($validated['prompt'], $options);

        if ($result['success']) {
            $video->update([
                'status' => 'processing',
                'google_operation_name' => $result['operation_name'],
            ]);

            return redirect()->route('videos.show', $video)
                ->with('success', 'Video đang được tạo! Quá trình này có thể mất vài phút.');
        } else {
            $video->update([
                'status' => 'failed',
                'error_message' => $result['error'] ?? 'Lỗi không xác định',
            ]);

            return redirect()->route('videos.show', $video)
                ->with('error', 'Lỗi khi gửi yêu cầu tạo video: ' . ($result['error'] ?? 'Lỗi không xác định'));
        }
    }

    /**
     * Display a specific video
     */
    public function show(Video $video)
    {
        return view('videos.show', compact('video'));
    }

    /**
     * Check video generation status (AJAX)
     */
    public function checkStatus(Video $video)
    {
        if (!$video->google_operation_name || $video->status === 'completed') {
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

        $options = [
            'aspect_ratio' => $video->metadata['aspect_ratio'] ?? '16:9',
            'duration' => $video->duration ?? 8,
            'resolution' => $video->metadata['resolution'] ?? $video->resolution ?? '720p',
        ];

        $result = $this->aiService->generateVideo($video->prompt, $options);

        if ($result['success']) {
            $video->update([
                'status' => 'processing',
                'google_operation_name' => $result['operation_name'],
                'error_message' => null,
            ]);

            return redirect()->route('videos.show', $video)
                ->with('success', 'Đang thử tạo lại video!');
        }

        $video->update([
            'error_message' => $result['error'] ?? 'Lỗi không xác định',
        ]);

        return back()->with('error', 'Thử lại thất bại: ' . ($result['error'] ?? 'Lỗi không xác định'));
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

        return \Illuminate\Support\Facades\Storage::disk('public')->download(
            $video->video_path,
            Str::slug($video->title) . '.mp4'
        );
    }
}
