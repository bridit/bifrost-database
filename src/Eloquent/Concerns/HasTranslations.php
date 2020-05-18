<?php

namespace Bifrost\Database\Eloquent\Concerns;

use Exception;
use Throwable;
use ArrayAccess;
use Webpatser\Uuid\Uuid;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

trait HasTranslations
{

  /**
   * Model translatable attributes.
   *
   * @var array
   */
  protected array $translatableAttributes = [];

  /**
   * @param string $key
   * @return bool
   */
  public function hasTranslatableBehaviour(string $key): bool
  {
    return in_array($key, $this->translatableAttributes);
  }

  /**
   * @param string $key
   * @return void
   * @throws Throwable
   */
  public function guardTranslatableBehaviour(string $key): void
  {
    throw_if(!$this->hasTranslatableBehaviour($key), new Exception($key . ' is not a translatable attribute.'));
  }

  /**
   * @param string|null $locale
   * @return string
   */
  protected function getLocale(?string $locale = null): string
  {
    return $locale ?? Config::get('app.locale');
  }

  /**
   * Get all translations for a given attribute.
   *
   * @param string $key
   * @return array
   */
  public function getTranslations(string $key): array
  {
    $translations = Arr::get($this->attributes, $key) ?? '{}';

    return is_string($translations) ? $this->fromJson($translations) : $translations;
  }

  /**
   * Check if an attribute has translation.
   *
   * @param string $key
   * @param string|null $locale
   * @return bool
   */
  public function hasTranslation(string $key, ?string $locale = null): bool
  {
    return isset($this->getTranslations($key)[$this->getLocale($locale)]);
  }

  /**
   * Translate a given attribute.
   *
   * @param string $key
   * @param string|null $locale
   * @return string
   */
  public function translate(string $key, ?string $locale = null): string
  {
    return $this->getTranslation($key, $locale);
  }

  /**
   * Translate a given attribute.
   *
   * @param string $key
   * @param string|null $locale
   * @return array|ArrayAccess|mixed
   */
  public function getTranslation(string $key, ?string $locale = null)
  {
    $translations = $this->getTranslations($key);
    $fallbackLocale = Config::get('app.fallback_locale');

    return Arr::get($translations, $this->getLocale($locale), Arr::get($translations, $fallbackLocale));
  }

  /**
   * Set a translation for a given attribute.
   *
   * @param string $key
   * @param string $locale
   * @param $value
   * @return $this
   * @throws Throwable
   */
  public function setTranslation(string $key, string $value, ?string $locale = null): self
  {
    $this->guardTranslatableBehaviour($key);

    $translations = $this->getTranslations($key);
    $translations[$this->getLocale($locale)] = $value;

    $this->attributes[$key] = $this->asJson($translations);

    return $this;
  }

  /**
   * Set multiple translations for a given attribute.
   *
   * @param string $key
   * @param array $translations
   * @return $this
   * @throws Throwable
   */
  public function setTranslations(string $key, array $translations): self
  {
    $this->guardTranslatableBehaviour($key);

    $this->attributes[$key] = $this->asJson(array_merge($this->getTranslations($key), $translations));

    return $this;
  }

  /**
   * Forget a translation for a given attribute.
   *
   * @param string $key
   * @param string|null $locale
   * @return $this
   * @throws Throwable
   */
  public function forgetTranslation(string $key, ?string $locale = null): self
  {
    $this->guardTranslatableBehaviour($key);

    $translations = $this->getTranslations($key);

    if (!$this->hasTranslation($key, $locale)) {
      return $this;
    }

    unset($translations[$this->getLocale($locale)]);

    $this->attributes[$key] = $this->asJson($translations);

    return $this;
  }

  /**
   * Forget multiple translations for a given attribute.
   *
   * @param string $key
   * @param array $locales
   * @return $this
   * @throws Throwable
   */
  public function forgetTranslations(string $key, array $locales): self
  {
    $this->guardTranslatableBehaviour($key);

    $translations = $this->getTranslations($key);

    foreach ($locales as $locale)
    {
      if (!array_key_exists($locale, $translations)) {
        continue;
      }

      unset($translations[$locale]);
    }

    $this->attributes[$key] = $this->asJson($translations);

    return $this;
  }

}
