<?php 

namespace Kirby\Plugins\ImageKit\Widget;

use Tpl;

$translations = Translations::load();

return [
  
  'title' => [
    'text' => $translations->get('imagekit.widget.title'),
  ],
  
  'options' => [
    [
      'text' => $translations->get('imagekit.widget.action.clear'),
      'icon' => 'trash-o',
      'link' => '#imagekit-action-clear',
    ],
    [
      'text' => $translations->get('imagekit.widget.action.create'),
      'icon' => 'play-circle-o',
      'link' => '#imagekit-action-create',
    ],
  ],
  
  'html' => function() use ($translations) {
    return tpl::load(__DIR__ . DS . 'imagekit.html.php', compact('translations'));
  }  
  
];
