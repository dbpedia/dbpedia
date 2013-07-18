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
	import flash.events.Event;
	import flash.events.EventDispatcher;
	import graphElements.events.PropertyChangedEvent;
	import mx.collections.ArrayCollection;
	
	public class RelType extends EventDispatcher
	{
		private var _id:String;
		private var _label:String;
		private var _isVisible:Boolean = true;
		private var _canBeChanged:Boolean = true;	//whether the visibility of the concept can be changed! Or the change of the visibility has any effect on the graph!
		
		private var _relations:ArrayCollection = new ArrayCollection();
		private var _numVisibleRelations:int = 0;
		private var _stringNumOfRelations:String = "";
		
		public static var VCHANGE:String = "isVisibleChange";
		public static var NUMVRCHANGE:String = "numberOfVisibleRelationsChange";
		
		public function RelType(_id:String, _label:String) {
			this._id = _id;
			this._label = _label;
		}
		
		public function removeListener():void {
			for each(var r:Relation in _relations) {
				r.removeEventListener(Relation.VCHANGE, relationVChangeHandler);
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
		
		[Bindable(event=RelType.VCHANGE)]
		public function get isVisible():Boolean {
			return _isVisible;
		}
		
		public function set isVisible(b:Boolean):void {
			/*if (app().delayedDrawing) {
				app().delayedDrawing = false;
			}*/
			if (_isVisible != b) {
				trace("set relType("+id+") visible: "+b);
				_isVisible = b;
				var event:PropertyChangedEvent = new PropertyChangedEvent(RelType.VCHANGE, this, "isVisible");
				dispatchEvent(event);
			}
		}
		
		[Bindable(event = RelType.NUMVRCHANGE)]
		public function get stringNumOfRelations():String {
			return this._stringNumOfRelations;
		}
		
		//[Bindable(event=RelType.NUMVRCHANGE)]
		public function get numVisibleRelations():int {
			return this._numVisibleRelations;
		}
		
		public function set numVisibleRelations(n:int):void {
			if (this._numVisibleRelations != n) {
				this._numVisibleRelations = n;
				this._stringNumOfRelations = this._numVisibleRelations.toString() + "/" + this._relations.length.toString();
				dispatchEvent(new Event(RelType.NUMVRCHANGE));
			}
		}
		
		public function addRelation(r:Relation):void {
			if (!_relations.contains(r)) {
				this._relations.addItem(r);
				if (r.isVisible) {
					numVisibleRelations = _numVisibleRelations + 1;
				}
				r.addEventListener(Relation.VCHANGE, relationVChangeHandler);
				
			}
		}
		
		private function relationVChangeHandler(event:Event):void {
			if (allRelationsAreInvisible()) {	//if the relType is visible but the relations are not
				if (isVisible) {	//so it is not because of an invisible reltype!!
					_canBeChanged = false;
				}else {
					//_canBeChanged = true;
				}
			}else {
				_canBeChanged = true;
			}
		}
		
		private function allRelationsAreInvisible():Boolean {
			var tempNum:int = 0;
			for each(var r:Relation in _relations) {
				if (r.isVisible) {
					tempNum++;
				}
			}
			numVisibleRelations = tempNum;
			
			if (numVisibleRelations == 0) {
				return true;
			}else {
				return false;
			}
			
		}
	}
	
}