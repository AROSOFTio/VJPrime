<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImageAssetService
{
    /**
     * Store image on public disk as optimized webp when supported.
     */
    public function storeOptimizedPublicImage(
        UploadedFile $file,
        string $directory,
        int $maxWidth,
        int $maxHeight,
        int $quality = 82,
        string $errorField = 'poster_file'
    ): string {
        $mime = strtolower((string) $file->getMimeType());
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];

        if (! in_array($mime, $allowed, true)) {
            throw ValidationException::withMessages([
                $errorField => 'Only JPG, JPEG, PNG, and WEBP images are allowed.',
            ]);
        }

        $baseName = pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeBase = Str::slug($baseName, '-');
        if ($safeBase === '') {
            $safeBase = 'image';
        }

        $timestamp = now()->format('YmdHis');
        $directory = trim($directory, '/');

        if (! $this->canOptimizeWithGd()) {
            return $this->storeOriginalPublic($file, $directory, $safeBase, $timestamp, $mime, $errorField);
        }

        $source = $this->createImageResource($file->getRealPath(), $mime);
        if (! is_resource($source) && ! is_object($source)) {
            return $this->storeOriginalPublic($file, $directory, $safeBase, $timestamp, $mime, $errorField);
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            imagedestroy($source);

            return $this->storeOriginalPublic($file, $directory, $safeBase, $timestamp, $mime, $errorField);
        }

        $ratio = min(
            1,
            $maxWidth > 0 ? ($maxWidth / $sourceWidth) : 1,
            $maxHeight > 0 ? ($maxHeight / $sourceHeight) : 1
        );
        $targetWidth = max(1, (int) floor($sourceWidth * $ratio));
        $targetHeight = max(1, (int) floor($sourceHeight * $ratio));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        $tempPath = tempnam(sys_get_temp_dir(), 'vjprime_img_');
        if (! is_string($tempPath) || $tempPath === '') {
            imagedestroy($source);
            imagedestroy($target);

            return $this->storeOriginalPublic($file, $directory, $safeBase, $timestamp, $mime, $errorField);
        }

        $written = imagewebp($target, $tempPath, max(60, min(92, $quality)));
        imagedestroy($source);
        imagedestroy($target);

        if (! $written) {
            @unlink($tempPath);

            return $this->storeOriginalPublic($file, $directory, $safeBase, $timestamp, $mime, $errorField);
        }

        $relativePath = "{$directory}/{$safeBase}-{$timestamp}.webp";
        $stream = fopen($tempPath, 'rb');
        if (! is_resource($stream)) {
            @unlink($tempPath);

            return $this->storeOriginalPublic($file, $directory, $safeBase, $timestamp, $mime, $errorField);
        }

        Storage::disk('public')->put($relativePath, $stream);
        fclose($stream);
        @unlink($tempPath);

        return Storage::disk('public')->url($relativePath);
    }

    private function canOptimizeWithGd(): bool
    {
        return function_exists('imagecreatetruecolor')
            && function_exists('imagecopyresampled')
            && function_exists('imagewebp');
    }

    private function createImageResource(string $path, string $mime): mixed
    {
        return match ($mime) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : false,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($path) : false,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
    }

    private function storeOriginalPublic(
        UploadedFile $file,
        string $directory,
        string $safeBase,
        string $timestamp,
        string $mime,
        string $errorField
    ): string {
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => strtolower($file->getClientOriginalExtension() ?: 'jpg'),
        };

        $storedPath = $file->storeAs($directory, "{$safeBase}-{$timestamp}.{$extension}", 'public');
        if (! is_string($storedPath) || $storedPath === '') {
            throw ValidationException::withMessages([
                $errorField => 'Failed to store image file.',
            ]);
        }

        return Storage::disk('public')->url($storedPath);
    }
}
