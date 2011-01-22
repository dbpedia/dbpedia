/**
 * Copyright (C) 2009 Philipp Heim, Sebastian Hellmann, Jens Lehmann, Steffen Lohmann and Timo Stegemann
 * 
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses/>.
 */ 
package popup 
{
	import flash.events.Event;
	
	/**
	 * ...
	 * @author Timo Stegemann
	 */
	public class InputSelectionEvent extends Event 
	{
		
		public static const INPUTSELECTION:String = "InputSelectionEvent";
		
		public var autoCompleteIndex:int;
		public var selectedItem:Object;
		
		public function InputSelectionEvent(type:String, autoCompleteIndex:int, selectedItem:Object, bubbles:Boolean=false, cancelable:Boolean=false) 
		{ 
			this.autoCompleteIndex = autoCompleteIndex;
			this.selectedItem = selectedItem;
			
			super(type, bubbles, cancelable);
		} 
		
		public override function clone():Event 
		{ 
			return new InputSelectionEvent(type, autoCompleteIndex, selectedItem, bubbles, cancelable);
		} 
		
		public override function toString():String 
		{ 
			return formatToString("InputSelectionEvent", "type", "bubbles", "cancelable", "eventPhase"); 
		}
		
	}
	
}