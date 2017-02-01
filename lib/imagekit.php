<?php

namespace Kirby\Plugins\ImageKit;

use F;
use Obj;
use Str;


/**
 * Utility class for retrieving information about the plugin
 * version and it’s license.
 */
class ImageKit {
  
  protected $version;
  
  protected function __construct() {
    // Just declared to prevent direct instantiation of this
    // class (singleton pattern).
  }

  public static function instance() {
    static $instance;
    return ($instance ?: $instance = new static());
  }
  
  public function version() {
    if(is_null($this->version)) {
      $package = json_decode(f::read(dirname(__DIR__) . DS . 'package.json'));
      $this->version = $package->version;
    }

    return $this->version;
  }

  public function root() {
    return dirname(__DIR__);
  }
  
  public function license() {
    $key  = kirby()->option('imagekit.license');
    $type = 'trial';
    
    /**
     * Hey there,
     * 
     * if you have digged deep into Kirby’s source code,
     * than you’ve probably stumbled across a similiar
     * message, asking you to be honest when using the
     * software. I ask you the same, if your intention is to
     * use ImageKit. Writing this plugin took a lot of time
     * and it hopefully saves you a lot of headaches. If you
     * would use a cloud-provider instead of rolling your own
     * thumb engine, then your would also have to pay them.
     *
     * Anyway, have a nice day!
     *
     * Fabian
     */  
    if (str::startsWith($key, 'IMGKT1') && str::length($key) === 39) {
      $type = 'ImageKit 1';
    } else {
      $key = null;
    }
    
    return new Obj(array(
      'key'   => $key,
      'type'  => $type,
    ));
  }
  
}
