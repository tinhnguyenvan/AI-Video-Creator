@extends('layouts.app')

@section('title', 'Chỉnh sửa Video')
@section('page-title', 'Chỉnh sửa Video')
@section('breadcrumb')
    <a href="{{ route('home') }}">Trang chủ</a> <span class="mx-1">/</span>
    <a href="{{ route('videos.index') }}">Videos</a> <span class="mx-1">/</span>
    <a href="{{ route('videos.show', $video) }}">{{ Str::limit($video->title, 20) }}</a> <span class="mx-1">/</span> Chỉnh sửa
@endsection

@section('content')
    <div class="row g-4">
        {{-- Main Form --}}
        <div class="col-lg-8">
            <div class="card-panel">
                <div class="card-panel-header">
                    <h5><i class="bi bi-pencil-square me-2"></i>Chỉnh sửa Video</h5>
                </div>
                <div class="card-panel-body">
                    <form action="{{ route('videos.update', $video) }}" method="POST" id="editVideoForm">
                        @csrf
                        @method('PUT')

                        {{-- Project --}}
                        <div class="mb-4">
                            <label for="project_id" class="form-label">
                                <i class="bi bi-folder me-1"></i> Dự án <span class="fw-normal text-muted">(Tùy chọn)</span>
                            </label>
                            <select class="form-select @error('project_id') is-invalid @enderror"
                                    id="project_id" name="project_id">
                                <option value="">-- Không thuộc dự án --</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ old('project_id', $video->project_id) == $project->id ? 'selected' : '' }}>
                                        {{ $project->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('project_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <a href="{{ route('projects.create') }}" class="text-decoration-none" style="color: var(--accent);">
                                    <i class="bi bi-plus-circle me-1"></i>Tạo dự án mới
                                </a>
                            </div>
                        </div>

                        {{-- Title --}}
                        <div class="mb-4">
                            <label for="title" class="form-label">
                                <i class="bi bi-type me-1"></i> Tiêu đề video
                            </label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                   id="title" name="title" value="{{ old('title', $video->title) }}"
                                   placeholder="Ví dụ: Hoàng hôn trên biển" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Prompt --}}
                        <div class="mb-4">
                            <label for="prompt" class="form-label">
                                <i class="bi bi-chat-text me-1"></i> Mô tả video (Prompt)
                            </label>
                            <textarea class="form-control @error('prompt') is-invalid @enderror"
                                      id="prompt" name="prompt" rows="6"
                                      placeholder="Mô tả chi tiết video bạn muốn tạo..."
                                      required>{{ old('prompt', $video->prompt) }}</textarea>
                            @error('prompt')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text d-flex justify-content-between mt-1">
                                <span><i class="bi bi-lightbulb me-1"></i> Mô tả càng chi tiết, video càng chính xác</span>
                                <span id="charCount" class="fw-medium">0/2000</span>
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="{{ route('videos.show', $video) }}" class="btn btn-ghost">
                                <i class="bi bi-x-lg me-1"></i> Hủy
                            </a>
                            <button type="submit" class="btn btn-primary-dark btn-lg px-5" id="submitBtn">
                                <i class="bi bi-check-lg me-2"></i>Cập nhật
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div class="col-lg-4">
            {{-- Video Preview --}}
            @if($video->status === 'completed' && $video->video_path)
            <div class="card-panel mb-4 overflow-hidden">
                <div class="ratio ratio-16x9" style="background: var(--navy-900);">
                    <video controls muted style="border-radius: 12px 12px 0 0;">
                        <source src="{{ asset('storage/' . $video->video_path) }}" type="video/mp4">
                    </video>
                </div>
            </div>
            @endif

            {{-- Details --}}
            <div class="card-panel mb-4">
                <div class="card-panel-header">
                    <h5><i class="bi bi-info-circle me-2"></i>Thông tin video</h5>
                </div>
                <div class="card-panel-body">
                    <table class="table table-detail table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td><i class="bi bi-hash me-2"></i>Trạng thái</td>
                                <td>
                                    <span class="badge bg-{{ $video->status_badge }}">{{ $video->status_label }}</span>
                                </td>
                            </tr>
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
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-panel" style="background: linear-gradient(135deg, var(--navy-900), var(--navy-700)); border-color: var(--navy-600);">
                <div class="card-panel-body text-center" style="border: none;">
                    <i class="bi bi-info-circle-fill text-warning mb-2" style="font-size: 1.5rem;"></i>
                    <p class="text-white mb-2 fw-semibold small">Lưu ý</p>
                    <p class="mb-0" style="font-size: 0.78rem; color: var(--navy-200); line-height: 1.6;">
                        Bạn có thể cập nhật tiêu đề, mô tả prompt và dự án cho video. Các thông số kỹ thuật (tỉ lệ, thời lượng, phân giải) không thể thay đổi sau khi tạo.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Character counter
        const promptInput = document.getElementById('prompt');
        const charCount = document.getElementById('charCount');

        function updateCharCount() {
            const count = promptInput.value.length;
            charCount.textContent = count + '/2000';
            charCount.style.color = count > 1800 ? '#dc3545' : '';
        }

        promptInput.addEventListener('input', updateCharCount);
        updateCharCount();

        // Submit loading state
        const form = document.getElementById('editVideoForm');
        const submitBtn = document.getElementById('submitBtn');

        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang cập nhật...';
        });
    });
</script>
@endpush
