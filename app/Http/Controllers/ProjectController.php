<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Video;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
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
}
