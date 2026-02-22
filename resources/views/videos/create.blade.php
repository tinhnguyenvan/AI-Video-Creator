@extends('layouts.app')

@section('title', 'Tạo Video Mới')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            {{-- Back Button --}}
            <a href="{{ route('videos.index') }}" class="btn btn-light rounded-pill mb-3">
                <i class="bi bi-arrow-left me-1"></i> Quay lại
            </a>

            <div class="card card-custom">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h4 class="fw-bold mb-1">
                        <i class="bi bi-magic me-2 text-primary"></i>Tạo Video Bằng AI
                    </h4>
                    <p class="text-muted mb-0">Mô tả video bạn muốn tạo, AI sẽ biến ý tưởng thành hiện thực</p>
                </div>
                <div class="card-body px-4 pb-4">
                    <form action="{{ route('videos.store') }}" method="POST" enctype="multipart/form-data" id="createVideoForm">
                        @csrf

                        {{-- Title --}}
                        <div class="mb-4">
                            <label for="title" class="form-label fw-semibold">
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
                            <label for="prompt" class="form-label fw-semibold">
                                <i class="bi bi-chat-text me-1"></i> Mô tả video (Prompt)
                            </label>
                            <textarea class="form-control @error('prompt') is-invalid @enderror"
                                      id="prompt" name="prompt" rows="5"
                                      placeholder="Mô tả chi tiết video bạn muốn tạo. Ví dụ: Cảnh quay từ trên cao về một bãi biển nhiệt đới lúc hoàng hôn, nước biển trong xanh, sóng nhẹ nhàng vỗ bờ, ánh nắng vàng chiếu xuống mặt nước tạo nên sắc cam ấm áp..."
                                      required>{{ old('prompt') }}</textarea>
                            @error('prompt')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <i class="bi bi-lightbulb me-1"></i>
                                Mô tả càng chi tiết, video tạo ra càng chính xác. Tối đa 2000 ký tự.
                                <span id="charCount" class="float-end">0/2000</span>
                            </div>
                        </div>

                        {{-- Options Row --}}
                        <div class="row g-3 mb-4">
                            {{-- Aspect Ratio --}}
                            <div class="col-md-4">
                                <label for="aspect_ratio" class="form-label fw-semibold">
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

                            {{-- Duration --}}
                            <div class="col-md-4">
                                <label for="duration" class="form-label fw-semibold">
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

                            {{-- Resolution --}}
                            <div class="col-md-4">
                                <label for="resolution" class="form-label fw-semibold">
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

                        {{-- Reference Image (optional) --}}
                        <div class="mb-4">
                            <label for="reference_image" class="form-label fw-semibold">
                                <i class="bi bi-image me-1"></i> Ảnh tham chiếu (Tùy chọn)
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

                        {{-- Prompt Tips --}}
                        <div class="alert alert-light border rounded-3 mb-4">
                            <h6 class="fw-semibold mb-2"><i class="bi bi-stars me-1 text-warning"></i> Mẹo viết prompt hiệu quả:</h6>
                            <ul class="mb-0 small text-muted">
                                <li>Mô tả rõ ràng chủ thể, hành động và bối cảnh</li>
                                <li>Sử dụng các thuật ngữ nhiếp ảnh/quay phim: "aerial shot", "close-up", "slow motion"</li>
                                <li>Thêm mô tả về ánh sáng, màu sắc, phong cách</li>
                                <li>Ghi rõ chuyển động camera mong muốn: "panning left", "zooming in"</li>
                            </ul>
                        </div>

                        {{-- Submit --}}
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('videos.index') }}" class="btn btn-light rounded-pill px-4">
                                <i class="bi bi-x-lg me-1"></i> Hủy
                            </a>
                            <button type="submit" class="btn btn-gradient btn-lg rounded-pill px-5" id="submitBtn">
                                <i class="bi bi-magic me-2"></i>Tạo Video
                            </button>
                        </div>
                    </form>
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
