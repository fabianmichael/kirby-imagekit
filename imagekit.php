<?php 

namespace Kirby\Plugins\ImageKit;


// A constant used internally by the plugin to resove plugin paths
define('IMAGEKIT_BASE_DIRECTORY', __DIR__);
define('IMAGEKIT_VERSION', '1.0.0-beta1');

load([
  'kirby\\plugins\\imagekit\\imagekit'         => 'lib' . DS . 'imagekit.php',
  'kirby\\plugins\\imagekit\\component\\thumb' => 'lib' . DS . 'component' . DS . 'thumb.php',
  'kirby\\plugins\\imagekit\\lazythumb'        => 'lib' . DS . 'lazythumb.php',
  'kirby\\plugins\\imagekit\\proxyasset'       => 'lib' . DS . 'proxyasset.php',
], __DIR__);

$kirby = kirby();

$kirby->set('component', 'thumb', '\\Kirby\\Plugins\\ImageKit\\Component\\Thumb');

if($kirby->option('imagekit.widget')) {
  require_once __DIR__ . DS . 'widgets' . DS . 'imagekit' . DS . 'bootstrap.php';
}
