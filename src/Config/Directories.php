<?php

namespace DevCoding\Pleasing\Config;

use Symfony\Component\HttpKernel\Kernel;

/**
 * @property string $public
 * @property string $vendor
 * @property string $node_modules
 * @property string $cache
 * @property string $project
 */
class Directories extends \ArrayObject
{
  public function __construct($array = [], ?Kernel $kernel = null)
  {
    parent::__construct($array, \ArrayObject::ARRAY_AS_PROPS, \ArrayIterator::class);

    if (!parent::offsetExists(Config::PROJECT) || !$this->offsetExists(Config::CACHE))
    {
      $kernel = $this->kernel($kernel);
      $this->offsetSet(Config::PROJECT, $kernel->getProjectDir());
      $this->offsetSet(Config::CACHE, $kernel->getCacheDir());
    }

    foreach([Config::PUBLIC, Config::VENDOR, Config::NODE] as $dir)
    {
      if (!parent::offsetExists($dir))
      {
        $this->offsetSet($dir, $this->findDir($dir));
      }
    }
  }

  /**
   * @param string $suffix
   *
   * @return string|null
   */
  private function findDir(string $suffix): ?string
  {
    $dir = sprintf('%s/%s', $this->offsetGet('project'), $suffix);

    if ($suffix === Config::PUBLIC)
    {
      if (!is_dir($dir))
      {
        return $this->findDir('web');
      }
    }
    elseif ($suffix === 'web')
    {
      if (!is_dir($dir))
      {
        $pj = $this->offsetGet('project');
        throw new \LogicException(sprintf(
            'The public directory could not be found at "%s/public" or "%s/web".',
            $pj,
            $pj
        ));
      }
    }

    return is_dir($suffix) ? $dir : null;
  }

  private function kernel($kernel): Kernel
  {
    if (!$kernel instanceof Kernel)
    {
      if (!isset($_SERVER['APP_ENV']))
      {
        throw new \InvalidArgumentException('The "APP_ENV" environment variable must be set if no Kernel is provided.');
      }

      if (!isset($_SERVER['APP_DEBUG']))
      {
        throw new \InvalidArgumentException('The "APP_DEBUG" environment variable must be set if no Kernel is provided.');
      }

      return new FauxKernel($_SERVER['APP_ENV'], $_SERVER['APP_DEBUG']);
    }

    return $kernel;
  }
}
