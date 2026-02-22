<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoMergeService
{
    protected string $ffmpegPath;
    protected string $ffprobePath;

    public function __construct()
    {
        $this->ffmpegPath = config('services_google.ffmpeg_path', '/opt/homebrew/bin/ffmpeg');
        $this->ffprobePath = config('services_google.ffprobe_path', '/opt/homebrew/bin/ffprobe');
    }

    /**
     * Merge multiple videos into one using FFmpeg concat demuxer
     *
     * @param array $videoPaths Array of video storage paths (relative to public disk)
     * @param string $outputFilename Desired output filename
     * @param string $transition Transition type: 'none', 'fade', 'crossfade'
     * @param float $transitionDuration Duration of transition in seconds
     * @return array ['success' => bool, 'path' => string|null, 'error' => string|null, 'duration' => float|null]
     */
    public function mergeVideos(
        array $videoPaths,
        string $outputFilename,
        string $transition = 'none',
        float $transitionDuration = 0.5
    ): array {
        if (count($videoPaths) < 2) {
            return ['success' => false, 'path' => null, 'error' => 'Cần ít nhất 2 video để ghép.', 'duration' => null];
        }

        // Verify all files exist
        $storagePath = Storage::disk('public')->path('');
        $absolutePaths = [];
        foreach ($videoPaths as $path) {
            $fullPath = $storagePath . $path;
            if (!file_exists($fullPath)) {
                return [
                    'success' => false,
                    'path' => null,
                    'error' => 'File không tồn tại: ' . $path,
                    'duration' => null,
                ];
            }
            $absolutePaths[] = $fullPath;
        }

        try {
            if ($transition === 'none') {
                return $this->concatVideos($absolutePaths, $outputFilename);
            } else {
                return $this->mergeWithTransition($absolutePaths, $outputFilename, $transition, $transitionDuration);
            }
        } catch (\Exception $e) {
            Log::error('Video merge failed: ' . $e->getMessage(), [
                'paths' => $videoPaths,
                'transition' => $transition,
            ]);

            return [
                'success' => false,
                'path' => null,
                'error' => 'Lỗi ghép video: ' . $e->getMessage(),
                'duration' => null,
            ];
        }
    }

    /**
     * Simple concat using FFmpeg concat demuxer (fastest, no re-encoding for same format)
     */
    protected function concatVideos(array $absolutePaths, string $outputFilename): array
    {
        $outputDir = Storage::disk('public')->path('videos');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $outputPath = $outputDir . '/' . $outputFilename;

        // Create a temporary file list for concat
        $listFile = tempnam(sys_get_temp_dir(), 'ffmpeg_concat_');
        $listContent = '';
        foreach ($absolutePaths as $path) {
            $listContent .= "file '" . str_replace("'", "'\\''", $path) . "'\n";
        }
        file_put_contents($listFile, $listContent);

        // FFmpeg concat demuxer - re-encode to ensure compatibility
        $command = sprintf(
            '%s -y -f concat -safe 0 -i %s -c:v libx264 -preset fast -crf 23 -c:a aac -b:a 128k -movflags +faststart %s 2>&1',
            escapeshellarg($this->ffmpegPath),
            escapeshellarg($listFile),
            escapeshellarg($outputPath)
        );

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        @unlink($listFile);

        if ($returnCode !== 0) {
            $errorOutput = implode("\n", array_slice($output, -10));
            Log::error('FFmpeg concat failed', ['output' => $errorOutput, 'code' => $returnCode]);
            return [
                'success' => false,
                'path' => null,
                'error' => 'FFmpeg concat thất bại (code: ' . $returnCode . ')',
                'duration' => null,
            ];
        }

        $duration = $this->getVideoDuration($outputPath);

        return [
            'success' => true,
            'path' => 'videos/' . $outputFilename,
            'error' => null,
            'duration' => $duration,
        ];
    }

    /**
     * Merge with fade/crossfade transitions using xfade filter
     */
    protected function mergeWithTransition(
        array $absolutePaths,
        string $outputFilename,
        string $transition,
        float $transitionDuration
    ): array {
        $outputDir = Storage::disk('public')->path('videos');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $outputPath = $outputDir . '/' . $outputFilename;

        // Get durations of each video
        $durations = [];
        foreach ($absolutePaths as $path) {
            $dur = $this->getVideoDuration($path);
            if ($dur === null || $dur <= 0) {
                return [
                    'success' => false,
                    'path' => null,
                    'error' => 'Không thể đọc thời lượng video: ' . basename($path),
                    'duration' => null,
                ];
            }
            $durations[] = $dur;
        }

        // Build complex filter for xfade transitions
        $inputs = '';
        foreach ($absolutePaths as $i => $path) {
            $inputs .= sprintf(' -i %s', escapeshellarg($path));
        }

        $n = count($absolutePaths);
        $xfadeType = $transition === 'crossfade' ? 'fade' : 'fade';
        $filterParts = [];
        $offsets = [];

        // Calculate offsets: each transition starts at (cumulative duration - transition_duration * index)
        $cumulativeDuration = 0;
        for ($i = 0; $i < $n - 1; $i++) {
            $cumulativeDuration += $durations[$i];
            $offset = $cumulativeDuration - $transitionDuration * ($i + 1);
            $offsets[] = max(0, round($offset, 3));

            $prevLabel = $i === 0 ? '[0:v]' : '[v' . $i . ']';
            $nextLabel = '[' . ($i + 1) . ':v]';
            $outLabel = $i === $n - 2 ? '[vout]' : '[v' . ($i + 1) . ']';

            $filterParts[] = sprintf(
                '%s%sxfade=transition=%s:duration=%s:offset=%s%s',
                $prevLabel,
                $nextLabel,
                $xfadeType,
                $transitionDuration,
                $offsets[$i],
                $outLabel
            );
        }

        // Audio crossfade
        $audioFilters = [];
        for ($i = 0; $i < $n - 1; $i++) {
            $prevLabel = $i === 0 ? '[0:a]' : '[a' . $i . ']';
            $nextLabel = '[' . ($i + 1) . ':a]';
            $outLabel = $i === $n - 2 ? '[aout]' : '[a' . ($i + 1) . ']';

            $audioFilters[] = sprintf(
                '%s%sacrossfade=d=%s:c1=tri:c2=tri%s',
                $prevLabel,
                $nextLabel,
                $transitionDuration,
                $outLabel
            );
        }

        $filterComplex = implode(';', $filterParts);

        // Check if videos have audio - if not, skip audio crossfade
        $hasAudio = $this->videoHasAudio($absolutePaths[0]);

        if ($hasAudio && !empty($audioFilters)) {
            $filterComplex .= ';' . implode(';', $audioFilters);
            $mapArgs = '-map "[vout]" -map "[aout]"';
        } else {
            $mapArgs = '-map "[vout]"';
        }

        $command = sprintf(
            '%s -y%s -filter_complex %s %s -c:v libx264 -preset fast -crf 23 -c:a aac -b:a 128k -movflags +faststart %s 2>&1',
            escapeshellarg($this->ffmpegPath),
            $inputs,
            escapeshellarg($filterComplex),
            $mapArgs,
            escapeshellarg($outputPath)
        );

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error('FFmpeg xfade merge failed', [
                'command' => $command,
                'output' => implode("\n", array_slice($output, -15)),
                'code' => $returnCode,
            ]);

            // Fallback to simple concat
            Log::info('Falling back to simple concat...');
            return $this->concatVideos($absolutePaths, $outputFilename);
        }

        $duration = $this->getVideoDuration($outputPath);

        return [
            'success' => true,
            'path' => 'videos/' . $outputFilename,
            'error' => null,
            'duration' => $duration,
        ];
    }

    /**
     * Get video duration in seconds using ffprobe
     */
    public function getVideoDuration(string $filePath): ?float
    {
        $command = sprintf(
            '%s -v error -show_entries format=duration -of csv=p=0 %s 2>&1',
            escapeshellarg($this->ffprobePath),
            escapeshellarg($filePath)
        );

        $output = trim(shell_exec($command) ?? '');
        return is_numeric($output) ? (float) $output : null;
    }

    /**
     * Check if a video file has an audio stream
     */
    protected function videoHasAudio(string $filePath): bool
    {
        $command = sprintf(
            '%s -v error -select_streams a -show_entries stream=codec_type -of csv=p=0 %s 2>&1',
            escapeshellarg($this->ffprobePath),
            escapeshellarg($filePath)
        );

        $output = trim(shell_exec($command) ?? '');
        return str_contains($output, 'audio');
    }

    /**
     * Check if FFmpeg is available
     */
    public function isAvailable(): bool
    {
        return file_exists($this->ffmpegPath) && is_executable($this->ffmpegPath);
    }

    /**
     * Get FFmpeg version info
     */
    public function getVersion(): ?string
    {
        if (!$this->isAvailable()) {
            return null;
        }

        $output = shell_exec(escapeshellarg($this->ffmpegPath) . ' -version 2>&1');
        if ($output && preg_match('/ffmpeg version (\S+)/', $output, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
