<?php 

namespace Kirby\Plugins\ImageKit;

use Tpl;


$l = kirby_imagekit_widget_get_translations(panel()->translation()->code());

return [
  'title' => [
    'text' => $l['imagekit.widget.title'],
  ],
  
  'options' => [
    [
      'text' => $l['imagekit.widget.action.clear'],
      'icon' => 'trash-o',
      'link' => '#imagekit-action-clear',
      'modal' => true,
    ],
    [
      'text' => $l['imagekit.widget.action.create'],
      'icon' => 'play-circle-o',
      'link' => '#imagekit-action-create',
    ],
  ],
  'html' => function() use ($l) {
    
    $license = kirby_imagekit_license();
    
    return tpl::load(__DIR__ . DS . 'imagekit.html.php', compact('l', 'license'));
  }  
];
