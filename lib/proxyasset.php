<?php 

namespace Kirby\Plugins\ImageKit;

use A;
use Asset;
use Dimensions;
use Exception;
use F;
use Kirby;
use Media;
use Str;
use Thumb;
use Url;


/**
 * An extended version of Kirby’s Asset class, which also
 * works with files that do not exist yet.
 */
class ProxyAsset extends Asset {
  
  // Thumb parameters like you would path to the `Thumb`
  // class’ constructor
  protected $options   = [];
  
  // Will be changed to true if thumbnail has been generated
  protected $generated = false;
  
  /**
   * Constructor
   *
   * @param Media|string $path
   */
  public function __construct($path) {
    // This constructor function mimics the behavior of both
    // Asset’s and Media’s constructors. Because `realpath()`
    // (used in Media’s constructor) does not work with
    // non-existing files, the complete constructors of both
    // classes are redeclared here, using a different function
    // for resolving paths of
    // non-existing files. This constructor should always try
    // to match the behavior of Kirby’s original Asset class.
    
    // Asset’s constructor
    $this->kirby = kirby::instance();
    
    if($path instanceof Media) {      
      // Because “root” of non-existing files does not work,
      // when they’re initialized as Media objects, we’ll
      // reconstruct the file’s path from it’s URL.
      $root = $this->kirby->roots()->index() . str_replace(kirby()->urls->index, '', $path->url());
      $url  = $path->url();
    } else {
      $root = url::isAbsolute($path) ? null : $this->kirby->roots()->index() . DS . ltrim($path, DS);
      $url  = url::makeAbsolute($path);
    }
    
    // Media’s constructor
    $this->url       = $url;
    $this->root      = $root === null ? $root : static::normalizePath($root);
    $this->filename  = basename($root);
    $this->name      = pathinfo($root, PATHINFO_FILENAME);
    $this->extension = strtolower(pathinfo($root, PATHINFO_EXTENSION));
  }
  
  /**
   * Tries to normalize a given path by resolving `..`
   * and `.`. Tries to mimick the behavoir of `realpath()`
   * which does not work on non-existing files.
   * Source: http://php.net/manual/de/function.realpath.php#84012
   *
   * @param string $path
   * @return string
   */
  protected static function normalizePath($path) {
    $path      = str_replace(['/', '\\'], DS, $path);
    $parts     = explode(DS, $path);
    $absolutes = [];
    foreach($parts as $part) {
      if('.' === $part) continue;
      if('..' === $part)
        array_pop($absolutes);
      else
        $absolutes[] = $part;
    }  
    return implode(DS, $absolutes);
  }
  
  /**
   * Setter and getter for transformation parameters
   *
   * @param array $options
   * @return string
   */
  public function options(array $options = null) {
    if($options === null) {
      return $this->options;      
    } else {
      $this->options = $options;
      return $this;
    }
  }
  
  /**
   * Returns the dimensions of the file if possible. If the
   * proxy asset has not been generated yet, dimensions are
   * read from source file and then calculated according to
   * given thumb options.
   *
   * @return Dimensions
   */
  public function dimensions() {
    
    if($this->generated) {
      // If the thumbnail has been generated, get dimensions
      // from thumb file.
      return parent::dimensions();
    }
    
    if(isset($this->cache['dimensions'])) {
      return $this->cache['dimensions'];
    }
    
    if(!$this->original) {
      throw new Exception('Cannot calculate dimensions of ProxyAsset without a valid original.');
    }
    
    if(!is_array($this->options)) {
      throw new Exception('You have to set transformation options on ProxyAsset before calling `dimensions()`');
    }
    
    if(in_array($this->original->mime(), ['image/jpeg', 'image/png', 'image/gif'])) {
      $size   = (array)getimagesize($this->original->root());
      $width  = a::get($size, 0, 0);
      $height = a::get($size, 1, 0);
    } else {
      $width  = 0;
      $height = 0;
    }
    
    // Create dimensions object and resize according to thumb options.
    $dimensions = new Dimensions($width, $height);
    
    if($this->options['crop']) {
      $dimensions->crop($this->options['width'], $this->options['height']);
    } else {
      $dimensions->fitWidthAndHeight($this->options['width'], $this->options['height'], $this->options['upscale']);
    }
    
    return $this->cache['dimensions'] = $dimensions;
  }
  
  /**
   * Generate the actual thumbnail. This function is triggered
   * by certain methods, which need the final thumbnail to be
   * there for returning reasonable results.
   */
  public function generate() {
    
    if($this->generated) return;    
    
    $thumb = new Thumb($this->original, $this->options);
    
    // Just to be sure to have all corrent data of the
    // resulting object in place, we override this object’s
    // properties with those of thumb’s result.
    $this->reset();
    foreach(['url', 'root', 'filename', 'name', 'extension', 'content'] as $prop) {  
      $this->$prop = $thumb->result->$prop;
    }
    
    $this->generated = true;
  }
  
  public function mime() {
    return f::mime($this->generated ? $this->root : $this->original->root());
  }
  
  public function type() {
    return f::type($this->generated ? $this->root : $this->original->root());
  }
  
  public function is($value) {
    return f::is($this->generated ? $this->root : $this->original->root(), $value);
  }
  
  public function read($format = null) {
    $this->generate();
    return parent::read($format);
  }  
  
  public function content($content = null, $format = null) {
    if(is_null($content)) $this->generate();
    return parent::content($content, $format);
  }
  
  public function move($to) {
    $this->generate();
    return parent::move($to);
  }  
  
  public function copy($to) {
    $this->generate();
    return parent::copy($to);
  }  
  
  public function size() {
    $this->generate();
    return parent::size();
  }
  
  public function modified($format = null, $handler = 'date') {
    $this->generate();
    return parent::modified($format, $handler);
  }
  
  public function base64() {
    $this->generate();
    return parent::base64();
  }
  
  public function exists() {
    $this->generate();
    return parent::exists();
  }
  
  public function isWritable() {
    $this->generate();
    return parent::isWritable();
  }
  
  public function isReadable() {
    $this->generate();
    return parent::isReadable();
  }
  
  public function load($data = array()) {
    $this->generate();
    return parent::load($data);
  }
  
  public function show() {
    $this->generate();
    return parent::show();
  }
  
  public function download($filename = null) {
    $this->generate();
    return parent::download($filename);
  }
  
  public function exif() {
    $this->generate();
    return parent::exif();
  }
  
  public function imagesize() {
    $this->generate();
    return parent::imagesize();
  }
   
  // Methods that don’t need to be overloaded:
  // thumb, resize, crop, width, height, ratio, scale, bw, blur
  // => thumb() fails automatically, if called on a (Proxy)Asset with original set.
  // isThumb, isWebsafe
  // => Don’t need the result file to be in place and work without any further help.
}
