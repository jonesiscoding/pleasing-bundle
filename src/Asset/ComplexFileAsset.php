<?php

namespace DevCoding\Pleasing\Asset;

use DevCoding\Pleasing\Filter\FilterCollection;

/**
 * Base class for FileAssets that use "import" style statements to include other assets.
 */
abstract class ComplexFileAsset extends FileAsset
{
  /** @var ComplexFileAsset[] */
  protected array $imports;

  /**
   * @param string                $path     Absolute path to the asset file
   * @param FileAsset[]           $imports  Array of FileAsset objects representing the 'imports' of this asset
   * @param FilterCollection|null $Filters  Possible filters to apply to this asset or the imports.
   */
  public function __construct(string $path, array $imports, ?FilterCollection $Filters = null)
  {
    $this->imports = $imports;

    parent::__construct($path, $Filters);
  }

  /**
   * Returns an array of FileAsset object representing the files included or imported by this ComplexFileAsset
   *
   * @return FileAsset[]
   */
  public function getImports(): array
  {
    return $this->imports;
  }
}
