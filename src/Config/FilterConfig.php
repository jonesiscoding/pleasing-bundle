<?php

namespace DevCoding\Pleasing\Config;

use DevCoding\Pleasing\Filter\AsseticFilter;
use DevCoding\Pleasing\Filter\FilterInterface;

/**
 * @property string $apply_to
 * @property array  $import_paths
 * @property string $class
 */
class FilterConfig extends \ArrayObject
{
  const ASSETIC_FILTER = '\Assetic\Filter\FilterInterface';
  const KEY_CLASS      = 'class';
  const KEY_APPLY_TO   = 'apply_to';
  const KEY_BIN        = 'bin';

  public function __construct(array $array = [])
  {
    parent::__construct($array, \ArrayObject::ARRAY_AS_PROPS);
  }

  /**
   * @return void
   */
  public function validate()
  {
    if (!$this->isPleasing() && !$this->isAssetic())
    {
      if (parent::offsetExists(self::KEY_CLASS))
      {
        throw new \InvalidArgumentException(sprintf(
          'Class "%s" must implement "%s" or "%s"',
          parent::offsetGet(self::KEY_CLASS),
          FilterInterface::class,
          '\Assetic\Filter\FilterInterface'
        ));
      }
      elseif (parent::offsetExists(self::KEY_BIN))
      {
        throw new \InvalidArgumentException('Binary only filters are not supported.  A PHP class is required.');
      }
      else
      {
        throw new \InvalidArgumentException('The filter must have either a class or bin key.');
      }
    }
  }

  /**
   * Builds a filter from a configuration array.
   *
   * @return FilterInterface A filter that implements the FilterInterface.
   */
  public function build(): FilterInterface
  {
    $this->validate();

    $filter = new $this->class();

    // Apply Parameters
    foreach($this as $key => $value)
    {
      if (self::KEY_CLASS !== $key && self::KEY_APPLY_TO !== $key)
      {
        $methodName = 'set' . (str_replace(" ", "", ucwords(strtr($key, "_-", "  "))));

        try
        {
          $filter->$methodName($value);
        }
        catch(\Exception $e)
        {
          throw new \InvalidArgumentException(sprintf(
            'Could not set the option "%s" to "%s" (%s)',
            $key,
            $value,
            $e->getMessage()
          ));
        }

      }
    }

    if ($this->isAssetic())
    {
      $filter = new AsseticFilter($filter);
    }

    return $filter;
  }

  /**
   * Evaluates if the class property is an existing class that implements '\Assetic\Filter\FilterInterface'
   *
   * @return bool
   */
  protected function isAssetic(): bool
  {
    if (parent::offsetExists(self::KEY_CLASS))
    {
      $class = parent::offsetGet(self::KEY_CLASS);
      if (class_exists($class) && interface_exists(self::ASSETIC_FILTER))
      {
        return is_subclass_of($class, self::ASSETIC_FILTER, true);
      }
    }

    return false;
  }

  /**
   * Evaluates if the class property is an existing class that implements FilterInterface
   *
   * @return bool
   */
  protected function isPleasing(): bool
  {
    if (parent::offsetExists(self::KEY_CLASS))
    {
      $class = parent::offsetGet(self::KEY_CLASS);
      if (class_exists($class))
      {
        return is_subclass_of($class, FilterInterface::class, true);
      }
    }

    return false;
  }
}
