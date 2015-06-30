<?php if (!defined('APPLICATION')) exit();
$meta = $this->data('meta');

echo '<div class="Meta'.val('cssClass', $meta).'">';
foreach(val('metaItems', $meta) as $metaItem) {

  echo '<span class="MItem '.val('cssClass', $metaItem).'">';
  if ($url = val('url', $metaItem)) {
    echo '<a class="MItemLink" href="'.$url.'">';
  }
  echo '<span class="label">'.val('text', $metaItem).'</span>';
  if (val('value', $metaItem)) {
    echo '<span class="value">'.val('value', $metaItem).'</span>';
  }
  if (val('url', $metaItem)) {
    echo '</a>';
  }
  echo '</span>';
}
echo '</div>';

