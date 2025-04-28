<?php

namespace DevCoding\Pleasing\Processor;

use DevCoding\Pleasing\Config\AssetConfig;
use DevCoding\Pleasing\Config\MimeTypes;

/**
 * Handles the translation of image files to the proper output path.
 */
class ImageAssetProcessor extends AbstractStaticProcessor
{
  /**
   * Returns TRUE for extensions in PleasingAssetCollection::IMAGES.
   *
   * @param AssetConfig $AssetConfig
   *
   * @return bool
   */
  public function handles(AssetConfig $AssetConfig): bool
  {
    if ($ext = $AssetConfig->extension)
    {
      if (in_array(strtolower($ext), MimeTypes::images()->extensions()))
      {
        if (!empty($AssetConfig->inputs))
        {
          return true;
        }
      }
    }

    return false;
  }

  /**
   * Handles any transformations or resizing specified in the AssetConfig.
   *
   * @param AssetConfig $AssetConfig
   *
   * @return ImageAssetProcessor
   */
  protected function modify(AssetConfig $AssetConfig): ImageAssetProcessor
  {
    return $this;
  }
}
