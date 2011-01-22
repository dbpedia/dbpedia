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
	import flash.display.Graphics;
	import com.adobe.flex.extras.controls.springgraph.Item;
	import mx.core.UIComponent;
	
	/** Defines an object that knows how to draw the edges between 2 items in 
	 * a SpringGraph. */
	public interface IEdgeRenderer
	{
		/** SpringGraph will call this function each time it needs to draw
		 * a link connecting two itemRenderer.
		 * Note that fromView.data is the 'from' Item and toView.data is the 'to' Item.
		 * @param g a Flash graphics object, representing the entire screen area of the 
		 * SpringGraph component. You can use various Flash drawing commands to draw
		 * onto this drawing surface
		 * @param fromView the itemRenderer instance for the 'from' Item of this linik
		 * @param toView the itemRenderer instance for the 'to' Item of this link
		 * @param fromX the x-coordinate of fromView
		 * @param fromY the y-coordinate of fromView
		 * @param toX the x-coordinate of toView
		 * @param toY the y-coordinate of toView
		 * @param graph the Graph that we are drawing
		 * @return true if we successfully drew the edge, false if we want the SpringGraph
		 * to draw the edge. 
		 */
		function draw(g: Graphics, fromView: UIComponent, toView: UIComponent,
			fromX: int, fromY: int, toX: int, toY: int, graph: Graph): Boolean;
	}
}