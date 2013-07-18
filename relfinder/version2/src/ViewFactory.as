/**
 * Copyright (C) 2009 Philipp Heim, Sebastian Hellmann, Jens Lehmann, Steffen Lohmann and Timo Stegemann
 * 
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses/>.
 */ 

package {

	import com.adobe.flex.extras.controls.springgraph.Graph;
	import graphElements.*;
	import mx.core.UIComponent;
	import com.adobe.flex.extras.controls.springgraph.IViewFactory;
	import com.adobe.flex.extras.controls.springgraph.Item;
	
	/** The object that knows how to create views that correspond to a given Item. 
	 * We recognize these types:
	 * - for Items of type Word, we create a WordView
	 * - for Items of type Meaning, we create a MeaningView
	 */
	public class ViewFactory implements IViewFactory {
		
		public function ViewFactory() {
			
		}
		
		public function getView(item:Item):UIComponent
		{
			//trace("getView from the viewFactory for item: "+item.id);
			if (item is RelationNode) {
				return new graphElements.RelationNodeView();
			}/*else if (item is ElementNode) {
				return new graphElements.ElementNodeView();
			}*/else if (item is GivenNode) {
				return new graphElements.GivenNodeView();
			}else if (item is FoundNode) {
				return new graphElements.FoundNodeView();
			}
			trace("return null");
			return null;
		}
		
	}
	
}
