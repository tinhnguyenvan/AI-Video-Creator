@extends('layouts.app')

@section('title', 'Chỉnh sửa: ' . $project->name)
@section('page-title', 'Chỉnh sửa dự án')
@section('breadcrumb')
    <a href="{{ route('home') }}">Trang chủ</a> <span class="mx-1">/</span> <a href="{{ route('projects.index') }}">Dự án</a> <span class="mx-1">/</span> <a href="{{ route('projects.show', $project) }}">{{ Str::limit($project->name, 20) }}</a> <span class="mx-1">/</span> Chỉnh sửa
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card-panel">
                <div class="card-panel-header">
                    <h5><i class="bi bi-pencil-square me-2"></i>Chỉnh sửa Dự Án</h5>
                </div>
                <div class="card-panel-body">
                    <form action="{{ route('projects.update', $project) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Name --}}
                        <div class="mb-4">
                            <label for="name" class="form-label">
                                <i class="bi bi-type me-1"></i> Tên dự án
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $project->name) }}"
                                   placeholder="Tên dự án" required autofocus>
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
                                      placeholder="Mô tả ngắn gọn về dự án...">{{ old('description', $project->description) }}</textarea>
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
                                      placeholder="Mô tả chi tiết nhân vật chính xuất hiện xuyên suốt các video...">{{ old('master_character', $project->master_character) }}</textarea>
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
                                               {{ old('color', $project->color) === $hex ? 'checked' : '' }} class="d-none">
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
                            <a href="{{ route('projects.show', $project) }}" class="btn btn-ghost">
                                <i class="bi bi-x-lg me-1"></i> Hủy
                            </a>
                            <button type="submit" class="btn btn-primary-dark btn-lg px-5">
                                <i class="bi bi-check-lg me-2"></i>Lưu thay đổi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar info --}}
        <div class="col-lg-4">
            <div class="card-panel mb-4">
                <div class="card-panel-header">
                    <h5><i class="bi bi-info-circle me-2"></i>Thông tin</h5>
                </div>
                <div class="card-panel-body">
                    <table class="table table-detail table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td><i class="bi bi-calendar3 me-2"></i>Tạo ngày</td>
                                <td>{{ $project->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            <tr>
                                <td><i class="bi bi-collection-play me-2"></i>Số video</td>
                                <td>{{ $project->videos()->count() }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-panel" style="border-color: #fecaca;">
                <div class="card-panel-header" style="background: #fef2f2; border-bottom-color: #fecaca;">
                    <h5 class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Vùng nguy hiểm</h5>
                </div>
                <div class="card-panel-body">
                    <p class="text-muted small mb-3">Xóa dự án sẽ không xóa các video bên trong. Video sẽ trở thành "không thuộc dự án nào".</p>
                    <form action="{{ route('projects.destroy', $project) }}" method="POST"
                          onsubmit="return confirm('Bạn có chắc muốn xóa dự án này?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-ghost text-danger w-100" style="border-color: #fecaca;">
                            <i class="bi bi-trash me-1"></i> Xóa dự án
                        </button>
                    </form>
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
