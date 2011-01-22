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

	public class PathLength extends EventDispatcher 
	{
		private var _id:String;
		private var _label:String;
		private var _num:int = -1;
		private var _isVisible:Boolean = true;
		private var _canBeChanged:Boolean = true;	//whether the visibility of the concept can be changed! Or the change of the visibility has any effect on the graph!
		
		private var _paths:ArrayCollection = new ArrayCollection();
		private var _numVisiblePaths:int = 0;
		private var _stringNumOfPaths:String = "";
		
		public static var VCHANGE:String = "isVisibleChange";
		public static var NUMVPCHANGE:String = "numberOfVisiblePathsChange";
		
		public function PathLength(_id:String, _num:int) {
			this._id = _id;
			this._label = _num.toString();
			/*if (_num == 1) {
				this._label += " node in between";
			}else {
				this._label += " nodes in between";
			}*/
			this._num = _num;
		}
		
		public function removeListener():void {
			for each(var p:Path in _paths) {
				p.removeEventListener(Path.VCHANGE, pathVChangeHandler);
			}
		}
		
		public function get id():String {
			return _id;
		}
		
		public function get label():String {
			return _label;
		}
		
		public function get num():int {
			return _num;
		}
		
		public function get canBeChanged():Boolean {
			return _canBeChanged;
		}
		
		[Bindable(event=PathLength.VCHANGE)]
		public function get stringNumOfPaths():String {
			return _stringNumOfPaths;
		}
		
		//[Bindable(event=PathLength.VCHANGE)]
		public function get isVisible():Boolean {
			return _isVisible;
		}
		
		public function set isVisible(b:Boolean):void {
			/*if (app().delayedDrawing) {
				app().delayedDrawing = false;
			}*/
			//trace("set PathLenght isVisible");
			if (_isVisible != b) {
				//trace("set pathLength("+id+") visible: "+b);
				_isVisible = b;
				var event:PropertyChangedEvent = new PropertyChangedEvent(PathLength.VCHANGE, this, "isVisible");
				dispatchEvent(event);
			}
		}
		
		[Bindable(event=PathLength.NUMVECHANGE)]
		public function get numVisiblePaths():int {
			return this._numVisiblePaths;
		}
		
		public function set numVisiblePaths(n:int):void {
			if (this._numVisiblePaths != n) {
				this._numVisiblePaths = n;
				this._stringNumOfPaths = this._numVisiblePaths.toString() + "/" + this._paths.length.toString();
				dispatchEvent(new Event(PathLength.NUMVPCHANGE));
			}
		}
		
		public function addPath(p:Path):void {
			if (!_paths.contains(p)) {
				this._paths.addItem(p);
				//trace("add path "+p.isVisible);
				if (p.isVisible) {
					numVisiblePaths = _numVisiblePaths + 1;
				}
				p.addEventListener(Path.VCHANGE, pathVChangeHandler);
				
			}
		}
		
		private function pathVChangeHandler(event:Event):void {
			if (allPathsAreInvisible()) {	//if the pathLength is visible but the paths are not
				if (isVisible) {	//so it is not because of an invisible pathLength!!
					_canBeChanged = false;
				}else {
					//_canBeChanged = true;
				}
			}else {
				_canBeChanged = true;
			}
		}
		
		private function allPathsAreInvisible():Boolean {
			var tempNum:int = 0;
			for each(var p:Path in _paths) {
				if (p.isVisible) {
					tempNum++;
				}
			}
			numVisiblePaths = tempNum;
			
			if (numVisiblePaths == 0) {
				return true;
			}else {
				return false;
			}
			
		}
	}
	
	
	
}