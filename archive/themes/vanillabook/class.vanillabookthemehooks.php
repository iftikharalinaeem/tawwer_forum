<?php if (!defined('APPLICATION')) exit();

class VanillabookThemeHooks implements Gdn_IPlugin {
	
	/**
	 * Move the guest module over to the BodyMenu asset.
	 */
	public function Base_Render_Before($Sender) {
		Gdn::Locale()->SetTranslation('Activity.Delete', '×');
		Gdn::Locale()->SetTranslation('Draft.Delete', '×');
		Gdn::Locale()->SetTranslation('All Conversations', 'Inbox');
		Gdn::Locale()->SetTranslation('Apply for Membership', 'Sign Up');
		Gdn::Locale()->SetTranslation('Apply', 'Sign Up');
		// Move the howdy stranger module into the BodyMenu asset container
		if (array_key_exists('Panel', $Sender->Assets)) {
			if (array_key_exists('GuestModule', $Sender->Assets['Panel'])) {
				$Sender->Assets['BodyMenu']['GuestModule'] = $Sender->Assets['Panel']['GuestModule'];
				unset($Sender->Assets['Panel']['GuestModule']);
			}
		}
	}
	
	/**
	 * Move some assets around on the profile page.
	 */
	public function ProfileController_Render_Before($Sender) {
		// Move the "About this user" module into the content asset on the profile page
		if (array_key_exists('Panel', $Sender->Assets)) {
			if (array_key_exists('UserInfoModule', $Sender->Assets['Panel'])) {
				$Sender->Assets['Content']['UserInfoModule'] = $Sender->Assets['Panel']['UserInfoModule'];
				unset($Sender->Assets['Panel']['UserInfoModule']);
			}
		}

		// Move the profile tabs into the Panel asset.
		if ($Sender->DeliveryType() == DELIVERY_TYPE_ALL) {
			$ProfileTabs = str_replace(array('<div class="StatusArrow"></div>', 'Tabs ProfileTabs'), array('', 'MainMenu ProfileMenu'), $Sender->FetchView('tabs'));
			$Sender->AddAsset('Panel', $ProfileTabs);
		}
	}
	
	/**
	 * Write ProfileInfo in BodyMenu asset if not on the profile page.
	 */
	public function Base_BeforeRenderAsset_Handler($Sender) {
		if (in_array($Sender->MasterView, array('default', '')) && GetValue('Name', $Sender, '') != 'ProfileController' && $Sender->EventArguments['AssetName'] == 'BodyMenu') {
			$User = Gdn::Session()->User;
			if (is_object($User)) {
				echo '<div class="ProfileInfo">';
				echo UserPhoto($User, 'UserPhoto');
				echo UserAnchor($User, 'UserAnchor');
				echo Anchor(T('Edit My Profile'), '/profile', 'UserEdit');
				echo '</div>';
			}
		}
	}
	
	/**
	 * Place a little arrow above the status form.
	 */
	public function Base_BeforeStatusForm_Handler($Sender) {
		if (Gdn::Session()->IsValid())
			echo '<div class="StatusArrow"></div>';
	}
	
	/**
	 * Add the user photo in each discussion list item.
	 */
	public function Base_BeforeDiscussionContent_Handler($Sender) {
		$Discussion = GetValue('Discussion', $Sender->EventArguments);
		if (is_object($Discussion))
			echo '<div class="Photo">'.UserPhoto(UserBuilder($Discussion, 'First'), 'Photo').'</div>';
	}
	
	/**
	 * Add the user photo on each comment in search results.
	 */
	public function Base_BeforeItemContent_Handler($Sender) {
		$User = GetValue('User', $Sender->EventArguments);
		if (is_object($User))
			echo '<div class="Photo">'.UserPhoto($User, 'Photo').'</div>';
		
		$Row = GetValue('Row', $Sender->EventArguments);
		if (is_object($Row))
			echo '<div class="Photo">'.UserPhoto($Row, 'Photo').'</div>';
	}

	/**
	 * Add a discussion excerpt in each discussion list item.
	 */
	public function Base_AfterDiscussionTitle_Handler($Sender) {
		$Discussion = GetValue('Discussion', $Sender->EventArguments);
		if (is_object($Discussion))
			echo '<div class="Excerpt">'.SliceString(Gdn_Format::Text($Discussion->Body, FALSE), 100).'</div>';
	}

	/**
	 * Change the "all discussions" link above categories to the current page
	 * title, and then change it back after.
	 */
   public function CategoriesController_BeforeDiscussionTabs_Handler($Sender, $Args) {
      Gdn::Locale()->SetTranslation('All Discussions', $Sender->Data('Title'));
   }
   public function CategoriesController_AfterAllDiscussionsTab_Handler($Sender, $Args) {
      Gdn::Locale()->SetTranslation('All Discussions', 'All Discussions');
   }
	

   public function Setup() {
		return TRUE;
   }

   public function OnDisable() {
      return TRUE;
   }
	
	
}