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
	public class ElementNode extends MyNode{	//OBSOLET!!!
		/*[Bindable]
		public var eLabel:String = "";*/
		public function ElementNode(_id:String, _label:String){
			super(_id, _label);
			//this.eLabel = _label;
		}
		
		/*public function hasBeenClicked():void {
			//app().setCurrentElement(this.element);
			//app().setCurrentItem(this);
		}
		
		public function forceXPos(_num:Number):void {
			if (this.node == null) {
				this.getNode();
			}
			if (this.node != null) {
				this.node.x = _num;
				//this.node.fixed = true;
				this.node.commit();
			}
		}
		
		public function forceYPos(_num:Number):void {
			if (this.node == null) {
				this.getNode();
			}
			if (this.node != null) {
				this.node.y = _num;
				//this.node.fixed = true;
				this.node.commit();
			}
		}
		
		public function pin():void {
			if (this.node == null) {
				this.getNode();
			}
			if (this.node != null) {
				//trace("fixed = true!");
				this.node.fixed = true;
				this.node.commit();
			}else {
				trace("this.node "+this.id+" == null");
			}
		}
		
		public function unpin():void {
			if (this.node != null) {
				this.node.fixed = false;
				this.node.commit();
			}
		}*/
		
	}
	
}