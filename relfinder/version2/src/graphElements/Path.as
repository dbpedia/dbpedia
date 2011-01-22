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
	import graphElements.events.PropertyChangedEvent;
	import mx.collections.ArrayCollection;
	import flash.events.Event;
	import flash.events.EventDispatcher;
	import mx.core.Application;

	public class Path extends EventDispatcher{
		private var _id:String;
		private var _relations:ArrayCollection = new ArrayCollection();
		private var _isVisible:Boolean = false;
		private var _inRange:Boolean = false;
		private var _allRelsVisible:Boolean = false;
		
		//private var _pathLength:int = -1;	//to count only the instances in between and not the relations
		private var _layout:Object = new Object();
		private var _isHighlighted:Boolean = false;
		
		public static var VCHANGE:String = "isVisibleChange";
		public static var RCHANGE:String = "inRangeChange";
		public static var HCHANGE:String = "isHighlightedChange";
		
		private var _pathLength:PathLength = null;
		
		public function Path(id:String, rels:Array = null, pL:PathLength = null) {
			_id = id;
			_layout.settings = { alpha: 1, color: 0xcccccc, thickness: 1 };
			
			if (pL != null) {
				pathLength = pL;
				pathLength.addPath(this);
			}
			
			for each(var r:Relation in rels) {
				addRelation(r);
			}
			
			pathLength.dispatchEvent(new Event(PathLength.VCHANGE)); //to get the current state
			
			//this.addListener();
			//app().addEventListener(app().MINPLCHANGE, selectedPathLengthRangeChangeHandler );
			//app().addEventListener(app().HPATHSCHANGE, hPathsChangeHandler );
			
			//app().dispatchEvent(new Event(app().PLRCHANGE));	//to get the current state
			//app().dispatchEvent(new Event(app().MINPLCHANGE));
		}
		
		/*private function addListener():void {
			app().addEventListener(app().PLRCHANGE, selectedPathLengthRangeChangeHandler);
		}*/
		
		public function removeListener():void {
			this.pathLength.removeEventListener(PathLength.VCHANGE, pathLengthVChangeHandler);
			for each(var r:Relation in _relations) {
				r.removeEventListener(Relation.VCHANGE, relationIsVisibleChangeHandler);
				r.relType.removeEventListener(RelType.VCHANGE, relTypeIsVisibleChangeHandler);
			}
			
		}
		
		public function get id():String {
			return _id;
		}
		
		public function get layout():Object {
			return _layout;
		}
		
		public function get pathLength():PathLength {
			return _pathLength;
		}
		
		[Bindable(event=Path.VCHANGE)]
		public function get isVisible():Boolean {
			return _isVisible;
		}
		
		public function set isVisible(b:Boolean):void {
			if (_isVisible != b) {
				//trace("set path("+id+") visible: " + b);
				_isVisible = b;
				//dispatchEvent(new Event(Path.VCHANGE));
				dispatchEvent(new PropertyChangedEvent(Path.VCHANGE, this, "isVisible"));
				
				if (_isVisible) {
					app().drawPath(this);
				}
				//trace("dispatch event");
				
			}
		}
		
		public function get relations():ArrayCollection {
			return _relations;
		}
		
		public function set pathLength(p:PathLength):void {
			if (this._pathLength != p) {
				if (this._pathLength != null) {
					this._pathLength.removeEventListener(PathLength.VCHANGE, pathLengthVChangeHandler);
				}
				this._pathLength = p;
				this._pathLength.addEventListener(PathLength.VCHANGE, pathLengthVChangeHandler);
			}
		}
		
		private function pathLengthVChangeHandler(event:Event):void {
			//trace("vchangehandler pathlength: " + _pathLength.isVisible + ", this: " + this.isVisible);
			if (!_pathLength.isVisible) {
				inRange = false;	//TODO inRange is obsolete
				this.isVisible = false;
			}else {
				inRange = true;	//TODO inRange is obsolete
				if (this._allRelsVisible) {
					this.isVisible = true;
				}
			}
		}
		
		/*public function get pathLength():int {
			return _pathLength;
		}*/
		
		public function addRelation(r:Relation):void {
			//trace("addRelation: " + r.id + " , to path: " + id);
			_relations.addItem(r);
			r.addPath(this);
			r.addEventListener(Relation.VCHANGE, relationIsVisibleChangeHandler);
			r.relType.addEventListener(RelType.VCHANGE, relTypeIsVisibleChangeHandler);
			
			
			trace("dispatch event, relType.visible: " + r.relType.isVisible + ", " + r.relType.id);
			var event:PropertyChangedEvent = new PropertyChangedEvent(RelType.VCHANGE, r.relType, "isVisible");
			r.relType.dispatchEvent(event);
			//relType.dispatchEvent(new Event(RelType.VCHANGE)); //to get the current state
				
			//r.relType.dispatchEvent(new Event(RelType.VCHANGE));	//to get the current state
			//_pathLength++;
		}
		
		private function relTypeIsVisibleChangeHandler(event:Event):void {
			//trace("reltype v handler");
			if ((event.target as RelType).isVisible) {
				/*if (this.inRange && this._allRelsVisible) {	//is handled in relation class
					this.isVisible = true;
				}*/
			}else {
				//trace("is not visible");
				this.isVisible = false;
			}
		}
		
		[Bindable(event=Path.RCHANGE)]
		public function get inRange():Boolean {	//TODO inRange is obsolete
			return _inRange;
		}
		
		public function set inRange(b:Boolean):void { //TODO inRange is obsolete
			if (_inRange != b) {
				//trace("set inRange: " + b + " of path: " + id);
				_inRange = b;
				//trace("dispatch event");
				//dispatchEvent(new Event(Path.RCHANGE));
				dispatchEvent(new PropertyChangedEvent(Path.RCHANGE, this, "inRange"));	//ORIGIN1
			}
		}
		
		/*private function selectedPathLengthRangeChangeHandler(event:Event):void {	//TODO inRange is obsolete
			if ((this._pathLength.num < app().selectedMinPathLength) || (this._pathLength.num > app().selectedMaxPathLength)) {
				inRange = false;
				this.isVisible = false;
			}else {
				inRange = true;
				if (this._allRelsVisible) {
					this.isVisible = true;
				}
			}
		}*/
		
		/**
		 * Relation visibility change based possible change of visibility
		 * @param	event
		 */
		private function relationIsVisibleChangeHandler(event:Event):void {
			//trace("relationIsVChange");
			var allCV:Boolean = true;
			var allCRTV:Boolean = true; // all concepts and relTypes are visible
			for each(var r:Relation in this._relations) {
				if (!r.bothConceptsAreVisible()) {
					allCV = false;
					allCRTV = false;
					
					//trace("rel with concepts not visible: " + r.id);
					break;
				}
				if (!r.relType.isVisible) {
					allCRTV = false;
					break;
				}
			}
			this._allRelsVisible = allCRTV; //allCV;
			
			if (this.isVisible) {
				//trace("test isVisble ???");
				if (!this._allRelsVisible) {
					this.isVisible = false;
				}
			}else {	//this is invisible
				//trace("allRelsVis: " + _allRelsVisible);
				if (this._allRelsVisible && this._inRange) {
					this.isVisible = true;
				}
			}
			//trace("relation changed visiblity");
		}
		
		public function set isHighlighted(b:Boolean):void {
			//trace("set is highlighted " + b);
			if (b != _isHighlighted) {
				_isHighlighted = b;
				//dispatchEvent(new Event(Path.HCHANGE));
				dispatchEvent(new PropertyChangedEvent(Path.HCHANGE, this, "isHighlighted"));
				
				if (_isHighlighted) {
					_layout.settings = { alpha: 1, color: 0xFF0000, thickness: 2 };
					if(_isVisible)	app().drawPath(this, true);	//only if is visible
				}else {
					_layout.settings = { alpha: 1, color: 0xcccccc, thickness: 1 }; 
					if(_isVisible)	app().drawPath(this, true);	//only if is visible
				}
				
			}
		}
		
		[Bindable(event=Path.HCHANGE)]
		public function get isHighlighted():Boolean {
			return _isHighlighted;
		}
		
		/*private function hPathsChangeHandler(event:Event):void {
			if (app().highlightedPaths.contains(this) && !_isHighlighted) {	//has not been contained but is now!
				_isHighlighted = true;
				_layout.settings = { alpha: 1, color: 0xFF0000, thickness: 2 };
				app().drawPath(this);
			}else if(!app().highlightedPaths.contains(this) && _isHighlighted) {	//has been contained!
				_isHighlighted = false;
				_layout.settings = { alpha: 1, color: 0xcccccc, thickness: 1 }; 
				app().drawPath(this);
			}
		}*/
		
		private function app(): Main {
			return Application.application as Main;
		}
	}
	
}