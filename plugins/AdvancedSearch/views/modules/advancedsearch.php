<?php if (!defined('APPLICATION')) { exit(); } ?>
<div class="AdvancedSearch <?php echo getIncomingValue('adv') ? 'Open' : ''; ?>">
    <?php
    $Form = new Gdn_Form();
    $Form = $this->Form;
    echo $Form->open(['action' => url('/search')]),
        $Form->hidden('adv');

//    decho(Gdn::Controller()->Data('CalculatedSearch'), 'calc search');

    ?>
    <div class="KeywordsWrap InputAndButton">
        <?php
        echo $Form->textBox('search', ['class' => 'InputBox BigInput', 'placeholder' => t('SearchBoxPlaceHolder', 'Search'), 'autocomplete' => 'off', 'aria-label' => t('Enter your search term.')]).
            ' <a href="#" class="Handle" title="'.t('Options').'"><span class="Arrow"></span></a> '.
            '<span class="bwrap"><button type="submit" class="Button" title="'.t('Search').'">'.sprite('SpSearch').'</button></span>';
        ?>
        <!--<div class="Gloss"><a href="#"><?php echo t('Search help'); ?></a></div>-->
    </div>
    <div class="AdvancedWrap">
        <?php if ($Discussion = Gdn::controller()->data('Discussion')): ?>
            <div class="P">
                <?php
                echo
                    $Form->label('Discussion', 'discussionid', ['class' => 'Heading']).
                    $Form->checkBox('discussionid', Gdn_Format::text(getValue('Name', $Discussion)), ['nohidden' => true, 'value' => getValue('DiscussionID', $Discussion)]);
                ?>
            </div>
        <?php endif; ?>
        <div class="P TitleRow<?php if ($Discussion) {
     echo ' Hidden';
} ?>">
            <?php
            echo $Form->label('Title', 'title', ['class' => 'Heading']).
                $Form->textBox('title', ['class' => 'InputBox BigInput']);
            ?>
        </div>
        <div class="P">
            <?php
            echo $Form->label('Author', 'author', ['class' => 'Heading']).
                $Form->textBox('author', ['class' => 'InputBox BigInput']);
            ?>
        </div>
        <div class="P">
            <?php
            echo $Form->label('Category', 'cat', ['class' => 'Heading']).
                $Form->categoryDropDown('cat', ['Permission' => 'view', 'Headings' => false, 'IncludeNull' => ['all', t('(All)')], 'class' => 'BigInput']);
            ?>
            <div class="Checkboxes Inline">
                <?php
                echo $Form->checkBox('subcats', t('search subcategories'), ['nohidden' => true]).
                    ' '.
                    $Form->checkBox('archived', t('search archived'), ['nohidden' => true])
                ?>
            </div>
        </div>
        <?php if ($this->IncludeTags): ?>
        <div class="P">
            <?php
            echo $Form->label('Tags', 'tags', ['class' => 'Heading']).
                $Form->textBox('tags', ['class' => 'InputBox BigInput', 'data-tags' => json_encode($this->data('Tags', ''))]);
            ?>
        </div>
        <?php endif; ?>
        <div class="P">
            <?php
            echo $Form->label('What to search', '', ['class' => 'Heading']);
            echo '<div class="Inline">';
            foreach ($this->Types as $name => $label) {
                 echo ' '.$Form->checkBox($name, $label, ['nohidden' => true]).' ';
            }
            echo '</div>';
            ?>
        </div>
        <div class="P Inline">
            <?php
            echo $Form->label('Date within', 'within').' '.
                $Form->dropDown('within', $this->DateWithinOptions).' '.
                $Form->label('of', 'date').' '.
                $Form->textBox('date', ['class' => 'InputBox DateBox']).
                ' <span class="Gloss">'.t('Date Examples', 'Examples: Monday, today, last week, Mar 26, 3/26/04').'</span>';
            ?>
        </div>
        <div class="P Buttons">
            <?php
            echo '<button type="submit" class="Button" title="'.t('Search').'" aria-label="'.t('Search').'">'.t('Search').'</button>';
            ?>
        </div>
    </div>
    <?php
    echo $Form->close();
    ?>
</div>
