<?php

namespace DevCoding\Pleasing\Config;

use Symfony\Component\Config\Resource\SelfCheckingResourceChecker;
use Symfony\Component\Config\ResourceCheckerConfigCache;

class AssetConfig extends ResourceCheckerConfigCache
{
  /** @var string */
  public string $extension;
  /** @var string $filename */
  public string $filename;
  /** @var string[] */
  public array $filters = [];
  /** @var string[] */
  public array $inputs;
  /** @var string */
  public string $mime_type;
  /** @var string */
  public string $output;
  /** @var bool */
  public bool $alwaysExport = false;
  /** @var bool  */
  public bool $alwaysWarm = false;
  /** @var bool  */
  public bool $loaded = false;
  /** @var string */
  protected string $hash;
  /** @var bool  */
  protected bool $debug;

  /**
   * @param array $options The absolute cache path
   * @param bool  $debug   Whether debugging (dev mode) is enabled or not
   *
   * @throws \Exception
   */
  public function __construct($options, bool $debug)
  {
    // Required Options
    $this->output = $options['output'];
    $this->inputs = $options['inputs'];
    $this->debug  = $debug;

    // Optional Options
    $this->filters      = $options['filters']       ?? $this->filters;
    $this->alwaysWarm   = $options['always_warm']   ?? $this->alwaysWarm;
    $this->alwaysExport = $options['always_export'] ?? $this->alwaysExport;

    // Full path to output file - used in parent
    $file = $options[ 'cache' ] . DIRECTORY_SEPARATOR . $this->output;

    // Resource Checkers - used in parent
    if(!empty($options[ 'resource_checker' ]))
    {
      $checkers = $options[ 'resource_checker' ];
      if(!is_array($checkers))
      {
        $checkers = [ $checkers ];
      }
    }
    else
    {
      $checkers = [ new SelfCheckingResourceChecker() ];
    }

    parent::__construct($file, $checkers);

    $fileInfo        = new \SplFileInfo($file);
    $this->extension = $fileInfo->getExtension();
    $this->filename  = $fileInfo->getFilename();
    $this->mime_type = $this->getMimeType();
  }

  // region //////////////////////////////////////////////// Public Getters

  /**
   * Returns an ETag for the asset based on the output file path and the last modified date.
   * ETag is generated on the first call to this method and is cached.
   *
   * @return string
   */
  public function getETag(): string
  {
    return $this->getHash() . '_' . $this->getLastModified();
  }

  /**
   * Returns the last modified date of the output file.
   *
   * @return false|int
   */
  public function getLastModified()
  {
    return filemtime($this->getPath());
  }

  /**
   * Checks if the written output file is still fresh.
   *
   * This implementation always returns TRUE when debug is off and the cache file exists.
   *
   * @return bool true if the cache is fresh, false otherwise
   */
  public function isFresh(): bool
  {
    if(!$this->debug && is_file($this->getPath()))
    {
      return true;
    }

    return parent::isFresh();
  }

  // endregion ///////////////////////////////////////////// End Public Getters

  // region //////////////////////////////////////////////// Helpers

  /**
   * Returns a hash generated from the relative output path of this asset.  The hash is generated on the first
   * call to this method then cached.
   *
   * @return string
   */
  private function getHash(): string
  {
    if(empty($this->hash))
    {
      $this->hash = hash('crc32', json_encode([ $this->output ]));
    }

    return $this->hash;
  }

  /**
   * Returns the proper mime type for this asset.  If the mime type hasn't been injected, it is determined using
   * the file extension of the output file and the list of matching mime types in the Pleasing::MIME_TYPE constant.
   *
   * @return string         The mime type.
   * @throws \Exception     If the asset is of a type that cannot be matched to a mime type.
   */
  private function getMimeType(): string
  {
    if($type = (new MimeTypes())[$this->extension] ?? null)
    {
      return $type;
    }

    throw new \InvalidArgumentException('Unable to determine proper mime type for extension: ' . $this->extension);
  }

  // endregion ///////////////////////////////////////////// End Helpers

}
