<?php if (!defined('APPLICATION')) { exit(); } ?>

<?php if (!count($this->data('SearchResults')) && $this->data('SearchTerm')) {
    echo '<p class="NoResults">', sprintf(t('No results for %s.', 'No results for <b>%s</b>.'), htmlspecialchars($this->data('SearchTerm'))), '</p>';
} ?>
<ol id="search-results" class="DataList DataList-Search" start="<?php echo $this->data('From'); ?>">
    <?php foreach ($this->data('SearchResults') as $Row): ?>
    <li class="Item Item-Search">
        <h3><?php echo anchor($Row['Title'], $Row['Url']); ?></h3>
        <div class="Item-Body Media">
            <?php
            $Photo = userPhoto($Row, array('LinkClass' => 'Img'));
            if ($Photo) {
                echo $Photo;
            }

            $i = 0;
            foreach ($Row['Media'] as $Media) {
                $src = $Media['src'];

                if (isUrl($src)) {
                    echo '<div class="ImgExt">'.
                        $Media['preview'].
                        '</div>';
                    $i++;

                    if ($i >= 1 || $Media['type'] == 'video') {
                        break;
                    }
                }
            }
            ?>
            <div class="Media-Body">
                <div class="Meta">
                    <?php
                    echo ' <span class="MItem-Author">'.
                        sprintf(t('%s by %s'), t($Row['Type']), userAnchor($Row)).
                        '</span>';

                    echo bullet(' ');
                    echo ' <span clsss="MItem-DateInserted">'.
                        Gdn_Format::date($Row['DateInserted'], 'html').
                        '</span> ';

                    if (isset($Row['Breadcrumbs'])) {
                        echo bullet(' ');
                        echo ' <span class="MItem-Location">'.Gdn_Theme::breadcrumbs($Row['Breadcrumbs'], FALSE).'</span> ';
                    }

                    if (isset($Row['Notes'])) {
                        echo ' <span class="Aside Debug">debug('.$Row['Notes'].')</span>';
                    }
                    ?>
                </div>
                <div class="Summary">
                    <?php echo $Row['Summary']; ?>
                </div>
                <?php
                $Count = getValue('Count', $Row);

                if (($Count) > 1) {
                    $url = $this->data('SearchUrl').'&discussionid='.urlencode($Row['DiscussionID']).'#search-results';
                    echo '<div>'.anchor(plural($Count, '%s result', '%s results'), $url).'</div>';
                }
                ?>
            </div>
        </div>
    </li>
<?php endforeach; ?>
</ol>
<?php
echo '<div class="PageControls Bottom">';

$RecordCount = $this->data('RecordCount');
if ($RecordCount >= 1000) {
    echo '<span class="Gloss">'.plural($RecordCount, '>%s result', '>%s results').'</span>';
} elseif ($RecordCount) {
    echo '<span class="Gloss">'.plural($RecordCount, '%s result', '%s results').'</span>';
}

PagerModule::write(array('Wrapper' => '<div %1$s>%2$s</div>'));

echo '</div>';
