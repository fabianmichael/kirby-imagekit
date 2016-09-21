<?php

namespace Kirby\Plugins\ImageKit\Optimizer;


/**
 *  Uses `mozjpeg` for encoding JPEG files. This often creates
 *  much smaller JPEG files than most other encoders at a
 *  comparable quality.
 *
 *  See: https://github.com/mozilla/mozjpeg
 */
class MozJPEG extends Base {

  public static $selector    = ['image/jpeg'];
  public $priority           = [100, 5];
  
  protected $targetFile;
  protected $tmpFile;


  public static function defaults() {
    return [
      'imagekit.mozjpeg.bin'         => null,
      'imagekit.mozjpeg.quality'     => 85,
      'imagekit.mozjpeg.flags'       => '',
    ];
  }

  public static function available() {
    return !empty(static::$kirby->option('imagekit.mozjpeg.bin'));
  }

  public function pre() {
    $this->targetFile = $this->thumb->destination->root;

    if($this->isDriver('im')) {
      // Instruct imagemagick driver to write out a temporary,
      // uncrompressed TGA file, so our JPEG will not be
      // compressed twice. I played around with PNM too,
      // because it’s much faster as an intermediate format,
      // but some images got corrupted by mozjpeg.
      $this->tmpFile = $this->getTemporaryFilename('tga');
      $this->thumb->destination->root = $this->tmpFile;
    } else {
      // If GD driver (or an unknown driver) is active, we
      // need to encode twice, because SimpleImage can only
      // save to JPEG, PNG or GIF. As saving a 24-bit
      // lossless PNG as an intermediate step is too
      // expensive for large images, we need to encode
      // as JPEG :-(
      // This also needs a temporary file, because it seems
      // that mozjpeg cannot overwrite the it’s file.
      $this->tmpFile = $this->getTemporaryFilename();
      $this->thumb->destination->root = $this->tmpFile;
      $this->thumb->options['quality'] = 99;
    }
  }

  public function post() {

    $command = [];

    $command[] = static::$kirby->option('imagekit.mozjpeg.bin');    
    
    // Quality
    $command[] = '-quality ' . $this->option('imagekit.mozjpeg.quality');

    // Interlace
    if($this->thumb->options['interlace']) {
      $command[] = '-progressive';
    }

    // Grayscale
    if($this->thumb->options['grayscale']) {
      $command[] = '-grayscale';
    }

    // Set output file.
    $command[] = '-outfile "' . $this->targetFile . '"';

    $flags = $this->option('imagekit.mozjpeg.flags');
    if(!empty($flags)) {
      // Add exra flags, if defined by user.
      $command[] = $flags;
    }
    
    // Use tmp file as input.
    $command[] = '"' . $this->tmpFile . '"';

    exec(implode(' ', $command));
    // Delete temporary file and restore destination path
    // on thumb object. This only needs to be done, if
    // ImageMagick driver is used and the input file was
    // in PNM format.
    @unlink($this->tmpFile);
    $this->thumb->destination->root = $this->targetFile;
  }

}
