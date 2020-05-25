<?php

namespace Bifrost\Database\Eloquent\Concerns;

use Throwable;
use Illuminate\Support\Arr;
use UnexpectedValueException;
use Illuminate\Database\Eloquent\Builder;
use Spatie\SchemalessAttributes\SchemalessAttributes;

trait HasSchemaless
{

  protected function hasSchemalessBehaviour(string $key): bool
  {
    return in_array($key, $this->schemalessAttributes);
  }

  /**
   * @param string $key
   * @return SchemalessAttributes
   */
  protected function getSchemalessAttribute(string $key): SchemalessAttributes
  {
    return SchemalessAttributes::createForModel($this, $key);
  }

  /**
   * Scope a query to apply json conditions.
   *
   * @param Builder $builder
   * @param mixed ...$arguments
   * @return Builder
   * @throws Throwable
   */
  public function scopeWithJson($builder, ...$arguments): Builder
  {
    $schemalessAttributes = [];
    $attributeName = Arr::first($this->schemalessAttributes) ?? 'json_data';

    if (count($arguments) === 0) {
      return $builder->whereNotNull($attributeName);
    }

    if (count($arguments) === 1) {
      [$schemalessAttributes] = $arguments;
      throw_if(!is_array($schemalessAttributes), new UnexpectedValueException('Array of conditions expected.'));
    }

    if (count($arguments) === 2) {
      [$name, $value] = $arguments;
      $schemalessAttributes = [$name => $value];
    }

    if (count($arguments) >= 3) {
      [$attributeName, $name, $value] = $arguments;
      $schemalessAttributes = [$name => $value];
    }

    foreach ($schemalessAttributes as $name => $value) {
      $builder->where(str_replace('.', '->', "{$attributeName}->{$name}"), $value);
    }

    return $builder;
  }

}
