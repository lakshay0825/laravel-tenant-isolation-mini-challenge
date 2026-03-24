<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class PhiDocumentStorageService
{
    public function diskName(): string
    {
        return (string) config('compliance.phi_documents_disk', 'phi_local');
    }

    public function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    public function put(string $relativePath, mixed $contents, array $options = []): bool|string
    {
        return $this->disk()->put($relativePath, $contents, $options);
    }

    public function exists(string $relativePath): bool
    {
        return $this->disk()->exists($relativePath);
    }

    /**
     * Local path for native file response; only valid for local / sftp-style drivers.
     */
    public function absolutePath(string $relativePath): string
    {
        return $this->disk()->path($relativePath);
    }

    public function isCloudDriver(): bool
    {
        $driver = config('filesystems.disks.'.$this->diskName().'.driver');

        return $driver === 's3';
    }

    /**
     * Return a file download for local disks, or a temporary redirect to a pre-signed URL for S3.
     */
    public function responseForRelativePath(string $relativePath, int $temporaryMinutes = 5): BinaryFileResponse|RedirectResponse
    {
        if ($this->isCloudDriver()) {
            $url = $this->disk()->temporaryUrl($relativePath, now()->addMinutes($temporaryMinutes));

            return redirect()->away($url);
        }

        return response()->file($this->absolutePath($relativePath));
    }
}
