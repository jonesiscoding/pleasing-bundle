<?php

namespace DevCoding\Pleasing\DependencyInjection\Locator;

use Symfony\Component\Config\FileLocator;

/**
 * FileLocator that adds a 'glob' method to return an array of located files.  Note that the 'locate' method does not
 * handle glob syntax, only the glob method.
 *
 * Class GlobFileLocator
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @package AppBundle\Config
 */
class GlobFileLocator extends FileLocator
{
  public function glob($name, $currentPath = null): array
  {
    $files = [];
    $paths = $this->paths;

    if (null !== $currentPath)
    {
      array_unshift($paths, $currentPath);
    }

    $paths = array_unique($paths);

    foreach($paths as $path)
    {
      foreach(glob($path . DIRECTORY_SEPARATOR . $name, GLOB_BRACE) as $file)
      {
        $files[] = $file;
      }
    }

    return $files;
  }
}
