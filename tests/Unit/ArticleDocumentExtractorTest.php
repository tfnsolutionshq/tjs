<?php

namespace Tests\Unit;

use App\Services\Articles\ArticleDocumentExtractor;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArticleDocumentExtractorTest extends TestCase
{
    #[Test]
    public function it_parses_two_column_scholarly_front_matter(): void
    {
        // Simulates messy PDF reading order from a two-column first page
        $text = <<<'TXT'
Journal of Mathematics Instruction, Social Research and Opinion
Vol. 5, No. 2, June 2026, pp. 1387 - 1400, https://doi.org/10.58421/misro.v5i2.1221
ISSN 2962-7842 1387
Journal homepage: https://journal-gehu.com/index.php/misro
Mathematical and Statistical Foundations of Big Data Science: A Review of Methods and
Challenges
Noora Ali Mohsin
1
, Nooralhuda Salem Hadi
2
, Maryam Zwain
3
1,2,3
Al-Furat Al-Awsat Technical University (ATU), The Technical Administrative college/kufa, Najaf, Iraq
Article Info ABSTRACT
Article history:
Received 2026-02-21
Revised 2026-03-13
Accepted 2026-03-26
Keywords:
Big Data Science, High-Dimensional Data, Large-Scale Optimization, Mathematical Foundations, Statistical Inference
Big Data Science has emerged as a transformative field driven by the rapid growth of large, complex, and high-dimensional datasets. This review examines mathematical and statistical foundations.
TXT;

        $result = (new ArticleDocumentExtractor)->parseText($text);
        $fields = $result['fields'];
        $preview = $result['preview'];

        $this->assertSame(
            'Mathematical and Statistical Foundations of Big Data Science: A Review of Methods and Challenges',
            $fields['title']
        );
        $this->assertSame("Noora Ali Mohsin\nNooralhuda Salem Hadi\nMaryam Zwain", $fields['authors_text']);
        $this->assertSame('10.58421/misro.v5i2.1221', $fields['doi']);
        $this->assertSame('1387-1400', $fields['page_range']);
        $this->assertStringContainsString('Big Data Science, High-Dimensional Data', (string) $fields['keywords']);
        $this->assertStringContainsString('Big Data Science has emerged', (string) $fields['abstract']);
        $this->assertStringNotContainsString('Article history', (string) $fields['abstract']);
        $this->assertStringNotContainsString('Received 2026', (string) $fields['abstract']);
        $this->assertSame('2026-02-21', $preview['history']['received'] ?? null);
        $this->assertSame($fields['title'], $preview['title']);
        $this->assertSame(['Noora Ali Mohsin', 'Nooralhuda Salem Hadi', 'Maryam Zwain'], $preview['authors']);
    }

    #[Test]
    public function it_does_not_use_citation_line_as_title(): void
    {
        $text = <<<'TXT'
Journal of Example Research
Vol. 1, No. 1, January 2026, pp. 1 - 10, https://doi.org/10.1000/example.1
A Practical Guide to Clean Metadata Extraction: Lessons from Journals
Ada Lovelace1, Grace Hopper2
1 University of Example
ABSTRACT
This article explains metadata extraction quality.
Keywords: metadata, extraction, journals
TXT;

        $fields = (new ArticleDocumentExtractor)->parseText($text)['fields'];

        $this->assertSame(
            'A Practical Guide to Clean Metadata Extraction: Lessons from Journals',
            $fields['title']
        );
        $this->assertStringContainsString('Ada Lovelace', (string) $fields['authors_text']);
        $this->assertStringNotContainsString('Vol.', (string) $fields['title']);
    }
}
