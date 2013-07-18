////////////////////////////////////////////////////////////////////////////////
//
// Copyright (C) 2003-2006 Adobe Macromedia Software LLC and its licensors.
// All Rights Reserved.
// The following is Sample Code and is subject to all restrictions on such code
// as contained in the End User License Agreement accompanying this product.
// If you have received this file from a source other than Adobe,
// then your use, modification, or distribution of it requires
// the prior written permission of Adobe.
//
////////////////////////////////////////////////////////////////////////////////

import mx.controls.Alert;
import mx.controls.ComboBox;
import mx.events.ItemClickEvent;


[Bindable]
public var myArray:Array=[{country:"Australia",capital:"Canberra"},
							{country:"Austria",capital:"Vienna"},
							{country:"Belgium",capital:"Brussels"},
							{country:"Bermuda",capital:"Hamilton"},
							{country:"Brazil",capital:"Brasilia"},
							{country:"Canada",capital:"Ottawa"},
							{country:"China",capital:"Beijing"},
							{country:"Czech Republic",capital:"Prague"},
							{country:"Denmark",capital:"Copenhagen"},
							{country:"Finland",capital:"Helsinki"},
							{country:"France",capital:"Paris"},
							{country:"Germany",capital:"Berlin"},
							{country:"Hongkong",capital:"Victoria City"},
							{country:"India",capital:"New Delhi"},
							{country:"Ireland",capital:"Dublin"},
							{country:"Italy",capital:"Rome"},
							{country:"Japan",capital:"Tokyo"},
							{country:"Korea",capital:"Seoul"},
							{country:"Mexico",capital:"Mexico City"},
							{country:"Netherlands",capital:"Amsterdam"},
							{country:"Norway",capital:"Oslo"},
							{country:"Singapore",capital:"Singapore"},
							{country:"Spain",capital:"Madrid"},
							{country:"Sweden",capital:"Stockholm"},
							{country:"Switzerland",capital:"Bern"},
							{country:"Taiwan",capital:"Taipei"},
							{country:"United Kingdom",capital:"London"},
							{country:"United States",capital:"Washington D.C"}]
									 
private var dropWidth:int;
private var defaultFilterFunction:Function;

private function initData():void
{
	dropWidth = mySTI.dropdownWidth;
	dropDownWidth.value = dropWidth;
	defaultFilterFunction=mySTI.filterFunction;
}

public function restoreAutoCompleteDefaults():void {
	mySTI.clearStyle('openDuration');
	mySTI.clearStyle('closeDuration');
	openDuration.value=mySTI.getStyle('openDuration');
	closeDuration.value=mySTI.getStyle('closeDuration');
	
	mySTI.rowCount=rowCount.value=7;
	dropDownWidth.value= dropWidth;
	mySTI.dropdownWidth=dropWidth;
	
	mySTI.lookAhead=lookAhead.selected=false;
	mySTI.keepLocalHistory=keepLocalHistory.selected=false;
		
	mySTI.filterFunction=defaultFilterFunction;
	beginFunction.selected=true;
	
	mySTI.labelField='country';
	country.selected=true;
	
	mySTI.text="";
}

private function beginFilterFunction(element:*, text:String):Boolean
{
	var label:String=mySTI.itemToLabel(element);
	return(label.toLowerCase().indexOf(text.toLowerCase())==0);
}

 public function regularExpression(element:*, text:String):Boolean
   {
      var regExp:RegExp = new RegExp(text,"i");
      return(regExp.test(mySTI.itemToLabel(element)));
   }
   
private function substringFilterFunction(element:*, text:String):Boolean
{
	var label:String=mySTI.itemToLabel(element);
	return(label.toLowerCase().indexOf(text.toLowerCase())!=-1);
}

public function changeFilterFunction(event:ItemClickEvent):void
{
	if(event.currentTarget.selectedValue=="regEx")
		mySTI.filterFunction=regularExpression;
	else if(event.currentTarget.selectedValue=="sub")
		mySTI.filterFunction=substringFilterFunction;
	else if(event.currentTarget.selectedValue=="begin")
		mySTI.filterFunction=defaultFilterFunction;
	 
	mySTI.text="";
}


