@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('breadcrumb')
    <a href="{{ route('home') }}">Trang chủ</a> <span class="mx-1">/</span> Dashboard
@endsection

@section('content')
    {{-- Page Hero --}}
    <div class="page-hero">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1><i class="bi bi-camera-reels me-2"></i>AI Video Creator</h1>
                <p>Tạo video chuyên nghiệp bằng AI với Google AI Studio (Veo 3.1)</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="{{ route('videos.create') }}" class="btn btn-cta btn-lg px-4">
                    <i class="bi bi-plus-lg"></i> Tạo Video Mới
                </a>
                <div class="mt-2" style="font-size: 0.75rem;">
                    <span style="color: rgba(255,255,255,0.7);">
                        <i class="bi bi-speedometer2 me-1"></i>API:
                        <strong style="color: {{ $rateLimit['rpd_remaining'] > 3 ? '#4ade80' : ($rateLimit['rpd_remaining'] > 0 ? '#fbbf24' : '#f87171') }};">{{ $rateLimit['rpd_used'] }}/{{ $rateLimit['rpd_limit'] }}</strong> hôm nay
                        &bull;
                        <strong style="color: {{ $rateLimit['rpm_remaining'] > 0 ? '#4ade80' : '#fbbf24' }};">{{ $rateLimit['rpm_used'] }}/{{ $rateLimit['rpm_limit'] }}</strong> /phút
                    </span>
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

    {{-- Video Grid --}}
    <div class="card-panel">
        <div class="card-panel-header">
            <h5><i class="bi bi-grid-3x3-gap me-2"></i>Danh sách Video</h5>
            <a href="{{ route('videos.create') }}" class="btn btn-primary-dark btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Tạo mới
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
                                            @if(in_array($video->status, ['processing', 'pending']))
                                                <div class="spinner-border spinner-accent" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            @elseif($video->status === 'queued')
                                                <i class="bi bi-hourglass-split" style="color: var(--navy-300); font-size: 2rem;"></i>
                                            @elseif($video->status === 'failed')
                                                <i class="bi bi-exclamation-triangle-fill"></i>
                                            @else
                                                <i class="bi bi-film"></i>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="video-thumb-badge">
                                        <span class="badge badge-status bg-{{ $video->status_badge }} {{ in_array($video->status, ['queued', 'pending', 'processing']) ? 'badge-pulse' : '' }}">
                                            {{ $video->status_label }}
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title text-truncate">{{ $video->title }}</h6>
                                    @if($video->project)
                                        <div class="mb-1">
                                            <a href="{{ route('projects.show', $video->project) }}" class="badge text-decoration-none" style="background: {{ $video->project->color }}20; color: {{ $video->project->color }}; font-size: 0.7rem; font-weight: 600;">
                                                <i class="bi bi-folder-fill me-1"></i>{{ $video->project->name }}
                                            </a>
                                        </div>
                                    @endif
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
                    <h5>Chưa có video nào</h5>
                    <p>Bắt đầu tạo video đầu tiên của bạn bằng AI. Chỉ cần mô tả ý tưởng!</p>
                    <a href="{{ route('videos.create') }}" class="btn btn-primary-dark btn-lg">
                        <i class="bi bi-plus-lg me-2"></i>Tạo Video Ngay
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
