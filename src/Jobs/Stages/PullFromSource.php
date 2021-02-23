<?php

namespace Matchish\ScoutElasticSearch\Jobs\Stages;

use Elasticsearch\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Matchish\ScoutElasticSearch\Jobs\ImportContext;
use Matchish\ScoutElasticSearch\Searchable\ImportSource;

/**
 * @internal
 */
final class PullFromSource
{
    /**
     * @var ImportSource
     */
    private $source;

    /**
     * @param ImportSource $source
     */
    public function __construct(ImportSource $source)
    {
        $this->source = $source;
    }

    public function handle(Client $elasticsearch, ImportContext $context): void
    {
        $results = $this->source->get()->filter->shouldBeSearchable();
        if (! $results->isEmpty()) {
            $results->first()->searchableUsing()->update($results);
            // Cache last id
            $context->lastImportId = $results->last()->getKey();
        }
    }

    public function estimate(): int
    {
        return 1;
    }

    public function title(): string
    {
        return 'Indexing...';
    }

    /**
     * @param ImportSource $source
     * @param ImportContext $context
     * @return Collection
     */
    public static function chunked(ImportSource $source, ImportContext $context): Collection
    {
        return $source->chunked($context)->map(function ($chunk) {
            return new static($chunk);
        });
    }
}
