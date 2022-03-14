<?php

namespace Matchish\ScoutElasticSearch\Jobs\Stages;

use Elastic\Elasticsearch\Client;
use Matchish\ScoutElasticSearch\Jobs\ImportContext;

interface StageInterface
{
    public function title(): string;

    public function estimate(): int;

    public function handle(Client $elasticsearch, ImportContext $context): void;
}
