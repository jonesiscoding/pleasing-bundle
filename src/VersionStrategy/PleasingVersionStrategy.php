<?php

namespace DevCoding\Pleasing\VersionStrategy;

use Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy;
use Symfony\Component\HttpKernel\Kernel;

class PleasingVersionStrategy extends JsonManifestVersionStrategy
{
  /** @var string  */
  protected string $public;
  /** @var bool  */
  protected bool $debug;

  /**
   * @param string $manifestPath
   * @param string $public
   * @param bool $debug
   */
  public function __construct(string $manifestPath, string $public, bool $debug = false)
  {
    $this->public = $public;
    $this->debug  = $debug;

    parent::__construct($manifestPath);
  }

  public function applyVersion(string $path)
  {
    if (!$this->debug)
    {
      $url = parent::applyVersion($path);
      if ($url !== $path)
      {
        return $url;
      }
    }

    if ($pub = $this->getPublicPath($path))
    {
      return $pub;
    }

    if (3 === Kernel::MAJOR_VERSION)
    {
      return $this->getPrefixedUrl($path);
    }

    return $path;
  }

  protected function getPrefixedUrl($url)
  {
    $url = '/pleasing/' . $url;

    if (3 === Kernel::MAJOR_VERSION)
    {
      if(!empty($_SERVER[ 'SCRIPT_FILENAME' ]))
      {
        // Append the app_dev.php dynamically
        $url = '/' . basename($_SERVER[ 'SCRIPT_FILENAME' ]) . $url;
      }
    }

    return str_replace('//', '/', $url);
  }

  protected function getPublicPath($url)
  {
    $path = str_replace('//', '/', $this->public . '/' . $url);

    /** @noinspection PhpExpressionAlwaysNullInspection */
    return file_exists($path) ? $url : null;
  }
}
