<?php if (!defined('APPLICATION')) exit();
$list = $this->data('list');
?>
<div class="media-list-container Group-Box <?php echo val('cssClass', $list); ?>">
  <div class="PageControls">
    <h2 class="H media-list-heading"><?php echo val('title', $list); ?></h2> <?php
    if (val('buttons', $list)) {
      foreach (val('buttons', $list) as $button) { ?>
        <div class="Button-Controls <?php echo val('buttonsCssClass', $list); ?>">
          <a class="Button <?php echo val('cssClass', $button); ?>" href="<?php echo val('url', $button) ?>" role="button"><?php echo val('text', $button); ?></a>
        </div>
      <?php } ?>
    <?php } ?>
  </div>
  <ul class="media-list DataList">
    <?php foreach(val('items', $list, array()) as $item) { ?>
      <li id="<?php echo val('id', $item); ?>" class="Item <?php echo val('cssClass', $item); ?>">
        <?php if (val('imageSource', $item)) { ?>
          <a href="<?php echo val('url', $item); ?>" class="PhotoWrap">
            <img class="ProfilePhoto ProfilePhotoMedium <?php echo val('imageCssClass', $item); ?>" src="<?php echo val('imageSource', $item); ?>">
          </a>
        <?php } ?>
        <?php if (val('dateTile', $item)) { ?>
          <span class="DateTile">
               <span class="Month"><?php echo val('monthTile', $item); ?></span>
               <span class="Day"><?php echo val('dayTile', $item); ?></span>
             </span>
        <?php } ?>
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
        <div class="ItemContent">
          <div class="Title">
            <a href="<?php echo val('url', $item); ?>">
              <?php echo val('heading', $item); ?>
            </a>
          </div>
          <div class="Excerpt <?php echo val('textCssClass', $item); ?>"><?php echo val('text', $item); ?></div>
          <div class="Meta">
            <?php foreach(val('meta', $item) as $metaItem) { ?>
              <span role="presentation" class="MItem <?php echo val('cssClass', $metaItem); ?>">
            <?php echo val('text', $metaItem);
            if (val('linkText', $metaItem)) { ?>
              <a href="<?php echo val('url', $metaItem); ?>"><?php echo val('linkText', $metaItem); ?></a>
            <?php } ?>
          </span>
            <?php } ?>
          </div>
        </div>
      </li>
    <?php } ?>
  </ul>
  <?php if (val('emptyMessage', $list) && !val('items', $list)) { ?>
    <div class="EmptyMessage <?php echo val('emptyMessageCssClass', $list); ?>"><?php echo val('emptyMessage', $list); ?></div>
  <?php } ?>
  <?php if (val('moreLink', $list)) { ?>
    <div class="MoreWrap">
      <a class="more <?php echo val('moreCssClass', $list); ?>" href="<?php echo val('moreUrl', $list); ?>"><?php echo val('moreLink', $list); ?></a>
    </div>
  <?php } ?>
</div>

