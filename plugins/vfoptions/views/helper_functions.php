<?php

function writeAddonMediaItem($addonName, $info, $isEnabled) {
  $slug = Gdn_Format::url(strtolower($addonName));
  $name = $info['DisplayName'] ?? $info['displayName'] ?? $info['Name'] ?? $info['name'] ?? $addonName;
  $description = Gdn_Format::html(val('Description', $info, val('description', $info)));
  $icon = val('IconUrl', $info);

  $media = new MediaItemModule($name, '', $description, 'li', ['id' => $slug.'-addon']);
  $media->setImage($icon);
  $media->setView('media-addon');

  // Settings button

  $settingsUrl = val('SettingsUrl', $info, val('settingsUrl', $info));
  $settingsPopupClass = val('UsePopupSettings', $info, true) ? ' js-modal' : '';

  if ($isEnabled && $settingsUrl != '') {
    $attr['class'] = 'btn btn-icon-border'.$settingsPopupClass;
    $attr['aria-label'] = sprintf(t('Settings for %s'), $name);
    $attr['data-reload-page-on-save'] = false;
    $media->addButton(dashboardSymbol('settings'), url($settingsUrl), $attr);
  }

  // Toggle

  if ($isEnabled) {
    $label = sprintf(t('Disable %s'), $name);
  } else {
    $label = sprintf(t('Enable %s'), $name);
  }
  $media->setToggle(slugify($addonName), $isEnabled, val('ToggleUrl', $info, ''), $label);
  echo $media;
}
