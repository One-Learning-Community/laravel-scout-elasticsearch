<?php

namespace Matchish\ScoutElasticSearch\Searchable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Matchish\ScoutElasticSearch\Database\Scopes\AfterIdChunkScope;
use Matchish\ScoutElasticSearch\Database\Scopes\PageScope;
use Matchish\ScoutElasticSearch\ElasticSearch\Index;
use Matchish\ScoutElasticSearch\Jobs\ImportContext;

class DefaultImportSource implements ImportSource
{
    const DEFAULT_CHUNK_SIZE = 500;

    const CHUNK_MODE_PAGE = 'page';
    const CHUNK_MODE_AFTER_ID = 'after_id';

    /**
     * @var string
     */
    private $className;
    /**
     * @var array
     */
    private $scopes;

    /**
     * @var string
     */
    private $chunkMode;

    /**
     * @var mixed
     */
    private $lastImportId;

    /**
     * DefaultImportSource constructor.
     * @param string $className
     * @param array $scopes
     * @param string|null $chunkMode
     */
    public function __construct(string $className, array $scopes = [], string $chunkMode = null)
    {
        $this->className = $className;
        $this->scopes = $scopes;
        $this->chunkMode = $chunkMode ?: config('elasticsearch.chunk_mode', self::CHUNK_MODE_AFTER_ID);
    }

    public function syncWithSearchUsingQueue(): ?string
    {
        return $this->model()->syncWithSearchUsingQueue();
    }

    public function syncWithSearchUsing(): ?string
    {
        return $this->model()->syncWithSearchUsing();
    }

    public function searchableAs(): string
    {
        return $this->model()->searchableAs();
    }

    public function chunked(ImportContext $context): Collection
    {
        $query = $this->newQuery();
        $totalSearchables = $query->count();
        if ($totalSearchables) {
            $chunkSize = $this->getChunkSize();
            $totalChunks = (int) ceil($totalSearchables / $chunkSize);

            return collect(range(1, $totalChunks))->map(function ($page) use ($chunkSize, $context) {
                if ($this->chunkMode === self::CHUNK_MODE_AFTER_ID) {
                    $chunkScope = new AfterIdChunkScope($chunkSize, $context);
                } else {
                    $chunkScope = new PageScope($page, $chunkSize);
                }

                return new static($this->className, array_merge($this->scopes, [$chunkScope]));
            });
        } else {
            return collect();
        }
    }

    public function getChunkSize(): int
    {
        return (int) config('scout.chunk.searchable', self::DEFAULT_CHUNK_SIZE);
    }

    public function defineIndex(): Index
    {
        $name = $this->searchableAs().'_'.time();
        $settingsConfigKey = "elasticsearch.indices.settings.{$this->searchableAs()}";
        $mappingsConfigKey = "elasticsearch.indices.mappings.{$this->searchableAs()}";
        $defaultSettings = [
            'number_of_shards' => 1,
            'number_of_replicas' => 0,
        ];
        $settings = config($settingsConfigKey, config('elasticsearch.indices.settings.default', $defaultSettings));
        $mappings = config($mappingsConfigKey, config('elasticsearch.indices.mappings.default'));

        return new Index($name, $settings, $mappings);
    }

    /**
     * @return mixed
     */
    protected function model()
    {
        return new $this->className;
    }

    protected function newQuery(): Builder
    {
        $query = $this->model()->newQuery();
        $softDelete = $this->className::usesSoftDelete() && config('scout.soft_delete', false);
        $query
            ->when($softDelete, function ($query) {
                return $query->withTrashed();
            })
            ->orderBy($this->model()->getKeyName());
        $scopes = $this->scopes;

        return collect($scopes)->reduce(function ($instance, $scope) {
            $instance->withGlobalScope(get_class($scope), $scope);

            return $instance;
        }, $query);
    }

    public function get(): EloquentCollection
    {
        /** @var EloquentCollection $models */
        $models = $this->newQuery()->get();

        return $models;
    }
}
