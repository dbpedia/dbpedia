////////////////////////////////////////////////////////////////////////////////
//
//  Copyright (C) 2006 Adobe Macromedia Software LLC and its licensors.
//  All Rights Reserved. The following is Source Code and is subject to all
//  restrictions on such code as contained in the End User License Agreement
//  accompanying this product.
//
////////////////////////////////////////////////////////////////////////////////

package com.adobe.flex.extras.controls.springgraph {

	import mx.core.UIComponent;
	import mx.core.Application;
	import mx.controls.Alert;
	import com.adobe.flex.extras.controls.forcelayout.Node;
	import flash.geom.Rectangle;
	
	/** Represents one node of a SpringGraph 
	 * 
	 * @author   Mark Shepherd
	 * @private
	 */
	public class GraphNode extends Node {
		
		public var view: UIComponent;
		public var item: Item;
		
		public override function refresh(): void {
			this.x = getX();
			this.y = getY();
			this.repulsion = getRepulsion();
		}
		
		public override function commit(): void {
			setX(this.x);
			setY(this.y);
		}

		public function GraphNode(view: UIComponent, context: GraphDataProvider, item: Item) {
			super();
			this.view = view;
			this.context = context;
			this.item = item;
		}

		// -------------------------------------------------
		// Private stuff 
		// -------------------------------------------------

	    private function getX(): Number {
			
	    	return view.x + (view.width / 2); // we use the center point
	    }
	    
	    private function setX(x: Number): void {
			
	    	/*
	    	if(context.boundary != null) {
	    		if((x < context.boundary.left) || ((x + view.width) > context.boundary.right))
	    			return;
	    	}
	    	*/
	    	if ((x != (view.x + (view.width / 2))) && item.okToMove()) {
				//trace("setX : " + this.view.x);
		    	context.layoutChanged = true;
		    	view.x = x  - (view.width / 2);
		    }
	    }
	    
	    private function getY(): Number {
	    	return view.y + (view.height / 2); // we use the center point
	    }
	    
	    private function setY(y: Number): void {
	    	/*
	    	if(context.boundary != null) {
	    		if((y < context.boundary.top) || ((y + view.height) > context.boundary.bottom))
	    			return;
	    	}
	    	*/
	    	if((y != (view.y + (view.height / 2))) && item.okToMove()) {
		    	context.layoutChanged = true;
		    	view.y = y - (view.height / 2);
		    }
	    }
	    
		private function getRepulsion(): int {
			var result: int = (view.width + view.height) * context.repulsionFactor;
			if(result == 0)
				return context.defaultRepulsion;
			return result;
		}
		
		private var context: GraphDataProvider;
	}
}