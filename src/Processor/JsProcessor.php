<?php

namespace DevCoding\Pleasing\Processor;

use DevCoding\Pleasing\Config\Config;
use DevCoding\Pleasing\Handler\HandlerInterface;
use DevCoding\Pleasing\Handler\JsHandlerTrait;
use DevCoding\Pleasing\Locators\JsLocator;

/**
 * @property JsLocator $Locator
 */
class JsProcessor extends FileProcessor implements HandlerInterface, ProcessorInterface
{
  use JsHandlerTrait;

  /**
   * @param Config           $Config
   * @param JsLocator|null $Locator
   */
  public function __construct(Config $Config, ?JsLocator $Locator = null)
  {
    parent::__construct($Config, $Locator ?? new JsLocator($Config));
  }
}
