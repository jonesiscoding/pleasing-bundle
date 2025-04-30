<?php

namespace DevCoding\Pleasing\Locators;

use DevCoding\Pleasing\Asset\JsAsset;
use DevCoding\Pleasing\Config\Config;
use DevCoding\Pleasing\Handler\HandlerInterface;
use DevCoding\Pleasing\Handler\JsHandlerTrait;
use Symfony\Component\Config\FileLocator;

class JsLocator implements LocatorInterface, HandlerInterface
{
  use JsHandlerTrait;

  /** @var JsAsset[] */
  protected array $assets = [];
  protected Config $Config;

  public function __construct(Config $Config)
  {
    $this->Config = $Config;
  }

  /**
   * Locates the absolute path of to the relative path given.
   *
   * @param string      $file       The relative path to the file
   * @param string|null $parentPath The path to the parent file, if any
   * @param bool        $first      Whether to return only the first path found.  Defaults TRUE
   *
   * @return JsAsset
   */
  public function locate(string $file, string $parentPath = null, bool $first = true): JsAsset
  {
    if ($parentPath)
    {
      $file = $this->locateFile($file, $parentPath, $first);
    }

    if(!array_key_exists($file, $this->assets))
    {
      $this->assets[$file] = new JsAsset($file);
    }

    return $this->assets[$file];
  }

  /**
   * Locates the file using a Symfony file locator.
   *
   * @param string $file       The relative path to the file
   * @param string $parentPath The path to the parent file, if any
   * @param bool $first      Whether to return only the first path found.  Defaults TRUE
   *
   * @return \SplFileInfo
   */
  protected function locateFile(string $file, string $parentPath, bool $first = true): string
  {
    $paths = [];
    if (false !== strpos($file, '~'))
    {
      $paths = [$this->Config->directories->node_modules, $this->Config->directories->vendor];
    }

    $locator = new FileLocator($paths);

    return new \SplFileInfo($locator->locate($file, dirname($parentPath), $first));
  }
}
