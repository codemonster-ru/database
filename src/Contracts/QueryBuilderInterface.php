<?php

namespace Codemonster\Database\Contracts;

interface QueryBuilderInterface
{
    public function get(): array;
    public function first(): ?array;
    public function toSql(): string;
}
