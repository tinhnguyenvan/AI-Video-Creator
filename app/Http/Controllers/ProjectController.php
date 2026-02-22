<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Video;
use App\Services\VideoMergeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    protected VideoMergeService $mergeService;

    public function __construct(VideoMergeService $mergeService)
    {
        $this->mergeService = $mergeService;
    }
    /**
     * Display listing of all projects
     */
    public function index()
    {
        $projects = Project::withCount([
            'videos',
            'videos as completed_videos_count' => function ($q) {
                $q->where('status', 'completed');
            },
            'videos as processing_videos_count' => function ($q) {
                $q->whereIn('status', ['pending', 'processing']);
            },
        ])->latest()->paginate(12);

        $stats = [
            'total' => Project::count(),
            'active' => Project::whereHas('videos')->count(),
            'total_videos' => Video::count(),
            'completed_videos' => Video::where('status', 'completed')->count(),
        ];

        return view('projects.index', compact('projects', 'stats'));
    }

    /**
     * Show the create project form
     */
    public function create()
    {
        $colors = Project::colorOptions();
        return view('projects.create', compact('colors'));
    }

    /**
     * Store a new project
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'master_character' => 'nullable|string|max:2000',
            'color' => 'required|string|max:7',
        ]);

        $project = Project::create($validated);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Dự án "' . $project->name . '" đã được tạo thành công!');
    }

    /**
     * Display a specific project with its videos
     */
    public function show(Project $project)
    {
        $videos = $project->videos()->latest()->paginate(12);

        $stats = [
            'total' => $project->videos()->count(),
            'completed' => $project->videos()->where('status', 'completed')->count(),
            'processing' => $project->videos()->whereIn('status', ['pending', 'processing'])->count(),
            'failed' => $project->videos()->where('status', 'failed')->count(),
        ];

        return view('projects.show', compact('project', 'videos', 'stats'));
    }

    /**
     * Show the edit project form
     */
    public function edit(Project $project)
    {
        $colors = Project::colorOptions();
        return view('projects.edit', compact('project', 'colors'));
    }

    /**
     * Update a project
     */
    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'master_character' => 'nullable|string|max:2000',
            'color' => 'required|string|max:7',
        ]);

        $project->update($validated);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Dự án đã được cập nhật!');
    }

    /**
     * Delete a project (videos become unassigned)
     */
    public function destroy(Project $project)
    {
        $name = $project->name;
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Dự án "' . $name . '" đã được xóa. Các video vẫn được giữ lại.');
    }

    /**
     * Show the merge videos page for a project
     */
    public function merge(Project $project)
    {
        $completedVideos = $project->videos()
            ->where('status', 'completed')
            ->whereNotNull('video_path')
            ->orderBy('created_at')
            ->get();

        $ffmpegAvailable = $this->mergeService->isAvailable();
        $ffmpegVersion = $this->mergeService->getVersion();

        return view('projects.merge', compact('project', 'completedVideos', 'ffmpegAvailable', 'ffmpegVersion'));
    }

    /**
     * Execute the video merge
     */
    public function executeMerge(Request $request, Project $project)
    {
        $validated = $request->validate([
            'video_ids' => 'required|array|min:2',
            'video_ids.*' => 'required|exists:videos,id',
            'title' => 'required|string|max:255',
            'transition' => 'required|in:none,fade,crossfade',
            'transition_duration' => 'required|numeric|min:0.3|max:2.0',
        ]);

        if (!$this->mergeService->isAvailable()) {
            return back()->with('error', 'FFmpeg chưa được cài đặt trên server.');
        }

        // Get videos in the specified order
        $videos = Video::whereIn('id', $validated['video_ids'])
            ->where('status', 'completed')
            ->whereNotNull('video_path')
            ->get()
            ->sortBy(function ($video) use ($validated) {
                return array_search($video->id, $validated['video_ids']);
            });

        if ($videos->count() < 2) {
            return back()->with('error', 'Cần ít nhất 2 video đã hoàn thành để ghép.');
        }

        $videoPaths = $videos->pluck('video_path')->toArray();
        $outputFilename = Str::slug($validated['title']) . '-merged-' . time() . '.mp4';

        $result = $this->mergeService->mergeVideos(
            $videoPaths,
            $outputFilename,
            $validated['transition'],
            (float) $validated['transition_duration']
        );

        if ($result['success']) {
            // Create a new video record for the merged video
            $mergedVideo = Video::create([
                'title' => $validated['title'],
                'prompt' => 'Video ghép từ ' . $videos->count() . ' video: ' . $videos->pluck('title')->implode(', '),
                'status' => 'completed',
                'video_path' => $result['path'],
                'duration' => $result['duration'] ? (int) ceil($result['duration']) : null,
                'resolution' => $videos->first()->resolution ?? '720p',
                'project_id' => $project->id,
                'metadata' => [
                    'type' => 'merged',
                    'source_video_ids' => $validated['video_ids'],
                    'transition' => $validated['transition'],
                    'transition_duration' => $validated['transition_duration'],
                    'merged_at' => now()->toISOString(),
                    'completed_at' => now()->toISOString(),
                ],
            ]);

            return redirect()->route('videos.show', $mergedVideo)
                ->with('success', 'Ghép video thành công! ' . $videos->count() . ' video đã được gộp thành một.');
        }

        return back()->with('error', $result['error'] ?? 'Lỗi không xác định khi ghép video.');
    }
}
