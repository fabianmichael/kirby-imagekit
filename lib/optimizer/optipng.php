<?php

namespace Kirby\Plugins\ImageKit\Optimizer;


/**
 * Lossless optimization of PNG images using `optipng`.
 *
 * See: http://optipng.sourceforge.net/
 * and: http://optipng.sourceforge.net/pngtech/optipng.html
 */
class OptiPNG extends Base {

  public static $selector    = ['image/png'];
  public $priority           = [false, 50];

  protected $targetFile;
  protected $tmpFile;
  

  public static function defaults() {
    return [
      'imagekit.optipng.bin'   => null,
      'imagekit.optipng.level' => 2,
      'imagekit.optipng.strip' => 'all',
      'imagekit.optipng.flags' => '',
    ];
  }

  public static function available() {
    return !empty(static::$kirby->option('imagekit.optipng.bin'));
  }

  public function post() {

    $command = [];

    $command[] = static::$kirby->option('imagekit.optipng.bin');    

    // Optimization Level
    $level = $this->option('imagekit.optipng.level');
    if($level !== false) { // Level can be 0, so strict comparison is neccessary
      $command[] = "-o$level";
    }

    // Should we strip Metadata?
    if($strip = $this->option('imagekit.optipng.strip')) {
      $command[] = "-strip $strip";
    }

    $flags = $this->option('imagekit.optipng.flags');
    if(!empty($flags)) {
      // Add exra flags, if defined by user.
      $command[] = $flags;
    }

    // Set file to optimize
    $command[] = '"' . $this->thumb->destination->root . '"';
    
    exec(implode(' ', $command));

  }

}
