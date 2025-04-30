<?php

namespace DevCoding\Pleasing\Processor;

use DevCoding\Pleasing\Config\AssetConfig;
use DevCoding\Pleasing\Config\MimeTypes;

/**
 * Handles the translation of a font file to the proper output path.
 */
class FontProcessor extends AbstractStaticProcessor
{
  /**
   * Returns TRUE for extensions in PleasingAssetCollection::FONTS.
   *
   * @param AssetConfig $AssetConfig
   *
   * @return bool
   */
  public function handles(AssetConfig $AssetConfig): bool
  {
    if ($ext = $AssetConfig->extension)
    {
      if (in_array(strtolower($ext), MimeTypes::fonts()->extensions()))
      {
        return true;
      }
    }

    return false;
  }

  /**
   * Performs no modifications.
   *
   * @param AssetConfig $AssetConfig
   *
   * @return $this
   */
  protected function modify(AssetConfig $AssetConfig): FontProcessor
  {
    // No modifications needed
    return $this;
  }
}
