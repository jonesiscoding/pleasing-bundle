<?php

namespace DevCoding\Pleasing\Processor;

use DevCoding\Pleasing\Config\AssetConfig;
use DevCoding\Pleasing\Handler\HandlerInterface;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Base class for asset 'compiler' classes that simply copy a file, with or without additional modifications.
 *
 * Class AbstractStaticAssetCompiler
 * @package App\View\Pleasing\Compiler
 */
abstract class AbstractStaticProcessor implements ProcessorInterface, HandlerInterface
{
  /**
   * Must perform any needed modifications to the file. The path returned by AssetConfig::getPath can be assumed to
   * exist when passed to this method.
   *
   * @param AssetConfig $AssetConfig
   *
   * @return $this|AbstractStaticProcessor|ProcessorInterface
   */
  abstract protected function modify(AssetConfig $AssetConfig);

  /**
   * Performs a simple copy from an asset's first input file to the cache path, then calls the 'modify' method for
   * the instance to perform any modifications on the copied file.
   *
   * @param AssetConfig $AssetConfig
   *
   * @return $this|ProcessorInterface
   */
  public function process(AssetConfig $AssetConfig): AssetConfig
  {
    $inputs = $AssetConfig->inputs;
    $input  = reset($inputs);
    $output = $AssetConfig->getPath();
    $outDir = dirname($output);

    if (!is_dir($outDir))
    {
      @mkdir($outDir, 0777, true);
    }

    if (is_readable($input))
    {
      if (!@copy($input, $output))
      {
        throw new IOException(sprintf('Copy Failed: "%s" => "%s"', $input, $output));
      }

      // Perform any modifications
      $this->modify($AssetConfig);
    }

    return $this;
  }

  /**
   * Gets the extension from a filename, for use in situations where a full path is not available.
   *
   * @param string $filename The filename to determine the extension of.
   *
   * @return  string         The extension.  If no extension can be found, then returns an empty string.
   */
  protected static function getExtension(string $filename): string
  {
    if (false !== $pos = strrpos($filename, '.'))
    {
      return substr($filename, $pos);
    }

    return '';
  }
}
