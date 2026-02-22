<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = [
        'title',
        'prompt',
        'status',
        'video_path',
        'thumbnail_path',
        'google_operation_name',
        'error_message',
        'duration',
        'resolution',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'processing' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
            default => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Chờ xử lý',
            'processing' => 'Đang tạo',
            'completed' => 'Hoàn thành',
            'failed' => 'Thất bại',
            default => 'Không xác định',
        };
    }
}
