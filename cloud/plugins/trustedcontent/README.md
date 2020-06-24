# Trusted Content

This plugin allows admins to whitelist domains from which content (images) can be embedded in forum posts.

* **Summary**: Create a way for storing trusted URLs from which the forum will restrict users to embed images from trusted sites only in their content.
* **Description**: Allowing user to embed images or other assests from domains other than the forum is a security risk. The Trusted Content Sources feature gives an interface in the Vanilla dashboard that allows admins to create a "white list" of verified URLs where users can host images. It is not necessary to put in any of Vanilla's domains (vanillaforums.com', 'vanillastaging.com', 'vanillacommunities.com', etc.), the CDN used by Vanilla for this forum, or the address of the forum itself. Turning this feature off allows users to embed from anywhere, turning it on restricts them to embedding only from the trusted URLs
* **Configs set or added**: 
	* `Garden.HTML.FilterContentSources` is set to true or false to turn this feature on and off.
	* `Garden.TrustedContentSources` is the list of domains that are trusted for hosting images.
* **Events used**:
	* `format_filterHtml` Found in the core class.format.php
* **QA steps**: 
	1. Turn on the "Trusted Embeded Content Plugin"
	2. Click on the Settings icon
	3. Toggle the "Allow Embedded Content" on.
	4. Put the domain and sub domain of an image hosting site.
	5. Embed an image from the site you entered, also embed an image from another image hosting site.
	6. Create a post or comment on the site, embed the image from the whitelisted image hosting site and one from the non-whitelisted site.
	7. Check that your post shows one embedded image and one link to the non-whitelisted image.