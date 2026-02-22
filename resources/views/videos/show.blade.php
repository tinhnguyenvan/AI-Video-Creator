@extends('layouts.app')

@section('title', $video->title)

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-10">
            {{-- Back Button --}}
            <a href="{{ route('videos.index') }}" class="btn btn-light rounded-pill mb-3">
                <i class="bi bi-arrow-left me-1"></i> Quay lại Dashboard
            </a>

            <div class="row g-4">
                {{-- Video Player / Status --}}
                <div class="col-lg-7">
                    <div class="card card-custom overflow-hidden">
                        @if($video->status === 'completed' && $video->video_path)
                            <div class="ratio ratio-16x9 bg-dark rounded-top">
                                <video controls autoplay muted class="rounded-top" id="videoPlayer">
                                    <source src="{{ asset('storage/' . $video->video_path) }}" type="video/mp4">
                                    Trình duyệt không hỗ trợ video.
                                </video>
                            </div>
                        @elseif(in_array($video->status, ['pending', 'processing']))
                            <div class="bg-dark d-flex flex-column align-items-center justify-content-center text-white" style="min-height: 400px;" id="processingArea">
                                <div class="spinner-border spinner-border-lg spinner-gradient mb-4" style="width: 4rem; height: 4rem;" role="status">
                                    <span class="visually-hidden">Đang tạo video...</span>
                                </div>
                                <h5 class="fw-semibold mb-2" id="statusText">Đang tạo video...</h5>
                                <p class="text-white-50 mb-0" id="statusSubtext">Quá trình này có thể mất 2-5 phút</p>
                                <div class="mt-3">
                                    <div class="progress" style="width: 200px; height: 4px;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" style="width: 100%"></div>
                                    </div>
                                </div>
                            </div>
                            {{-- Hidden video player (shown when completed) --}}
                            <div class="ratio ratio-16x9 bg-dark rounded-top d-none" id="completedVideoArea">
                                <video controls autoplay muted class="rounded-top" id="newVideoPlayer">
                                    <source src="" type="video/mp4" id="videoSource">
                                </video>
                            </div>
                        @else
                            <div class="bg-dark d-flex flex-column align-items-center justify-content-center text-white" style="min-height: 400px;">
                                <i class="bi bi-exclamation-triangle text-danger" style="font-size: 4rem;"></i>
                                <h5 class="fw-semibold mt-3 mb-2">Tạo video thất bại</h5>
                                <p class="text-white-50 mb-0 px-4 text-center">{{ $video->error_message ?? 'Lỗi không xác định' }}</p>
                            </div>
                        @endif

                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h4 class="fw-bold mb-1">{{ $video->title }}</h4>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar3 me-1"></i>{{ $video->created_at->format('d/m/Y H:i') }}
                                    </small>
                                </div>
                                <span class="badge bg-{{ $video->status_badge }} fs-6 {{ in_array($video->status, ['pending', 'processing']) ? 'badge-pulse' : '' }}" id="statusBadge">
                                    {{ $video->status_label }}
                                </span>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="d-flex gap-2 flex-wrap">
                                @if($video->status === 'completed' && $video->video_path)
                                    <a href="{{ route('videos.download', $video) }}" class="btn btn-gradient rounded-pill">
                                        <i class="bi bi-download me-1"></i> Tải xuống
                                    </a>
                                @endif

                                @if($video->status === 'failed')
                                    <form action="{{ route('videos.retry', $video) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-warning rounded-pill">
                                            <i class="bi bi-arrow-repeat me-1"></i> Thử lại
                                        </button>
                                    </form>
                                @endif

                                <form action="{{ route('videos.destroy', $video) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Bạn có chắc muốn xóa video này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger rounded-pill">
                                        <i class="bi bi-trash me-1"></i> Xóa
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Video Details --}}
                <div class="col-lg-5">
                    {{-- Prompt Card --}}
                    <div class="card card-custom mb-4">
                        <div class="card-header bg-white border-0 pt-4 px-4">
                            <h6 class="fw-semibold mb-0"><i class="bi bi-chat-text me-2 text-primary"></i>Prompt</h6>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <p class="mb-0 text-muted" style="line-height: 1.7;">{{ $video->prompt }}</p>
                        </div>
                    </div>

                    {{-- Details Card --}}
                    <div class="card card-custom mb-4">
                        <div class="card-header bg-white border-0 pt-4 px-4">
                            <h6 class="fw-semibold mb-0"><i class="bi bi-info-circle me-2 text-primary"></i>Thông tin chi tiết</h6>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <table class="table table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <td class="text-muted ps-0" style="width: 140px;">
                                            <i class="bi bi-aspect-ratio me-2"></i>Tỉ lệ
                                        </td>
                                        <td class="fw-medium">{{ $video->metadata['aspect_ratio'] ?? '16:9' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted ps-0">
                                            <i class="bi bi-stopwatch me-2"></i>Thời lượng
                                        </td>
                                        <td class="fw-medium">{{ $video->duration ?? 8 }} giây</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted ps-0">
                                            <i class="bi bi-display me-2"></i>Độ phân giải
                                        </td>
                                        <td class="fw-medium">{{ $video->resolution }}</td>
                                    </tr>

                                    <tr>
                                        <td class="text-muted ps-0">
                                            <i class="bi bi-calendar3 me-2"></i>Tạo lúc
                                        </td>
                                        <td class="fw-medium">{{ $video->created_at->format('d/m/Y H:i:s') }}</td>
                                    </tr>
                                    @if($video->metadata['completed_at'] ?? null)
                                    <tr>
                                        <td class="text-muted ps-0">
                                            <i class="bi bi-check2-circle me-2"></i>Hoàn thành
                                        </td>
                                        <td class="fw-medium">{{ \Carbon\Carbon::parse($video->metadata['completed_at'])->format('d/m/Y H:i:s') }}</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Error Card (if failed) --}}
                    @if($video->status === 'failed' && $video->error_message)
                        <div class="card card-custom border-danger">
                            <div class="card-header bg-danger bg-opacity-10 border-0 pt-4 px-4">
                                <h6 class="fw-semibold mb-0 text-danger">
                                    <i class="bi bi-exclamation-triangle me-2"></i>Thông báo lỗi
                                </h6>
                            </div>
                            <div class="card-body px-4 pb-4">
                                <p class="mb-0 text-muted small">{{ $video->error_message }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
@if(in_array($video->status, ['pending', 'processing']))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const videoId = {{ $video->id }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        let pollInterval;
        let pollCount = 0;
        const maxPolls = 120; // 10 minutes max (5s interval)

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
                // Update badge
                const badge = document.getElementById('statusBadge');
                badge.textContent = data.status_label;
                badge.className = `badge bg-${data.status_badge} fs-6`;

                if (data.status === 'completed' && data.video_url) {
                    clearInterval(pollInterval);

                    // Show video player
                    document.getElementById('processingArea').classList.add('d-none');
                    const videoArea = document.getElementById('completedVideoArea');
                    videoArea.classList.remove('d-none');
                    document.getElementById('videoSource').src = data.video_url;
                    document.getElementById('newVideoPlayer').load();

                    // Reload page to show full UI
                    setTimeout(() => location.reload(), 1000);

                } else if (data.status === 'failed') {
                    clearInterval(pollInterval);
                    document.getElementById('statusText').textContent = 'Tạo video thất bại';
                    document.getElementById('statusSubtext').textContent = data.error_message || 'Lỗi không xác định';

                    // Reload page to show error state
                    setTimeout(() => location.reload(), 2000);

                } else {
                    // Still processing
                    const dots = '.'.repeat((pollCount % 3) + 1);
                    document.getElementById('statusText').textContent = 'Đang tạo video' + dots;
                    document.getElementById('statusSubtext').textContent = `Đang xử lý (${pollCount * 5}s)... Vui lòng chờ`;
                }
            })
            .catch(error => {
                console.error('Status check error:', error);
            });
        }

        // Poll every 5 seconds
        pollInterval = setInterval(checkStatus, 5000);

        // Initial check after 3 seconds
        setTimeout(checkStatus, 3000);
    });
</script>
@endif
@endpush
