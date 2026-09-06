<?php

namespace Tests\Unit;

use App\Services\Storage\HybridDisk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HybridDiskTest extends TestCase
{
    public function test_reads_local_files_when_documents_disk_is_s3(): void
    {
        Storage::fake('local');
        Storage::fake('s3');

        config(['filesystems.documents' => 's3']);

        Storage::disk('local')->put('journals/legacy/manuscript.pdf', 'legacy-bytes');

        $disks = app(HybridDisk::class);

        $this->assertTrue($disks->exists('journals/legacy/manuscript.pdf', HybridDisk::KIND_DOCUMENTS));
        $this->assertSame('local', $disks->locate('journals/legacy/manuscript.pdf', HybridDisk::KIND_DOCUMENTS));
        $this->assertSame(
            'legacy-bytes',
            $disks->filesystem('local')->get('journals/legacy/manuscript.pdf')
        );
    }

    public function test_reads_s3_files_when_documents_disk_is_local(): void
    {
        Storage::fake('local');
        Storage::fake('s3');

        config(['filesystems.documents' => 'local']);

        Storage::disk('s3')->put('journals/cloud/manuscript.pdf', 's3-bytes');

        $disks = app(HybridDisk::class);

        $this->assertSame(
            's3',
            $disks->locate('journals/cloud/manuscript.pdf', HybridDisk::KIND_DOCUMENTS)
        );
        $this->assertTrue($disks->exists('journals/cloud/manuscript.pdf', HybridDisk::KIND_DOCUMENTS));
    }

    public function test_new_uploads_follow_configured_documents_disk(): void
    {
        Storage::fake('local');
        Storage::fake('s3');
        config(['filesystems.documents' => 's3']);

        $file = UploadedFile::fake()->create('paper.pdf', 20, 'application/pdf');
        [$path, $disk] = app(HybridDisk::class)->storeAs($file, 'journals/demo', 'manuscript.pdf', HybridDisk::KIND_DOCUMENTS);

        $this->assertSame('s3', $disk);
        Storage::disk('s3')->assertExists($path);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_recorded_disk_is_preferred_when_file_exists_on_both(): void
    {
        Storage::fake('local');
        Storage::fake('s3');

        Storage::disk('local')->put('journals/both.pdf', 'local-bytes');
        Storage::disk('s3')->put('journals/both.pdf', 's3-bytes');

        $disks = app(HybridDisk::class);

        $this->assertSame('s3', $disks->locate('journals/both.pdf', HybridDisk::KIND_DOCUMENTS, 's3'));
        $this->assertSame('local', $disks->locate('journals/both.pdf', HybridDisk::KIND_DOCUMENTS, 'local'));
    }

    public function test_s3_disabled_when_region_is_invalid(): void
    {
        config([
            'filesystems.disks.s3.bucket' => 'my-bucket',
            'filesystems.disks.s3.region' => '...',
        ]);

        $this->assertFalse(app(HybridDisk::class)->s3Enabled());
    }

    public function test_s3_disabled_when_bucket_is_placeholder(): void
    {
        config([
            'filesystems.disks.s3.bucket' => '...',
            'filesystems.disks.s3.region' => 'us-east-1',
        ]);

        $this->assertFalse(app(HybridDisk::class)->s3Enabled());
    }
}
