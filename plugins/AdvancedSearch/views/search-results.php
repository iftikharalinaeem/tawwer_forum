<?php if (!defined('APPLICATION')) { exit(); } ?>

<?php if (!count($this->Data('SearchResults')) && $this->Data('SearchTerm')) {
    echo '<p class="NoResults">', sprintf(T('No results for %s.', 'No results for <b>%s</b>.'), htmlspecialchars($this->Data('SearchTerm'))), '</p>';
} ?>
<ol id="search-results" class="DataList DataList-Search" start="<?php echo $this->Data('From'); ?>">
    <?php foreach ($this->Data('SearchResults') as $Row): ?>
    <li class="Item Item-Search">
        <h3><?php echo Anchor($Row['Title'], $Row['Url']); ?></h3>
        <div class="Item-Body Media">
            <?php
            $Photo = UserPhoto($Row, array('LinkClass' => 'Img'));
            if ($Photo) {
                echo $Photo;
            }

            $i = 0;
            foreach ($Row['Media'] as $Media) {
                $src = $Media['src'];

                if (IsUrl($src)) {
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
                        sprintf(T('%s by %s'), T($Row['Type']), UserAnchor($Row)).
                        '</span>';

                    echo Bullet(' ');
                    echo ' <span clsss="MItem-DateInserted">'.
                        Gdn_Format::Date($Row['DateInserted'], 'html').
                        '</span> ';

                    if (isset($Row['Breadcrumbs'])) {
                        echo Bullet(' ');
                        echo ' <span class="MItem-Location">'.Gdn_Theme::Breadcrumbs($Row['Breadcrumbs'], FALSE).'</span> ';
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
                $Count = GetValue('Count', $Row);

                if (($Count) > 1) {
                    $url = $this->Data('SearchUrl').'&discussionid='.urlencode($Row['DiscussionID']).'#search-results';
                    echo '<div>'.Anchor(Plural($Count, '%s result', '%s results'), $url).'</div>';
                }
                ?>
            </div>
        </div>
    </li>
<?php endforeach; ?>
</ol>
<?php
echo '<div class="PageControls Bottom">';

$RecordCount = $this->Data('RecordCount');
if ($RecordCount >= 1000) {
    echo '<span class="Gloss">'.Plural($RecordCount, '>%s result', '>%s results').'</span>';
} elseif ($RecordCount) {
    echo '<span class="Gloss">'.Plural($RecordCount, '%s result', '%s results').'</span>';
}

PagerModule::Write(array('Wrapper' => '<div %1$s>%2$s</div>'));

echo '</div>';
