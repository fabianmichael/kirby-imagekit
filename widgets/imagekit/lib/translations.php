<?php

namespace Kirby\Plugins\ImageKit\Widget;

/**
 * A very simple translations class, which can load an
 * associative PHP array of language strings.
 */
class Translations {
  
  protected static $cache;
  
  protected $translations = [];
  
  public function __construct($code = 'en') {
    $language_directory = dirname(__DIR__) . DS . 'translations';
  
    if ($code !== 'en') {
      if (!preg_match('/^[a-z]{2}([_-][a-z0-9]{2,})?$/i', $code) ||
          !file_exists($language_directory . DS . $code . '.php')) {
        // Set to fallback language, if not a valid code or no translation available.
        $code = 'en';
      }
    }
    
    $this->translations = require($language_directory . DS . $code . '.php');
  }
  
  public function get($key = null) {
    if(is_null($key)) {
      return $this->translations;
    } else if (isset($this->translations[$key])) {
      return $this->translations[$key];
    } else {
      return '[missing translation: ' . $key . ']';
    }
  }
  
  public static function load($code = null) {
    
    if(is_null($code)) {
      $code = panel()->translation()->code();
    }
    
    if (!isset(static::$cache[$code])) {
      static::$cache[$code] = new static($code);
    }
    
    return static::$cache[$code];
  }
  
}
