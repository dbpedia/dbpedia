/**
 * Copyright (C) 2009 Philipp Heim, Sebastian Hellmann, Jens Lehmann, Steffen Lohmann and Timo Stegemann
 * 
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses/>.
 */ 

package graphElements {
	import de.polygonal.ds.HashMap;
	import graphElements.events.PropertyChangedEvent;
	import mx.collections.ArrayCollection;
	import flash.events.Event;
	import flash.events.EventDispatcher;
	import mx.core.Application;
	
	public class Concept extends EventDispatcher{
		
		private var _id:String;
		private var _label:String;
		private var _isVisible:Boolean = true;
		private var _canBeChanged:Boolean = true;	//whether the visibility of the concept can be changed! Or the change of the visibility has any effect on the graph!
		//private var _isRestrictedBy:HashMap = new HashMap();
		
		private var _elements:ArrayCollection = new ArrayCollection();
		private var _numVisibleElements:int = 0;
		private var _stringNumOfElements:String = "";	//textual representation of the number of visible and not visible elements
		
		public static var VCHANGE:String = "isVisibleChange";
		public static var NUMVECHANGE:String = "numberOfVisibleElementsChange";
		
		public function Concept(_id:String, _label:String) {
			this._id = _id;
			this._label = _label;
		}
		
		public function removeListener():void {
			for each(var e:Element in _elements) {
				e.removeEventListener(Element.VCHANGE, elementVChangeHandler);
			}
		}
		
		public function get id():String {
			return _id;
		}
		
		public function get label():String {
			return _label;
		}
		
		public function get canBeChanged():Boolean {
			return _canBeChanged;
		}
		
		[Bindable(event=Concept.VCHANGE)]
		public function get isVisible():Boolean {
			return _isVisible;
		}
		
		public function set isVisible(b:Boolean):void {
			if (app().delayedDrawing) {
				//app().emptyToDrawPaths();
				app().delayedDrawing = false;
			}
			//trace("Test"+b);
			if (_isVisible != b) {
				trace("set concept("+id+") visible: "+b);
				_isVisible = b;
				var event:PropertyChangedEvent = new PropertyChangedEvent(Concept.VCHANGE, this, "isVisible");
				dispatchEvent(event);
				//dispatchEvent(new Event(Concept.VCHANGE));
				//trace("event dispatched "+id);
			}
			/*for each(var e:Element in _elements) {
				if (_isVisible) {
					app().showNode(app().getInstanceNode(e.id, e));
				}else {
					app().hideNode(app().getInstanceNode(e.id, e));
				}
			}*/
		}
		
		/*[Bindable(event="elementsChange")]
		public function get elements():ArrayCollection {
			return this._elements;
		}*/
		
		public function addElement(e:Element):void {
			if (!_elements.contains(e)) {
				//trace(">addElement to concept "+e.id);
				this._elements.addItem(e);
				if (e.isVisible) {
					numVisibleElements = _numVisibleElements + 1;
				}
				e.addEventListener(Element.VCHANGE, elementVChangeHandler);
				//e.addEventListener(Element.NEWRCHANGE, elementNewRestrictionChange);
				//dispatchEvent(new PropertyChangedEvent(PropertyChangedEvent.PROPERTY_CHANGED, this, "elementsChange"));
				//e.addEventListener(Element.VCHANGE, elementVChangeHandler);
				
			}
		}
		
		[Bindable(event=Concept.NUMVECHANGE)]
		public function get stringNumOfElements():String {
			return this._stringNumOfElements;
		}
		
		//[Bindable(event=Concept.NUMVECHANGE)]
		public function get numVisibleElements():int {
			return this._numVisibleElements;
		}
		
		public function set numVisibleElements(n:int):void {
			if (this._numVisibleElements != n) {
				this._numVisibleElements = n;
				this._stringNumOfElements = this._numVisibleElements.toString() + "/" + this._elements.length.toString();
				dispatchEvent(new Event(Concept.NUMVECHANGE));
			}
		}
		
		/*private function propertyChangedHandler(event:PropertyChangedEvent):void {
			if (event.origin is Element) {
				if (event.propery == "isVisible") {
					if (allElementsAreInvisible()) {	//if the concept is visible but the elements are not
						if (isVisible) {	//so it is not because of an invisible concept!!
							_canBeChanged = false;
						}else {
							//_canBeChanged = true;
						}
					}else {
						_canBeChanged = true;
					}
				}
			}
		}*/
		
		private function elementVChangeHandler(event:Event):void {
			if (allElementsAreInvisible()) {	//if the concept is visible but the elements are not
				if (isVisible) {	//so it is not because of an invisible concept!!
					_canBeChanged = false;
				}else {
					//_canBeChanged = true;
				}
			}else {
				_canBeChanged = true;
			}
		}
		
		/*private function elementNewRestrictionChange(event:PropertyChangedEvent):void {
			trace("newRestriction " + this.id);
			if (!isVisible) {
				_canBeChanged = false;
			}
		}*/
		
		private function allElementsAreInvisible():Boolean {
			var tempNum:int = 0;
			for each(var e:Element in _elements) {
				if (e.isVisible) {
					tempNum++;
				}
			}
			numVisibleElements = tempNum;
			
			if (numVisibleElements == 0) {
				return true;
			}else {
				return false;
			}
			
		}
		
		private function app(): Main {
			return Application.application as Main;
		}
	}
	
}