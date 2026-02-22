@extends('layouts.app')

@section('title', 'Tạo Dự Án Mới')
@section('page-title', 'Tạo Dự Án Mới')
@section('breadcrumb')
    <a href="{{ route('home') }}">Trang chủ</a> <span class="mx-1">/</span> <a href="{{ route('projects.index') }}">Dự án</a> <span class="mx-1">/</span> Tạo mới
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card-panel">
                <div class="card-panel-header">
                    <h5><i class="bi bi-folder-plus me-2"></i>Tạo Dự Án Mới</h5>
                </div>
                <div class="card-panel-body">
                    <form action="{{ route('projects.store') }}" method="POST">
                        @csrf

                        {{-- Name --}}
                        <div class="mb-4">
                            <label for="name" class="form-label">
                                <i class="bi bi-type me-1"></i> Tên dự án
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}"
                                   placeholder="Ví dụ: Video quảng cáo sản phẩm" required autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Description --}}
                        <div class="mb-4">
                            <label for="description" class="form-label">
                                <i class="bi bi-text-paragraph me-1"></i> Mô tả <span class="fw-normal text-muted">(Tùy chọn)</span>
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="4"
                                      placeholder="Mô tả ngắn gọn về dự án...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Master Character --}}
                        <div class="mb-4">
                            <label for="master_character" class="form-label">
                                <i class="bi bi-person-badge me-1"></i> Nhân vật chính (Master Character) <span class="fw-normal text-muted">(Tùy chọn)</span>
                            </label>
                            <textarea class="form-control @error('master_character') is-invalid @enderror"
                                      id="master_character" name="master_character" rows="4"
                                      placeholder="Mô tả chi tiết nhân vật chính xuất hiện xuyên suốt các video. Ví dụ: Một người phụ nữ trẻ châu Á, tóc dài đen, mặc áo dài trắng, nụ cười tươi sáng...">{{ old('master_character') }}</textarea>
                            @error('master_character')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                Mô tả này sẽ được tự động ghép vào prompt của mỗi video trong dự án để đảm bảo nhân vật đồng bộ.
                            </div>
                        </div>

                        {{-- Color --}}
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="bi bi-palette me-1"></i> Màu nhận diện
                            </label>
                            <div class="d-flex gap-2 flex-wrap">
                                @foreach($colors as $hex => $label)
                                    <label class="color-option">
                                        <input type="radio" name="color" value="{{ $hex }}"
                                               {{ old('color', '#3b82f6') === $hex ? 'checked' : '' }} class="d-none">
                                        <div class="color-swatch" style="background: {{ $hex }};" title="{{ $label }}">
                                            <i class="bi bi-check-lg text-white"></i>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            @error('color')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Submit --}}
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="{{ route('projects.index') }}" class="btn btn-ghost">
                                <i class="bi bi-x-lg me-1"></i> Hủy
                            </a>
                            <button type="submit" class="btn btn-primary-dark btn-lg px-5">
                                <i class="bi bi-folder-plus me-2"></i>Tạo Dự Án
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            <div class="card-panel mb-4">
                <div class="card-panel-header">
                    <h5><i class="bi bi-info-circle me-2"></i>Về dự án</h5>
                </div>
                <div class="card-panel-body">
                    <div class="d-flex gap-3 mb-3">
                        <div class="flex-shrink-0">
                            <div class="stat-icon blue" style="width: 36px; height: 36px; font-size: 0.9rem;"><i class="bi bi-folder-fill"></i></div>
                        </div>
                        <div>
                            <div class="fw-semibold small mb-1">Tổ chức video</div>
                            <div class="text-muted" style="font-size: 0.8rem;">Nhóm các video liên quan vào cùng một dự án để dễ quản lý</div>
                        </div>
                    </div>
                    <div class="d-flex gap-3 mb-3">
                        <div class="flex-shrink-0">
                            <div class="stat-icon green" style="width: 36px; height: 36px; font-size: 0.9rem;"><i class="bi bi-collection-play-fill"></i></div>
                        </div>
                        <div>
                            <div class="fw-semibold small mb-1">Nhiều video</div>
                            <div class="text-muted" style="font-size: 0.8rem;">Mỗi dự án có thể chứa không giới hạn số lượng video</div>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <div class="flex-shrink-0">
                            <div class="stat-icon yellow" style="width: 36px; height: 36px; font-size: 0.9rem;"><i class="bi bi-palette-fill"></i></div>
                        </div>
                        <div>
                            <div class="fw-semibold small mb-1">Màu nhận diện</div>
                            <div class="text-muted" style="font-size: 0.8rem;">Dùng màu để phân biệt nhanh giữa các dự án khác nhau</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .color-option .color-swatch {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        border: 3px solid transparent;
    }

    .color-option .color-swatch i {
        opacity: 0;
        font-size: 1.1rem;
        transition: opacity 0.2s ease;
    }

    .color-option input:checked + .color-swatch {
        border-color: var(--navy-900);
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .color-option input:checked + .color-swatch i {
        opacity: 1;
    }

    .color-option .color-swatch:hover {
        transform: scale(1.05);
    }
</style>
@endpush
