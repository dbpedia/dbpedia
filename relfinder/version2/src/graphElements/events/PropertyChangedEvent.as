/**
 * Copyright (C) 2009 Philipp Heim, Sebastian Hellmann, Jens Lehmann, Steffen Lohmann and Timo Stegemann
 * 
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses/>.
 */ 

package graphElements.events {
	
	import flash.events.Event;
	
	public class PropertyChangedEvent extends Event {
		private var _origin:Object = null;
		private var _property:String = "";
		
		// Define static constant.
        public static const PROPERTY_CHANGED:String = "propertyChanged";
		
		// Public constructor. 
        public function PropertyChangedEvent(type:String, origin:Object, property:String) {
                // Call the constructor of the superclass.
                super(type);
                this._origin = origin;
				this._property = property;
        }
		
		public function get origin():Object {
			return _origin;
		}
		
		public function get propery():String {
			return _property;
		}
		
        // Override the inherited clone() method. 
        override public function clone():Event {
            return new PropertyChangedEvent(type, _origin, _property);
        }
		
	}
	
}