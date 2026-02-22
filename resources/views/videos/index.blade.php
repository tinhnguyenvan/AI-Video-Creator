@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    {{-- Page Header --}}
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fw-bold mb-2"><i class="bi bi-camera-reels me-2"></i>AI Video Creator</h1>
                <p class="mb-0 opacity-75">Tạo video chuyên nghiệp bằng AI với Google AI Studio (Veo 2.0)</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="{{ route('videos.create') }}" class="btn btn-light btn-lg rounded-pill px-4">
                    <i class="bi bi-plus-lg me-2"></i>Tạo Video Mới
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stats-card bg-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-collection-play"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Tổng video</div>
                            <div class="fs-4 fw-bold">{{ $stats['total'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stats-card bg-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Hoàn thành</div>
                            <div class="fs-4 fw-bold">{{ $stats['completed'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stats-card bg-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-info bg-opacity-10 text-info me-3">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Đang xử lý</div>
                            <div class="fs-4 fw-bold">{{ $stats['processing'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stats-card bg-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-danger bg-opacity-10 text-danger me-3">
                            <i class="bi bi-x-circle"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Thất bại</div>
                            <div class="fs-4 fw-bold">{{ $stats['failed'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Video Grid --}}
    <div class="card card-custom">
        <div class="card-header bg-white border-0 pt-4 pb-3 px-4">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="fw-semibold mb-0"><i class="bi bi-grid-3x3-gap me-2"></i>Danh sách Video</h5>
            </div>
        </div>
        <div class="card-body px-4 pb-4">
            @if($videos->count() > 0)
                <div class="row g-4">
                    @foreach($videos as $video)
                        <div class="col-md-6 col-lg-4">
                            <div class="card video-card h-100">
                                @if($video->video_path)
                                    <video class="card-img-top" muted preload="metadata" style="height: 200px; object-fit: cover;">
                                        <source src="{{ asset('storage/' . $video->video_path) }}" type="video/mp4">
                                    </video>
                                @else
                                    <div class="video-placeholder">
                                        @if($video->status === 'processing' || $video->status === 'pending')
                                            <div class="spinner-border spinner-gradient" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        @elseif($video->status === 'failed')
                                            <i class="bi bi-exclamation-triangle text-danger"></i>
                                        @else
                                            <i class="bi bi-film"></i>
                                        @endif
                                    </div>
                                @endif
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title fw-semibold mb-0 text-truncate me-2">{{ $video->title }}</h6>
                                        <span class="badge bg-{{ $video->status_badge }} {{ in_array($video->status, ['pending', 'processing']) ? 'badge-pulse' : '' }} flex-shrink-0">
                                            {{ $video->status_label }}
                                        </span>
                                    </div>
                                    <p class="card-text text-muted small mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        {{ $video->prompt }}
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i>{{ $video->created_at->diffForHumans() }}
                                        </small>
                                        <a href="{{ route('videos.show', $video) }}" class="btn btn-sm btn-outline-primary rounded-pill">
                                            <i class="bi bi-eye me-1"></i>Chi tiết
                                        </a>
                                    </div>
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
                    <i class="bi bi-camera-reels d-block"></i>
                    <h5 class="fw-semibold text-dark mb-2">Chưa có video nào</h5>
                    <p class="mb-4">Bắt đầu tạo video đầu tiên của bạn bằng AI</p>
                    <a href="{{ route('videos.create') }}" class="btn btn-gradient btn-lg">
                        <i class="bi bi-plus-lg me-2"></i>Tạo Video Ngay
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
