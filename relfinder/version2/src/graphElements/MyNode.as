/**
 * Copyright (C) 2009 Philipp Heim, Sebastian Hellmann, Jens Lehmann, Steffen Lohmann and Timo Stegemann
 * 
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses/>.
 */ 

package graphElements 
{
	import com.adobe.flex.extras.controls.springgraph.GraphDataProvider;
	import com.adobe.flex.extras.controls.springgraph.GraphNode;
	import com.adobe.flex.extras.controls.springgraph.Item;
	import flash.events.Event;
	import mx.core.Application;
	
	public class MyNode extends Item
	{
		protected var _node:GraphNode = null;
		
		protected var _element:Element = null;
		
		[Bindable(event="elementChange")]
		public function get element():Element {
			return _element;
		}
		
		public function set element(value:Element):void {
			_element = value;
			_element.addEventListener("rdfLabelChange", rdfLabelChangeHandler);
			_element.addEventListener("isLoadingChange", isLoadingChangeHandler);
			dispatchEvent(new Event("elementChange"));
		}
		
		private function isLoadingChangeHandler(event:Event):void {
			trace("loading change handler");
			dispatchEvent(new Event("isLoadingChange"));
		}
		
		private function rdfLabelChangeHandler(event:Event):void {
			dispatchEvent(new Event("rdfLabelChange"));
		}
		
		[Bindable(event="nodeChange")]
		public function get node():GraphNode {
			return _node;
		}
		
		public function set node(value:GraphNode):void {
			_node = value;
			_node.addEventListener("fixedChange", fixedChangeHandler);
			dispatchEvent(new Event("nodeChange"));
		}
		
		private function fixedChangeHandler(event:Event):void {
			dispatchEvent(new Event("fixedChange"));
		}
		
		public function MyNode(_id:String, _element:Element){
			super(_id);
			this.element = _element;
		}
		
		public function getNode():GraphNode {
			if (this.node == null) {
				var dP:GraphDataProvider = app().sGraph.getDataProvider() as GraphDataProvider;
				//elementNode = dP.findNodeByItem(eItem);
				this.node = dP.findNodeByItem(this);
			}
			return this.node;
		}
		
		protected function app():Main {
			return Application.application as Main;
		}
		
		public function getX():Number {
			if (this.node != null) {
				return this.node.x;
			}
			return 0;//FALSCH
		}
		
		public function getY():Number {
			if (this.node != null) {
				return this.node.y;
			}
			return 0;//FALSCH!
		}
		
		public function setPosition(_x:Number, _y:Number):void {
			
			if (this.node == null) {
				this.getNode();
			}
			if (this.node != null) {
				//trace("set position "+_x+" "+_y);
				this.node.x = _x;
				this.node.y = _y;
				this.node.fixed = true;
				this.node.commit();
			}else {
				//trace(("this.node == null");
			}
		}
		
		public function forceXPos(_num:Number):void {
			if (this.node == null) {
				this.getNode();
			}
			if (this.node != null) {
				this.node.x = _num;
				this.node.fixed = true;
				this.node.commit();
			}else {
				//trace(("--------------------");
				//trace(("this.node == null");
			}
		}
		
		public function forceYPos(_num:Number):void {
			if (this.node == null) {
				this.getNode();
			}
			if (this.node != null) {
				this.node.y = _num;
				this.node.fixed = true;
				this.node.commit();
			}else {
				//trace(("--------------------");
				//trace(("this.node == null");
			}
		}
		
		public function pin():void {
			if (this.node == null) {
				this.getNode();
			}
			if (this.node != null && this.node.fixed == false) {
				//trace("fixed = true!");
				this.node.fixed = true;
				this.node.commit();
			}
		}
		
		public function unpin():void {
			if (this.node != null) {
				this.node.fixed = false;
				this.node.commit();
			}
		}
		
		public function hasBeenClicked():void {
			app().selectedElement = this.element; // setCurrentNode(this);
			//trace(("has been clicked");
			//app().setCurrentElement(this.element);
			
		}
		
		
	}
	
}