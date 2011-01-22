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
	import flash.events.EventDispatcher;
	import flash.events.Event;
	import mx.core.Application;
	import graphElements.events.PropertyChangedEvent;
	
	public class Relation extends EventDispatcher{
		public var id:String;
		public var subject:Element;
		public var predicate:Element;
		public var object:Element;
		
		public static var VCHANGE:String = "isVisibleChange";
		//public static var NEWRCHANGE:String = "newRestrictionChange";
		
		private var _paths:Array = new Array();
		private var _isVisible:Boolean = false;
		
		private var _relType:RelType = null;
		
		//private var _newRestriction:PropertyChangedEvent = null;
		
		public function Relation(_id:String, _sub:Element, _pred:Element, _obj:Element, rT:RelType = null){
			this.id = _id;
			this.subject = _sub;
			this.predicate = _pred;
			this.object = _obj;
			if (rT != null) {
				relType = rT;
				relType.addRelation(this);
			}
			
			this.subject.addRelation(this);
			this.object.addRelation(this);
			
			this.subject.addEventListener(Element.CONCEPTCHANGE, conceptChangeHandler);
			this.object.addEventListener(Element.CONCEPTCHANGE, conceptChangeHandler);
			//this.subject.addEventListener(Element.VCHANGE, elementVChangeHandler);
			//this.object.addEventListener(Element.VCHANGE, elementVChangeHandler);
		}
		
		public function removeListener():void {
			this.subject.removeEventListener(Element.CONCEPTCHANGE, conceptChangeHandler);
			this.object.removeEventListener(Element.CONCEPTCHANGE, conceptChangeHandler);
			for each(var p:Path in _paths) {
				p.removeEventListener(Path.VCHANGE, pathVChangeHandler);
				p.removeEventListener(Path.RCHANGE, pathInRangeChangeHandler);
			}
			this._relType.removeEventListener(RelType.VCHANGE, relTypeVChangeHandler);
		}
		
		public function get paths():Array {
			return _paths;
		}
		
		public function addPath(p:Path):void {
			if (paths.indexOf(p) == -1) {
				paths.push(p);
				//trace("addeventlistener");
				//p.addEventListener(PropertyChangedEvent.PROPERTY_CHANGED, propertyChangedHandler);
				p.addEventListener(Path.VCHANGE, pathVChangeHandler);
				p.addEventListener(Path.RCHANGE, pathInRangeChangeHandler);
			}
		}
		
		[Bindable(event=Relation.VCHANGE)]
		public function get isVisible():Boolean {
			return _isVisible;
		}
		
		public function set isVisible(b:Boolean):void {
			if (_isVisible != b) {
				//trace("set relation("+id+") visibile: " + b);
				_isVisible = b;
				//dispatchEvent(new Event(Relation.VCHANGE));
				dispatchEvent(new PropertyChangedEvent(Relation.VCHANGE, this, "isVisible"));
				
				if (!_isVisible) {
					//trace("hide relationNode :" + id);
					app().hideNode(app().getRelationNode(id, this));
				}else {
					//wird über path gesteuert!
				}
				//trace("dispatch event");
				
			}
		}
		
		public function get relType():RelType {
			return this._relType;
		}
		
		public function set relType(rT:RelType):void {
			if (this._relType != rT) {
				if (this._relType != null) {
					this._relType.removeEventListener(RelType.VCHANGE, relTypeVChangeHandler);
				}
				this._relType = rT;
				this._relType.addEventListener(RelType.VCHANGE, relTypeVChangeHandler);
			}
		}
		
		private function relTypeVChangeHandler(event:Event):void {
			trace("relTypeVChange: in Relation");
			if (_relType.isVisible) {
				if (((subject.concept == null) || subject.concept.isVisible) && ((object.concept == null) || object.concept.isVisible)) {	//if both concepts are visible or null
					this.isVisible = true;	//todo path visible setzen?!
				}
			}/*else {	//is handled in path class
				if (this.isVisible) {
					//set all paths that are running via this relation invisible
					
					isVisible = false;
				}
			}*/
		}
		
		private function pathVChangeHandler(event:Event):void {
			//trace("pathVchange "+id+", rel: "+id);
			if (_isVisible) {
				if (!onePathIsVisible()) {	//if all paths are invisible
					isVisible = false;
				}
			}else {
				if ((event.target as Path).isVisible) {	//this is the first visible path!
					//trace("set relation visible: " + id);
					isVisible = true;
				}else {
					
				}
			}
			
		}
		
		private function pathInRangeChangeHandler(event:PropertyChangedEvent):void {
			var p:Path = event.origin as Path;
			//trace("pathRangeChange: "+p.inRange);
			if (p.inRange) {
				if (((subject.concept == null) || subject.concept.isVisible) && ((object.concept == null) || object.concept.isVisible)) {	//if both concepts are visible or null
					this.isVisible = true;
				}else {	//if at least one concept is invisible
					//trace("subject or object have invisible concepts");
				}
			}else {
				if (_isVisible) {
					if (!onePathInRange()) {	//if all paths are not in range
						isVisible = false;
						
					}
				}else {	//if invisible and path not in range! -> new restriction
					//TEST
					//newRestriction = event;
					/*if (!onePathInRange()) {	//if all paths are not in range
						isVisible = false;
						
					}*/
					
				}
			}
		}
		
		/*public function set newRestriction(e:PropertyChangedEvent):void {
			if (_newRestriction != e) {
				_newRestriction = e;
				dispatchEvent(new PropertyChangedEvent(Relation.NEWRCHANGE, this, "newRestriction"));
			}
		}*/
		
		/*public function get newRestriction():PropertyChangedEvent {
			return _newRestriction;
		}*/
		
		/*private function propertyChangedHandler(event:PropertyChangedEvent):void {
			if (event.origin is Concept) {
				if (event.propery == "isVisible") {
					if (bothConceptsAreVisible()) {
						//trace("both concepts are visible");
						if (onePathInRange()) {	//if at least on of the paths is in the current range
							this.isVisible = true;
						}else {
							//trace("paths not in range");
						}
					}else { //one concept has become invisible
						this.isVisible = false;
						
						
					}
				}
			}else if (event.origin is Path) {
				if (event.propery == "inRange") {
					var p:Path = event.origin as Path;
					//trace("pathRangeChange: "+p.inRange);
					if (p.inRange) {
						if (((subject.concept == null) || subject.concept.isVisible) && ((object.concept == null) || object.concept.isVisible)) {	//if both concepts are visible or null
							this.isVisible = true;
						}else {	//if at least one concept is invisible
							//trace("subject or object have invisible concepts");
						}
					}else {
						if (_isVisible) {
							if (!onePathInRange()) {	//if all paths are not in range
								isVisible = false;
								
							}
						}else {	//if invisible
							
							
						}
					}
				}else if (event.propery == "isVisible") {
					//trace("pathVchange "+id+", rel: "+id);
					if (_isVisible) {
						if (!onePathIsVisible()) {	//if all paths are invisible
							isVisible = false;
						}
					}else {
						if ((event.target as Path).isVisible) {	//this is the first visible path!
							//trace("set relation visible: " + id);
							isVisible = true;
						}
					}
				}
			}else if (event.origin is Element) {
				if (event.propery == "concept") {	// if a concept has been set
					var e:Element = event.origin as Element;
					e.concept.addEventListener(PropertyChangedEvent.PROPERTY_CHANGED, propertyChangedHandler);
				}
			}
		}*/
		
		
		
		private function conceptChangeHandler(event:PropertyChangedEvent):void {
			var e:Element = event.origin as Element;
			e.concept.addEventListener(Concept.VCHANGE, conceptVChangeHandler);
		}
		
		private function conceptVChangeHandler(event:Event):void {
			if (bothConceptsAreVisible()) {
				//trace("both concepts are visible");
				if (onePathInRange()) {	//if at least on of the paths is in the current range
					this.isVisible = true;
				}else {
					//trace("paths not in range "+this.id);
				}
			}else { //one concept has become invisible
				//trace("one of the concepts is still invisible! "+id);
				this.isVisible = false;
				
				
			}
		}
		
		public function bothConceptsAreVisible():Boolean {
			if (((object.concept == null) || object.concept.isVisible) && ((subject.concept == null) || this.subject.concept.isVisible)) {
				return true;
			}else {
				return false;
			}
		}
		
		private function elementVChangeHandler(event:Event):void {
			var e:Element = event.target as Element;
			//trace(("elementVChange " + e.id + ", v: " + e.isVisible);
			if (e.isVisible) {	//the element has become visible
				//TODO: testen, ob diese relation visible werden muss!
				if (onePathIsVisible()) {	//at least one path over this relation is visible
					//trace(("one path is visible");
					if (e.id == this.object.id) {
						if (this.subject.isVisible) {
							//isVisible = true;
							
							//app().drawRelation(this);
						}
					}else {
						if (this.object.isVisible) {
							//isVisible = true;
							
							//app().drawRelation(this);
						}
					}
				}
			}else {
				this.isVisible = false;
				app().hideNode(app().getRelationNode(id, this));
			}
		}
		
		private function onePathIsVisible():Boolean {
			for each(var p:Path in paths) {
				//trace(("path: " + p.id + " v: " + p.isVisible);
				if (p.isVisible) {
					return true;
				}
			}
			//trace(("all paths are invisible!");
			return false;
		}
		
		private function onePathInRange():Boolean {
			for each(var p:Path in paths) {
				//trace(("path: " + p.id + " v: " + p.isVisible);
				if (p.inRange) {
					return true;
				}
			}
			//trace(("all paths are invisible!");
			return false;
		}
		
		private function app(): Main {
			return Application.application as Main;
		}
	}
	
}