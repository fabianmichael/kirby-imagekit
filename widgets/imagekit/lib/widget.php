<?php 

namespace Kirby\Plugins\ImageKit\Widget;

class Widget {
  
  public static function instance() {
    static $instance;
    return $instance ?: $instance = new static();
  }
  
  protected function __construct() {
    
    $kirby = kirby();
    
    // Register the Widget
    $kirby->set('widget', 'imagekit', dirname(__DIR__));
  }
  
}
