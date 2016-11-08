<?php

namespace Kirby\Plugins\ImageKit;
use A;
use Dir;
use F;
use Thumb;


class Optimizer {

  protected static $kirby;
  protected static $optimizers = [];  

  // These variables store all loaded optimizers of an
  // actual instance of the Optimizer object, sorted by
  // their priority.
  protected $pre;
  protected $post;

  /**
   * Creates an optimmizer for given Thumb
   * 
   * @param Thumb $thumb
   * @param array $pre   Optimizers to apply prior to
   *                     thumbnail creation.
   * @param array $post  Optimizers to apply after
   *                     thumbnail creation.
   */
  protected function __construct($thumb, $pre, $post) {
    static::init();
    $this->thumb = $thumb;
    $this->pre   = $pre;
    $this->post  = $post; 
  }

  /**
   * Creates a new instance of this class for given thumb.
   * 
   * @param  Thumb $thumb 
   * @return Optimizer
   */
  public static function create(Thumb $thumb) {
    static::init();

    $pre  = [];
    $post = [];

    // Get optimizers parameter
    $optimizers = a::get($thumb->options, 'imagekit.optimize', kirby()->option('imagekit.optimize'), true);
    
    foreach(static::$optimizers as $optimizerClass) {
      if($optimizers === true || (is_array($optimizers) && in_array($optimizerClass::name(), $optimizers))) {      
        if($optimizer = $optimizerClass::create($thumb)) {        
          if($optimizer->priority('pre') !== false) {
            $pre[]  = $optimizer;
          }
          if($optimizer->priority('post') !== false) {
            $post[] = $optimizer;
          }
        }  
      }
    }
    
    // Sort all applicable optimization operations.
    usort($pre, function($a, $b) {
      if($a === $b) return 0;
      return ($a->priority('pre') < $b->priority('pre')) ? -1 : 1;
    });

    usort($post, function($a, $b) {
      if($a === $b) return 0;
      return ($a->priority('post') < $b->priority('post')) ? -1 : 1;
    });

    return new static($thumb, $pre, $post);
  }

  /**
   * Runs all operations that should happen before thumbnail
   * creation.
   */
  public function pre() {
    foreach($this->pre as $optimizer) {
      $optimizer->pre();
    }
  }

  /**
   * Runs all operations that should happen after thumbnail
   * creation.
   */
  public function post() {
    foreach($this->post as $optimizer) {
      $optimizer->post();
    }
  }

  /**
   * Registers the optimizer by extending all thumbnail
   * drivers in Kirby’s toolkit.
   */
  public static function register() {
    static $registred;
    if($registred) return;

    foreach(thumb::$drivers as $name => $driver) {
      thumb::$drivers[$name] = function($thumb) use ($driver) {

        if(a::get($thumb->options, 'imagekit.optimize', kirby()->option('imagekit.optimize')) !== false) {
          $optimizer = static::create($thumb);
          
          $optimizer->pre();
          $driver($thumb);
          $optimizer->post();

        } else {
          $driver($thumb);
        }
      };
    }

    $registred = true;
  }

  /**
   * Scans `optimizer` subdir for available optimizers and
   * loads them, if they’re available.
   * 
   * @param  Kirby $kirby
   */
  public static function init($kirby = null) {
    static $initialized;
    if ($initialized) return;

    static::$kirby = $kirby ?: kirby();

    $lib = imagekit()->root() . DS . 'lib' . DS . 'optimizer';

    // Load and initialize all optimizers
    foreach(dir::read($lib, ['base.php']) as $basename) {
      require_once($lib . DS . $basename);
      $optimizerClass = __NAMESPACE__ . '\\Optimizer\\' . f::name($basename);

      // Setup defaults
      static::$kirby->options = array_merge($optimizerClass::defaults(), static::$kirby->options);

      $optimizerClass::configure(static::$kirby);

      if ($optimizerClass::available()) {
        static::$optimizers[] = $optimizerClass;
      }
    }

    $initialized = true;
  }

  public static function available($name) {
    static::init();
    $name = strtolower($name);

    foreach(static::$optimizers as $optimizer) {
      if($optimizer::name() === $name) return true;
    }

    return false;
  }

}
