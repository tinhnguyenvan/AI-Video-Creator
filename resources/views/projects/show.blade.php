@extends('layouts.app')

@section('title', $project->name)
@section('page-title', $project->name)
@section('breadcrumb')
    <a href="{{ route('home') }}">Trang chủ</a> <span class="mx-1">/</span> <a href="{{ route('projects.index') }}">Dự án</a> <span class="mx-1">/</span> {{ Str::limit($project->name, 30) }}
@endsection

@section('content')
    {{-- Project Header --}}
    <div class="page-hero" style="border-left: 5px solid {{ $project->color }};">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div style="width: 48px; height: 48px; border-radius: 12px; background: {{ $project->color }}25; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="bi bi-folder-fill" style="color: {{ $project->color }}; font-size: 1.3rem;"></i>
                    </div>
                    <div>
                        <h1 class="mb-0">{{ $project->name }}</h1>
                        @if($project->description)
                            <p class="mb-0 mt-1" style="font-size: 0.85rem;">{{ $project->description }}</p>
                        @endif
                        @if($project->master_character)
                            <p class="mb-0 mt-1" style="font-size: 0.78rem; color: rgba(255,255,255,0.7);">
                                <i class="bi bi-person-badge me-1"></i>{{ Str::limit($project->master_character, 80) }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <div class="d-flex gap-2 justify-content-md-end">
                    <a href="{{ route('videos.create', ['project' => $project->id]) }}" class="btn btn-cta px-3">
                        <i class="bi bi-plus-lg"></i> Tạo Video
                    </a>
                    @if($stats['completed'] >= 2)
                        <a href="{{ route('projects.merge', $project) }}" class="btn btn-ghost" style="border-color: rgba(255,255,255,0.2); color: #fff;">
                            <i class="bi bi-layers me-1"></i> Ghép Video
                        </a>
                    @endif
                    <a href="{{ route('projects.edit', $project) }}" class="btn btn-ghost" style="border-color: rgba(255,255,255,0.2); color: #fff;">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <form action="{{ route('projects.destroy', $project) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Bạn có chắc muốn xóa dự án này? Các video sẽ không bị xóa.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-ghost" style="border-color: rgba(255,255,255,0.2); color: #fca5a5;">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="bi bi-collection-play-fill"></i></div>
                <div class="stat-info">
                    <div class="stat-label">Tổng video</div>
                    <div class="stat-value">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-info">
                    <div class="stat-label">Hoàn thành</div>
                    <div class="stat-value">{{ $stats['completed'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon yellow"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-info">
                    <div class="stat-label">Đang xử lý</div>
                    <div class="stat-value">{{ $stats['processing'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon red"><i class="bi bi-x-circle-fill"></i></div>
                <div class="stat-info">
                    <div class="stat-label">Thất bại</div>
                    <div class="stat-value">{{ $stats['failed'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Videos Grid --}}
    <div class="card-panel">
        <div class="card-panel-header">
            <h5><i class="bi bi-grid-3x3-gap me-2"></i>Video trong dự án</h5>
            <a href="{{ route('videos.create', ['project' => $project->id]) }}" class="btn btn-primary-dark btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Tạo video mới
            </a>
        </div>
        <div class="card-panel-body">
            @if($videos->count() > 0)
                <div class="row g-4">
                    @foreach($videos as $video)
                        <div class="col-md-6 col-xl-4">
                            <div class="video-grid-card h-100">
                                <div class="video-thumb">
                                    @if($video->video_path)
                                        <video muted preload="metadata">
                                            <source src="{{ asset('storage/' . $video->video_path) }}" type="video/mp4">
                                        </video>
                                    @else
                                        <div class="video-thumb-placeholder {{ $video->status === 'failed' ? 'failed' : ($video->status !== 'completed' ? 'processing' : '') }}">
                                            @if($video->status === 'processing' || $video->status === 'pending')
                                                <div class="spinner-border spinner-accent" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            @elseif($video->status === 'failed')
                                                <i class="bi bi-exclamation-triangle-fill"></i>
                                            @else
                                                <i class="bi bi-film"></i>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="video-thumb-badge">
                                        <span class="badge badge-status bg-{{ $video->status_badge }} {{ in_array($video->status, ['pending', 'processing']) ? 'badge-pulse' : '' }}">
                                            {{ $video->status_label }}
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title text-truncate">{{ $video->title }}</h6>
                                    <p class="card-text mb-0" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        {{ $video->prompt }}
                                    </p>
                                </div>
                                <div class="card-footer">
                                    <small class="text-muted" style="font-size: 0.75rem;">
                                        <i class="bi bi-clock me-1"></i>{{ $video->created_at->diffForHumans() }}
                                    </small>
                                    <a href="{{ route('videos.show', $video) }}" class="btn btn-navy btn-sm" style="font-size: 0.75rem; padding: 0.3rem 0.75rem;">
                                        Chi tiết <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 d-flex justify-content-center">
                    {{ $videos->links() }}
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-camera-reels"></i>
                    </div>
                    <h5>Chưa có video nào trong dự án</h5>
                    <p>Tạo video đầu tiên cho dự án "{{ $project->name }}"</p>
                    <a href="{{ route('videos.create', ['project' => $project->id]) }}" class="btn btn-primary-dark btn-lg">
                        <i class="bi bi-plus-lg me-2"></i>Tạo Video Ngay
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
