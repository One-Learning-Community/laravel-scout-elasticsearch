<?php

declare(strict_types=1);

namespace Matchish\ScoutElasticSearch\ElasticSearch;

/**
 * Marks a builder query as hand-built query_string syntax that must reach
 * Elasticsearch verbatim, even when `elasticsearch.escape_query` is on.
 */
interface RawQuery extends \Stringable
{
}
