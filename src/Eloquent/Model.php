<?php

namespace Bifrost\Database\Eloquent;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Bifrost\Database\Eloquent\Concerns\HasAttributesResolver;

class Model extends BaseModel
{

  use HasAttributesResolver;

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
   * @inheritdoc
   */
  public function getAttributeValue($key)
  {
    if (!$this->hasEncryptionBehaviour($key)) {
      return parent::getAttributeValue($key);
    }

    return $this->decryptValue(parent::getAttributeValue($key));
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

}
