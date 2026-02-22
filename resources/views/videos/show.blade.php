@extends('layouts.app')

@section('title', $video->title)
@section('page-title', $video->title)
@section('breadcrumb')
    <a href="{{ route('home') }}">Trang chủ</a> <span class="mx-1">/</span>
    @if($video->project)
        <a href="{{ route('projects.index') }}">Dự án</a> <span class="mx-1">/</span>
        <a href="{{ route('projects.show', $video->project) }}">{{ Str::limit($video->project->name, 20) }}</a> <span class="mx-1">/</span>
    @else
        <a href="{{ route('videos.index') }}">Videos</a> <span class="mx-1">/</span>
    @endif
    {{ Str::limit($video->title, 30) }}
@endsection

@section('content')
    <div class="row g-4">
        {{-- Video Player / Status --}}
        <div class="col-lg-8">
            <div class="card-panel overflow-hidden">
                @if($video->status === 'completed' && $video->video_path)
                    <div class="ratio ratio-16x9" style="background: var(--navy-900);">
                        <video controls autoplay muted id="videoPlayer" style="border-radius: 12px 12px 0 0;">
                            <source src="{{ asset('storage/' . $video->video_path) }}" type="video/mp4">
                            Trình duyệt không hỗ trợ video.
                        </video>
                    </div>
                @elseif(in_array($video->status, ['queued', 'pending', 'processing']))
                    <div style="background: linear-gradient(135deg, var(--navy-900), var(--navy-700)); min-height: 420px; border-radius: 12px 12px 0 0;"
                         class="d-flex flex-column align-items-center justify-content-center text-white" id="processingArea">
                        @if($video->status === 'queued')
                            <div class="mb-4" style="width: 56px; height: 56px; border-radius: 50%; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-hourglass-split" style="font-size: 1.5rem; color: var(--accent);"></i>
                            </div>
                            <h5 class="fw-bold mb-2" id="statusText">Đang chờ trong hàng đợi...</h5>
                            <p class="mb-3" style="color: var(--navy-200); font-size: 0.9rem;" id="statusSubtext">Video sẽ tự động chuyển sang xử lý khi đến lượt</p>
                        @else
                            <div class="spinner-border spinner-accent mb-4" style="width: 3.5rem; height: 3.5rem; border-width: 3px;" role="status">
                                <span class="visually-hidden">Đang tạo video...</span>
                            </div>
                            <h5 class="fw-bold mb-2" id="statusText">Đang tạo video...</h5>
                            <p class="mb-3" style="color: var(--navy-200); font-size: 0.9rem;" id="statusSubtext">Quá trình này có thể mất 2-5 phút</p>
                        @endif
                        <div class="progress" style="width: 220px; height: 3px; background: var(--navy-600); border-radius: 4px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%; background: var(--accent);"></div>
                        </div>
                    </div>
                    <div class="ratio ratio-16x9 d-none" style="background: var(--navy-900);" id="completedVideoArea">
                        <video controls autoplay muted id="newVideoPlayer" style="border-radius: 12px 12px 0 0;">
                            <source src="" type="video/mp4" id="videoSource">
                        </video>
                    </div>
                @else
                    <div style="background: linear-gradient(135deg, var(--navy-900), var(--navy-700)); min-height: 420px; border-radius: 12px 12px 0 0;"
                         class="d-flex flex-column align-items-center justify-content-center text-white">
                        <div class="stat-icon red mb-3" style="width: 64px; height: 64px; font-size: 1.6rem;">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Tạo video thất bại</h5>
                        <p class="mb-0 px-4 text-center" style="color: var(--navy-200); max-width: 400px; font-size: 0.9rem;">{{ $video->error_message ?? 'Lỗi không xác định' }}</p>
                    </div>
                @endif

                <div class="card-panel-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h4 class="fw-bold mb-1" style="color: var(--navy-900);">{{ $video->title }}</h4>
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-muted">
                                    <i class="bi bi-calendar3 me-1"></i>{{ $video->created_at->format('d/m/Y H:i') }}
                                </small>
                                @if(($video->metadata['type'] ?? null) === 'merged')
                                    <span class="badge" style="background: #7c3aed20; color: #7c3aed; font-size: 0.65rem; font-weight: 600;">
                                        <i class="bi bi-layers me-1"></i>Video ghép
                                    </span>
                                @endif
                            </div>
                        </div>
                        <span class="badge badge-status bg-{{ $video->status_badge }} {{ in_array($video->status, ['pending', 'processing']) ? 'badge-pulse' : '' }}" style="font-size: 0.8rem; padding: 0.4rem 0.85rem;" id="statusBadge">
                            {{ $video->status_label }}
                        </span>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex gap-2 flex-wrap">
                        @if($video->status === 'completed' && $video->video_path)
                            <a href="{{ route('videos.download', $video) }}" class="btn btn-primary-dark">
                                <i class="bi bi-download me-1"></i> Tải xuống
                            </a>
                        @endif

                        <a href="{{ route('videos.edit', $video) }}" class="btn btn-ghost" style="border-color: var(--navy-200);">
                            <i class="bi bi-pencil-square me-1"></i> Chỉnh sửa
                        </a>

                        @if($video->status === 'failed')
                            <form action="{{ route('videos.retry', $video) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-warning" style="border-radius: 8px; font-size: 0.85rem; font-weight: 600;">
                                    <i class="bi bi-arrow-repeat me-1"></i> Thử lại
                                </button>
                            </form>
                        @endif

                        <form action="{{ route('videos.destroy', $video) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Bạn có chắc muốn xóa video này?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-ghost text-danger" style="border-color: #fecaca;">
                                <i class="bi bi-trash me-1"></i> Xóa
                            </button>
                        </form>

                        <a href="{{ route('videos.index') }}" class="btn btn-ghost ms-auto">
                            <i class="bi bi-arrow-left me-1"></i> Quay lại
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar Details --}}
        <div class="col-lg-4">
            {{-- Prompt --}}
            <div class="card-panel mb-4">
                <div class="card-panel-header">
                    <h5><i class="bi bi-chat-text me-2"></i>Prompt</h5>
                </div>
                <div class="card-panel-body">
                    <p class="mb-0 text-muted" style="line-height: 1.7; font-size: 0.875rem;">{{ $video->prompt }}</p>
                </div>
            </div>

            {{-- Details --}}
            <div class="card-panel mb-4">
                <div class="card-panel-header">
                    <h5><i class="bi bi-info-circle me-2"></i>Chi tiết</h5>
                </div>
                <div class="card-panel-body">
                    <table class="table table-detail table-borderless mb-0">
                        <tbody>
                            @if($video->project)
                            <tr>
                                <td><i class="bi bi-folder-fill me-2"></i>Dự án</td>
                                <td>
                                    <a href="{{ route('projects.show', $video->project) }}" class="text-decoration-none fw-semibold" style="color: {{ $video->project->color }};">
                                        {{ $video->project->name }}
                                    </a>
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <td><i class="bi bi-aspect-ratio me-2"></i>Tỉ lệ</td>
                                <td>{{ $video->metadata['aspect_ratio'] ?? '16:9' }}</td>
                            </tr>
                            <tr>
                                <td><i class="bi bi-stopwatch me-2"></i>Thời lượng</td>
                                <td>{{ $video->duration ?? 8 }} giây</td>
                            </tr>
                            <tr>
                                <td><i class="bi bi-display me-2"></i>Phân giải</td>
                                <td>{{ $video->resolution }}</td>
                            </tr>
                            <tr>
                                <td><i class="bi bi-calendar3 me-2"></i>Tạo lúc</td>
                                <td>{{ $video->created_at->format('d/m/Y H:i:s') }}</td>
                            </tr>
                            @if($video->metadata['completed_at'] ?? null)
                            <tr>
                                <td><i class="bi bi-check2-circle me-2"></i>Hoàn thành</td>
                                <td>{{ \Carbon\Carbon::parse($video->metadata['completed_at'])->format('d/m/Y H:i:s') }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Error --}}
            @if($video->status === 'failed' && $video->error_message)
                <div class="card-panel" style="border-color: #fecaca;">
                    <div class="card-panel-header" style="background: #fef2f2; border-bottom-color: #fecaca;">
                        <h5 class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Lỗi</h5>
                    </div>
                    <div class="card-panel-body">
                        <p class="mb-0 text-muted small">{{ $video->error_message }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
@if(in_array($video->status, ['queued', 'pending', 'processing']))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const videoId = {{ $video->id }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        let pollInterval;
        let pollCount = 0;
        const maxPolls = 120;

        function checkStatus() {
            pollCount++;

            if (pollCount > maxPolls) {
                clearInterval(pollInterval);
                document.getElementById('statusText').textContent = 'Quá thời gian chờ';
                document.getElementById('statusSubtext').textContent = 'Vui lòng tải lại trang để kiểm tra';
                return;
            }

            fetch(`/videos/${videoId}/check-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('statusBadge');
                badge.textContent = data.status_label;
                badge.className = `badge badge-status bg-${data.status_badge}`;

                if (data.status === 'completed' && data.video_url) {
                    clearInterval(pollInterval);
                    document.getElementById('processingArea').classList.add('d-none');
                    const videoArea = document.getElementById('completedVideoArea');
                    videoArea.classList.remove('d-none');
                    document.getElementById('videoSource').src = data.video_url;
                    document.getElementById('newVideoPlayer').load();
                    setTimeout(() => location.reload(), 1000);

                } else if (data.status === 'failed') {
                    clearInterval(pollInterval);
                    document.getElementById('statusText').textContent = 'Tạo video thất bại';
                    document.getElementById('statusSubtext').textContent = data.error_message || 'Lỗi không xác định';
                    setTimeout(() => location.reload(), 2000);

                } else if (data.status === 'queued') {
                    document.getElementById('statusText').textContent = 'Đang chờ trong hàng đợi...';
                    document.getElementById('statusSubtext').textContent = 'Video sẽ tự động chuyển sang xử lý khi đến lượt';

                } else if (data.status === 'processing') {
                    const dots = '.'.repeat((pollCount % 3) + 1);
                    document.getElementById('statusText').textContent = 'Đang tạo video' + dots;
                    document.getElementById('statusSubtext').textContent = `Đang xử lý (${pollCount * 5}s)... Vui lòng chờ`;

                } else {
                    const dots = '.'.repeat((pollCount % 3) + 1);
                    document.getElementById('statusText').textContent = 'Đang tạo video' + dots;
                    document.getElementById('statusSubtext').textContent = `Đang xử lý (${pollCount * 5}s)... Vui lòng chờ`;
                }
            })
            .catch(error => {
                console.error('Status check error:', error);
            });
        }

        pollInterval = setInterval(checkStatus, 5000);
        setTimeout(checkStatus, 3000);
    });
</script>
@endif
@endpush
