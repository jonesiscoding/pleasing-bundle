<?php

namespace DevCoding\Pleasing\Locators;

use DevCoding\Pleasing\Asset\ComplexFileAsset;
use DevCoding\Pleasing\Asset\CssAsset;
use DevCoding\Pleasing\Asset\SassAsset;
use DevCoding\Pleasing\Parsers\ParserInterface;
use DevCoding\Pleasing\Handler\SassHandler;
use DevCoding\Pleasing\Parsers\SassParser;
use DevCoding\Pleasing\Config\Config;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;

class SassLocator extends SassHandler implements LocatorInterface
{
  /** @var SassAsset[] */
  protected array $assets = [];
  /** @var \SplFileInfo[] */
  protected array $children = [];
  protected Config $Config;
  /** @var ParserInterface */
  protected $Parser;

  /**
   * @param Config $config
   */
  public function __construct(Config $config)
  {
    $this->Parser = new SassParser();
    $this->Config = $config;
  }

  /**
   * Locates the absolute path of to the relative path given.
   *
   * @param string      $file       The relative path to the file
   * @param string|null $parentPath The path to the parent file, if any
   * @param bool        $first      Whether to return only the first path found.  Defaults TRUE
   *
   * @return ComplexFileAsset
   */
  public function locate($file, string $parentPath = null, bool $first = true): ComplexFileAsset
  {
    if ($parentPath)
    {
      $ext = pathinfo($file, PATHINFO_EXTENSION) ?: pathinfo($parentPath, PATHINFO_EXTENSION);
      if (in_array($ext, ['scss', 'sass']))
      {
        $file = $this->locatePreCss($file, $parentPath, $first);
      }
      else
      {
        $file = $this->locateFile($file, $parentPath, $first);
      }
    }

    if(!array_key_exists($file, $this->assets))
    {

      if ($this->Parser->handles($file))
      {
        // Sass File
        $located = [];
        foreach($this->Parser->parse($file) as $import)
        {
          $located[] = $this->locate($import, $file);
        }

        $filters = $this->Config->filters->for($file);

        $asset = new SassAsset($file, $located, $filters, $this->Config->directories->cache);
      }
      else
      {
        $asset = new CssAsset($file);
      }

      $this->assets[$file] = $asset;
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
    if (empty($this->children[$parentPath][$file]))
    {
      if (false !== strpos($file, '~'))
      {
        $paths = [$this->Config->directories->node_modules, $this->Config->directories->vendor];
      }
      else
      {
        $paths = $this->getImportPaths($parentPath);
      }

      $locator = new FileLocator($paths);

      $this->children[$parentPath][$file] = new \SplFileInfo($locator->locate($file, dirname($parentPath), $first));
    }

    return $this->children[$parentPath][$file];
  }

  /**
   * Locates a LESS, SCSS, or SASS file.  These files can be prefixed with an underscore, and may or may not have
   * their extension given.  If no extension is given, the parent's extension is used for a hint.
   *
   * @param string $file       The relative path to the file
   * @param string|null $parentPath The path to the parent file, if any
   * @param bool $first      Whether to return only the first path found.  Defaults TRUE
   *
   * @return \SplFileInfo
   */
  protected function locatePreCss(string $file, string $parentPath = null, bool $first = true): string
  {
    $ext   = pathinfo($file, PATHINFO_EXTENSION) ?: pathinfo($parentPath, PATHINFO_EXTENSION);
    $base  = basename($file, '.' . $ext);
    $dir   = dirname($file);
    $tried = [];

    try
    {
      $tried[] = $file;

      return $this->locateFile($file, $parentPath, $first);
    }
    catch(FileLocatorFileNotFoundException $outside)
    {
      try
      {
        $tried[] = sprintf('%s/_%s.%s', $dir, $base, $ext);

        return $this->locateFile(sprintf('%s/_%s.%s', $dir, $base, $ext), $parentPath, $first);
      }
      catch(FileLocatorFileNotFoundException $e)
      {
        try
        {
          return $this->locateFile(sprintf('%s/%s.%s', $dir, $base, $ext), $parentPath, $first);
        }
        catch(FileLocatorFileNotFoundException $e)
        {
          $all = [];
          foreach($tried as $try)
          {
            foreach($outside->getPaths() as $path)
            {
              $all[] = $path . '/' . $try;
            }
          }

          $message = sprintf('The file "%s" requested in "%s" does not exist (tried: "%s")', $file, $parentPath, implode('", "', $all));

          throw new FileLocatorFileNotFoundException($message, 0, $outside, $outside->getPaths());
        }
      }
    }
  }

  /**
   * Returns the import paths relevant to the given file, including those configured in filters for this type of file,
   * and the path of the file itself.  Used to determine where to source from when an @import statement is relative.
   *
   * @param string $filePath
   *
   * @return array
   */
  private function getImportPaths(string $filePath): array
  {
    // Add Import Paths from Filters
    $importPaths   = [];
    $filtersConfig = $this->Config->filters->config;
    if(!empty($filtersConfig))
    {
      foreach($filtersConfig as $filterConfig)
      {
        $applyTo      = $filterConfig->apply_to     ?? null;
        $fImportPaths = $filterConfig->import_paths ?? null;

        if($fImportPaths && $applyTo && preg_match('#' . $applyTo . '#i', $filePath))
        {
          foreach($fImportPaths as $fImportPath)
          {
            $importPaths[] = $fImportPath;
          }
        }
      }
    }

    return array_unique($importPaths);
  }
}
