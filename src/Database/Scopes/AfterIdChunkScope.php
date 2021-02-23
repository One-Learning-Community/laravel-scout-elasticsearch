<?php

namespace Matchish\ScoutElasticSearch\Database\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Cache;
use Matchish\ScoutElasticSearch\Jobs\ImportContext;

class AfterIdChunkScope implements Scope
{
    /**
     * @var int
     */
    private $perPage;

    /**
     * @var ImportContext
     */
    private $context;

    /**
     * PageScope constructor.
     * @param int $perPage
     * @param ImportContext $context
     */
    public function __construct($perPage, ImportContext $context)
    {
        $this->perPage = $perPage;
        $this->context = $context;
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model   $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->forPageAfterId($this->perPage, $this->context->lastImportId, $model->getKeyName());
    }
}
