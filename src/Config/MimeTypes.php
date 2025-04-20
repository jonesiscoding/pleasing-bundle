<?php

namespace DevCoding\Pleasing\Config;

/**
 * @property string $js;
 * @property string $css;
 * @property string $svg;
 * @property string $gif;
 * @property string $jpg;
 * @property string $jpeg;
 * @property string $swf;
 * @property string $psd;
 * @property string $bmp;
 * @property string $tif;
 * @property string $tiff;
 * @property string $jpc;
 * @property string $jp2;
 * @property string $jpx;
 * @property string $iff;
 * @property string $xbm;
 * @property string $ico;
 * @property string $webp;
 * @property string $ovif;
 * @property string $pnm;
 */
class MimeTypes extends \ArrayObject implements \JsonSerializable
{
  private array $default = [ 'js' => 'text/javascript', 'css' => 'text/css', 'svg' => 'image/svg+xml' ];

  public function __construct($mimeTypes = [])
  {
    parent::__construct(
      array_merge($mimeTypes, $this->__images(), $this->default),
      \ArrayObject::ARRAY_AS_PROPS,
      \ArrayIterator::class
    );
  }

  private function __images(): array
  {
    $output = [];
    for ($x = 1; $x < IMAGETYPE_COUNT; ++$x)
    {
      $tMime = @image_type_to_mime_type($x);
      $tExt  = @image_type_to_extension($x, false);

      if($tMime && $tExt)
      {
        if ('tiff' === $tExt)
        {
          $output['tif'] = $tMime;
        }

        if ('jpeg' === $tExt)
        {
          $output['jpg'] = $tMime;
        }

        $output[$tExt] = $tMime;
      }
    }

    $output['pnm'] = 'image/x-portable-anymap';

    return $output;
  }

  public function jsonSerialize()
  {
    json_encode($this->getArrayCopy());
  }
}
