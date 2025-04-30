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
  private array $code  = ['js' => 'text/javascript'];
  private array $style = ['css' => 'text/css'];

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
      array_merge($mimeTypes, $this->code, $this->style, $this->font, $this->__images()),
      \ArrayObject::ARRAY_AS_PROPS,
      \ArrayIterator::class
    );
  }

  public function extensions(): array
  {
    return array_keys($this->getArrayCopy());
  }

  public static function code(): MimeTypes
  {
    $mime = new MimeTypes();
    foreach($mime->getArrayCopy() as $ext => $type)
    {
      if (!isset($mime->code[$ext]))
      {
        $mime->offsetUnset([$ext]);
      }
    }

    return $mime;
  }

  public static function images(): MimeTypes
  {
    $mime = new MimeTypes();
    $not  = array_keys(array_merge($mime->code, $mime->style, $mime->font));
    foreach($not as $ext)
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

  public static function styles(): MimeTypes
  {
    $mime = new MimeTypes();
    foreach($mime->getArrayCopy() as $ext => $type)
    {
      if (!isset($mime->style[$ext]))
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
