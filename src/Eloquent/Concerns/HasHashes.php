<?php

namespace Bifrost\Database\Eloquent\Concerns;

use Illuminate\Support\Facades\Hash;

trait HasHashes
{

  /**
   * Check if hashing can be done.
   *
   * @param string $key
   * @return bool
   */
  protected function hasHashingBehaviour(string $key): bool
  {
    return in_array($key, $this->hashedAttributes ?? []) && array_key_exists($key, $this->attributes);
  }

  /**
   * Attempt to hash a stored attribute.
   *
   * @param string $key
   * @return void
   */
  protected function hashAttribute(string $key): void
  {
    if (!$this->hasHashingBehaviour($key)) {
      return;
    }

    $this->attributes[$key] = Hash::make($this->attributes[$key], $this->hashingConfig ?? []);
  }

  /**
   * Check if value matches with hash.
   *
   * @param string $key
   * @param string $value
   * @return bool
   */
  public function hashCheck(string $key, string $value): bool
  {
    if (!$this->hasHashingBehaviour($key)) {
      return false;
    }

    return Hash::check($value, $this->attributes[$key]);
  }

}
