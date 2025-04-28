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
  private array $default = [ 'js' => 'text/javascript', 'css' => 'text/css'];

  private array $font = [
      'eot'   => 'application/vnd.ms-fontobject',
      'ttf'   => 'application/x-font-ttf',
      'otf'   => 'application/x-font-opentype',
      'woff'  => 'application/font-woff',
      'woff2' => 'application/font-woff2',
      'svg'   => 'image/svg+xml',
  ];

  public function __construct($mimeTypes = [])
  {
    parent::__construct(
      array_merge($mimeTypes, $this->__images(), $this->default, $this->font),
      \ArrayObject::ARRAY_AS_PROPS,
      \ArrayIterator::class
    );
  }

  public function extensions(): array
  {
    return array_keys($this->getArrayCopy());
  }

  public static function images(): MimeTypes
  {
    $mime = new MimeTypes();
    foreach($mime->default as $ext => $type)
    {
      $mime->offsetUnset($ext);
    }

    return $mime;
  }

  public static function fonts(): MimeTypes
  {
    $mime = new MimeTypes();
    foreach($mime->getArrayCopy() as $ext => $type)
    {
      if (!isset($mime->fonts[$ext]))
      {
        $mime->offsetUnset([$ext]);
      }
    }

    return $mime;
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

    $output['svg'] = 'image/svg+xml';
    $output['pnm'] = 'image/x-portable-anymap';

    return $output;
  }

  public function jsonSerialize()
  {
    json_encode($this->getArrayCopy());
  }
}
