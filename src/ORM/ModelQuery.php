<?php

namespace Codemonster\Database\ORM;

use Codemonster\Database\Query\QueryBuilder;

class ModelQuery
{
    protected QueryBuilder $builder;
    protected string $modelClass;

    public function __construct(QueryBuilder $builder, string $modelClass)
    {
        $this->builder = $builder;
        $this->modelClass = $modelClass;
    }

    public function getBuilder(): QueryBuilder
    {
        return $this->builder;
    }

    public function get(): ModelCollection
    {
        $rows = $this->builder->get();

        /** @var class-string<Model> $model */
        $model = $this->modelClass;

        return $model::hydrate($rows);
    }

    public function first(): ?Model
    {
        $row = $this->builder->first();

        if (!$row) {
            return null;
        }

        /** @var class-string<Model> $model */
        $model = $this->modelClass;

        return new $model((array) $row, true);
    }

    public function exists(): bool
    {
        return $this->builder->exists();
    }

    public function count(): int
    {
        return (int) $this->builder->count();
    }

    public function __call(string $name, array $arguments): self
    {
        $result = $this->builder->$name(...$arguments);

        if ($result instanceof QueryBuilder) {
            $this->builder = $result;
        }

        return $this;
    }
}
