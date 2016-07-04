<style><?= f::read(__DIR__ . '/assets/css/widget.min.css') ?></style>

<div class="dashboard-box">
  <div class="js-imagekit-info / text">
    <table class="imagekit-stats">
      <tr>
        <th><?= $l['imagekit.widget.status.pending'] ?></th>
        <td class="js-imagekit-pending">…</td>
      </tr>
      <tr>
        <th><?= $l['imagekit.widget.status.created'] ?></th>
        <td class="js-imagekit-created">…</td>
      </tr>
    </table>
  </div>
</div>

<progress class="imagekit-progress is-hidden / js-imagekit-progress"></progress>

<?php if ($license->type === 'trial' || $license->type === 'beta'): ?>
  <p class="debug-warning marginalia" style="position: relative; padding-left: 30px; font-size: 14px; padding-top: 12px;">
    <span class="fa fa-exclamation-triangle" style="position: absolute; top: 15px; left: 5px; font-size: 14px;"></span>
    <?php
    if($license->type === 'beta'):
      echo $l['imagekit.widget.license.beta'];
    else:
      echo $l['imagekit.widget.license.trial'];
    endif;
    ?>
  </p>
<?php endif; ?>

<script>
<?php
echo 'window.ImageKitSettings = ' . json_encode([
  'api'         => kirby()->urls()->index() . '/plugins/imagekit/widget/api/',
  'translations' => $l
]) . ';';

echo f::read(__DIR__ . '/assets/js/dist/widget.min.js');
?>
</script>
