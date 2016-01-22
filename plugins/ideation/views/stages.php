<?php if (!defined('APPLICATION')) exit; ?>
<style>
    .NameColumn {
        width: 175px;
    }

    tbody .NameColumn {
        font-size: 15px;
        font-weight: bold;
        vertical-align: middle;
    }

    tbody .NameColumn img {
        vertical-align: middle;
    }
</style>
<h1><?php echo $this->data('Title'); ?></h1>
<!--<div class="Info PageInfo">-->
<!--    <p><b>Heads up!</b> Here are the ranks that users can achieve on your site.-->
<!--        You can customize these ranks and even add new ones.-->
<!--        Here are some tips.-->
<!--    </p>-->
<!--    <ol>-->
<!--        <li>-->
<!--            You don't want to have too many ranks. We recommend starting with five. You can add more if your community is really large.-->
<!--        </li>-->
<!--        <li>-->
<!--            It's a good idea to have special ranks for moderators and administrators so that your community can easily see who's in charge.-->
<!--        </li>-->
<!--        <li>-->
<!--            Be creative! Try naming your ranks after things that the community talks about.-->
<!--        </li>-->
<!--    </ol>-->
<!--</div>-->
<table id="stages" class="AltColumns">
    <thead>
    <tr>
        <th class="NameColumn"><?php echo t('Stage'); ?></th>
        <th class="DescriptionColumn"><?php echo t('Description'); ?></th>
        <th class="IsOpenColumn"><?php echo t('Status'); ?></th>
        <th class="OptionsColumn"><?php echo t('Options'); ?></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($this->data('Stages') as $row): ?>
        <tr id="Stage_<?php echo $row['StageID']; ?>">
            <td class="NameColumn"><div class="CellWrap">
                    <?php
                    echo $row['Name'];
                    ?></div>
            </td>
            <td>
                <?php
                echo $row['Description'];
                ?>
            </td>
            <td>
                <?php
                echo $row['Status'];
                ?>
            </td>
            <td>
                <?php
                echo anchor(t('Edit'), '/settings/editstage/'.$row['StageID'], 'SmallButton Popup');
                echo anchor(t('Delete'), '/settings/deletestage?stageid='.$row['StageID'], 'SmallButton Popup');
                ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div class="Wrap">
    <?php
    echo Anchor(sprintf(T('Add %s'), T('Stage')), '/settings/addstage', 'SmallButton');
    ?>
</div>
