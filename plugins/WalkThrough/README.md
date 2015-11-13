#WalkTrhough Plugin

##How to display a tour
In order to display a tour to a user, you need to get the instance of the WalkThroughPlugin.<br/>
Once the plugin is acquired and after the UserID is known, you can push a new tour to the plugin.


	$tour = [
		// Required
	    'name' => 'Tour example',
	  
	    // Required
	    //
	    // The attributes are described further in this document
	    'steps' => [
	        [
	            'intro' => 'This step is generic and doesn\'t reference anything in particular.',
	        ],
	        [
	            'element' => '.SiteMenu',
	            'intro' => 'This next step displays the menu and emphasizes it.',
	        ],
	        [
	            'element' => '#DiscussionForm',
	            'intro' => 'This last step will appear on the post a new discussion page.  It shows the discussion form by referencing the element by its ID',
	            'page' => 'post/discussion',
	        ]
	    ]
	    
	    // Optional
	    //
	    // These options will affect the display of the tour.
	    // They are described further in this document
	    'options' => [
			//'cssFile' => 'http://domain.com/custom_tour.css',
			'exitOnEsc' => true,
			'exitOnOverlayClick' => true
	    ],	    
	];
        
    // Make sure the plugin is enabled
	if (! Gdn::pluginManager()->isEnabled('WalkThrough')) {
		return;
	}
	
	// Get the plugin instance
	$plugin = Gdn::pluginManager()->getPluginInstance('WalkThrough', Gdn_PluginManager::ACCESS_PLUGINNAME);
	
	// Push a tour to the current user if possible
	if ($plugin->shouldUserSeeTour(Gdn::session()->UserID, 'My tour name')) {
		$plugin->loadTour($tour);
	}


###Steps format
 - `intro`: The tooltip text of step
 - `element`: Optionally defines the element to showcase.  Can be a `.css_class`, `#any_id` or even`document.querySelector("input[name=login]")`
 - `page`: Optionally defines the Vanilla page that the step needs to be viewed on.  Example: `discussions`
 - `tooltipClass`: Optionally define a CSS class for tooltip
 - `highlightClass`: Optionally append a CSS class to the helperLayer
 - `position`: Optionally define the position of tooltip, `top`, `left`, `right`, `bottom`, `bottom-left-aligned` (same as 'bottom'), 'bottom-middle-aligned' and 'bottom-right-aligned'. Default is `bottom`

 
###Options:

 - `cssFile`: Attach a custom CSS file to customize the tour.  File must be an `absolute URL`
 - `nextLabel`: Next button label.  Default is `Next &rarr;`
 - `nextPageLabel`: Next page button label.  Default is `Next page &rarr;`
 - `prevLabel`: Previous button label.  Default is `&larr; Back`
 - `prevPageLabel`: Previous page button label.  Default is `&larr; Previous page`
 - `skipLabel`: Skip button label.  Default is `Skip`
 - `doneLabel`: Done button label.  Default is `Done`
 - `tooltipPosition`: Default tooltip position.  Default is `auto`
 - `positionPrecedence`: Precedence of positions, when `tooltipPosition` is `auto`.  Default is `['bottom', 'top', 'right', 'left']`
 - `tooltipClass`: Adding CSS class to all tooltips
 - `highlightClass`: Additional CSS class for the helperLayer
 - `exitOnEsc`: Exit introduction when pressing Escape button, `true` or `false`
 - `exitOnOverlayClick`: Exit introduction when clicking on overlay layer, `true` or `false`
 - `showStepNumbers`: Show steps number in the red circle or not, `true` or `false`
 - `keyboardNavigation`: Navigating with keyboard or not, `true` or `false`.  Default is `true`
 - `showButtons`: Show introduction navigation buttons or not, `true` or `false`.  Default is `true`
 - `showBullets`: Show introduction bullets or not, `true` or `false`.  Default is `true`
 - `showProgress`: Show introduction progress or not, `true` or `false`.  Default is `false`
 - `scrollToElement`: Auto scroll to highlighted element if it's outside of viewport, `true` or `false`
 - `overlayOpacity`: Adjust the overlay opacity, `0.0 to 1.0`.  Default is `0.7`
 - `disableInteraction`: Disable an interaction inside element or not, `true` or `false`.  Default is `true`