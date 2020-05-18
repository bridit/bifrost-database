<?php

namespace Bifrost\Database\Eloquent;

use Throwable;
use Bifrost\Database\Eloquent\Concerns\HasHashes;
use Bifrost\Database\Eloquent\Concerns\HasStates;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Bifrost\Database\Eloquent\Concerns\HasSchemaless;
use Bifrost\Database\Eloquent\Concerns\HasEncryption;
use Bifrost\Database\Eloquent\Concerns\HasTranslations;
use Bifrost\Database\Eloquent\Concerns\HasAttributesResolver;

class Model extends BaseModel
{

  use HasHashes, HasEncryption, HasSchemaless, HasTranslations, HasStates, HasAttributesResolver;

  /**
   * Indicates when model key is UUID.
   *
   * @var bool
   */
  protected bool $uuidPrimaryKey = true;

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

    if ($this->hasSchemalessBehaviour($key)) {
      return $this->getSchemalessAttribute($key);
    }

    if ($this->hasTranslatableBehaviour($key)) {
      return $this->getTranslation($key);
    }

    return parent::getAttributeValue($key);
  }

  /**
   * Set a given attribute on the model.
   *
   * @param string $key
   * @param mixed $value
   * @return mixed
   * @throws Throwable
   */
  public function setAttribute($key, $value)
  {
    if ($this->hasTranslatableBehaviour($key)) {
      return is_array($value) ? $this->setTranslations($key, $value) : $this->setTranslation($key, $value);
    }

    parent::setAttribute($key, $value);

    if ($value !== null && $this->hasHashingBehaviour($key)) {
      $this->hashAttribute($key);
    }

    if ($value !== null && $this->hasEncryptionBehaviour($key)) {
      $this->encryptAttribute($key);
    }

    return $this;
  }

}
