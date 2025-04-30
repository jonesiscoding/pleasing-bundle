<?php

namespace DevCoding\Pleasing\Processor;

use DevCoding\Pleasing\Handler\HandlerInterface;
use DevCoding\Pleasing\Handler\SassHandlerTrait;
use DevCoding\Pleasing\Locators\SassLocator;
use DevCoding\Pleasing\Config\Config;

/**
 * @property SassLocator $Locator
 */
class SassProcessor extends FileProcessor implements HandlerInterface, ProcessorInterface
{
  use SassHandlerTrait;

  /**
   * @param Config           $Config
   * @param SassLocator|null $Locator
   */
  public function __construct(Config $Config, ?SassLocator $Locator = null)
  {
    parent::__construct($Config, $Locator ?? new SassLocator($Config));
  }
}
