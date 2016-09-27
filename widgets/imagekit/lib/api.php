<?php

namespace Kirby\Plugins\ImageKit\Widget;

use Response;

use Kirby\Plugins\ImageKit\LazyThumb;
use Kirby\Plugins\ImageKit\ComplainingThumb;

class API {
  
  public $kirby;
  
  public static function instance() {
    static $instance;
    return $instance ?: $instance = new static();
  }
  
  protected function __construct() {
    $self  = $this;
    
    $this->kirby = kirby();
    
    $this->kirby->set('route', [
      'pattern' => 'plugins/imagekit/widget/api/(:any)',
      'action'  => function($action) use ($self) {
        
        if($error = $this->authorize()) {
          return $error;
        }
        
        if(method_exists($self, $action)) {
          return $this->$action();
        } else {
          return Response::error('Invalid plugin action. The action "' . html($action) . '" is not defined.');
        }
      },
    ]);
    
    if(isset($_SERVER['HTTP_X_IMAGEKIT_INDEXING'])) {
      $this->indexRequest();
    }
  }
  
  protected function authorize() {
    $user = kirby()->site()->user();
    if (!$user || !$user->hasPanelAccess()) { // !$user->hasPermission('panel.access')
      return Response::error('Only logged-in users can use the ImageKit widget. Please reload this page to get show the login form.', 401);
    }
  }
  
  protected function indexRequest() {
    
    if($error = $this->authorize()) {
      return $error;
    }    
      
    ob_start(function($text) {
      $links = [];
      
      try {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($text);
        libxml_clear_errors();
        
        $elements = array_merge(
          iterator_to_array($doc->getElementsByTagName('a')),
          iterator_to_array($doc->getElementsByTagName('link'))
        );
        
        foreach ($elements as $elm) {
          $rel = $elm->getAttribute("rel");
          if($rel === 'next' || $rel === 'prev') {
            $links[] = $elm->getAttribute('href');
          }
        }
      } catch(Exception $e) {
        // fail silently is the dom parser throws an error.
      }
      
      return response::success(true, [
        'links'  => array_unique($links),
        'status' => lazythumb::status(),
      ]);
      
    });
  }
  
  public function status() {
    return Response::success(true, lazythumb::status());
  }
  
  public function clear() {
    return Response::success(lazythumb::clear(), lazythumb::status());
  }
  
  public function create() {
    
    $pending = lazythumb::pending();
    $step    = kirby()->option('imagekit.widget.step');
    
    // Always complain when trying to create thumbs from the widget
    complainingthumb::enableSendError();
    complainingthumb::setErrorFormat('json');
    
    for($i = 0; $i < sizeof($pending) && $i < $step; $i++) {
      lazythumb::process($pending[$i]);
    }
    
    return Response::success(true, lazythumb::status());
  }
  
  public function index() {
    
    $index = [];
    
    $this->kirby->cache()->flush();
    
    $site        = site();
    $isMultilang = $site->multilang() && $site->languages()->count() > 1;
    
    if($isMultilang) {
      $languageCodes = [];
      foreach($site->languages() as $language) {
        $languageCodes[] = $language->code();
      }
    }
    
    foreach($site->index() as $page) {
      if($isMultilang) {
        foreach($languageCodes as $code) {
          $index[] = $page->url($code);
        }
      } else {
        $index[] = $page->url();
      }
    }
    
    return Response::success(true, $index);
  }
}
