<?php 

namespace Kirby\Plugins\ImageKit;

load([
  'kirby\\plugins\\imagekit\\imagekit'         => 'lib' . DS . 'imagekit.php',
  'kirby\\plugins\\imagekit\\component\\thumb' => 'lib' . DS . 'component' . DS . 'thumb.php',
  'kirby\\plugins\\imagekit\\lazythumb'        => 'lib' . DS . 'lazythumb.php',
  'kirby\\plugins\\imagekit\\complainingthumb' => 'lib' . DS . 'complainingthumb.php',
  'kirby\\plugins\\imagekit\\proxyasset'       => 'lib' . DS . 'proxyasset.php',
  'kirby\\plugins\\imagekit\\optimizer'        => 'lib' . DS . 'optimizer.php',
  
  // Only the base optimizer class is autoloaded, all other
  // optimizers are loaded by scanning the directory.
  'kirby\\plugins\\imagekit\\optimizer\\base'  => 'lib' . DS . 'optimizer' . DS . 'base.php',
], __DIR__);

require_once __DIR__ . DS . 'helpers.php';

// Initialize the plugin

$kirby = kirby();

$kirby->set('component', 'thumb', '\\Kirby\\Plugins\\ImageKit\\Component\\Thumb');

if($kirby->option('imagekit.widget')) {
  require_once __DIR__ . DS . 'widgets' . DS . 'imagekit' . DS . 'bootstrap.php';
}
