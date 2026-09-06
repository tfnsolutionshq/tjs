<?php

namespace App\Services\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class HybridDisk
{
    public const KIND_DOCUMENTS = 'documents';

    public const KIND_MEDIA = 'media';

    public function documentsDisk(): string
    {
        return $this->normalize(config('filesystems.documents', 'local'), self::KIND_DOCUMENTS);
    }

    public function mediaDisk(): string
    {
        return $this->normalize(config('filesystems.media', 'public'), self::KIND_MEDIA);
    }

    /**
     * Disks to try, preferred first, then current config, then the other provider.
     *
     * @return list<string>
     */
    public function candidates(string $kind, ?string $preferred = null): array
    {
        $primary = $kind === self::KIND_MEDIA ? $this->mediaDisk() : $this->documentsDisk();
        $legacy = $kind === self::KIND_MEDIA ? 'public' : 'local';

        $disks = [$preferred, $primary, $legacy, 's3'];

        return array_values(array_unique(array_filter($disks)));
    }

    public function locate(string $path, string $kind, ?string $preferred = null): ?string
    {
        foreach ($this->candidates($kind, $preferred) as $disk) {
            if ($this->existsOn($disk, $path)) {
                return $disk;
            }
        }

        return null;
    }

    public function exists(string $path, string $kind, ?string $preferred = null): bool
    {
        return $this->locate($path, $kind, $preferred) !== null;
    }

    public function filesystem(string $disk): Filesystem
    {
        return Storage::disk($disk);
    }

    public function storeAs(UploadedFile $file, string $directory, string $filename, string $kind): array
    {
        $disk = $kind === self::KIND_MEDIA ? $this->mediaDisk() : $this->documentsDisk();

        try {
            $path = $file->storeAs($directory, $filename, $disk);
        } catch (Throwable $e) {
            if ($disk !== 's3') {
                throw $e;
            }

            // Misconfigured S3 should not block uploads — fall back to local disks.
            $disk = $kind === self::KIND_MEDIA ? 'public' : 'local';
            $path = $file->storeAs($directory, $filename, $disk);
        }

        return [$path, $disk];
    }

    public function store(UploadedFile $file, string $directory, string $kind): array
    {
        $disk = $kind === self::KIND_MEDIA ? $this->mediaDisk() : $this->documentsDisk();

        try {
            $path = $file->store($directory, $disk);
        } catch (Throwable $e) {
            if ($disk !== 's3') {
                throw $e;
            }

            $disk = $kind === self::KIND_MEDIA ? 'public' : 'local';
            $path = $file->store($directory, $disk);
        }

        return [$path, $disk];
    }

    public function put(string $path, mixed $contents, string $kind): string
    {
        $disk = $kind === self::KIND_MEDIA ? $this->mediaDisk() : $this->documentsDisk();
        Storage::disk($disk)->put($path, $contents);

        return $disk;
    }

    public function copy(string $from, string $to, string $kind, ?string $preferred = null): ?string
    {
        $disk = $this->locate($from, $kind, $preferred);
        if (! $disk) {
            return null;
        }

        Storage::disk($disk)->copy($from, $to);

        return $disk;
    }

    public function delete(string $path, string $kind, ?string $preferred = null): void
    {
        foreach ($this->candidates($kind, $preferred) as $disk) {
            if ($this->existsOn($disk, $path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }

    public function deleteDirectory(string $directory): void
    {
        foreach (array_unique([...$this->candidates(self::KIND_MEDIA), ...$this->candidates(self::KIND_DOCUMENTS)]) as $disk) {
            try {
                Storage::disk($disk)->deleteDirectory($directory);
            } catch (Throwable) {
                // Ignore missing remote credentials / empty prefixes.
            }
        }
    }

    public function url(string $path, string $kind, ?string $preferred = null): ?string
    {
        $disk = $this->locate($path, $kind, $preferred);
        if (! $disk) {
            return null;
        }

        try {
            return Storage::disk($disk)->url($path);
        } catch (Throwable) {
            return null;
        }
    }

    public function download(string $path, string $kind, ?string $preferred = null, ?string $name = null): StreamedResponse
    {
        $disk = $this->locate($path, $kind, $preferred);
        abort_unless($disk, 404, 'Document not found.');

        return Storage::disk($disk)->download($path, $name ?: basename($path));
    }

    /**
     * Stream a file from whichever disk holds it (local or S3).
     *
     * @param  array<string, string>  $headers
     */
    public function response(string $path, string $kind, ?string $preferred, string $name, array $headers = []): StreamedResponse
    {
        $disk = $this->locate($path, $kind, $preferred);
        abort_unless($disk, 404, 'Document not found.');

        return Storage::disk($disk)->response($path, $name, $headers);
    }

    /**
     * Absolute local path for tools that need a real file (PDF stamping).
     * Remote objects are copied onto the local disk cache.
     */
    public function localAbsolutePath(string $path, string $kind, ?string $preferred = null): ?string
    {
        $disk = $this->locate($path, $kind, $preferred);
        if (! $disk) {
            return null;
        }

        $adapterRoot = config("filesystems.disks.{$disk}.root");
        if (is_string($adapterRoot) && $adapterRoot !== '' && Storage::disk($disk)->exists($path)) {
            try {
                $absolute = Storage::disk($disk)->path($path);
                if (is_file($absolute)) {
                    return $absolute;
                }
            } catch (Throwable) {
                // Cloud disks have no local path.
            }
        }

        $cache = 'remote-cache/'.sha1($disk.'|'.$path).'-'.basename($path);
        if (! Storage::disk('local')->exists($cache)) {
            Storage::disk('local')->put($cache, Storage::disk($disk)->get($path));
        }

        return Storage::disk('local')->path($cache);
    }

    public function s3Enabled(): bool
    {
        $bucket = trim((string) config('filesystems.disks.s3.bucket'));
        $region = trim((string) config('filesystems.disks.s3.region'));

        if ($bucket === '' || in_array(strtolower($bucket), ['...', 'your-bucket', 'null', 'none'], true)) {
            return false;
        }

        // AWS regions must be RFC host labels (e.g. us-east-1). Placeholders like "..." break the SDK.
        if ($region === '' || ! preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/i', $region)) {
            return false;
        }

        return true;
    }

    private function normalize(?string $disk, string $kind): string
    {
        $disk = $disk ?: ($kind === self::KIND_MEDIA ? 'public' : 'local');

        if ($disk === 's3' && ! $this->s3Enabled()) {
            return app()->environment('testing')
                ? 's3'
                : ($kind === self::KIND_MEDIA ? 'public' : 'local');
        }

        if ($kind === self::KIND_MEDIA && $disk === 'local') {
            return 'public';
        }

        return $disk;
    }

    private function existsOn(string $disk, string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if ($disk === 's3' && ! $this->s3Enabled() && ! app()->environment('testing')) {
            return false;
        }

        try {
            return Storage::disk($disk)->exists($path);
        } catch (Throwable) {
            return false;
        }
    }
}
