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
    if ($key === null || !$this->hasEncryptionBehaviour($key) || Encrypter::isEncrypted($this->attributes[$key])) {
      return;
    }

    $this->attributes[$key] = Encrypter::encrypt($this->attributes[$key]);
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
    if (Encrypter::isEncrypted($value)) {
      return Encrypter::decrypt($value);
    }

    return $value;
  }

}
