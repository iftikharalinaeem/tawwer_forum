var psMdNew = 0;
// variables used to change selection when "===" selected
var nNewModelPrevSelected = new Number(0);
var nNewMakePrevSelected = new Number(0);

var newMakesName = new Array();   // used as a cross reference table for name and number
// the following are used as a 2D table for newMakes and models
var newMakes = new Array();
var newModels = new Array();
// load the arrays and construct the selections on the page
function initDocument(){
initNewMakes();
fillNewMakeSelect();
fillNewModelSelect( "1" );       // This should be changed to non-hard coding
}
// create car make objects and fill arrays
function A( newMakenumber, newMake ){
newMakesName[newMake] = new NewMakesName(newMake,newMakenumber);
newMakes[newMakenumber] = new NewMake(newMake,newMakenumber );
}
// make name constructor
function NewMakesName( newMakeName,newMakeNumber ){
this.newMakeNumber = newMakeNumber;
}
// make constructor
function NewMake( newMakeName,newMakeNumber ){
this.newMakeName = newMakeName;
this.newMakeNumber = newMakeNumber;
// create array associated with newMakes
this.newModels = new Array();
}
// construct make selection on page
function fillNewMakeSelect(){

document.NewQuickForm.newmk.options.selectedIndex = 0; // init selection index
document.NewQuickForm.newmk.options.length = 1;  // clear select

// fill selection with newMakes
var i = 0;
for ( newMakesIdx in newMakes ){
var newAMake = newMakes[newMakesIdx];
if(newAMake.newMakeName == undefined) {continue;}
document.NewQuickForm.newmk.options[ i ] = new Option( newAMake.newMakeName, newAMake.newMakeNumber );
i++;
}
document.NewQuickForm.newmk.options[ i ] = new Option( "=================" , "" );

document.NewQuickForm.newmk.options[ 0 ].selected = true;  // select first item

}
// create car model objects and fill arrays
function L( newMakeNumber, newModel, newModelName )
{
var newModelObj = new NewModel( newModel, newModelName);
newMakes[newMakeNumber].newModels[newModel] = newModelObj;
}
// model constructor
function NewModel( newModel,newModelName )
{
this.newModelName = newModelName;
this.newModelNbr = newModel;
}
// construct model selection on page
function fillNewModelSelect( newMakeNbr )
{
if (document.NewQuickForm.newmd){ // This checks if there is a model select input in the widget
	document.NewQuickForm.newmd.options.selectedIndex = 0;
	document.NewQuickForm.newmd.options.length = 1; 
	var newSelectedModels;

	newSelectedModels = (newMakes[newMakeNbr].newModels);
	var i=0;
	document.NewQuickForm.newmd.options[ i ] = new Option( "All" , "All" );
	i++;
	for ( newAModelIdx in newSelectedModels )
	{
	newAModel = newSelectedModels[ newAModelIdx ];
	if(newAModel.newModelName == undefined) {continue;}
	document.NewQuickForm.newmd.options[ i ] = new Option( newAModel.newModelName, newAModel.newModelNbr );
	i++;
	}
	document.NewQuickForm.newmd.options[ i ] = new Option( "=================" , "" );

	//document.NewQuickForm.newmd.options[ 0 ].selected = true;
	if(psMdNew != 0){document.getElementById("newmodel").options[psMdNew].selected = true;psMdNew=0;}
}
}
// when make selected fill model selection
function newSelectedMake( newASelectedMake )
{
var newSelectedIdx = newASelectedMake.selectedIndex;
var newSelectedMakeName = (newASelectedMake.options[ newSelectedIdx ]).value;

var i = 0;
for (aIdx in newMakesName)
{
if (aIdx == newSelectedMakeName)
i++;
}

if (i == 0)
{
newPopulate(document.NewQuickForm.newmk,0);
}		
else
{
var newMakeNBR = newMakesName[newSelectedMakeName].newMakeNumber;  // use cross reference table to get makeintid

fillNewModelSelect( newMakeNBR );
}
}
function newPopulateClient(listBox, prevSelected)
{
var selectedValue = new String(listBox.options[listBox.selectedIndex].value);

alert (newMakesName[1].value);

with (document.NewQuickForm)
{                       
if(listBox.selectedIndex != listBox.options.length-1)
newSelectedMake(listBox);
else
{
listBox.options[prevSelected].selected = true;
newSelectedMake(listBox);
}
}
}

// check user selection on model selection list
function validateModel()
{
with (document.NewQuickForm)
{
if(newmd.options[newmd.selectedIndex].value == "")
newmd.options[nNewModelPrevSelected].selected = true;
}
}
// check user input for zipcode entry field
function Validate() {
with (document.NewQuickForm) {
if (zc.value == "")
	{
	alert("Please enter a valid ZIP code.");
	zc.focus();
	return false;
	}
}
}

function processError()
{
with (document.NewQuickForm)
{
if(newmk.options[newmk.selectedIndex].value != "")
{
newPopulate(newmk,0);
}
else
newmk.options[0].selected = true;			 
}
return true;
}

var nModelPrevSelected = new Number(0);
var nMakePrevSelected = new Number(0);
var zcOK = false;

function Validate(formType) {

	zcOK = true;
	if (formType == "new") {
		with (document.NewQuickForm) {
			if (zc.value == "" || zc.value.length < 5 ) {
				alert("Please enter a valid five-digit ZIP code to find cars in your area.");
				zcOK = false;
				return false;
			}
		}
	}
	else {
		with (document.QuickForm) {		
			if (zc.value == "" || zc.value.length < 5 ) {
				alert("Please enter a valid five-digit ZIP code to find cars in your area.");
				zcOK = false;
				return false;
			}
		}
	}
}

var typeSubmitted = "";

function NewSearchType() {
	Validate("new");
	if (zcOK) {
		if (typeSubmitted == "search") {			
			document.NewQuickForm.mknm.value=document.NewQuickForm.newmk[document.NewQuickForm.newmk.selectedIndex].text;			document.NewQuickForm.mdnm.value=document.NewQuickForm.newmd[document.NewQuickForm.newmd.selectedIndex].text;
			document.NewQuickForm.srv.value="adlocator";
			document.NewQuickForm.act.value="search";
			document.NewQuickForm.so.value="desc";
			document.NewQuickForm.sb.value="ftr";
			document.NewQuickForm.action = "http://www.cars.com/go/search/search.jsp";	
			return true;
		}else if (typeSubmitted == "buildYourOwn") {	
			document.NewQuickForm.mkid.value=document.NewQuickForm.newmk[document.NewQuickForm.newmk.selectedIndex].value;
			document.NewQuickForm.mknm.value=document.NewQuickForm.newmk[document.NewQuickForm.newmk.selectedIndex].text;	

			if (document.NewQuickForm.newmd){ // This checks if there is a model select input in the widget	
				document.NewQuickForm.mdid.value=document.NewQuickForm.newmd[document.NewQuickForm.newmd.selectedIndex].value;
				if(document.NewQuickForm.mdid.value < 0 || document.NewQuickForm.mdid.value == 'All'){document.NewQuickForm.mdid.value = "";}

				document.NewQuickForm.mdnm.value=document.NewQuickForm.newmd[document.NewQuickForm.newmd.selectedIndex].text;
			}
			document.NewQuickForm.ddrd.value=document.NewQuickForm.rd.value;

			document.NewQuickForm.srv.value="dealer";
			document.NewQuickForm.act.value="ncbssrch";
			document.NewQuickForm.so.value="asc";
			document.NewQuickForm.sb.value="dst";
			if(document.NewQuickForm.nclp){
			document.NewQuickForm.action = "http://www.cars.com/go/search/search.jsp";
			} else {
			document.NewQuickForm.action = "http://www.cars.com/c"+"arsapp/"+document.NewQuickForm.aff.value+"/";
			}


			return true;
		}else {
			return false;
		}
	}else {
		return false;
	}
}


function NewUsedSwitch() {
	Validate();
	if (zcOK == true){
		if (document.QuickForm.newUsed[0].checked) {
			document.QuickForm.flt.value="zr,n_ms,new";
			document.QuickForm.action = "http://www.cars.com/go/search/search.jsp";	
		}
		else if (document.QuickForm.newUsed[1].checked) {			
			document.QuickForm.flt.value="zr,n_ms,used";
			document.QuickForm.action = "http://www.cars.com/go/search/search.jsp";	
		}
		else { 
			document.QuickForm.flt.value="zr,n_ms";
			document.QuickForm.action = "http://www.cars.com/go/search/search.jsp";	
		}
	}
	else {
	return false;
	}
}


function validateMake(make)
	{
		if(make.options[make.selectedIndex].value == "")
			{
			alert ("Please choose a valid make.")
			make.options[0].selected = true;
			}
	}	

function sendForm()
{
	if(NewSearchType()) document.NewQuickForm.submit();
}


function checkZip(){
dhtmlLoadScript("http://www.cars.com/go/includes/_zipValid.jsp?js=true&zc="+document.NewQuickForm.zc.value);
}


function continueSubmit(zipOK){
if(zipOK){sendForm()} else {alert('Please enter a valid ZIP code');}
}

function dhtmlLoadScript(url)
{
var e = document.createElement("script");
e.src = url;
e.type="text/javascript";
document.getElementsByTagName("head")[0].appendChild(e);
}


function delayModelSelectNew(n){
if(n == 0){
window.setTimeout('delayModelSelectNew(1)', 500);
} else if(n == 1){
psMdNew = document.getElementById('newmodel').options.selectedIndex;
var nObj = document.getElementById('newmake').options;
window.setTimeout('fillNewModelSelect('+nObj[nObj.selectedIndex].value+')', 500);
}
}
function initNew(){
initNewMakes();
fillNewMakeSelect();
fillNewModelSelect(1);
delayModelSelectNew(0);
}