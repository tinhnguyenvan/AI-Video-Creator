@extends('layouts.app')

@section('title', 'Ghép Video - ' . $project->name)
@section('page-title', 'Ghép Video')
@section('breadcrumb')
    <a href="{{ route('home') }}">Trang chủ</a> <span class="mx-1">/</span>
    <a href="{{ route('projects.index') }}">Dự án</a> <span class="mx-1">/</span>
    <a href="{{ route('projects.show', $project) }}">{{ Str::limit($project->name, 20) }}</a> <span class="mx-1">/</span>
    Ghép Video
@endsection

@section('content')
    @if(!$ffmpegAvailable)
        <div class="alert mb-4" style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px;">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon red" style="width: 48px; height: 48px;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1 text-danger">FFmpeg chưa được cài đặt</h6>
                    <p class="mb-0 text-muted small">Tính năng ghép video yêu cầu FFmpeg. Chạy <code>brew install ffmpeg</code> trên macOS hoặc <code>apt install ffmpeg</code> trên Ubuntu.</p>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('projects.execute-merge', $project) }}" method="POST" id="mergeForm">
        @csrf
        <div class="row g-4">
            {{-- Main: Video Selection & Ordering --}}
            <div class="col-lg-8">
                <div class="card-panel mb-4">
                    <div class="card-panel-header">
                        <h5><i class="bi bi-film me-2"></i>Chọn & Sắp xếp Video</h5>
                        <span class="badge bg-info" id="selectedCount">0 video đã chọn</span>
                    </div>
                    <div class="card-panel-body">
                        @if($completedVideos->count() >= 2)
                            <p class="text-muted small mb-3">
                                <i class="bi bi-info-circle me-1"></i>
                                Nhấn vào video để chọn/bỏ chọn. Kéo thả để sắp xếp thứ tự ghép.
                            </p>

                            {{-- Available Videos --}}
                            <div class="mb-4">
                                <label class="form-label fw-semibold"><i class="bi bi-collection me-1"></i> Video có sẵn</label>
                                <div class="row g-3" id="availableVideos">
                                    @foreach($completedVideos as $video)
                                        <div class="col-md-6">
                                            <div class="merge-video-item" data-video-id="{{ $video->id }}" data-video-title="{{ $video->title }}" onclick="toggleVideoSelection(this)">
                                                <div class="merge-video-thumb">
                                                    @if($video->video_path)
                                                        <video muted preload="metadata">
                                                            <source src="{{ asset('storage/' . $video->video_path) }}" type="video/mp4">
                                                        </video>
                                                    @else
                                                        <div class="merge-video-placeholder">
                                                            <i class="bi bi-film"></i>
                                                        </div>
                                                    @endif
                                                    <div class="merge-video-check">
                                                        <i class="bi bi-check-lg"></i>
                                                    </div>
                                                    <div class="merge-video-duration">
                                                        {{ $video->duration ?? '?' }}s
                                                    </div>
                                                </div>
                                                <div class="merge-video-info">
                                                    <div class="fw-semibold small text-truncate">{{ $video->title }}</div>
                                                    <div class="text-muted" style="font-size: 0.7rem;">
                                                        {{ $video->resolution }} &bull; {{ $video->created_at->format('d/m/Y') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Selected Order --}}
                            <div>
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-sort-numeric-down me-1"></i> Thứ tự ghép
                                    <span class="text-muted fw-normal">(kéo thả để sắp xếp)</span>
                                </label>
                                <div id="sortableList" class="merge-sort-list">
                                    <div class="merge-sort-empty" id="sortEmptyState">
                                        <i class="bi bi-hand-index-thumb mb-2" style="font-size: 1.5rem; color: var(--navy-300);"></i>
                                        <p class="mb-0 text-muted small">Nhấn chọn video ở trên để thêm vào danh sách ghép</p>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="empty-state py-4">
                                <div class="empty-state-icon" style="width: 64px; height: 64px; border-radius: 16px; font-size: 1.5rem;">
                                    <i class="bi bi-camera-reels"></i>
                                </div>
                                <h5>Không đủ video để ghép</h5>
                                <p>Cần ít nhất 2 video đã hoàn thành. Hiện có {{ $completedVideos->count() }} video.</p>
                                <a href="{{ route('videos.create', ['project' => $project->id]) }}" class="btn btn-primary-dark">
                                    <i class="bi bi-plus-lg me-2"></i>Tạo thêm Video
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Sidebar: Options --}}
            <div class="col-lg-4">
                {{-- Output Settings --}}
                <div class="card-panel mb-4">
                    <div class="card-panel-header">
                        <h5><i class="bi bi-gear me-2"></i>Cài đặt ghép</h5>
                    </div>
                    <div class="card-panel-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">
                                <i class="bi bi-type me-1"></i> Tiêu đề video ghép
                            </label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                   id="title" name="title"
                                   value="{{ old('title', $project->name . ' - Ghép') }}"
                                   placeholder="Tên video đầu ra" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="transition" class="form-label">
                                <i class="bi bi-arrows-collapse me-1"></i> Hiệu ứng chuyển cảnh
                            </label>
                            <select class="form-select" id="transition" name="transition">
                                <option value="none" {{ old('transition') === 'none' ? 'selected' : '' }}>Không có (Cắt thẳng)</option>
                                <option value="fade" {{ old('transition', 'fade') === 'fade' ? 'selected' : '' }}>Fade (Mờ dần)</option>
                                <option value="crossfade" {{ old('transition') === 'crossfade' ? 'selected' : '' }}>Crossfade (Chồng mờ)</option>
                            </select>
                        </div>

                        <div class="mb-4" id="transitionDurationGroup">
                            <label for="transition_duration" class="form-label">
                                <i class="bi bi-stopwatch me-1"></i> Thời lượng chuyển cảnh
                            </label>
                            <select class="form-select" id="transition_duration" name="transition_duration">
                                <option value="0.3">0.3 giây</option>
                                <option value="0.5" selected>0.5 giây</option>
                                <option value="0.8">0.8 giây</option>
                                <option value="1.0">1.0 giây</option>
                                <option value="1.5">1.5 giây</option>
                                <option value="2.0">2.0 giây</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary-dark w-100 btn-lg" id="mergeBtn" disabled>
                            <i class="bi bi-layers me-2"></i>Ghép Video
                        </button>
                    </div>
                </div>

                {{-- Preview Info --}}
                <div class="card-panel mb-4">
                    <div class="card-panel-header">
                        <h5><i class="bi bi-info-circle me-2"></i>Thông tin</h5>
                    </div>
                    <div class="card-panel-body">
                        <table class="table table-detail table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td><i class="bi bi-collection-play me-2"></i>Đã chọn</td>
                                    <td><span id="infoCount">0</span> video</td>
                                </tr>
                                <tr>
                                    <td><i class="bi bi-stopwatch me-2"></i>Tổng thời lượng</td>
                                    <td><span id="infoDuration">0</span> giây</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- FFmpeg Info --}}
                <div class="card-panel" style="background: linear-gradient(135deg, var(--navy-900), var(--navy-700)); border: none;">
                    <div class="card-panel-body text-white">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="stat-icon {{ $ffmpegAvailable ? 'green' : 'red' }}" style="width: 36px; height: 36px; font-size: 0.9rem;">
                                <i class="bi bi-{{ $ffmpegAvailable ? 'check-circle-fill' : 'x-circle-fill' }}"></i>
                            </div>
                            <div>
                                <div class="fw-bold small">FFmpeg</div>
                                <div style="font-size: 0.75rem; color: var(--navy-200);">
                                    {{ $ffmpegAvailable ? 'v' . $ffmpegVersion : 'Chưa cài đặt' }}
                                </div>
                            </div>
                        </div>
                        <p class="mb-0" style="font-size: 0.78rem; color: var(--navy-200); line-height: 1.6;">
                            FFmpeg xử lý ghép video phía server. Video được re-encode để đảm bảo tương thích.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('styles')
<style>
    .merge-video-item {
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.25s ease;
        position: relative;
    }

    .merge-video-item:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }

    .merge-video-item.selected {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px var(--accent-soft), 0 4px 12px rgba(0,0,0,0.06);
    }

    .merge-video-thumb {
        height: 120px;
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, var(--navy-900), var(--navy-700));
    }

    .merge-video-thumb video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .merge-video-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--navy-400);
        font-size: 2rem;
    }

    .merge-video-check {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: rgba(255,255,255,0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transform: scale(0.8);
        transition: all 0.2s ease;
        color: var(--accent);
        font-size: 1rem;
        font-weight: bold;
    }

    .merge-video-item.selected .merge-video-check {
        opacity: 1;
        transform: scale(1);
        background: var(--accent);
        color: #fff;
    }

    .merge-video-duration {
        position: absolute;
        bottom: 6px;
        right: 6px;
        background: rgba(0,0,0,0.7);
        color: #fff;
        font-size: 0.65rem;
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: 600;
    }

    .merge-video-info {
        padding: 0.6rem 0.75rem;
    }

    /* Sortable List */
    .merge-sort-list {
        min-height: 60px;
        border: 2px dashed #e2e8f0;
        border-radius: 10px;
        padding: 0.5rem;
        transition: border-color 0.2s;
    }

    .merge-sort-list:empty + .merge-sort-empty,
    .merge-sort-empty {
        padding: 1.5rem;
        text-align: center;
    }

    .merge-sort-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 0.6rem 0.75rem;
        margin-bottom: 0.35rem;
        cursor: grab;
        transition: all 0.2s ease;
        user-select: none;
    }

    .merge-sort-item:active {
        cursor: grabbing;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border-color: var(--accent);
    }

    .merge-sort-item:last-child {
        margin-bottom: 0;
    }

    .merge-sort-item .sort-handle {
        color: var(--navy-300);
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .merge-sort-item .sort-number {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        background: var(--accent);
        color: #fff;
        font-size: 0.7rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .merge-sort-item .sort-title {
        flex: 1;
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--navy-900);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .merge-sort-item .sort-remove {
        color: var(--navy-300);
        cursor: pointer;
        font-size: 0.85rem;
        padding: 2px;
        border-radius: 4px;
        transition: all 0.15s;
    }

    .merge-sort-item .sort-remove:hover {
        color: var(--danger);
        background: #fef2f2;
    }

    .merge-sort-item .sort-transition-icon {
        display: none;
    }

    /* Dragging visual */
    .merge-sort-item.dragging {
        opacity: 0.5;
    }

    /* Loading overlay */
    .merge-loading {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.6);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        color: #fff;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectedVideoIds = [];
    const sortableList = document.getElementById('sortableList');
    const sortEmptyState = document.getElementById('sortEmptyState');
    const mergeBtn = document.getElementById('mergeBtn');
    const transitionSelect = document.getElementById('transition');
    const transitionDurationGroup = document.getElementById('transitionDurationGroup');

    // Toggle transition duration visibility
    transitionSelect.addEventListener('change', function() {
        transitionDurationGroup.style.display = this.value === 'none' ? 'none' : 'block';
    });
    transitionSelect.dispatchEvent(new Event('change'));

    // Toggle video selection
    window.toggleVideoSelection = function(element) {
        const videoId = parseInt(element.dataset.videoId);
        const videoTitle = element.dataset.videoTitle;
        const idx = selectedVideoIds.indexOf(videoId);

        if (idx > -1) {
            // Deselect
            selectedVideoIds.splice(idx, 1);
            element.classList.remove('selected');
            removeSortItem(videoId);
        } else {
            // Select
            selectedVideoIds.push(videoId);
            element.classList.add('selected');
            addSortItem(videoId, videoTitle);
        }

        updateUI();
    };

    function addSortItem(videoId, title) {
        if (sortEmptyState) sortEmptyState.style.display = 'none';

        const item = document.createElement('div');
        item.className = 'merge-sort-item';
        item.dataset.videoId = videoId;
        item.draggable = true;
        item.innerHTML = `
            <span class="sort-handle"><i class="bi bi-grip-vertical"></i></span>
            <span class="sort-number">${selectedVideoIds.length}</span>
            <input type="hidden" name="video_ids[]" value="${videoId}">
            <span class="sort-title">${title}</span>
            <span class="sort-remove" onclick="removeFromSort(${videoId})" title="Bỏ chọn">
                <i class="bi bi-x-lg"></i>
            </span>
        `;

        // Drag events
        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragend', handleDragEnd);
        item.addEventListener('dragover', handleDragOver);
        item.addEventListener('drop', handleDrop);

        sortableList.appendChild(item);
    }

    function removeSortItem(videoId) {
        const item = sortableList.querySelector(`[data-video-id="${videoId}"]`);
        if (item) item.remove();

        if (sortableList.querySelectorAll('.merge-sort-item').length === 0 && sortEmptyState) {
            sortEmptyState.style.display = 'block';
        }

        renumberItems();
    }

    window.removeFromSort = function(videoId) {
        const idx = selectedVideoIds.indexOf(videoId);
        if (idx > -1) selectedVideoIds.splice(idx, 1);

        // Deselect the card
        const card = document.querySelector(`.merge-video-item[data-video-id="${videoId}"]`);
        if (card) card.classList.remove('selected');

        removeSortItem(videoId);
        updateUI();
    };

    function renumberItems() {
        sortableList.querySelectorAll('.merge-sort-item').forEach((item, i) => {
            item.querySelector('.sort-number').textContent = i + 1;
        });
    }

    function updateUI() {
        const count = selectedVideoIds.length;
        document.getElementById('selectedCount').textContent = count + ' video đã chọn';
        document.getElementById('infoCount').textContent = count;

        // Calculate total duration from data attributes
        let totalDuration = 0;
        selectedVideoIds.forEach(id => {
            const videoEl = document.querySelector(`.merge-video-item[data-video-id="${id}"]`);
            if (videoEl) {
                const durText = videoEl.querySelector('.merge-video-duration')?.textContent;
                const dur = parseFloat(durText);
                if (!isNaN(dur)) totalDuration += dur;
            }
        });
        document.getElementById('infoDuration').textContent = totalDuration || '~';

        mergeBtn.disabled = count < 2;
        if (count >= 2) {
            mergeBtn.innerHTML = '<i class="bi bi-layers me-2"></i>Ghép ' + count + ' Video';
        } else {
            mergeBtn.innerHTML = '<i class="bi bi-layers me-2"></i>Ghép Video';
        }
    }

    // Drag & Drop
    let draggedItem = null;

    function handleDragStart(e) {
        draggedItem = this;
        this.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    }

    function handleDragEnd(e) {
        this.classList.remove('dragging');
        draggedItem = null;
        renumberItems();
        // Update hidden input order
        updateHiddenInputOrder();
    }

    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const sortItems = [...sortableList.querySelectorAll('.merge-sort-item:not(.dragging)')];
        const nextItem = sortItems.find(item => {
            const rect = item.getBoundingClientRect();
            return e.clientY < rect.top + rect.height / 2;
        });

        if (nextItem) {
            sortableList.insertBefore(draggedItem, nextItem);
        } else {
            sortableList.appendChild(draggedItem);
        }
    }

    function handleDrop(e) {
        e.preventDefault();
    }

    function updateHiddenInputOrder() {
        // Update selectedVideoIds array to match DOM order
        selectedVideoIds.length = 0;
        sortableList.querySelectorAll('.merge-sort-item').forEach(item => {
            selectedVideoIds.push(parseInt(item.dataset.videoId));
        });
    }

    // Form submission with loading overlay
    document.getElementById('mergeForm').addEventListener('submit', function(e) {
        if (selectedVideoIds.length < 2) {
            e.preventDefault();
            return;
        }

        mergeBtn.disabled = true;
        mergeBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang ghép video...';

        // Show loading overlay
        const overlay = document.createElement('div');
        overlay.className = 'merge-loading';
        overlay.innerHTML = `
            <div class="spinner-border spinner-accent mb-4" style="width: 3.5rem; height: 3.5rem; border-width: 3px;"></div>
            <h5 class="fw-bold mb-2">Đang ghép video...</h5>
            <p style="color: var(--navy-200); font-size: 0.9rem;">Quá trình này có thể mất vài phút tùy theo số lượng và thời lượng video</p>
        `;
        document.body.appendChild(overlay);
    });
});
</script>
@endpush
