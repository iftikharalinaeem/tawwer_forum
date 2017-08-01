<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class CustomProfileFieldsPlugin extends Gdn_Plugin {

	/**
	 * Render the custom fields on the admin edit user form.
	 */
	public function userController_afterFormInputs_handler($sender) {
		echo '<ul>';
		$this->_FormFields($sender);
		echo '</ul>';
	}

	/**
	 * Render the custom fields on the profile edit user form.
	 */
	public function profileController_editMyAccountAfter_handler($sender) {
		$this->_FormFields($sender);
	}

	/**
	 * Render the custom fields.
	 */
	private function _FormFields($sender) {
		// Retrieve user's existing profile fields
		$suggestedFields = c('Plugins.CustomProfileFields.SuggestedFields', '');
		$suggestedFields = explode(',', $suggestedFields);
		$isPostBack = $sender->Form->isPostBack();
		$profileFields = [];
		if (is_object($sender->User))
			$profileFields = Gdn::userModel()->getAttribute($sender->User->UserID, 'CustomProfileFields', []);

		// Write out the suggested fields first
		if (count($suggestedFields) > 0)
			echo wrap(wrap(t('More Information'), 'label'), 'li');

		$countFields = 0;
		foreach ($suggestedFields as $field) {
			$countFields++;
			$value = $isPostBack ? $sender->Form->getValue($field, '') : getValue($field, $profileFields, '');

         $customFieldOptions = ['value' => $value];
         $sender->EventArguments['CustomField'] = $field;
         $sender->EventArguments['CustomFieldValue'] = &$value;
         $sender->EventArguments['CustomFieldOptions'] = &$customFieldOptions;
         $sender->fireAs('CustomProfileFieldsPlugin')->fireEvent('BeforeCustomField');

			echo '<li>';
				echo $sender->Form->hidden('CustomProfileFieldLabel[]', ['value' => $field]);
				echo $sender->Form->label($field, 'CustomProfileFieldValue[]');
				echo $sender->Form->textBox('CustomProfileFieldValue[]', $customFieldOptions);
			echo '</li>';
		}
		if (!c('Plugins.CustomProfileFields.Disallow')) {
		?>
<li>
	<label><?php echo t('Custom Information'); ?></label>
	<div><?php echo t('Use these fields to create custom profile information. You can enter things like "Relationship Status", "Skype ID", "Favorite Dinosaur", etc. Be creative!'); ?></div>
	<div class="CustomProfileFieldLabel"><?php echo t('Label'); ?></div>
	<div class="CustomProfileFieldValue"><?php echo t('Value'); ?></div>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$(document).on('blur', "input.CustomProfileFieldLabel", function() {
				var lastLabel = $('input.CustomProfileFieldLabel:last'),
					lastVal = $('input.CustomProfileFieldValue:last');

				if (lastLabel.val() != '' || lastLabel.index() == $(this).index()) {
					$(lastVal).after(lastVal.clone().val(''));
					$(lastVal).after(lastLabel.clone().val(''));
				}
				return;
			});
		});
	</script>
	<style type="text/css">
	div.CustomProfileFieldLabel,
	div.CustomProfileFieldValue {
		display: inline-block;
		font-weight: bold;
		width: 49%;
	}
	input.CustomProfileFieldLabel,
	input.CustomProfileFieldValue {
		width: 47%;
		margin-bottom: 4px;
	}
	input.CustomProfileFieldLabel {
		margin-right: 10px;
	}
	</style>
</li>
<?php
            // Write out user-defined custom fields
            $customProfileFieldLabel = getValue('CustomProfileFieldLabel', $sender->Form->formValues(), []);
            $customProfileFieldValue = getValue('CustomProfileFieldValue', $sender->Form->formValues(), []);
            foreach ($profileFields as $field => $value) {
               if (!in_array($field, $suggestedFields)) {
                  if ($isPostBack) {
                     $field = getValue($countFields, $customProfileFieldLabel, '');
                     $value = getValue($countFields, $customProfileFieldValue, '');
                  }
                  $countFields++;

                  $customFieldOptions = ['value' => $value, 'class' => 'CustomProfileFieldValue'];
                  $sender->EventArguments['CustomField'] = $field;
                  $sender->EventArguments['CustomFieldValue'] = &$value;
                  $sender->EventArguments['CustomFieldOptions'] = &$customFieldOptions;
                  $sender->fireAs('CustomProfileFieldsPlugin')->fireEvent('BeforeCustomField');

                  echo '<li>';
                     echo $sender->Form->textBox('CustomProfileFieldLabel[]', ['value' => $field, 'class' => 'CustomProfileFieldLabel']);
                     echo $sender->Form->textBox('CustomProfileFieldValue[]', $customFieldOptions);
                  echo '</li>';
               }
            }
            // Write out one empty row
            echo '<li>';
               echo $sender->Form->textBox('CustomProfileFieldLabel[]', ['class' => 'CustomProfileFieldLabel']);
               echo $sender->Form->textBox('CustomProfileFieldValue[]', ['class' => 'CustomProfileFieldValue']);
            echo '</li>';
         }
	}

	/**
	 * Save the custom profile fields when saving the user.
	 */
	public function userModel_afterSave_handler($sender) {
      $valueLimit = Gdn::session()->checkPermission('Garden.Moderation.Manage') ? 255 : c('Plugins.CustomProfileFields.ValueLength', 255);
		$userID = getValue('UserID', $sender->EventArguments);
		$formPostValues = getValue('FormPostValues', $sender->EventArguments);
		$customProfileFieldLabels = FALSE;
		$customProfileFieldValues = FALSE;
		$customProfileFields = FALSE;
		if (is_array($formPostValues)) {
			$customProfileFieldLabels = getValue('CustomProfileFieldLabel', $formPostValues);
			$customProfileFieldValues = getValue('CustomProfileFieldValue', $formPostValues);
			if (is_array($customProfileFieldLabels) && is_array($customProfileFieldValues)) {
				$this->_TrimValues($customProfileFieldLabels, 50);
				$this->_TrimValues($customProfileFieldValues, $valueLimit);
				$customProfileFields = array_combine($customProfileFieldLabels, $customProfileFieldValues);
			}

			// Don't save any empty values or labels
			if (is_array($customProfileFields)) {
				foreach ($customProfileFields as $field => $value) {
					if ($field == '' || $value == '')
						unset($customProfileFields[$field]);
				}
			}
		}

		if ($userID > 0 && is_array($customProfileFields))
			Gdn::userModel()->saveAttribute($userID, 'CustomProfileFields', $customProfileFields);
	}

	/**
	 * Loop through values, trimming them to the specified length.
	 */
	private function _TrimValues(&$array, $length = 200) {
		foreach ($array as $key => $val) {
			$array[$key] = substr($val, 0, $length);
		}
	}

	/**
	 * Render the values on the profile page.
	 */
	public function userInfoModule_onBasicInfo_handler($sender) {
		// Render the custom fields
		try {
         $hideFields = (array)explode(',', c('Plugins.CustomProfileFields.HideFields'));

			$customProfileFields = getValue('CustomProfileFields', $sender->User->Attributes, []);
			foreach ($customProfileFields as $label => $value) {
            if (in_array($label, $hideFields))
               continue;

            $value = Gdn_Format::links(htmlspecialchars($value));

				echo ' <dt class="CustomProfileField CustomProfileField-'.Gdn_Format::url($label).'">'.Gdn_Format::text($label).'</dt> ';
				echo ' <dd class="CustomProfileField CustomProfileField-'.Gdn_Format::url($label).'">'.$value.'</dd> ';
			}
		} catch (Exception $ex) {
			// No errors
		}
	}

	/**
	 * Configuration screen
	 */
	public function pluginController_customProfileFields_create($sender) {
		$conf = new ConfigurationModule($sender);
		$conf->initialize([
			'Plugins.CustomProfileFields.SuggestedFields' => ['Control' => 'TextBox', 'Options' => ['MultiLine' => TRUE]],
			'Plugins.CustomProfileFields.Disallow' => ['Type' => 'bool', 'Control' => 'CheckBox', 'LabelCode' => "Don't allow custom fields."]
		]);

     $sender->addSideMenu('plugin/customprofilefields');
     $sender->setData('Title', t('Custom Profile Field Settings'));
     $sender->ConfigurationModule = $conf;
     $conf->renderAll();
	}

	/**
	 * Add the admin config menu option.
	 */
	public function base_getAppSettingsMenuItems_handler($sender) {
      $menu = &$sender->EventArguments['SideMenu'];
      $menu->addLink('Users', t('Custom Profile Fields'), 'plugin/customprofilefields', 'Garden.User.Edit');
	}

   /**
    * Add suggested fields on install. These are configurable in conf/config.php.
    */
   public function setup() {
		$suggestedFields = c('Plugins.CustomProfileFields.SuggestedFields');
		if (!$suggestedFields)
			saveToConfig(
				'Plugins.CustomProfileFields.SuggestedFields',
				'Facebook,Twitter,Website,Xbox Live,Playstation ID,Wii Friend Code,Steam ID,WoW'
			);
   }
}
