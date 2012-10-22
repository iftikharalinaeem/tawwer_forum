<?php if (!defined('APPLICATION')) exit(); ?>

	<script src="/themes/CarTalk/js/prototype.js" type="text/javascript"></script>
	<script src="/themes/CarTalk/js/scriptaculous.js" type="text/javascript"></script>
	
	
<div id="cars_search">

<script language="Javascript">
Event.observe(window, 'load', function() {
	var a = $A(document.getElementsByTagName('a')).map(Element.extend);
	for(i=0;i<a.length;i++) {
		if($(a[i]).hasClassName('drop_down_closed')) {

			Event.observe($(a[i]), 'click', function() {
				this.toggleClassName('drop_down_open');
				this.next('form').toggle();
			});
		}
	}

});
</script>
<!-- this div tag controls the car talk toolbox images-->
<div id="cars_header"><a href="http://www.cars.com/go/index.jsp?aff=cartalk" TARGET="_blank"></a></div>

<div id="cars_widget_sections">
<!--*********************BEGIN RESEARCH A CAR WIDGET*************************-->
<div class="expandable">

	<a href="javascript:;" class="drop_down_closed" id="reasearch_a_car">Research a Car</a>
	<form id="research_car_form" action="#" method="post" style="display:none;">
		<div id="research_links">
			<label for="research_links">Research by Type</label>

			<a href="http://www.cars.com/go/crp/buyingGuides/Section.jsp?section=Passenger&amp;subject=Passenger&amp;year=New&amp;story=index&amp;aff=cartalk" target="_blank"><img src="/themes/CarTalk/design/passanger.jpg" width="35" height="19" border="0" />Passenger Cars</a>
			<a href="http://www.cars.com/go/crp/buyingGuides/Section.jsp?section=Sports&amp;subject=Sports&amp;year=New&amp;story=index&amp;aff=cartalk" target="_blank"><img src="/themes/CarTalk/design/sports.png" width="35" height="19" border="0" />Sports Cars</a>

			<a href="http://www.cars.com/go/crp/buyingGuides/Section.jsp?section=Luxury&amp;subject=Luxury&amp;year=New&amp;story=index&amp;aff=cartalk" target="_blank"><img src="/themes/CarTalk/design/luxury.png" width="35" height="19" border="0" />Luxury Cars</a>
			<a href="http://www.cars.com/go/crp/buyingGuides/Section.jsp?section=SUV&amp;subject=SUV&amp;year=New&amp;story=index&amp;aff=cartalk" target="_blank"><img src="/themes/CarTalk/design/suvs.png" width="35" height="19" border="0" />SUVs</a>
			<a href="http://www.cars.com/go/crp/buyingGuides/Section.jsp?section=Pickup&amp;subject=Pickup&amp;year=New&amp;story=index&amp;aff=cartalk" target="_blank"><img src="/themes/CarTalk/design/pickup.png" width="35" height="19" border="0" />Pickup Trucks</a>
			<a href="http://www.cars.com/go/crp/buyingGuides/Section.jsp?section=Van&amp;subject=Van&amp;year=New&amp;story=index&amp;aff=cartalk" target="_blank"><img src="/themes/CarTalk/design/minivans.png" width="35" height="19" border="0" />Minivans and Vans</a>

			<a href="http://www.cars.com/go/crp/buyingGuides/Section.jsp?section=Hybrid&amp;subject=Hybrid&amp;year=New&amp;story=index&amp;aff=cartalk" target="_blank"><img src="/themes/CarTalk/design/hybrid.png" width="35" height="19" border="0" />Hybrid Vehicles</a>

			<a href="http://www.cars.com/go/criteriaSearch/lifestyles.jsp" target="_blank"><img src="/themes/CarTalk/design/lifestyle.png" width="35" height="19" border="0" />Research by Lifestyle</a>
		</div>
	</form>				
	<img src="/themes/CarTalk/design/right_divider.png" width="237" height="5" border="0" />
</div>
<!--*********************END RESEARCH A CAR WIDGET*************************-->					

<!--*********************BEGIN SELL A CAR WIDGET*************************-->
<div class="expandable">
	<a href="javascript:;" class="drop_down_closed" id="sell_your_car">Sell Your Car</a> <div class="sellyrself"><a href="http://www.cartalk.com/content/features/Used-Car-Tips/">[Sell It Yourself Tips]</a></div>

	<form id="sell_car" action="http://siy.cars.com/beta/index.jhtml" method="get" style="display:none;">
		<div class="expandable_text">Reach 8 million shoppers:<br />Get thousands over trade-in</div>
		<label for="sell_car_zip">Your ZIP:</label>
		<input type="Hidden" name="aff" value="cartalk">
		<input type="Hidden" name="referer" value="fsbo_hplr2">
		<input type="text"  name="zc" size="5" value="" id="sell_car_zip"/>
		<br /><br />

		<input type="image" src="/themes/CarTalk/design/place-ad.gif" border="0" style="border-width: 0px;">

		<a href="http://siy.cars.com/siy/qsg/sellingBenefits.jsp?aff=cartalk">&gt; Seller's Guide</a>
		<a href="http://siy.cars.com/beta/edit_login.jhtml?aff=cartalk&referer=fsbo_hplr2">&gt; Edit or Renew Your Ad</a>
		<a href="http://siy.cars.com/siy/mbgPopUp.jsp?aff=cartalk">&gt; Money-back Guarantee</a>
	</form>

	<img src="/themes/CarTalk/design/right_divider.png" width="237" height="5" border="0" />		
</div>	
<!--*********************END SELL A CAR WIDGET*************************-->	

<!--*********************BEGIN FIND A NEW CAR WIDGET*************************-->
<div class="expandable">
	<a href="javascript:;" class="drop_down_closed" id="buy_a_new_car">Buy a New Car</a>

	<!-- *********************** NEW Widget Code ******************************* -->
	<script language="JavaScript" type="text/javascript" src="/media/cartalk/header/pa/new-car-widget_driver.js"></script>
	<script language="javascript" type="text/javascript" src="http://www.cars.com/includes/js/new_mm.js"></script>

	<form name="NewQuickForm" action="javascript:typeSubmitted='buildYourOwn';checkZip();" method="get" target="_top" style="display:none;">
		<input type="hidden" name="tracktype" value="newcc">
		<input type="hidden" name="searchType" value="85">
		<input name="srv" type="hidden">
		<input name="aff" type="hidden" value="cartalk">
		<input name="act" type="hidden">
		<input name="searchtype" type="hidden" value="85">
		<input name="fs" type="hidden">

		<input name="so" type="hidden">
		<input type="hidden" name="sb">
		<input type="hidden" name="newmdsearch" value="y"> 
		<input type="hidden" name="sk">
		<input type="hidden" name="fs">		
		<input type="hidden" name="ddrd">
		<input type="hidden" name="config">
		<input type="hidden" name="rt" value="quick_ncbs.tmpl">
		<input type="hidden" name="referrer" value="configurator">
		<input type="hidden" name="year" value="200">

		<input type="hidden" name="mkid">
		<input type="hidden" name="mknm">
		<input type="hidden" name="mdid">
		<input type="hidden" name="mdnm">
		<input type="hidden" name="nclp" value="true" />
		<div class="formLine">
			<span class="widget3">Make:</span>
			<select name="newmk" id="newmake" class ="widgetmm" onFocus="nNewMakePrevSelected=newmk.selectedIndex;" onChange="validateMake(this);fillNewModelSelect(this.value);">

				<option value="" selected>====================</option>
			</select>
		</div>
		<div class="formLine">
			<span class="widget3">Model:</span>
			<select name="newmd" id="newmodel" class ="widgetmm" size=1 onFocus="nNewModelPrevSelected=newmd.selectedIndex;" onChange="validateModel(this);">
				<option value="" selected>====================</option>

			</select>
		</div>
		<div class="formLine">
			<input type="hidden" name="rd" class="widget" value="30">
			<label for="find_new_car_zip">Your ZIP:</label>
			<input type=text size=5 maxlength=5 name="zc" id="zc" class="widget">
		</div>
		<input type="image" src="/themes/CarTalk/design/submit_with_options.png" width="132" height="17" border="0" style="border-width: 0px;" />

	</form>	
	<script language="javascript" type="text/javascript">initNew();</script>	
	<img src="/themes/CarTalk/design/right_divider.png" width="237" height="5" border="0" />
</div>
<!--*********************END FIND A NEW CAR WIDGET*************************-->

<!--******************BEGIN FIND A USED CAR WIDGET************************-->
<div class="expandable">
	<a href="javascript:;" class="drop_down_closed" id="buy_a_used_car">Buy a Used Car</a>
	<script language="javascript" type="text/javascript" src="http://www.cars.com/includes/js/used-car-widget_driver.js"></script><!-- pulls in our model table.  cars.com hosts the script to ensure its accuracy -->
	<script language="javascript" type="text/javascript" src="http://www.cars.com/includes/js/makemodels-used.js"></script>

	<form name="QuickForm" onSubmit="Validate();" action="http://www.cars.com/go/search/search.jsp" method=get target="_top" style="display:none;">
	<!--USED CAR SEARCH INPUTS -->

	<!-- This is very important -->

	<!-- Update 'national' in the hidden variable below to your actual affiliate code -->
	<!-- If you do not know your affiliate code, then contact cobrandedsites@cars.com -->
	<input type="hidden" name="aff" value="national">

	<!-- The default radius of '30' can be changed by updating the value of rd -->

	<!-- One of the following options can be used: 10, 20, 40, 50, 75 or 100  -->
	<input type="hidden" name="rd" value="30" >

	<!-- variables for application, leave in place -->
	<input type="hidden" name="tracktype" value="usedcc">
	<input type="hidden" name="searchType" value="38">
	<input type=hidden name=referrer value=richmedia>

	<!-- USED-CAR SEARCH SELECT -->
	<div class="formLine">

		<span class="widget3">Make:</span>
		<select name="mknm" id="usedmk" class="widgetmm" onFocus = "nMakePrevSelected=mknm.selectedIndex;" onchange="PopulateClient(this,nMakePrevSelected);">
		</select>
	</div>

	<div class="formLine">
		<span class="widget3">Model:</span>
		<select name="mdnm" id="usedmd" class="widgetmm" onChange = "validateModel();">

		</select>
	</div>
	
	<div class="formLine">	
		<span class="widget3">Your ZIP:</span>
		<input type=text size=5 maxlength=5 name=zc>  
	</div>
	<input type="image" src="/themes/CarTalk/design/search_used.png" size=5 maxlength=5 name=zc style="border-width: 0px;">	
	</form>				
	<img src="/themes/CarTalk/design/right_divider.png" width="237" height="5" border="0" />					
	<script language="javascript" type="text/javascript">initDocument();</script>

</div>
<!--*********************END FIND A USED CAR WIDGET*************************-->
</div></div>

<br />