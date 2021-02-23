<?php

namespace Matchish\ScoutElasticSearch\Jobs;

use Elasticsearch\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Matchish\ScoutElasticSearch\ProgressReportable;
use Matchish\ScoutElasticSearch\Searchable\ImportSource;

/**
 * @internal
 */
final class Import
{
    use Queueable;
    use ProgressReportable;

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

    /**
     * @param Client $elasticsearch
     */
    public function handle(Client $elasticsearch): void
    {
        $importContext = new ImportContext();
        $stages = $this->stages($importContext);
        $estimate = $stages->sum->estimate();
        $this->progressBar()->setMaxSteps($estimate);
        $stages->each(function ($stage) use ($elasticsearch, $importContext) {
            $this->progressBar()->setMessage($stage->title());
            $stage->handle($elasticsearch, $importContext);
            $this->progressBar()->advance($stage->estimate());
        });
    }

    private function stages(ImportContext $importContext): Collection
    {
        return ImportStages::fromSource($this->source, $importContext);
    }
}
