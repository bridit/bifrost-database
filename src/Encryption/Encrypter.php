<?php

namespace Bifrost\Database\Encryption;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Config;

/**
 * Class Encrypter
 * Just a helper to be used on Models
 * @package Bifrost\Database\Encryption
 */
class Encrypter
{

  /**
   * Get the prefix to be used on encryption (determine if a string is encrypted).
   *
   * @return string
   */
  public static function getPrefix()
  {
    return Config::get('bifrost.encryption.prefix', '__ENCRYPTED__');
  }

  /**
   * Check if a string is encrypted.
   *
   * @param string $value
   * @return bool
   */
  public static function isEncrypted(string $value): bool
  {
    return strpos($value, static::getPrefix()) === 0;
  }

  /**
   * Return the encrypted value.
   *
   * @param string $value
   * @return string
   */
  public static function encrypt(string $value)
  {
    if (static::isEncrypted($value)) {
      return $value;
    }

    return static::getPrefix() . Crypt::encrypt($value);
  }

  /**
   * Return the decrypted value.
   *
   * @param string  $value
   * @return string
   */
  public static function decrypt(string $value)
  {
    if (!static::isEncrypted($value)) {
      return $value;
    }

    return Crypt::decrypt(substr($value, strlen(static::getPrefix()), strlen($value)));
  }

}
