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
<table id="statuses" class="AltColumns">
    <thead>
    <tr>
        <th class="NameColumn"><?php echo t('Status'); ?></th>
        <th class="IsOpenColumn"><?php echo t('State'); ?></th>
        <th class="OptionsColumn"><?php echo t('Options'); ?></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($this->data('Statuses') as $row): ?>
        <tr id="Status_<?php echo $row['StatusID']; ?>">
            <td class="NameColumn"><div class="CellWrap">
                    <?php
                    echo $row['Name'];
                    echo ($row['IsDefault']) ? '<span class="default-tag Tag Meta">'.t('Default').'</span>' : '';
                    ?></div>
            </td>
            <td>
                <?php
                echo $row['State'];
                ?>
            </td>
            <td>
                <?php
                echo anchor(t('Edit'), '/settings/editstatus/'.$row['StatusID'], 'SmallButton Popup');
                echo anchor(t('Delete'), '/settings/deletestatus?statusid='.$row['StatusID'], 'SmallButton Popup');
                ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div class="Wrap">
    <?php
    echo Anchor(sprintf(t('Add %s'), t('Status')), '/settings/addstatus', 'SmallButton');
    ?>
</div>
