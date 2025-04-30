<?php

namespace DevCoding\Pleasing\Config\Normalizer;

use DevCoding\Pleasing\Config\AssetContainer;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;

/**
 * Provides analysis of all Twig templates in the root of the Twig paths with the given name, looking for assets
 * that should be marked required in the pleasing configuration.
 *
 * Class PleasingTwigRequirementAnalyzer
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @package App\View\Pleasing
 */
class TwigNormalizer implements NormalizerInterface
{
  /** @var LoaderInterface */
  protected LoaderInterface $loader;
  /** @var string  */
  protected string $filename;

  /**
   * @param FilesystemLoader $loader
   * @param string           $filename
   */
  public function __construct(FilesystemLoader $loader, string $filename = 'layout.html.twig')
  {
    $this->loader   = $loader;
    $this->filename = $filename;
  }

  /**
   * Returns an array of asset output paths for assets that are required by the base layout in Twig.
   */
  public function normalize(AssetContainer $AssetContainer)
  {
    $twigAssets = $this->getAssetsFromTwig();

    foreach($AssetContainer->getArrayCopy() as $Asset)
    {
      if (in_array($Asset->output, $twigAssets))
      {
        $Asset->alwaysExport = true;
        $Asset->alwaysWarm   = true;
      }
    }
  }

  protected function getAssetsFromTwig(): array
  {
    $assets   = [];
    $filename = $this->filename;
    foreach ($this->getTwigLayouts($filename) as $template)
    {
      $contents = file_get_contents($template);
      $regexes  = ["/{{ ?asset\(([^\)]+)\) ?}}/"];

      foreach ($regexes as $regex)
      {
        if (preg_match_all($regex, $contents, $matches))
        {
          foreach ($matches[ 1 ] as $assetInfo)
          {
            $assetInfo = str_replace('"', "'", $assetInfo);
            if (preg_match("/'([^']+)'/", $assetInfo, $parts))
            {
              $assets[] = $parts[ 1 ];
            }
          }
        }
      }
    }

    return array_keys(array_flip($assets));
  }

  /**
   * Returns an array of absolute paths to the given file, within the Twig paths specified by the loader for this
   * instance.
   *
   * @param string $filename
   *
   * @return array
   */
  protected function getTwigLayouts(string $filename = 'layout.html.twig'): array
  {
    $layouts = [];
    foreach ($this->getPathsFromLoader() as $basePath)
    {
      $layouts = array_merge($layouts, glob($basePath . '/*/' . $filename));
      $layouts = array_merge($layouts, glob($basePath . '/' . $filename));
    }

    return array_unique($layouts);
  }

  /**
   * Returns an array of directories to search for Twig templates, as given by the loader for this instance.
   *
   * @return array
   */
  protected function getPathsFromLoader(): array
  {
    $paths = [];
    if ($this->loader instanceof ChainLoader)
    {
      foreach ($this->loader->getLoaders() as $this->loader)
      {
        $paths = array_merge($paths, $this->getPathsFromLoader());
      }

      return $paths;
    }
    elseif ($this->loader instanceof FilesystemLoader)
    {
      $namespaces = $this->loader->getNamespaces();
      foreach($namespaces as $namespace)
      {
        $paths = array_merge($paths, $this->loader->getPaths($namespace));
      }

      return $paths;
    }

    return [];
  }
}
