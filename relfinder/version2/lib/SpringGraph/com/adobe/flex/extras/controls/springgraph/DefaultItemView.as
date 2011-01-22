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
	import mx.core.IDataRenderer;
	import flash.events.Event;
	
	/** @private */
	public class DefaultItemView extends UIComponent implements IDataRenderer
	{
		[Bindable("dataChange")]
		public function get data(): Object {
			return _data;
		}
		
		public function set data(d: Object): void {
			_data = d;
			dispatchEvent(new Event("dataChange"));
		}
		
		private var _data: Object = null; 
	}
}