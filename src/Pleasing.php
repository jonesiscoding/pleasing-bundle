<?php

namespace DevCoding\Pleasing;

use DevCoding\Html\Element\HtmlElement;
use DevCoding\Pleasing\Processor\ProcessorInterface;
use DevCoding\Pleasing\Config\AssetConfig;
use DevCoding\Pleasing\Config\Config;
use DevCoding\Pleasing\Exception\NotFoundException;

class Pleasing
{
  protected Config $Config;
  /** @var ProcessorInterface[] */
  protected array $processors;

  /**
   * @param Config               $Config
   * @param ProcessorInterface[] $processors
   */
  public function __construct(Config $Config, array $processors = [])
  {
    $this->Config = $Config;
  }

  public function get(string $item, $refresh = false): AssetConfig
  {
    if ($this->Config->assets->has($item))
    {
      $AssetConfig = $this->Config->assets->get($item);

      if($refresh || !$AssetConfig->isFresh())
      {
        $this->refresh($AssetConfig);
      }

      return $AssetConfig;
    }

    throw new NotFoundException($item);
  }

  /**
   * @param string|HtmlElement $item
   *
   * @return $this
   */
  public function load($item): Pleasing
  {
    if ($item instanceof HtmlElement)
    {
      $this->Config->elements->add($item);
    }
    else
    {
      if (!$this->Config->assets->has($item))
      {
        if ($this->Config->debug)
        {
          throw new NotFoundException(sprintf('The requested asset "%s" was not found.', $item));
        }

        // Add the Missing Asset (will generate a 404 when requesting the asset, but no exception in the view)
        $AssetConfig = new AssetConfig(['output' => $item, 'input' => []], $this->Config->debug);
        $this->Config->assets->getIterator()->offsetSet($item, $AssetConfig);
      }

      $this->Config->assets->get($item)->loaded = true;
    }

    return $this;
  }

  /**
   * @param AssetConfig $Asset
   *
   * @return AssetConfig
   * @throws \InvalidArgumentException
   */
  public function refresh(AssetConfig $Asset): AssetConfig
  {
    foreach($this->processors as $Processor)
    {
      if ($Processor->handles($Asset->output))
      {
        $Processor->process($Asset);

        return $Asset;
      }
    }

    throw new \InvalidArgumentException("No processors configured to handle this file type.");
  }

  public function warm()
  {
    $required = $this->Config->assets->filterByAlwaysWarm();

    foreach($required as $tAsset)
    {
      if (!$tAsset->isFresh())
      {
        $this->refresh($tAsset);
      }
    }

    $this->writeManifest();
  }

  public function readManifest(): array
  {
    if(isset($this->Config->manifest) && is_file($this->Config->manifest))
    {
      return json_decode(file_get_contents($this->Config->manifest), true);
    }

    return [];
  }

  public function writeManifest(): Pleasing
  {
    if(isset($this->Config->manifest))
    {
      $manifest = $this->readManifest();

      foreach($this->Config->assets->getArrayCopy() as $AssetConfig)
      {
        $manifest[ $AssetConfig->output ] = preg_replace(
          '#.'.$AssetConfig->extension.'$#',
          sprintf('%s.%s', $AssetConfig->getETag(), $AssetConfig->extension),
          $AssetConfig->output
        );
      }

      file_put_contents($this->Config->manifest, json_encode($manifest, JSON_PRETTY_PRINT), LOCK_EX);
    }

    return $this;
  }
}
