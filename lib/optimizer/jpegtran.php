<?php

namespace Kirby\Plugins\ImageKit\Optimizer;

use F;


/**
 * Lossless optimization of JPEG files by using `jpegtran`.
 *
 * See: http://jpegclub.org/jpegtran/
 * and: http://linux.die.net/man/1/jpegtran
 */
class JPEGTran extends Base {

  public static $selector    = ['image/jpeg'];
  public $priority           = [false, 50];

  protected $targetFile;
  protected $tempFile;
  

  public static function defaults() {
    return [
      'imagekit.jpegtran.bin'         => null,
      'imagekit.jpegtran.optimize'    => true,
      'imagekit.jpegtran.copy'        => 'none',
      'imagekit.jpegtran.flags'       => '',
    ];
  }

  public static function available() {
    return !empty(static::$kirby->option('imagekit.jpegtran.bin'));
  }

  public function post() {

    $tmpFile = $this->getTemporaryFilename();

    $command = [];

    $command[] = static::$kirby->option('imagekit.jpegtran.bin');    
    
    if($this->thumb->options['interlace']) {
      $command[] = '-progressive';
    }

    if($this->thumb->options['grayscale']) {
      $command[] = '-grayscale';
    }
    
    // Copy metadata (or not)?
    if($copy = $this->option('imagekit.jpegtran.copy')) {
      $command[] = "-copy $copy";
    }

    if($this->option('imagekit.jpegtran.optimize')) {
      $command[] = '-optimize';
    }

    // Write to a temporary file, so we can compare filesizes
    // after optimization and keep only the smaller file as
    // jpegtran does not always create smaller files.
    $command[] = '-outfile "' . $tmpFile . '"';

    $flags = $this->option('imagekit.jpegtran.flags');
    if(!empty($flags)) {
      // Add exra flags, if defined by user.
      $command[] = $flags;
    }

    $command[] = '"' . $this->thumb->destination->root . '"';

    exec(implode(' ', $command));

    $this->keepSmallestFile($this->thumb->destination->root, $tmpFile);
  }   

}
