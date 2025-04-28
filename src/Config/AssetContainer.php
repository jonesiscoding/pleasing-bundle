<?php

namespace DevCoding\Pleasing\Config;

use DevCoding\Pleasing\Base\AbstractContainer;

/**
 * @method AssetConfig[] getArrayCopy()
 * @method AssetConfig   get(string $id)
 */
class AssetContainer extends AbstractContainer
{
  /**
   * @param AssetConfig $asset
   *
   * @return AssetContainer
   */
  public function add($asset): AssetContainer
  {
    $this->getIterator()->offsetSet($asset->output, $asset);

    return $this;
  }

  /**
   * @return AssetContainer
   */
  public function filterByLoaded(): AssetContainer
  {
    return $this->with(array_filter($this->getIterator()->getArrayCopy(), function($asset) { return $asset->loaded; }));
  }

  /**
   * Returns an Asset collection that only contains configuration objects for which the output is a font
   *
   * @param bool $exclude
   *
   * @return AssetContainer
   */
  public function filterFonts(bool $exclude = false): AssetContainer
  {
    return $this->filterByExtension(MimeTypes::fonts()->extensions(), $exclude);
  }

  /**
   * Returns an Asset collection that only contains configuration objects for which the output is an image.
   *
   * @param bool $exclude
   *
   * @return AssetContainer
   */
  public function filterImages(bool $exclude = false): AssetContainer
  {
    return $this->filterByExtension(MimeTypes::images()->extensions(), $exclude);
  }

  /**
   * Returns an Asset collection that only contains configuration objects for which the output is a stylesheet.
   *
   * @param bool $exclude
   *
   * @return AssetContainer
   */
  public function filterStyles(bool $exclude = false): AssetContainer
  {
    return $this->filterByExtension(['css'], $exclude);
  }

  /**
   * Returns an Asset collection that only contains configuration objects for which the output is a javascript.
   *
   * @param bool $exclude
   *
   * @return AssetContainer
   */
  public function filterScripts(bool $exclude = false): AssetContainer
  {
    return $this->filterByExtension(['js'], $exclude);
  }

  /**
   * Returns a new AssetCollection containing only the assets with an output file that match the given extensions.
   * If the $exclude argument is true, the collection will contain only assets that DO NOT match.
   *
   * @param array|string $extensions The array of extensions to check
   * @param bool         $exclude    Reverse the filter, returning only assets without the given extensions.
   *
   * @return AssetContainer  The new filtered AssetCollection object
   */
  public function filterByExtension($extensions, bool $exclude = false): AssetContainer
  {
    $extensions = is_array($extensions) ? $extensions : [$extensions];
    $filtered   = [];

    foreach($this->getArrayCopy() as $key => $asset)
    {
      $lowExt = strtolower($asset->extension);
      if ($exclude && false === in_array($lowExt, $extensions))
      {
        $filtered[$key] = $asset;
      }
      elseif (!$exclude && in_array($lowExt, $extensions))
      {
        $filtered[$key] = $asset;
      }
    }

    return $this->with($filtered);
  }
}
