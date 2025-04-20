<?php

namespace DevCoding\Pleasing\Asset;

abstract class ComplexFileAsset extends FileAsset
{
  /** @var ComplexFileAsset[] */
  protected array $imports;

  public function __construct(string $path, array $imports, $Filters, $cache = null)
  {
    $this->imports = $imports;

    parent::__construct($path, $Filters, $cache);
  }

  public function getImports(): array
  {
    return $this->imports;
  }

  public function isFresh($timestamp): bool
  {
    if (!parent::isFresh($timestamp))
    {
      return false;
    }

    foreach ($this->imports as $import)
    {
      if (!$import->isFresh($timestamp))
      {
        return false;
      }
    }

    return true;
  }
}
