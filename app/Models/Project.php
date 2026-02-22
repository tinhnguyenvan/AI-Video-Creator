<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
        'master_character',
        'color',
        'status',
    ];

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function getVideoCountAttribute(): int
    {
        return $this->videos()->count();
    }

    public function getCompletedCountAttribute(): int
    {
        return $this->videos()->where('status', 'completed')->count();
    }

    public function getProcessingCountAttribute(): int
    {
        return $this->videos()->whereIn('status', ['pending', 'processing'])->count();
    }

    public function getStatusLabelAttribute(): string
    {
        $total = $this->videos()->count();
        if ($total === 0) return 'Trống';

        $processing = $this->processing_count;
        if ($processing > 0) return 'Đang xử lý';

        return 'Hoạt động';
    }

    public function getStatusBadgeAttribute(): string
    {
        $total = $this->videos()->count();
        if ($total === 0) return 'secondary';

        $processing = $this->processing_count;
        if ($processing > 0) return 'info';

        return 'success';
    }

    public static function colorOptions(): array
    {
        return [
            '#3b82f6' => 'Xanh dương',
            '#10b981' => 'Xanh lá',
            '#f59e0b' => 'Cam',
            '#ef4444' => 'Đỏ',
            '#8b5cf6' => 'Tím',
            '#ec4899' => 'Hồng',
            '#06b6d4' => 'Cyan',
            '#64748b' => 'Xám',
        ];
    }
}
