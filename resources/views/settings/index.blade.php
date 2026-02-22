@extends('layouts.app')

@section('title', 'Cài đặt')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <a href="{{ route('videos.index') }}" class="btn btn-light rounded-pill mb-3">
                <i class="bi bi-arrow-left me-1"></i> Quay lại
            </a>

            <div class="card card-custom">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h4 class="fw-bold mb-1">
                        <i class="bi bi-gear me-2 text-primary"></i>Cài đặt
                    </h4>
                    <p class="text-muted mb-0">Quản lý cấu hình ứng dụng</p>
                </div>
                <div class="card-body px-4 pb-4">
                    {{-- API Key Status --}}
                    <div class="card border rounded-3 mb-4">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="fw-semibold mb-1">
                                        <i class="bi bi-key me-2"></i>Google AI Studio API Key
                                    </h6>
                                    <p class="text-muted small mb-0">
                                        @if($apiKeyConfigured)
                                            <span class="text-success"><i class="bi bi-check-circle me-1"></i>API Key đã được cấu hình</span>
                                        @else
                                            <span class="text-danger"><i class="bi bi-x-circle me-1"></i>API Key chưa được cấu hình</span>
                                        @endif
                                    </p>
                                </div>
                                <button type="button" class="btn btn-outline-primary rounded-pill btn-sm" id="testConnectionBtn" {{ !$apiKeyConfigured ? 'disabled' : '' }}>
                                    <i class="bi bi-wifi me-1"></i> Kiểm tra kết nối
                                </button>
                            </div>
                            <div id="connectionResult" class="mt-3 d-none"></div>
                        </div>
                    </div>

                    {{-- Setup Instructions --}}
                    <div class="card border rounded-3">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-3">
                                <i class="bi bi-book me-2"></i>Hướng dẫn cài đặt
                            </h6>
                            <ol class="text-muted">
                                <li class="mb-2">
                                    Truy cập <a href="https://aistudio.google.com/apikey" target="_blank" class="fw-medium">Google AI Studio</a> và đăng nhập bằng tài khoản Google
                                </li>
                                <li class="mb-2">
                                    Tạo API Key mới (hoặc sử dụng key hiện có)
                                </li>
                                <li class="mb-2">
                                    Mở file <code>.env</code> trong thư mục project
                                </li>
                                <li class="mb-2">
                                    Thêm API Key vào dòng: <code>GOOGLE_AI_STUDIO_API_KEY=your_api_key_here</code>
                                </li>
                                <li class="mb-2">
                                    Chạy lệnh <code>php artisan config:clear</code> để xóa cache config
                                </li>
                            </ol>

                            <div class="alert alert-warning rounded-3 mb-0">
                                <i class="bi bi-shield-lock me-2"></i>
                                <strong>Lưu ý bảo mật:</strong> Không chia sẻ API Key. Key này được lưu trong file <code>.env</code> và không được commit vào Git.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const testBtn = document.getElementById('testConnectionBtn');
        const resultDiv = document.getElementById('connectionResult');

        testBtn.addEventListener('click', function() {
            testBtn.disabled = true;
            testBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang kiểm tra...';
            resultDiv.classList.add('d-none');

            fetch('{{ route("settings.test-connection") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                resultDiv.classList.remove('d-none');
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success rounded-3 mb-0">
                            <i class="bi bi-check-circle me-2"></i>
                            <strong>${data.message}</strong><br>
                            <small>Tìm thấy ${data.models_count} models. Video models: ${data.video_models.length}</small>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger rounded-3 mb-0">
                            <i class="bi bi-x-circle me-2"></i>${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultDiv.classList.remove('d-none');
                resultDiv.innerHTML = `
                    <div class="alert alert-danger rounded-3 mb-0">
                        <i class="bi bi-x-circle me-2"></i>Lỗi kết nối: ${error.message}
                    </div>
                `;
            })
            .finally(() => {
                testBtn.disabled = false;
                testBtn.innerHTML = '<i class="bi bi-wifi me-1"></i> Kiểm tra kết nối';
            });
        });
    });
</script>
@endpush
