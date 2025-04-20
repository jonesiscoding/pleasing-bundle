<?php

namespace DevCoding\Pleasing\Parsers;

interface ParserInterface
{
  public function parse(string $path): array;
}
