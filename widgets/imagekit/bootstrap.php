<?php

namespace Kirby\Plugins\ImageKit;

use Obj;
use Response;
use Tpl;


// This helper function looks pretty ugly … but this is a temporary solution
// until the Kirby panel fully supports localization.
function kirby_imagekit_widget_get_translations( $code = null ) {
  $language_directory = __DIR__ . DS . 'translations';
  
  if ($code !== 'en') {
    if (!preg_match('/^[a-z]{2}([_-][a-z0-9]{2,})?$/i', $code) ||
        !file_exists($language_directory . DS . $code . '.php')) {
      // Set to fallback language, if not a valid code or no translation available.
      $code = 'en';
    }
  }
  
  return require($language_directory . DS . $code . '.php');
}

function kirby_imagekit_license() {
  $key  = kirby()->option('imagekit.license');
  $type = 'trial';
  
  if (kirby()->option('imagekit.license') === 'BETA') {
    $type = 'beta';
  }
  
  return new Obj(array(
    'key'   => $key,
    'type'  => $type,
  ));
}

$kirby->set('widget', 'imagekit', __DIR__ );

$kirby->set('route', [
  'pattern' => 'plugins/imagekit/widget/api/(:any)',
  'action'  => function($action) {
    
    $user = site()->user()->current();
    if (!$user || !$user->isAdmin()) {
      return Response::error('Only administrators can access the widget API of the ImageKit plugin.', 401);
    }
    
    $l = kirby_imagekit_widget_get_translations($user->language());
    
    switch ($action) {

      case 'status':
        return Response::success(true, lazythumb::status());
        
      case 'clear':
        return Response::success(lazythumb::clear(), lazythumb::status());
        
      case 'create':
        $pending = lazythumb::pending();
        $step    = kirby()->option('imagekit.widget.step');
        
        for($i = 0; $i < sizeof($pending) && $i < $step; $i++) {
          lazythumb::process($pending[$i]);
        }
        return Response::success(true, lazythumb::status());
        
      default:
        return Response::error('Invalid plugin action. The action "' . html($action) . '" is not defined.');
    }
  }
]);
