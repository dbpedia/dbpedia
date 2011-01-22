/*
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
*/
import mx.controls.List;
import mx.controls.Alert;
import mx.collections.XMLListCollection;
import mx.core.Application;

	[Bindable]
	private var hotelList:XMLListCollection;
	[Bindable]
	private var myList:XMLList;
	private var temp:XMLList;
	private function InitializeData():void
	{
		myList = new XMLList(hotels.Hotel);
		hotelList = new XMLListCollection(myList);
		invalidateProperties();
	}
	private function SetData():void
	{
		if(ac.selectedItem!=null && ac.selectedIndex!=-1)
				{
						application.restaurantDetails.lname.visible=true;
						application.restaurantDetails.laddress.visible=true;
						application.restaurantDetails.lphone.visible=true;
						application.restaurantDetails.tcuisines.visible=true;
						application.restaurantDetails.img.source=ac.selectedItem.Photo;
						application.restaurantDetails.hotelname.text=ac.selectedItem.Name;
						application.restaurantDetails.address.text=ac.selectedItem.Address;
						application.restaurantDetails.city.text=ac.selectedItem.City;
						application.restaurantDetails.phone.text=ac.selectedItem.Phone;
						application.description.text=ac.selectedItem.Description;
						temp = ac.selectedItem.elements("Cuisines");
						temp = temp.elements("Cuisine");
						var availableCuisines:String = "";
						for each (var cuisine:XML in temp)
						{
							availableCuisines = availableCuisines+ cuisine.toString()+ "\n";
						}
						application.restaurantDetails.cuisines.text = availableCuisines;
				}
				else
				{
					Alert.show("Select an hotel from drop down list to see it's detailed information");
				}
	}
	private function CuisineLabel(item:Object):String
	{
		return (ac.typedText);
	}
	private function MyFilterFunction(element:*, typedText:String):Boolean
	{
		temp = element.elements("Cuisines");
		temp = temp.elements("Cuisine");
		for each (var cuisine:XML in temp)
		{
			if(cuisine.toString().toLowerCase().indexOf(ac.typedText.toLowerCase(),0) == 0)
				return true;
		}
		return false;
	}