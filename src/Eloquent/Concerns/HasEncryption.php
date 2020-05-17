<?php

namespace Bifrost\Database\Eloquent\Concerns;

use Bifrost\Database\Encryption\Encrypter;

trait HasEncryption
{

  /**
   * @var array
   */
  protected array $encrypted = [];

  /**
   * Check if encryption can be done.
   *
   * @param string $key
   * @return bool
   */
  protected function hasEncryptionBehaviour(string $key): bool
  {
    return in_array($key, $this->encrypted) && array_key_exists($key, $this->attributes);
  }

  /**
   * Attempt to hash a stored attribute.
   *
   * @param string $key
   * @return void
   */
  protected function encryptAttribute(string $key): void
  {
    $value = is_array($this->attributes[$key]) ? json_encode($this->attributes[$key]) : $this->attributes[$key];

    if ($key === null || !$this->hasEncryptionBehaviour($key) || Encrypter::isEncrypted($value)) {
      return;
    }

    $this->attributes[$key] = Encrypter::encrypt($value);
  }

  /**
   * Check if value matches with hash.
   *
   * @param string $key
   * @return mixed
   */
  protected function decryptAttribute(string $key)
  {
    if (!$this->hasEncryptionBehaviour($key)) {
      return data_get($this->attributes, $key);
    }

    return $this->decryptValue($this->attributes[$key]);
  }

  /**
   * @param mixed $value
   * @return mixed
   */
  protected function decryptValue($value)
  {
    if (!Encrypter::isEncrypted($value)) {
      return $value;
    }

    $value = Encrypter::decrypt($value);

    if (is_numeric($value)) {
      return $value;
    }

    if ($value == json_decode($value, true)) {
      return $value;
    }

    return json_decode($value, true);
  }

}
