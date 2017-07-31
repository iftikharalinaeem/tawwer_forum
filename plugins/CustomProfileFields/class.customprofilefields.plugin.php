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
	public function UserController_AfterFormInputs_Handler($sender) {
		echo '<ul>';
		$this->_FormFields($sender);
		echo '</ul>';
	}

	/**
	 * Render the custom fields on the profile edit user form.
	 */
	public function ProfileController_EditMyAccountAfter_Handler($sender) {
		$this->_FormFields($sender);
	}

	/**
	 * Render the custom fields.
	 */
	private function _FormFields($sender) {
		// Retrieve user's existing profile fields
		$suggestedFields = C('Plugins.CustomProfileFields.SuggestedFields', '');
		$suggestedFields = explode(',', $suggestedFields);
		$isPostBack = $sender->Form->IsPostBack();
		$profileFields = [];
		if (is_object($sender->User))
			$profileFields = Gdn::UserModel()->GetAttribute($sender->User->UserID, 'CustomProfileFields', []);

		// Write out the suggested fields first
		if (count($suggestedFields) > 0)
			echo Wrap(Wrap(T('More Information'), 'label'), 'li');

		$countFields = 0;
		foreach ($suggestedFields as $field) {
			$countFields++;
			$value = $isPostBack ? $sender->Form->GetValue($field, '') : GetValue($field, $profileFields, '');

         $customFieldOptions = ['value' => $value];
         $sender->EventArguments['CustomField'] = $field;
         $sender->EventArguments['CustomFieldValue'] = &$value;
         $sender->EventArguments['CustomFieldOptions'] = &$customFieldOptions;
         $sender->FireAs('CustomProfileFieldsPlugin')->FireEvent('BeforeCustomField');

			echo '<li>';
				echo $sender->Form->Hidden('CustomProfileFieldLabel[]', ['value' => $field]);
				echo $sender->Form->Label($field, 'CustomProfileFieldValue[]');
				echo $sender->Form->TextBox('CustomProfileFieldValue[]', $customFieldOptions);
			echo '</li>';
		}
		if (!C('Plugins.CustomProfileFields.Disallow')) {
		?>
<li>
	<label><?php echo T('Custom Information'); ?></label>
	<div><?php echo T('Use these fields to create custom profile information. You can enter things like "Relationship Status", "Skype ID", "Favorite Dinosaur", etc. Be creative!'); ?></div>
	<div class="CustomProfileFieldLabel"><?php echo T('Label'); ?></div>
	<div class="CustomProfileFieldValue"><?php echo T('Value'); ?></div>
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
            $customProfileFieldLabel = GetValue('CustomProfileFieldLabel', $sender->Form->FormValues(), []);
            $customProfileFieldValue = GetValue('CustomProfileFieldValue', $sender->Form->FormValues(), []);
            foreach ($profileFields as $field => $value) {
               if (!in_array($field, $suggestedFields)) {
                  if ($isPostBack) {
                     $field = GetValue($countFields, $customProfileFieldLabel, '');
                     $value = GetValue($countFields, $customProfileFieldValue, '');
                  }
                  $countFields++;

                  $customFieldOptions = ['value' => $value, 'class' => 'CustomProfileFieldValue'];
                  $sender->EventArguments['CustomField'] = $field;
                  $sender->EventArguments['CustomFieldValue'] = &$value;
                  $sender->EventArguments['CustomFieldOptions'] = &$customFieldOptions;
                  $sender->FireAs('CustomProfileFieldsPlugin')->FireEvent('BeforeCustomField');

                  echo '<li>';
                     echo $sender->Form->TextBox('CustomProfileFieldLabel[]', ['value' => $field, 'class' => 'CustomProfileFieldLabel']);
                     echo $sender->Form->TextBox('CustomProfileFieldValue[]', $customFieldOptions);
                  echo '</li>';
               }
            }
            // Write out one empty row
            echo '<li>';
               echo $sender->Form->TextBox('CustomProfileFieldLabel[]', ['class' => 'CustomProfileFieldLabel']);
               echo $sender->Form->TextBox('CustomProfileFieldValue[]', ['class' => 'CustomProfileFieldValue']);
            echo '</li>';
         }
	}

	/**
	 * Save the custom profile fields when saving the user.
	 */
	public function UserModel_AfterSave_Handler($sender) {
      $valueLimit = Gdn::Session()->CheckPermission('Garden.Moderation.Manage') ? 255 : C('Plugins.CustomProfileFields.ValueLength', 255);
		$userID = GetValue('UserID', $sender->EventArguments);
		$formPostValues = GetValue('FormPostValues', $sender->EventArguments);
		$customProfileFieldLabels = FALSE;
		$customProfileFieldValues = FALSE;
		$customProfileFields = FALSE;
		if (is_array($formPostValues)) {
			$customProfileFieldLabels = GetValue('CustomProfileFieldLabel', $formPostValues);
			$customProfileFieldValues = GetValue('CustomProfileFieldValue', $formPostValues);
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
			Gdn::UserModel()->SaveAttribute($userID, 'CustomProfileFields', $customProfileFields);
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
	public function UserInfoModule_OnBasicInfo_Handler($sender) {
		// Render the custom fields
		try {
         $hideFields = (array)explode(',', C('Plugins.CustomProfileFields.HideFields'));

			$customProfileFields = GetValue('CustomProfileFields', $sender->User->Attributes, []);
			foreach ($customProfileFields as $label => $value) {
            if (in_array($label, $hideFields))
               continue;

            $value = Gdn_Format::Links(htmlspecialchars($value));

				echo ' <dt class="CustomProfileField CustomProfileField-'.Gdn_Format::Url($label).'">'.Gdn_Format::Text($label).'</dt> ';
				echo ' <dd class="CustomProfileField CustomProfileField-'.Gdn_Format::Url($label).'">'.$value.'</dd> ';
			}
		} catch (Exception $ex) {
			// No errors
		}
	}

	/**
	 * Configuration screen
	 */
	public function PluginController_CustomProfileFields_Create($sender) {
		$conf = new ConfigurationModule($sender);
		$conf->Initialize([
			'Plugins.CustomProfileFields.SuggestedFields' => ['Control' => 'TextBox', 'Options' => ['MultiLine' => TRUE]],
			'Plugins.CustomProfileFields.Disallow' => ['Type' => 'bool', 'Control' => 'CheckBox', 'LabelCode' => "Don't allow custom fields."]
		]);

     $sender->AddSideMenu('plugin/customprofilefields');
     $sender->SetData('Title', T('Custom Profile Field Settings'));
     $sender->ConfigurationModule = $conf;
     $conf->RenderAll();
	}

	/**
	 * Add the admin config menu option.
	 */
	public function Base_GetAppSettingsMenuItems_Handler($sender) {
      $menu = &$sender->EventArguments['SideMenu'];
      $menu->AddLink('Users', T('Custom Profile Fields'), 'plugin/customprofilefields', 'Garden.User.Edit');
	}

   /**
    * Add suggested fields on install. These are configurable in conf/config.php.
    */
   public function Setup() {
		$suggestedFields = C('Plugins.CustomProfileFields.SuggestedFields');
		if (!$suggestedFields)
			SaveToConfig(
				'Plugins.CustomProfileFields.SuggestedFields',
				'Facebook,Twitter,Website,Xbox Live,Playstation ID,Wii Friend Code,Steam ID,WoW'
			);
   }
}
