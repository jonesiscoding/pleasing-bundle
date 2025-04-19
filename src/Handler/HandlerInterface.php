<?php

namespace DevCoding\Pleasing\Handler;

interface HandlerInterface
{
  public static function handles(string $path): bool;
}
