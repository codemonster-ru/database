<?php

namespace Codemonster\Database\Relations;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\ORM\ModelCollection;

class HasMany extends Relation
{
    protected string $foreignKey;
    protected string $localKey;

    public function __construct($builder, Model $parent, Model $related, string $foreignKey, string $localKey)
    {
        parent::__construct($builder, $parent, $related);

        $this->foreignKey = $foreignKey;
        $this->localKey   = $localKey;
    }

    public function getResults(): ModelCollection
    {
        $rows = $this->builder
            ->where($this->foreignKey, $this->parent->{$this->localKey})
            ->get();

        $class = get_class($this->related);

        /** @var class-string<Model> $class */
        return $class::hydrate($rows);
    }
}
