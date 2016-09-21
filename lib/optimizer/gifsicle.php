<?php

namespace Kirby\Plugins\ImageKit\Optimizer;

use F;


/**
 * Lossless optimization of GIF files by using `gifsicle`.
 * Supports animated GIFs.
 *
 * See: https://www.lcdf.org/gifsicle/
 */
class Gifsicle extends Base {

  public static $selector    = ['image/gif'];
  public $priority           = [false, 50];

  protected $targetFile;
  protected $tempFile;
  

  public static function defaults() {
    return [
      'imagekit.gifsicle.bin'    => null,
      'imagekit.gifsicle.level'  => 3,
      'imagekit.gifsicle.colors' => false,
      'imagekit.gifsicle.flags'  => '',
    ];
  }

  public static function available() {
    return !empty(static::$kirby->option('imagekit.gifsicle.bin'));
  }

  public function post() {

    $tmpFile = $this->getTemporaryFilename();

    $command = [];

    $command[] = static::$kirby->option('imagekit.gifsicle.bin');

    if($this->thumb->options['interlace']) {
      $command[] = '--interlace';
    }

    // Set colors
    $colors = $this->option('imagekit.gifsicle.colors');
    if($colors !== false) {
      $command[] = "--colors $colors";
    }

    // Set optimization level.
    $command[] = '--optimize=' . $this->option('imagekit.gifsicle.level');

    $flags = $this->option('imagekit.gifsicle.flags');
    if(!empty($flags)) {
      // Add exra flags, if defined by user.
      $command[] = $flags;
    }

    // Set output file
    $command[] = '--output "' . $tmpFile . '"';

    // Set input file
    $command[] = '"' . $this->thumb->destination->root . '"';
    
    exec(implode(' ', $command));

    $this->keepSmallestFile($this->thumb->destination->root, $tmpFile);
  }

}
