@extends('layouts.app')

@section('title', 'Dự án')
@section('page-title', 'Dự án')
@section('breadcrumb')
    <a href="{{ route('home') }}">Trang chủ</a> <span class="mx-1">/</span> Dự án
@endsection

@section('content')
    {{-- Page Hero --}}
    <div class="page-hero">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1><i class="bi bi-folder2-open me-2"></i>Quản lý Dự án</h1>
                <p>Tổ chức và quản lý video theo từng dự án</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="{{ route('projects.create') }}" class="btn btn-cta btn-lg px-4">
                    <i class="bi bi-plus-lg"></i> Tạo Dự Án Mới
                </a>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="bi bi-folder-fill"></i></div>
                <div class="stat-info">
                    <div class="stat-label">Tổng dự án</div>
                    <div class="stat-value">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="bi bi-folder-check"></i></div>
                <div class="stat-info">
                    <div class="stat-label">Đang hoạt động</div>
                    <div class="stat-value">{{ $stats['active'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon yellow"><i class="bi bi-collection-play-fill"></i></div>
                <div class="stat-info">
                    <div class="stat-label">Tổng video</div>
                    <div class="stat-value">{{ $stats['total_videos'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-info">
                    <div class="stat-label">Video hoàn thành</div>
                    <div class="stat-value">{{ $stats['completed_videos'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Projects Grid --}}
    <div class="card-panel">
        <div class="card-panel-header">
            <h5><i class="bi bi-grid-3x3-gap me-2"></i>Danh sách Dự án</h5>
            <a href="{{ route('projects.create') }}" class="btn btn-primary-dark btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Tạo mới
            </a>
        </div>
        <div class="card-panel-body">
            @if($projects->count() > 0)
                <div class="row g-4">
                    @foreach($projects as $project)
                        <div class="col-md-6 col-xl-4">
                            <a href="{{ route('projects.show', $project) }}" class="text-decoration-none">
                                <div class="card-panel h-100" style="border-left: 4px solid {{ $project->color }}; transition: all 0.25s ease; cursor: pointer;"
                                     onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.08)';"
                                     onmouseout="this.style.transform=''; this.style.boxShadow='';">
                                    <div class="card-panel-body">
                                        <div class="d-flex align-items-start justify-content-between mb-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div style="width: 44px; height: 44px; border-radius: 10px; background: {{ $project->color }}15; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                                    <i class="bi bi-folder-fill" style="color: {{ $project->color }}; font-size: 1.2rem;"></i>
                                                </div>
                                                <div>
                                                    <h6 class="fw-bold mb-0" style="color: var(--navy-900);">{{ $project->name }}</h6>
                                                    <small class="text-muted">{{ $project->created_at->format('d/m/Y') }}</small>
                                                </div>
                                            </div>
                                        </div>

                                        @if($project->description)
                                            <p class="text-muted small mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.6;">
                                                {{ $project->description }}
                                            </p>
                                        @endif

                                        <div class="d-flex gap-3">
                                            <div class="d-flex align-items-center gap-1">
                                                <i class="bi bi-collection-play text-muted" style="font-size: 0.8rem;"></i>
                                                <span class="fw-semibold small" style="color: var(--navy-900);">{{ $project->videos_count }}</span>
                                                <span class="text-muted small">video</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-1">
                                                <i class="bi bi-check-circle text-success" style="font-size: 0.8rem;"></i>
                                                <span class="fw-semibold small text-success">{{ $project->completed_videos_count }}</span>
                                                <span class="text-muted small">xong</span>
                                            </div>
                                            @if($project->processing_videos_count > 0)
                                                <div class="d-flex align-items-center gap-1">
                                                    <i class="bi bi-hourglass-split text-info" style="font-size: 0.8rem;"></i>
                                                    <span class="fw-semibold small text-info">{{ $project->processing_videos_count }}</span>
                                                    <span class="text-muted small">đang xử lý</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 d-flex justify-content-center">
                    {{ $projects->links() }}
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-folder-plus"></i>
                    </div>
                    <h5>Chưa có dự án nào</h5>
                    <p>Tạo dự án đầu tiên để bắt đầu tổ chức video của bạn!</p>
                    <a href="{{ route('projects.create') }}" class="btn btn-primary-dark btn-lg">
                        <i class="bi bi-plus-lg me-2"></i>Tạo Dự Án Ngay
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
