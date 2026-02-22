@extends('layouts.app')

@section('title', 'Cài đặt')
@section('page-title', 'Cài đặt')
@section('breadcrumb')
    <a href="{{ route('home') }}">Trang chủ</a> <span class="mx-1">/</span> Cài đặt
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            {{-- API Connection --}}
            <div class="card-panel mb-4">
                <div class="card-panel-header">
                    <h5><i class="bi bi-key me-2"></i>Google AI Studio API</h5>
                </div>
                <div class="card-panel-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            @if($apiKeyConfigured)
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="d-inline-block rounded-circle bg-success" style="width:10px;height:10px;"></span>
                                    <span class="fw-semibold" style="color: var(--navy-900);">API Key đã được cấu hình</span>
                                </div>
                                <small class="text-muted">Key được lưu trong file .env</small>
                            @else
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="d-inline-block rounded-circle bg-danger" style="width:10px;height:10px;"></span>
                                    <span class="fw-semibold text-danger">API Key chưa được cấu hình</span>
                                </div>
                                <small class="text-muted">Vui lòng thêm key vào file .env</small>
                            @endif
                        </div>
                        <button type="button" class="btn btn-primary-dark" id="testConnectionBtn" {{ !$apiKeyConfigured ? 'disabled' : '' }}>
                            <i class="bi bi-wifi me-1"></i> Kiểm tra kết nối
                        </button>
                    </div>
                    <div id="connectionResult" class="d-none"></div>
                </div>
            </div>

            {{-- Setup Instructions --}}
            <div class="card-panel">
                <div class="card-panel-header">
                    <h5><i class="bi bi-book me-2"></i>Hướng dẫn cài đặt</h5>
                </div>
                <div class="card-panel-body">
                    <ol class="text-muted mb-4" style="line-height: 2;">
                        <li>
                            Truy cập <a href="https://aistudio.google.com/apikey" target="_blank" class="fw-semibold" style="color: var(--accent-dark);">Google AI Studio</a> và đăng nhập bằng tài khoản Google
                        </li>
                        <li>Tạo API Key mới (hoặc sử dụng key hiện có)</li>
                        <li>Mở file <code>.env</code> trong thư mục project</li>
                        <li>Thêm API Key vào dòng: <code>GOOGLE_AI_STUDIO_API_KEY=your_api_key_here</code></li>
                        <li>Chạy lệnh <code>php artisan config:clear</code> để xóa cache config</li>
                    </ol>

                    <div class="alert mb-0" style="background: #fef3cd; border: 1px solid #ffc107; border-radius: 10px; font-size: 0.875rem;">
                        <i class="bi bi-shield-lock me-2"></i>
                        <strong>Lưu ý bảo mật:</strong> Không chia sẻ API Key. Key này được lưu trong file <code>.env</code> và không được commit vào Git.
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div class="col-lg-4">
            <div class="card-panel mb-4" style="background: linear-gradient(135deg, var(--navy-900), var(--navy-700)); border: none;">
                <div class="card-panel-body text-white">
                    <div class="stat-icon cyan mb-3" style="width: 48px; height: 48px;">
                        <i class="bi bi-robot" style="font-size: 1.1rem;"></i>
                    </div>
                    <h6 class="fw-bold mb-2">Về Google Veo 3.1</h6>
                    <p class="mb-0" style="font-size: 0.825rem; color: var(--navy-200); line-height: 1.7;">
                        Veo 3.1 là model tạo video AI mới nhất của Google. Hỗ trợ tạo video chất lượng cao với độ phân giải lên đến 1080p và thời lượng 5-8 giây.
                    </p>
                </div>
            </div>

            <div class="card-panel">
                <div class="card-panel-header">
                    <h5><i class="bi bi-cpu me-2"></i>Thông tin hệ thống</h5>
                </div>
                <div class="card-panel-body">
                    <table class="table table-detail table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td>Laravel</td>
                                <td>{{ app()->version() }}</td>
                            </tr>
                            <tr>
                                <td>PHP</td>
                                <td>{{ phpversion() }}</td>
                            </tr>
                            <tr>
                                <td>Model</td>
                                <td style="font-size: 0.78rem;">veo-3.1-generate-preview</td>
                            </tr>
                        </tbody>
                    </table>
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
                        <div class="alert alert-success mb-0" style="border-radius: 10px; font-size: 0.875rem;">
                            <i class="bi bi-check-circle me-2"></i>
                            <strong>\${data.message}</strong><br>
                            <small>Tìm thấy \${data.models_count} models. Video models: \${data.video_models.length}</small>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger mb-0" style="border-radius: 10px; font-size: 0.875rem;">
                            <i class="bi bi-x-circle me-2"></i>\${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultDiv.classList.remove('d-none');
                resultDiv.innerHTML = `
                    <div class="alert alert-danger mb-0" style="border-radius: 10px; font-size: 0.875rem;">
                        <i class="bi bi-x-circle me-2"></i>Lỗi kết nối: \${error.message}
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
