////////////////////////////////////////////////////////////////////////////////
//
//  Copyright (C) 2006 Adobe Macromedia Software LLC and its licensors.
//  All Rights Reserved. The following is Source Code and is subject to all
//  restrictions on such code as contained in the End User License Agreement
//  accompanying this product.
//
////////////////////////////////////////////////////////////////////////////////

package com.adobe.flex.extras.controls.springgraph
{
	import mx.core.UIComponent;
	
	/** Defines an object that knows how to create views for Items. */
	public interface IViewFactory
	{
		/** 
		 * Create a UIComponent to represent a given Item in a SpringGraph. The returned UIComponent should
		 * be a unique instance dedicated to that Item. This function might return a unique view component
		 * on each call, or it might cache views and return the same view if called repeatedly 
		 * for the same item. This function may return different classes of object based on the type
		 * or data of the Item.
		 * @param item an item for which y
		 * @return a unique UIComponent to represent the Item. This component must also implement the IDataRenderer interface.
		 * It's OK to return null.
		 * 
		 */
		function getView(item: Item): UIComponent;
	}
}