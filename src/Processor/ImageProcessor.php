<?php

namespace DevCoding\Pleasing\Processor;

use DevCoding\Pleasing\Config\AssetConfig;
use DevCoding\Pleasing\Config\MimeTypes;

/**
 * Handles the translation of image files to the proper output path.
 */
class ImageProcessor extends AbstractStaticProcessor
{
  /**
   * Returns TRUE for extensions in PleasingAssetCollection::IMAGES.
   *
   * @param string $file
   *
   * @return bool
   */
  public static function handles(string $file): bool
  {
    if ($ext = static::getExtension($file))
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
   * @return ImageProcessor
   */
  protected function modify(AssetConfig $AssetConfig): ImageProcessor
  {
    return $this;
  }
}
