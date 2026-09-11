<?php

declare(strict_types=1);

namespace Tests\Unit\ElasticSearch;

use App\Product;
use Laravel\Scout\Builder;
use Matchish\ScoutElasticSearch\ElasticSearch\RawQuery;
use Matchish\ScoutElasticSearch\ElasticSearch\SearchFactory;
use Tests\TestCase;

/**
 * `elasticsearch.escape_query` decides whether the builder's query is passed to
 * query_string verbatim (upstream behaviour, default) or with Lucene reserved
 * characters escaped. A RawQuery is verbatim either way.
 */
class SearchFactoryEscapeTest extends TestCase
{
    private function queryString(Builder $builder): string
    {
        return SearchFactory::create($builder)->toArray()['query']['query_string']['query'];
    }

    public function test_query_is_verbatim_by_default(): void
    {
        $this->app['config']->set('elasticsearch.escape_query', false);

        $this->assertSame('letter "i', $this->queryString(new Builder(new Product(), 'letter "i')));
    }

    public function test_query_is_escaped_when_enabled(): void
    {
        $this->app['config']->set('elasticsearch.escape_query', true);

        $this->assertSame('letter \\"i', $this->queryString(new Builder(new Product(), 'letter "i')));
    }

    public function test_raw_query_is_never_escaped(): void
    {
        $this->app['config']->set('elasticsearch.escape_query', true);

        $raw = new class('(title:this OR description:this)') implements RawQuery {
            public function __construct(private string $query)
            {
            }

            public function __toString(): string
            {
                return $this->query;
            }
        };

        $this->assertSame('(title:this OR description:this)', $this->queryString(new Builder(new Product(), $raw)));
    }
}
