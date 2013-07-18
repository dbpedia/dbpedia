/**
 * Copyright (C) 2009 Philipp Heim, Sebastian Hellmann, Jens Lehmann, Steffen Lohmann and Timo Stegemann
 * 
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses/>.
 */ 

package 
{
	import com.adobe.flex.extras.controls.springgraph.Graph;
	import com.adobe.flex.extras.controls.springgraph.IEdgeRenderer;
	import com.adobe.flex.extras.controls.springgraph.Item;
	import flash.display.Graphics;
	import flash.geom.Matrix;
	import flash.geom.Point;
	import mx.core.IDataRenderer;
	import mx.core.UIComponent;
	
	/**
	 * ...
	 * @author Philipp Heim
	 */
	public class DirectedEdge implements IEdgeRenderer
	{
		
		public function DirectedEdge() 
		{
			
		}
		
		public function draw(g:Graphics, fromView:UIComponent, toView:UIComponent, fromX:int, fromY:int, toX:int, toY:int, graph:Graph): Boolean {
			
			var fromItem:Item = (fromView as IDataRenderer).data as Item;
			var toItem: Item = (toView as IDataRenderer).data as Item;
			
			var linkData:Object = graph.getLinkData(fromItem, toItem);
			var alpha: Number = 1.0;
			var thickness: int = 1;
			var color: int = uint("0xcccccc");
			if (linkData != null) {
				
				if(linkData.hasOwnProperty("settings")){
					var settings: Object = linkData.settings;
					alpha = settings.alpha;
					thickness = settings.thickness;
					color = settings.color;
				}
				
				if (linkData.hasOwnProperty("startId")) {
					
					if(linkData.startId != fromItem.id) {	//falschrum!! alles umdrehen
						var tempX:Number = fromX;
						var tempY:Number = fromY;
						fromX = toX;
						fromY = toY;
						toX = tempX;
						toY = tempY;
					}else {
						//FlashConnect.trace("from: "+fromItem.id+", to: "+toItem.id);
					}
				}
			}

			g.lineStyle(thickness,color,alpha);
			g.beginFill(0);
			g.moveTo(fromX, fromY);
			g.lineTo(toX, toY);
			this.drawArrows(g, fromX, fromY, toX, toY);
			g.endFill();
			
			return true;
			
		}
		
		
		private function drawArrows(g:Graphics, fromX:int, fromY:int, toX:int, toY:int):void {
			var arrowLength:uint = 10;
			
			var dx:Number = toX - fromX;
			var dy:Number = toY - fromY;
			
			var mid1:Point = new Point(fromX + dx / 4, fromY + dy / 4);
			var mid2:Point = new Point(fromX + dx / 2, fromY + dy / 2);
			var mid3:Point = new Point(fromX + 3* dx / 4, fromY + 3* dy / 4);
			
			var vector:Point = new Point(dx, dy);
			
			// define a transformation matrix
            var m:Matrix = new Matrix();
            var rad:Number = 135 * Math.PI / 180;
            m.rotate(rad);  // angle has to be Rad
			
			var vectorR:Point = m.transformPoint(vector);
			vectorR.normalize(arrowLength);
			
			m = new Matrix();
			rad = 225 * Math.PI / 180;
			m.rotate(rad);
			
			var vectorL:Point = m.transformPoint(vector);
			vectorL.normalize(arrowLength);
			
			//this.drawArrow(g, mid1, vectorL, vectorR); 
			this.drawArrow(g, mid2, vectorL, vectorR); 
			//this.drawArrow(g, mid3, vectorL, vectorR); 
		}
		
		private function drawArrow(g:Graphics, p:Point, vL:Point, vR:Point):void {
			var rightX:Number = p.x + vR.x;
			var rightY:Number = p.y + vR.y;
			
			var leftX:Number = p.x + vL.x;
			var leftY:Number = p.y + vL.y;
			
			g.moveTo(p.x, p.y);
			g.lineTo(rightX, rightY);
			
			g.moveTo(p.x, p.y);
            g.lineTo(leftX, leftY);
		}
		
	}
	
}