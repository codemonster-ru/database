<?php

namespace Codemonster\Database\Relations;

use Codemonster\Database\ORM\Model;

class HasOne extends Relation
{
    protected string $foreignKey;
    protected string $localKey;

    public function __construct($builder, Model $parent, Model $related, string $foreignKey, string $localKey)
    {
        parent::__construct($builder, $parent, $related);

        $this->foreignKey = $foreignKey;
        $this->localKey   = $localKey;
    }

    public function getResults(): ?Model
    {
        $row = $this->builder
            ->where($this->foreignKey, $this->parent->{$this->localKey})
            ->first();

        if (!$row) {
            return null;
        }

        $class = get_class($this->related);

        return new $class((array) $row, true);
    }
}
