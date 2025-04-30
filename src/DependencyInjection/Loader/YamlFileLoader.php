<?php

namespace DevCoding\Pleasing\DependencyInjection\Loader;

use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader as SymfonyYamlFileLoader;

class YamlFileLoader extends SymfonyYamlFileLoader
{
  /** @var bool  */
  protected bool $mergeArray;

  /**
   * @param ContainerBuilder     $container   Symfony container builder
   * @param FileLocatorInterface $locator     Symfony file locator
   * @param bool                 $mergeArray  Indicates if parameters that are arrays should be merged, not overwritten
   */
  public function __construct(ContainerBuilder $container, FileLocatorInterface $locator, bool $mergeArray = true)
  {
    $this->mergeArray = $mergeArray;

    parent::__construct($container, $locator);
  }

  protected function loadFile($file): ?array
  {
    $content = parent::loadFile($file);

    if (!empty($content['parameters']))
    {
      // Deal just with the parameters
      $parameters = $content['parameters'];

      // Handle merging of array parameters, if applicable.
      // This is explicitly handled after the dot prefix handling above.
      if ($this->mergeArray)
      {
        foreach($parameters as $key => $value)
        {
          if (is_array($value))
          {
            if ($this->container->hasParameter($key))
            {
              $existing = $this->container->getParameter($key);
              if (is_array($existing))
              {
                $parameters[$key] = $this->array_merge_recursive_distinct($existing, $value);
              }
            }
          }
        }
      }

      // Put our changed parameter array back in
      $content['parameters'] = $parameters;
    }

    return $content;
  }

  private function array_merge_recursive_distinct(array $array1, array $array2)
  {
    $arrays = func_get_args();
    $base   = array_shift($arrays);
    if(!is_array($base))
    {
      $base = empty($base) ? [] : [$base];
    }
    foreach($arrays as $append)
    {
      if(!is_array($append))
      {
        // right side is not an array. since right side takes precedence,
        // squash whatever was on the left and make it a scalar value.
        $base = $append;

        continue;
      }
      foreach($append as $key => $value)
      {
        if(!array_key_exists($key, $base) and !is_numeric($key))
        {
          $base[$key] = $append[$key];

          continue;
        }
        if(is_array($value) or (isset($base[$key]) && is_array($base[$key])))
        {
          if (!isset($base[$key]))
          {
            $base[$key] = [];
          }
          $base[$key] = $this->array_merge_recursive_distinct($base[$key], $append[$key]);
        }
        elseif(is_numeric($key))
        {
          if(!in_array($value, $base))
          {
            $base[] = $value;
          }
        }
        else
        {
          $base[$key] = $value;
        }
      }
    }

    return $base;
  }
}
