# Watermark Images

This feature was created so that a forum could configure a Category so that any photos uploaded to that Category would receive a watermark. There is no default watermark, the admin has to upload an PNG image to be super-imposed over uploaded images.
**Warning: This feature was developed for one client and has not been thoroughly tested in conjuction with many different plugins. Use with caution.**

- **Summary:** Admins upload a transparent PNG which is used to watermark images uploaded to designated Categories.
- **Use case:** A client wants to protect images from being used on other sites on the web. Creates a watermark and has it imposed on all the images uploaded to a given Category.
- **Description:** Add watermarks to images.
- **Configs set or added:** 
	- `Watermark.WatermarkCategories` An array of categories that will contain watermarked images. The values of this array are populated by the Edit Category form.
	- `Watermark.WatermarkPath` Saved path to the PNG that has been uploaded as a watermark. This value is populated by the Watermark Settings form in the dashboard.
	- `Watermark.Position` The positioning of the watermark relative to the image on which it is being super imposed. This has to be added manually. Defaults to "0, 0, 0 ,0". The first number is the position from the top, the second is position from the left, the third is the position from the right, and the fourth is the position from the bottom. For example:
		- (20, 10, 0, 0) places the watermark in the top left corner: 20 pixels from the top and 10 pixels to the left. The other 2 numbers are ignored.
		- (0, 20, 10, 0) places the watermark in the top right corner: 20 pixels from the top and 10 pixles from the right.
		- (0, 0, 20, 10) places the watermark in the bottom right corner: 20 pixels from the bottom and 10 pixles from the right.
		- (20, 0, 0, 10) places the watermark in the bottom left corner: 20 pixels from the bottom and 10 pixles from the left.
 		- When they are all 0 (default setting) the watermark is centered.
	- `Watermark.Resize` Resizes the image to the percentage of the parent image. This has to be added manually. Default is 70. That means if the image you are watermarking is 200px by 200px the watermark will be 140px by 140px.
	- `Watermark.Quality` Set the quality of the watermark. This has to be added manually. Defaults to 70. The higher the quality the heavier the image.
- **Events used:** 
	- `settingsController_afterCategorySettings_handler` Add a toggle to the Categories form in the Dashboard to designate a Category to watermark all images uploaded to Discussions. 
	- `categoryModel_beforeSaveCategory_handler` Save CategoryID  to the `Watermark.WatermarkCategories` array in the config.
	- `editorPlugin_beforeSaveUploads_handler` When a user uploads a photo in a WatermarkCategory, add the watermark to the photo.
- **Setup Steps:**
	- Turn on the plugin.
	- Upload a (transparent) PNG image as a watermark.
	- Go to the Category form in the dashboard and designate at least on Category to Watermark.
	- Turn on Advanced Editor, give upload images privileges to at least some roles.
- **QA steps:**
     1. Turn on Watermark Plugin
     2. Go to Watermark Settings and upload in image.
     3. Upload a PNG.
     4. Delete the PNG.
     5. Upload another PNG.
     6. Upload another PNG to verify that it replaces the current PNG.
     7. Go to the Categories Form, toggle a Category to be Watermarked.
     8. Go to the Forum, upload an image to that Category.
     9. Verify that the image is watermarked correctly.
     10. In the Dashboard, toggle the Category to not be watermarked.
     11. Go back to the Forum. The image you uploaded should still be watermarked.
     12. Upload a new image. Verify that the image is **not** watermarked.
- **Troubleshooting and Gotchas:**

  If this feature doesn't work, turn on the DB_Logger and look in the Event Log, it is very well logged. Also look into the following:
	- Is the Advanced Editor turned on? Has a Format Type that supports images been selected?
	- If a user embeds an image from another site it will not be watermarked. Check the source URL of any non-watermarked images.
	- Look at the watermark image (it's path is saved in the config). It might be very small or too transparent.