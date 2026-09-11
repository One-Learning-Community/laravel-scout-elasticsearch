<?php

declare(strict_types=1);

namespace Tests\Unit\ElasticSearch;

use Matchish\ScoutElasticSearch\ElasticSearch\QueryStringEscaper;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test — no application, no engine. Pins the reserved-character
 * treatment a user-typed term gets before it becomes a query_string.
 */
class QueryStringEscaperTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function terms(): array
    {
        return [
            'stray double quote' => ['letter "i', 'letter \\"i'],
            'trailing backslash' => ['test\\', 'test\\\\'],
            'hyphen and parens' => ['pre-k (2)', 'pre\\-k \\(2\\)'],
            'slash' => ['9/11 report', '9\\/11 report'],
            'colon' => ['the planets: saturn', 'the planets\\: saturn'],
            'the rest of the reserved set' => ['a && b || c! ~x ^y ?z {u} [t] +v', 'a \\&& b \\|| c\\! \\~x \\^y \\?z \\{u\\} \\[t\\] \\+v'],
            'uppercase OR becomes a word' => ['black OR white', 'black or white'],
            'uppercase AND becomes a word' => ['cats AND dogs', 'cats and dogs'],
            'uppercase NOT becomes a word' => ['NOT this', 'not this'],
            'operator inside a word is left alone' => ['ANDROID ORANGE NOTE', 'ANDROID ORANGE NOTE'],
            'lowercase operators are already words' => ['cats and dogs', 'cats and dogs'],
            'angle brackets cannot be escaped, so they go' => ['i <3 math', 'i 3 math'],
            'a lone star is the match-all convention' => ['*', '*'],
            'a star inside a term is left live (P2 of the audit)' => ['*pre-k*', '*pre\\-k*'],
            'empty' => ['', ''],
            'plain words untouched' => ['the water cycle', 'the water cycle'],
        ];
    }

    /**
     * @dataProvider terms
     */
    public function test_escape(string $typed, string $expected): void
    {
        $this->assertSame($expected, QueryStringEscaper::escape($typed));
    }
}
