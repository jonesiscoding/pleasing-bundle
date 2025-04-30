<?php

namespace DevCoding\Pleasing\Twig;

use DevCoding\Pleasing\Config\Config;
use DevCoding\Pleasing\Config\ElementContainer;
use DevCoding\Html\Element\HtmlElement;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Component\Asset\Packages;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class PleasingTwig
 * @package Common\PleasingBundle\Twig\Extension
 */
class PleasingExtension extends AbstractExtension
{
  /** @var AssetExtension */
  protected AssetExtension $AssetExtension;
  /** @var Config */
  protected Config $Config;
  /** @var Packages */
  protected Packages $Packages;

  // region //////////////////////////////////////////////// Init & Alias Methods

  /**
   * @param Config         $Config
   * @param Packages       $packages
   * @param AssetExtension $AssetExtension
   */
  public function __construct(Config $Config, Packages $packages, AssetExtension $AssetExtension)
  {
    $this->Config         = $Config;
    $this->Packages       = $packages;
    $this->AssetExtension = $AssetExtension;
  }

  // endregion ///////////////////////////////////////////// End Init & Alias Methods

  // region //////////////////////////////////////////////// Required Twig Methods

  public function getName(): string
  {
    return "pleasing";
  }

  /**
   * Builds an array of functions that are accessible in Twig templates.  When adding a new method to this extension,
   * a line must be added to the array in this method.
   *
   * @return array
   */
  public function getFunctions(): array
  {
    return [
        new TwigFunction('asset', [ $this, 'getAssetUrl' ], [ 'is_safe' => [ 'html' ] ]),
        new TwigFunction('includes', [ $this, 'getIncludedAssets' ]),
        new TwigFunction('loaded_elements', [$this, 'getLoadedElements']),
        new TwigFunction('element', [$this, 'getAssetTag'], ['is_safe' => ['html']]),
        new TwigFunction('isUrl', [$this, 'isUrl']),
    ];
  }

  // endregion ///////////////////////////////////////////// End Required Twig Methods

  // region //////////////////////////////////////////////// Main Twig Facing Methods

  public function isUrl($url): bool
  {
    if (1 === count($url))
    {
      $parsed = parse_url($url[0]);

      return false !== $parsed && isset($parsed['host']);
    }

    return false;
  }

  /**
   * Returns the public url/path of an asset.
   *
   * If the package used to generate the path is an instance of
   * UrlPackage, you will always get a URL and not a path.
   */
  public function getAssetUrl(string $path, ?string $packageName = null): string
  {
    $url = $this->Packages->getUrl($path, $packageName);

    // Check if it exists in Symfony's default public dir.
    $absolutePath = $this->getAbsolutePath($url);
    if (!is_file($absolutePath) && null === $packageName)
    {
      $url = $this->Packages->getUrl($path, 'pleasing');
    }

    return $url;
  }

  private function getAbsolutePath(string $url): string
  {
    return $this->Config->directories->public . $url;
  }

  /**
   * Returns the version of an asset.
   */
  public function getAssetVersion(string $path, ?string $packageName = null): string
  {
    return $this->Packages->getVersion($path, $packageName);
  }

  /**
   * Retrieves an array of output paths for included assets of the given type.
   *
   * @param string $type
   *
   * @return string[]
   */
  public function getIncludedAssets(string $type): array
  {
    return array_keys($this->Config->assets->filterByLoaded()->filterByExtension($type)->getArrayCopy());
  }

  /**
   * @param string      $tag
   * @param string|null $parent
   *
   * @return ElementContainer
   */
  public function getLoadedElements(string $tag, string $parent = null): ElementContainer
  {
    $elements = $this->Config->elements->filterByTag($tag);

    return $parent ? $elements->filterByParent($parent) : $elements;
  }

  /**
   * @param HtmlElement $inline
   *
   * @return string
   */
  public function getAssetTag(HtmlElement $inline): string
  {
    return (string) $inline;
  }

  // endregion ///////////////////////////////////////////// End Main Twig Facing Methods

  // region //////////////////////////////////////////////// Dependent Methods

  /**
   * Returns indication of application is running in DEBUG mode.
   *
   * @return bool
   */
  protected function isDebug(): bool
  {
    return $this->Config->debug;
  }

  // endregion ///////////////////////////////////////////// End Dependent Methods
}
