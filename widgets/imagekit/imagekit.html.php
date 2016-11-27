<style><?= f::read(__DIR__ . '/assets/css/widget.min.css') ?></style>

<div class="dashboard-box">
  <div class="js-imagekit-info / text">
    <table class="imagekit-stats">
      <tr>
        <th><?= $translations->get('imagekit.widget.status.pending') ?></th>
        <td class="js-imagekit-pending">…</td>
      </tr>
      <tr>
        <th><?= $translations->get('imagekit.widget.status.created') ?></th>
        <td class="js-imagekit-created">…</td>
      </tr>
    </table>
  </div>
</div>

<progress class="imagekit-progress is-hidden / js-imagekit-progress"></progress>
<p class="marginalia imagekit-progress-text / js-imagekit-progress-text">…</span>
  
</p>

<?php if (imagekit()->license()->type === 'trial'): ?>
  <p class="debug-warning marginalia" style="position: relative; padding-left: 30px; font-size: 14px; padding-top: 12px;">
    <span class="fa fa-exclamation-triangle" style="position: absolute; top: 15px; left: 5px; font-size: 14px;"></span>
    <?php printf($translations->get('imagekit.widget.license.trial'), 'http://sites.fastspring.com/fabianmichael/product/imagekit') ?>
  </p>
<?php endif ?>

<script>
<?php
echo 'window.ImageKitSettings = ' . json_encode([
  'api'          => kirby()->urls()->index() . '/plugins/imagekit/widget/api/',
  'translations' => array_merge(
    $translations->get(), [
      'cancel' => i18n('cancel'),
      'ok'     => i18n('ok'),
    ]),
  'discover'     => kirby()->option('imagekit.widget.discover'),
]) . ';';

if(kirby()->option('imagekit.debug')) {
  echo f::read(__DIR__ . '/assets/js/src/widget.js');
} else {
  echo f::read(__DIR__ . '/assets/js/dist/widget.min.js');
}
?>
</script>
