<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

class ArticleExtractTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_extract_metadata_from_docx(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $path = storage_path('app/testing/test-article.docx');
        $this->makeDocx($path, "Research on Open Access\n\nJane Doe\n\nAbstract\nThis paper studies open repositories.\n\nKeywords: open access, repositories, publishing\nDOI: 10.1234/example.5678\n");

        $response = $this->actingAs($admin)->postJson(route('admin.articles.extract'), [
            'document' => new UploadedFile($path, 'test-article.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true),
        ]);

        $response->assertOk()
            ->assertJsonPath('method', 'docx_text')
            ->assertJsonPath('fields.doi', '10.1234/example.5678');

        $this->assertNotEmpty($response->json('fields.title'));
        $this->assertStringContainsString('open repositories', (string) $response->json('fields.abstract'));
    }

    public function test_guest_cannot_extract(): void
    {
        $this->postJson(route('admin.articles.extract'), [])
            ->assertUnauthorized();
    }

    private function makeDocx(string $path, string $text): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $escaped = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $paragraphs = '';
        foreach (preg_split("/\n+/", $escaped) as $line) {
            $paragraphs .= '<w:p><w:r><w:t>'.$line.'</w:t></w:r></w:p>';
        }

        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:body>'.$paragraphs.'</w:body></w:document>';

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('word/document.xml', $document);
        $zip->close();
    }
}
