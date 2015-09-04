<?php if (!defined('APPLICATION')) exit();
$list = $this->data('list');
?>
<div class="DataTableContainer Group-Box <?php echo val('cssClass', $list); ?>">
  <div class="PageControls">
    <?php
    if (val('title', $list)) { ?><h2 class="Groups H"><?php echo val('title', $list); ?></h2><?php }
    if (val('buttons', $list)) {
    echo '<div class="BoxButtons">';
    foreach (val('buttons', $list) as $button) { ?>
      <div class="Button-Controls <?php echo val('buttonsCssClass', $list); ?>">
        <a class="Button Primary <?php echo val('cssClass', $button); ?>" href="<?php echo val('url', $button) ?>" role="button"><?php echo val('text', $button); ?></a>
      </div>
    <?php } ?>
  </div>
  <?php } ?>
</div>
<?php
if (val('items', $list)) {
  ?>
  <div class="DataTableWrap GroupWrap">
    <table class="DataTable">
      <thead>
      <tr>
        <?php foreach(val('columns', $list) as $column) { ?>
          <td class="<?php echo val('columnCssClass', $column); ?>"><div class="Wrap"><?php echo val('columnLabel', $column); ?></div></td>
        <?php } ?>
      </tr>
      </thead>
      <?php foreach(val('items', $list) as $item) { ?>
        <tr id="<?php echo val('id', $item); ?>" class="Item <?php echo val('cssClass', $item); ?>">
          <?php foreach (val('rows', $item) as $row) { ?>
            <?php if (val('type', $row) == 'main') { ?>
              <td class="Name <?php echo val('cssClass', $row); ?>">
                <div class="Wrap">
                    <span class="Options">
                      <?php if (val('options', $item)) { writeGroupOptions(val('options', $item)); } ?>
                      <?php if (val('buttons', $item)) { ?>
                        <div class="Buttons <?php echo val('buttonsCssClass', $item); ?>">
                          <?php foreach (val('buttons', $item) as $button) { ?>
                            <a class="Button <?php echo val('cssClass', $button); ?>" href="<?php echo val('url', $button) ?>" role="button"><?php echo val('text', $button); ?></a>
                          <?php } ?>
                        </div>
                      <?php } ?>
                      <?php if ($dropdown = val('buttonDropdown', $item)) { ?>
                        <div class="ButtonGroup <?php echo val('cssClass', $dropdown); ?>">
                          <ul class="Dropdown MenuItems">
                            <?php foreach (val('options', $dropdown) as $option) { ?>
                              <li><a href="<?php echo val('url', $option) ?>" class="<?php echo val('cssClass', $option) ?>" data-name="<?php echo val('dataName', $option) ?>"><?php echo val('text', $option) ?></a></li>
                            <?php } ?>
                          </ul>
                          <?php $trigger = val('trigger', $dropdown); ?>
                          <a class="NavButton Handle Button <?php echo val('cssClass', $trigger); ?>" role="button"><?php echo val('text', $trigger); ?> <span class="Sprite SpDropdownHandle"></span></a>
                        </div>
                      <?php } ?>
                    </span>
                  <?php if (val('imageSource', $item)) { ?>
                    <?php if (val('url', $item)) { ?>
                      <a href="<?php echo val('url', $item); ?>" class="Item-Icon PhotoWrap">
                    <?php } ?>
                    <img class="ProfilePhoto ProfilePhotoMedium <?php echo val('imageCssClass', $item); ?>" src="<?php echo val('imageSource', $item); ?>" alt="<?php echo val('imageAlt', $item); ?>">
                    <?php if (val('url', $item)) { ?>
                      </a>
                    <?php } ?>
                  <?php } ?>
                  <?php if (val('dateTile', $item)) { ?>
                    <span class="DateTile">
                       <span class="Month"><?php echo val('monthTile', $item); ?></span>
                       <span class="Day"><?php echo val('dayTile', $item); ?></span>
                    </span>
                  <?php } ?>
                  <?php if (val('heading', $item)) { ?>
                    <h3 class="Title-Wrapper">
                      <?php if (val('url', $item)) { ?>
                      <a class="Title <?php echo val('headingCssClass', $item); ?>" href="<?php echo val('url', $item); ?>">
                        <?php } ?>
                        <?php echo val('heading', $item); ?>
                        <?php if (val('url', $item)) { ?>
                      </a>
                    <?php } ?>
                    </h3>
                  <?php } ?>
                  <div class="Description Excerpt <?php echo val('textCssClass', $item); ?>"><?php echo val('text', $item); ?></div>
                  <div class="Meta <?php echo val('metaCssClass', $item); ?>">
<!--                    --><?php
//                    if (val('meta', $item)) {
//                      foreach (val('meta', $item) as $metaItem) { ?>
<!--                        <span class="MItem --><?php //echo val('cssClass', $metaItem); ?><!--">-->
<!--                          --><?php //echo val('text', $metaItem);
//                          if (val('linkText', $metaItem)) { ?>
<!--                            <a href="--><?php //echo val('url', $metaItem); ?><!--">--><?php //echo val('linkText', $metaItem); ?><!--</a>-->
<!--                          --><?php //} ?>
<!--                        </span>-->
<!--                      --><?php //}
//                    } ?>
                    </ul>
                  </div>
              </td>
            <?php } ?>
            <?php if (val('type', $row) == 'count') { ?>
              <td class="BigCount <?php echo val('cssClass', $row); ?>">
                <div class="Wrap">
                  <span class="Number"><?php echo val('number', $row); ?></span>
                </div>
              </td>
            <?php } ?>
            <?php if (val('type', $row) == 'default') { ?>
              <td class="BlockColumn <?php echo val('cssClass', $row); ?>">
                <div class="Wrap">
                  <span class="Text"><?php echo val('text', $row); ?></span>
                </div>
              </td>
            <?php } ?>
            <?php if (val('type', $row) == 'user') { ?>
              <td class="BlockColumn BlockColumn-User <?php echo val('userFirstOrLast', $row); ?>User">
                <div class="Block Wrap">
                  <?php if (val('userImageUrl', $row)) { ?>
                    <a class="PhotoWrap PhotoWrapSmall" href="<?php echo val('userUrl', $row); ?>">
                      <img class="ProfilePhoto ProfilePhotoSmall" src="<?php echo val('userImageUrl', $row); ?>">
                    </a>
                  <?php } ?>
                  <a class="UserLink BlockTitle" href="<?php echo val('userUrl', $row); ?>"><?php echo val('userName', $row); ?></a>
                  <div class="Meta">
                    <a class="CommentDate MItem" href="<?php echo val('userPostUrl', $row); ?>"><?php echo val('userPostTime', $row); ?></a>
                  </div>
                </div>
              </td>
            <?php } ?>
            <?php if (val('type', $row) == 'buttons') { ?>
              <td class="BlockColumn BlockColumn-Buttons User">
                <div class="Buttons <?php echo val('cssClass', $row); ?>">
                  <?php foreach (val('buttons', $row) as $button) { ?>
                    <a class="Button <?php echo val('cssClass', $button); ?>" href="<?php echo val('url', $button) ?>" role="button"><?php echo val('text', $button); ?></a>
                  <?php } ?>
                </div>
              </td>
            <?php } ?>
            <?php if (val('type', $row) == 'lastPost') { ?>
              <td class="BlockColumn LatestPost">
                <?php if (val('title', $row)) { ?>
                  <div class="Block Wrap">
                    <?php if (val('imageSource', $row)) { ?>
                      <a class="PhotoWrap PhotoWrapSmall" href="<?php echo val('imageUrl', $row); ?>">
                        <img class="ProfilePhoto ProfilePhotoSmall" src="<?php echo val('imageSource', $row); ?>">
                      </a>
                    <?php } ?>
                    <a class="BlockTitle LatestPostTitle" href="<?php echo val('url', $row); ?>"><?php echo val('title', $row); ?></a>
                    <div class="Meta">
                      <a class="UserLink MItem" href="<?php echo val('userUrl', $row); ?>"><?php echo val('username', $row); ?></a>
                      <span class="Bullet">•</span>
                      <a class="CommentDate MItem" href="<?php echo val('url', $row); ?>"><?php echo val('date', $row); ?></a>

                    </div>
                  </div>
                <?php } ?>
              </td>
            <?php } ?>
          <?php } ?>
        </tr>
      <?php } ?>
    </table>
  </div>
<?php }
if (val('emptyMessage', $list) && !val('items', $list)) { ?>
  <div class="EmptyMessage <?php echo val('emptyMessageCssClass', $list); ?>"><?php echo val('emptyMessage', $list); ?></div>
<?php } ?>
<?php if (val('moreLink', $list)) { ?>
  <div class="MoreWrap">
    <a class="more <?php echo val('moreCssClass', $list); ?>" href="<?php echo val('moreUrl', $list); ?>"><?php echo val('moreLink', $list); ?></a>
  </div>
<?php } ?>
</div>
