<?php

namespace Bifrost\Database\Eloquent\Concerns;

use Symfony\Component\OptionsResolver\OptionsResolver;

trait HasAttributesResolver
{

  use AttributesResolverDefaults;

  /**
   * Options Resolver instance
   *
   * @var OptionsResolver[]
   */
  protected static array $optionsResolvers = [];

  /**
   * Create object's OptionsResolver.
   * @return void
   */
  protected function handleAttributesOptionsResolver(): void
  {
    $class = get_class($this);

    if (isset(self::$optionsResolvers[$class])) {
      self::$optionsResolvers[$class] = new OptionsResolver();
      $this->configureAttributesOptions(self::$optionsResolvers[$class]);
    }
  }

  /**
   * @param OptionsResolver $resolver
   * @return void
   */
  protected function configureAttributesOptions(OptionsResolver $resolver): void
  {
    foreach ($this->attributesMap as $name => $config)
    {
      static::addResolverSimpleOption($resolver, $name, $config);
    }
  }

  /**
   * @param OptionsResolver $resolver
   * @param string $attributeName
   * @param array $config
   */
  protected function addResolverSimpleOption(OptionsResolver $resolver, string $attributeName, array $config)
  {
    $behaviours = data_get($config, 'behaviours', []);
    $allowedTypes = data_get($config ,'type', []);
    $allowedTypes = !blank($allowedTypes) && is_string($allowedTypes) ? [$allowedTypes] : $allowedTypes;
    $allowedValues = data_get($config ,'allowed', null);
    $default = data_get($config ,'default', null);

    $resolver->setDefined($attributeName);

    if (in_array('fillable', $behaviours)) {
      $this->fillable[] = $attributeName;
    }

    if (in_array('deprecated', $behaviours)) {
      $resolver->setDeprecated($attributeName);
    }

    if (in_array('required', $behaviours)) {
      $resolver->setRequired($attributeName);
    }

    if (in_array('state', $behaviours)) {
      $this->statesAttributes[] = $attributeName;
    }

    if (in_array('encrypted', $behaviours)) {
      $this->encrypted[] = $attributeName;
    }

    if (in_array('hashed', $behaviours)) {
      $this->hashed[] = $attributeName;
    }

    if (in_array('nullable', $behaviours) && !in_array('null', $allowedTypes)) {
      $allowedTypes[] = 'null';
    }

    if (!blank($allowedTypes)) {
      $this->addCasts($attributeName, $allowedTypes);

      $resolver->setAllowedTypes($attributeName, $allowedTypes);
    }

    if ($allowedValues !== null) {
      $resolver->setAllowedValues($attributeName, $allowedValues);
    }

    if ($default !== null) {
      $resolver->setDefault($attributeName, $this->getAttributeDefaultValue($default));
    }
  }

  /**
   * @param string $attributeName
   * @param array $allowedTypes
   * @return void
   */
  private function addCasts(string $attributeName, array $allowedTypes): void
  {
    $casts = array_map(fn($item) => $this->typeConversion($item), $allowedTypes);
    $casts = array_filter($casts, fn($item) => in_array($item, ['string', 'integer', 'boolean', 'array', 'object', 'datetime']));
    $this->casts[$attributeName] = head($casts);
  }

  /**
   * @param string $type
   * @return string
   */
  private function typeConversion(string $type): string
  {
    switch ($type)
    {
      case 'Carbon\Carbon':
      case 'DateTime':
        return 'datetime';
      case 'int':
        return 'integer';
      case 'bool':
        return 'boolean';
      default:
        return $type;
    }
  }

  /**
   * @param $value
   * @return mixed
   */
  protected function getAttributeDefaultValue($value)
  {
    if (blank($value) || strpos($value, '{{') === false) {
      return $value;
    }

    preg_match("/{{(.*?)}}/", $value, $matches);

    if(!$matches) {
      return null;
    }

    return $this->{'attributeResolver' . ucfirst($matches[1])}();
  }

  /**
   * Create the event listeners for the creating and updating events.
   */
  public static function bootAttributesMap()
  {
    static::creating(function($entity){
      $entity->resolve();
    });

    static::updating(function($entity){
      $entity->resolve();
    });
  }

  /**
   * Resolve attributes on the model.
   *
   * @return void
   */
  protected function resolve()
  {
    if (isset(self::$optionsResolvers[get_class($this)])) {
      $options = self::$optionsResolvers[get_class($this)]->resolve($this->attributes);
      $this->attributes = array_merge($this->attributes, $options);
    }

    $this->checkStatesTransition();
  }

}
