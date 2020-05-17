<?php

namespace Bifrost\Database\Eloquent;

use Throwable;
use Illuminate\Support\Arr;
use UnexpectedValueException;
use Illuminate\Database\Eloquent\Builder;
use Bifrost\Database\Eloquent\Concerns\HasHashes;
use Bifrost\Database\Eloquent\Concerns\HasStates;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Bifrost\Database\Eloquent\Concerns\HasEncryption;
use Spatie\SchemalessAttributes\SchemalessAttributes;
use Bifrost\Database\Eloquent\Concerns\HasAttributesResolver;

class Model extends BaseModel
{

  use HasHashes, HasEncryption, HasStates, HasAttributesResolver;

  /**
   * Indicates when model key is UUID.
   *
   * @var bool
   */
  protected bool $uuidPrimaryKey = true;

  /**
   * Model schemaless attributes.
   *
   * @var array
   */
  protected array $schemalessAttributes = [];

  /**
   * Model attributes map.
   *
   * @var array
   */
  protected array $attributesMap = [];

  /**
   * Model states transitions map.
   *
   * @var array
   */
  protected array $stateTransitionsMap = [];

  /**
   * @inheritdoc
   */
  public function __construct(array $attributes = [])
  {
    $this->handlePrimaryKeyType();
    $this->handleAttributesOptionsResolver();

    parent::__construct($attributes);
  }

  /**
   * @inheritdoc
   */
  protected static function boot(): void
  {
    parent::boot();

    static::bootAttributesMap();
  }

  /**
   * Handle primary key data type.
   * @return void
   */
  protected function handlePrimaryKeyType(): void
  {
    if (!$this->uuidPrimaryKey) {
      return;
    }

    $this->incrementing = false;
    $this->keyType = 'string';
  }

  /**
   * Get all of the current attributes on the model.
   *
   * @return array
   */
  public function getAttributes()
  {
    parent::getAttributes();

    $this->mergeAttributesFromSchemaless();

    return $this->attributes;
  }

  /**
   * Merge schemaless attributes into the model.
   *
   * @return void
   */
  protected function mergeAttributesFromSchemaless()
  {
    foreach ($this->schemalessAttributes as $attribute) {
      $this->attributes[$attribute] ??= null;
    }
  }

  /**
   * @inheritdoc
   */
  public function getAttributeValue($key)
  {
    if ($this->hasEncryptionBehaviour($key)) {
      return $this->decryptValue(parent::getAttributeValue($key));
    }

    if (in_array($key, $this->schemalessAttributes)) {
      return SchemalessAttributes::createForModel($this, $key);
    }

    return parent::getAttributeValue($key);
  }

  /**
   * Set a given attribute on the model.
   *
   * @param  string  $key
   * @param  mixed  $value
   * @return mixed
   */
  public function setAttribute($key, $value)
  {
    parent::setAttribute($key, $value);

    if ($value !== null && $this->hasHashingBehaviour($key)) {
      $this->hashAttribute($key);
    }

    if ($value !== null && $this->hasEncryptionBehaviour($key)) {
      $this->encryptAttribute($key);
    }

    return $this;
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
