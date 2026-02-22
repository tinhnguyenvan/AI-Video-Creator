@extends('layouts.app')

@section('title', 'Tạo Video Mới')
@section('page-title', 'Tạo Video Mới')
@section('breadcrumb')
    <a href="{{ route('home') }}">Trang chủ</a> <span class="mx-1">/</span> <a href="{{ route('videos.index') }}">Videos</a> <span class="mx-1">/</span> Tạo mới
@endsection

@section('content')
    <div class="row g-4">
        {{-- Main Form --}}
        <div class="col-lg-8">
            <div class="card-panel">
                <div class="card-panel-header">
                    <h5><i class="bi bi-magic me-2"></i>Tạo Video Bằng AI</h5>
                </div>
                <div class="card-panel-body">
                    <form action="{{ route('videos.store') }}" method="POST" enctype="multipart/form-data" id="createVideoForm">
                        @csrf

                        {{-- Project --}}
                        <div class="mb-4">
                            <label for="project_id" class="form-label">
                                <i class="bi bi-folder me-1"></i> Dự án <span class="fw-normal text-muted">(Tùy chọn)</span>
                            </label>
                            <select class="form-select @error('project_id') is-invalid @enderror"
                                    id="project_id" name="project_id">
                                <option value="">-- Không thuộc dự án --</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" {{ old('project_id', $selectedProject) == $project->id ? 'selected' : '' }}>
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
                                   id="title" name="title" value="{{ old('title') }}"
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
                                      placeholder="Mô tả chi tiết video bạn muốn tạo. Ví dụ: Cảnh quay từ trên cao về một bãi biển nhiệt đới lúc hoàng hôn, nước biển trong xanh, sóng nhẹ nhàng vỗ bờ, ánh nắng vàng chiếu xuống mặt nước tạo nên sắc cam ấm áp..."
                                      required>{{ old('prompt') }}</textarea>
                            @error('prompt')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text d-flex justify-content-between mt-1">
                                <span><i class="bi bi-lightbulb me-1"></i> Mô tả càng chi tiết, video càng chính xác</span>
                                <span id="charCount" class="fw-medium">0/2000</span>
                            </div>
                        </div>

                        {{-- Options --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="aspect_ratio" class="form-label">
                                    <i class="bi bi-aspect-ratio me-1"></i> Tỉ lệ khung hình
                                </label>
                                <select class="form-select @error('aspect_ratio') is-invalid @enderror"
                                        id="aspect_ratio" name="aspect_ratio">
                                    <option value="16:9" {{ old('aspect_ratio') == '16:9' ? 'selected' : '' }}>16:9 (Ngang)</option>
                                    <option value="9:16" {{ old('aspect_ratio') == '9:16' ? 'selected' : '' }}>9:16 (Dọc)</option>
                                    <option value="1:1" {{ old('aspect_ratio') == '1:1' ? 'selected' : '' }}>1:1 (Vuông)</option>
                                </select>
                                @error('aspect_ratio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="duration" class="form-label">
                                    <i class="bi bi-stopwatch me-1"></i> Thời lượng
                                </label>
                                <select class="form-select @error('duration') is-invalid @enderror"
                                        id="duration" name="duration">
                                    <option value="5" {{ old('duration') == '5' ? 'selected' : '' }}>5 giây</option>
                                    <option value="6" {{ old('duration') == '6' ? 'selected' : '' }}>6 giây</option>
                                    <option value="7" {{ old('duration') == '7' ? 'selected' : '' }}>7 giây</option>
                                    <option value="8" {{ old('duration', '8') == '8' ? 'selected' : '' }}>8 giây</option>
                                </select>
                                @error('duration')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="resolution" class="form-label">
                                    <i class="bi bi-display me-1"></i> Độ phân giải
                                </label>
                                <select class="form-select @error('resolution') is-invalid @enderror"
                                        id="resolution" name="resolution">
                                    <option value="720p" {{ old('resolution', '720p') == '720p' ? 'selected' : '' }}>720p</option>
                                    <option value="1080p" {{ old('resolution') == '1080p' ? 'selected' : '' }}>1080p</option>
                                </select>
                                @error('resolution')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Reference Image --}}
                        <div class="mb-4">
                            <label for="reference_image" class="form-label">
                                <i class="bi bi-image me-1"></i> Ảnh tham chiếu <span class="fw-normal text-muted">(Tùy chọn)</span>
                            </label>
                            <input type="file" class="form-control @error('reference_image') is-invalid @enderror"
                                   id="reference_image" name="reference_image" accept="image/*">
                            @error('reference_image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                Upload ảnh để tạo video dựa trên hình ảnh (Image-to-Video). Tối đa 10MB.
                            </div>
                            <div id="imagePreview" class="mt-2 d-none">
                                <img src="" class="img-thumbnail rounded-3" style="max-height: 200px;" alt="Preview">
                                <button type="button" class="btn btn-sm btn-outline-danger ms-2" id="removeImage">
                                    <i class="bi bi-x-lg"></i> Xóa
                                </button>
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="{{ route('videos.index') }}" class="btn btn-ghost">
                                <i class="bi bi-x-lg me-1"></i> Hủy
                            </a>
                            <button type="submit" class="btn btn-primary-dark btn-lg px-5" id="submitBtn">
                                <i class="bi bi-magic me-2"></i>Tạo Video
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar Tips --}}
        <div class="col-lg-4">
            <div class="card-panel mb-4">
                <div class="card-panel-header">
                    <h5><i class="bi bi-stars me-2 text-warning"></i>Mẹo viết prompt</h5>
                </div>
                <div class="card-panel-body">
                    <div class="d-flex gap-3 mb-3">
                        <div class="flex-shrink-0">
                            <div class="stat-icon blue" style="width: 36px; height: 36px; font-size: 0.9rem;"><i class="bi bi-camera-fill"></i></div>
                        </div>
                        <div>
                            <div class="fw-semibold small mb-1">Chủ thể & Bối cảnh</div>
                            <div class="text-muted" style="font-size: 0.8rem;">Mô tả rõ ràng chủ thể, hành động và bối cảnh không gian</div>
                        </div>
                    </div>
                    <div class="d-flex gap-3 mb-3">
                        <div class="flex-shrink-0">
                            <div class="stat-icon green" style="width: 36px; height: 36px; font-size: 0.9rem;"><i class="bi bi-film"></i></div>
                        </div>
                        <div>
                            <div class="fw-semibold small mb-1">Thuật ngữ quay phim</div>
                            <div class="text-muted" style="font-size: 0.8rem;">"aerial shot", "close-up", "slow motion", "tracking shot"</div>
                        </div>
                    </div>
                    <div class="d-flex gap-3 mb-3">
                        <div class="flex-shrink-0">
                            <div class="stat-icon yellow" style="width: 36px; height: 36px; font-size: 0.9rem;"><i class="bi bi-brightness-high-fill"></i></div>
                        </div>
                        <div>
                            <div class="fw-semibold small mb-1">Ánh sáng & Màu sắc</div>
                            <div class="text-muted" style="font-size: 0.8rem;">"cinematic lighting", "golden hour", "warm tones", "vibrant colors"</div>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <div class="flex-shrink-0">
                            <div class="stat-icon red" style="width: 36px; height: 36px; font-size: 0.9rem;"><i class="bi bi-arrows-move"></i></div>
                        </div>
                        <div>
                            <div class="fw-semibold small mb-1">Chuyển động camera</div>
                            <div class="text-muted" style="font-size: 0.8rem;">"panning left", "zooming in", "dolly forward", "orbit around"</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-panel" style="background: linear-gradient(135deg, var(--navy-900), var(--navy-700)); border-color: var(--navy-600);">
                <div class="card-panel-body text-center" style="border: none;">
                    <i class="bi bi-lightbulb-fill text-warning mb-2" style="font-size: 1.5rem;"></i>
                    <p class="text-white mb-2 fw-semibold small">Prompt mẫu</p>
                    <p class="mb-0" style="font-size: 0.78rem; color: var(--navy-200); line-height: 1.6;">
                        "Cảnh quay drone từ trên cao về một bãi biển nhiệt đới lúc hoàng hôn. Nước biển trong xanh, sóng nhẹ vỗ bờ cát trắng. Cinematic, slow motion."
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

        promptInput.addEventListener('input', function() {
            const count = this.value.length;
            charCount.textContent = count + '/2000';
            charCount.style.color = count > 1800 ? '#dc3545' : '';
        });

        // Image preview
        const imageInput = document.getElementById('reference_image');
        const imagePreview = document.getElementById('imagePreview');
        const removeImage = document.getElementById('removeImage');

        imageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.querySelector('img').src = e.target.result;
                    imagePreview.classList.remove('d-none');
                };
                reader.readAsDataURL(this.files[0]);
            }
        });

        removeImage.addEventListener('click', function() {
            imageInput.value = '';
            imagePreview.classList.add('d-none');
        });

        // Submit loading state
        const form = document.getElementById('createVideoForm');
        const submitBtn = document.getElementById('submitBtn');

        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang gửi yêu cầu...';
        });
    });
</script>
@endpush
