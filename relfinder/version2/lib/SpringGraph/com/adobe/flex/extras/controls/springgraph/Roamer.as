////////////////////////////////////////////////////////////////////////////////
//
//  Copyright (C) 2006 Adobe Macromedia Software LLC and its licensors.
//  All Rights Reserved. The following is Source Code and is subject to all
//  restrictions on such code as contained in the End User License Agreement
//  accompanying this product.
//
////////////////////////////////////////////////////////////////////////////////

package com.adobe.flex.extras.controls.springgraph {
	import flash.events.Event;
	import flash.events.TimerEvent;
	import flash.geom.Rectangle;

	/**
	 *  Dispatched when there is any change to the nodes and/or links of this graph.
	 *
	 *  @eventType flash.events.Event
	 */
	[Event(name="change", type="flash.events.Event")]

	/**
	 * An extension to SpringGraph that restricts the visible items to a subset of the full graph.
	 * You can control which items are currently visible by using the <code>itemLimit</code>, 
	 * <code>maxDistanceFromCurrent</code>, and <code>currentItem properties</code>.
	 */ 
	public class Roamer extends SpringGraph {
		
		[Bindable]
		/**
		 * The maximum number of items that are visible at any time.
		 */
		public function get itemLimit(): int {
			return _itemLimit;
		}
		
		/**
		 * PHILIPP HEIM 2.April 08
		 */
		public function updateGraph():void {
			recreateGraph();
		}
		
		/**
		 * PHILIPP HEIM 29.April 08
		 */
		public function removeFromHistory(_item:Item):void {
			trace("remove item from history: " + _item.id);
			//FlashConnect.trace("test" + allCurrentItems);
			//FlashConnect.trace("test2 "+allCurrentItems.length);
			var i1:int = allCurrentItems.indexOf(_item);
			//FlashConnect.trace("test25");
			if (i1 > -1) {
				allCurrentItems.splice(i1, 1);
				//FlashConnect.trace("test255");
			}
			//FlashConnect.trace("test26");
			for (var i:int = 0; i < allCurrentItems.length; i++) {
				var item:Item = allCurrentItems[i];
				//FlashConnect.trace("test27");
				//FlashConnect.trace("item in allCurrentItems: "+item.id);
			}
			
			
			//FlashConnect.trace("test3"+_history);
			//allCurrentItems = new Array();
			
			var i2:int = _history.indexOf(_item);
			//trace("index 2: " + i2);
			if (i2 > -1) _history.splice(i2, 1);
			
			for each(var i3:Item in _history) {
				//FlashConnect.trace("item in history: "+i3.id);
			}
			
			dispatchEvent(new Event("historyChange"));
		}
		
		public function set itemLimit(i: int): void {
			_itemLimit = i;
			recreateGraph();
		}
		
		/**
		 * We only display items that are within this distance from the current item.
		 */
		public function set maxDistanceFromCurrent(i: int): void {
			_maxDistanceFromCurrent = i;
			recreateGraph();
		}
		
		public function get maxDistanceFromCurrent(): int {
			return _maxDistanceFromCurrent;
		}
	
		/**
		 * The item that current acts as the 'center' or 'root' of the graph. 
		 * This item defines the subset of the graph that will be visible.
		 */
		public function set currentItem(item: Item): void {
			//FlashConnect.trace("setCurrentItem");
			newCurrentItem(item);
			//FlashConnect.trace("recreate graph");
			recreateGraph();
			//FlashConnect.trace("test");
			//trace("after recreateGraph");
			dispatchEvent(new Event("currentItemChange"));
		}

		[Bindable("currentItemChange")]
		public function get currentItem(): Item {
			return _currentItem;
		}

		/** Find out if a given item has ever been the currentItem.
		 * @param item an Item that is contained in this graph
		 * @returns true if the indicated item has ever been the currentItem of this graph.
		 */
		public function hasBeenCurrentItem(item: Item): Boolean {
			return allCurrentItems.hasOwnProperty(item.id);
		}
		
		[Bindable("dataProviderChange")]
		/**
		 * Defines the data model for this springgraph. See SpringGraph.dataProvider
		 * for more information. */
		override public function get dataProvider(): Object {
			return fullGraph;
		}

		/** sets the data provider and chooses the initial currentItem.
		 */
		public function setDataProvider(dp: XML, currentId: String): void {
			var g: Graph = Graph.fromXML(dp, _xmlNames);
			g.distinguishedItem = g.find(currentId);
			doSetDataProvider(g);
		}

		override public function set dataProvider(obj: Object): void {
			if(obj is XML)
				obj = Graph.fromXML(obj as XML, _xmlNames);
			doSetDataProvider(obj as Graph);
		}
		
		/** Calulate the distance between 2 items. Currently this is a fast-but-cheezy 
		 * calculation that returns 0 (if the 2 items are the same), 1 (if the 2 items
		 * are linked), or 99 otherwise. 
		 * @return the distance between the two items. 
		 */
		public function distance(fromItem: Item, toItem: Item): int {
			if(fromItem == toItem)
				return 0;
			if(arrayIncludes(fullGraph.neighbors(fromItem.id), toItem))
				return 1;
			return 99;
		}

		/** The total number of items in the dataProvider.
		 */
		public function get fullNodeCount(): int {
			if(fullGraph != null)
				return fullGraph.nodeCount;
			return 0;
		}
		
		/** The number of items in the dataProvider that are currently visible.
		 */
		public function get visibleNodeCount(): int {
			if(_graph != null)
				return _graph.nodeCount;
			return 0;
		}
		
		/** An array of items that will not be displayed, even if they 
		 * are chosen to be visible by the Roamer's other computations.
		 */
		private var forceInvisible: Array = null;
		
		/** An array of items that will be displayed on the graph, even if they 
		 * are not chosen to be visible by the Roamer's other computations.
		 */
		private var forceVisible: Array = null;
		
		/** Call this function after modifying the forceInvisible or forceVisible properties */
		private function recreate(): void {
			recreateGraph();
		}

		[Bindable("showHistoryChange")]
		/** If true, then all items that have been the 'current item' are made visible.
		 * 
		 * */
		public function get showHistory(): Boolean {
			trace("showHistory " + _showHistory);
			return _showHistory;
		}
		
		public function set showHistory(show: Boolean): void {
			//this.forceVisible = show ? history : null;
			dispatchEvent(new Event("showHistoryChange"));
			_showHistory = show;
			var temp: Item = _currentItem;
			_currentItem = null;
			recreate();
			_currentItem = temp;
			recreate();
		}
		
		/** Forget which items have been the current item. This will affect the history
		 * used by 'showHistory' and 'hasBeenCurrentItem'.
		 * 
		 * */
		public function resetHistory(): void {
			allCurrentItems = new Array();
			_history = new Array();
			dispatchEvent(new Event("historyChange"));
		}
		
		/** Force the item to be shown, even if it is outside the limits set by 
		 * 'maxDistanceFromCurrent'. 
		 * */
		public function showItem(item: Item): void {
			if(forceVisible == null)
				forceVisible = [];
			forceVisible[item.id] = item;
			recreate();
		}
		
		/** Force the item to not be shown, even if it is inside the limits set by 
		 * 'maxDistanceFromCurrent'. 
		 * */
		public function hideItem(item: Item): void {
			if(forceInvisible == null)
				forceInvisible = [];
			forceInvisible[item.id] = item;
			//trace("force invisible item: " + item.id);
			recreate();
		}
		
		/** Cancels the effect of any prior calls to hideItem and/or showItem.
		 * */
		public function resetShowHide(): void {
			forceVisible = null;
			forceInvisible = null;
			recreate();
		}
		
		/** Sets the currentItem to the previous history Item. "historyIndex" will be decremented.
		 *  Has no effect if historyIndex is already 0. */
		public function back(): void {
			if (historyCurrentlyViewed > 0) {
				historyCurrentlyViewed--;
				_currentItem = _history[historyCurrentlyViewed];
				recreateGraph();
				dispatchEvent(new Event("currentItemChange"));
			}
		}
				
		/** Sets the currentItem to the next history Item. "historyIndex" will be incremented.
		 *  Has no effect if historyIndex is already the highest possible index in "history". */
		public function forward(): void {
			if(historyCurrentlyViewed < (_history.length - 1)) {
				historyCurrentlyViewed++;
				_currentItem = _history[historyCurrentlyViewed];
				recreateGraph();
				dispatchEvent(new Event("currentItemChange"));
			}
		}
		
		[Bindable("currentItemChange")]
		/** Whether the back() function will have any effect at the moment. */
		public function get backOK(): Boolean {
			return (historyCurrentlyViewed > 0);
		}
		
		[Bindable("currentItemChange")]
		/** Whether the forward() function will have any effect at the moment */
		public function get forwardOK(): Boolean {
			return (historyCurrentlyViewed < (_history.length - 1));
		}
		
		[Bindable("historyChange")]
		/** An ordered list of all the items that been the current item. */
		public function get history(): Array {
			trace("history Change: " + _history);
			return _history;
		}
		
		[Bindable("currentItemChange")]
		/** The index into "history" that corresponds to the currentItem.
		 * Can be any number between 0 and the index of the most recent history entry.
		 * Usually this is simply the index of the most recent history entry. 
		 * However, if you've used back() or forward() then this index will vary. You can also 
		 * set this index to any valid value; this will cause currentItem to become
		 * the corresponding history item.
		  */
		public function get historyIndex(): int {
			return historyCurrentlyViewed;
		}

		public function set historyIndex(i: int): void {
			if ((i >= 0) && (i < _history.length)) {
				historyCurrentlyViewed = i;
				_currentItem = _history[historyCurrentlyViewed];
				recreateGraph();
				dispatchEvent(new Event("currentItemChange"));
			}
		}
		
		[Bindable("tidyHistoryChange")]
		/** Keeps all visibleHistoryItems clustered together. If this is set to false,
		 * history items tend to form a long chain that makes the autoFit mode tend
		 * to shrink the repulsionFactor excessively. */
		public function get tidyHistory(): Boolean{
			return _tidyHistory;
		}

		public function set tidyHistory(b: Boolean): void {
			_tidyHistory = b;
			recreateGraph();
			dispatchEvent(new Event("tidyHistoryChange"));
		}
		
		[Bindable("visibleHistoryItemsChange")]
		/** A list of all the items that are currently visible only 
		 * because showHistory is enabled. In other words this list 
		 * is the entire history minus the items that are currently visible anyway. */
		public function get visibleHistoryItems(): Object{
			return _visibleHistoryItems;
		}
		private function set visibleHistoryItems(o: Object): void { }
		
		///// -------- private ------------

		private function doSetDataProvider(g: Graph): void {
			empty();
			setFullGraph(g);
			dispatchEvent(new Event("dataProviderChange"));
		}
		
		private function setFullGraph(g: Graph): void {
			fullGraph = g;
			resetHistory();
			newCurrentItem(g.distinguishedItem);
			dispatchEvent(new Event("currentItemChange"));
			recreateGraph();
		}
	
		private function addNodes(newNodes: Array, g: Graph): Object {
			var newItems: Object = [];
			var item: Item;
			var i: int;
			var id: String;
			//FlashConnect.trace("ttest2");
			for (id in newNodes) {
				if (!g.hasNode(id)) {
					//FlashConnect.trace("ttest25");
					item = fullGraph.find(id);
					//FlashConnect.trace("ttest3"+item);
					if (item != null) {
						g.add(item);
						newItems[item.id] = item;
					}else {
						//FlashConnect.trace("item is null " + id);
					}
					
				}
			}
			
			for (id in newItems) {
				item = newItems[id] as Item;
				var neighbors: Object = fullGraph.neighbors(item.id);
				for(var neighborId: String in neighbors) {
					if (g.hasNode(neighborId)) {
						var neighbor: Item = g.find(neighborId);
						g.link(item, neighbor, fullGraph.getLinkData(item, neighbor));	/* UPDATE Philipp Heim 27.2.08 */
					}
				}
			}
			return newItems;
		}
		
		private function recreateGraph(): void {
			var g: Graph = new Graph();
			if(_currentItem != null) {
				itemCount = 0;
				//FlashConnect.trace("addtoGraph currentItem: "+_currentItem.id);
				addToGraph(_currentItem, 1, g);
			}else {
				//FlashConnect.trace("current item is null!!!! recreateGraph");
				//trace("currentItme == null");
			}
			//trace("test2: "+allCurrentItems.length);
			
			_visibleHistoryItems = null;
			if (_showHistory) {
				//FlashConnect.trace("ttest");
				_visibleHistoryItems = addNodes(allCurrentItems, g);
			}
			
			
			if (forceVisible != null) {
				addNodes(forceVisible, g);
			}
			//trace("recreateGraph, test "+forceInvisible);
			if (forceInvisible != null) {
				//trace("forceInsvisible.length: " + forceInvisible.length);
				for each(var item:Item in forceInvisible) {
					g.remove(item);
					//trace("item :" + item.id + " removed");
				}
				/*for (var i: Number = 0; i < forceInvisible.length; i++) {
					var item: Item = forceInvisible[i] as Item;
					g.remove(item);
					trace("item :" + item.id + " removed");
				}*/
			}
			if(_tidyHistory)
				doTidyHistory(_visibleHistoryItems, g);
			
			
			super.dataProvider = g;
			dispatchEvent(new Event("change"));
			dispatchEvent(new Event("visibleHistoryItemsChange"));
		}

		private static var historySeed: Item = new HistorySeed();
		
		private function doTidyHistory(addedItems: Object, g: Graph): void {
			if(addedItems == null) return;
			
			var historyItemAdded: Boolean = false;
			var historyItemLinks: int = 0;
			
			for (var id: String in addedItems) {
				var addedItem: Item = addedItems[id] as Item;
				var neighbors: Object = g.neighbors(id);
				var connectedToGraph: Boolean = false;
				for(var neighborID: String in neighbors) {
					if(!addedItems.hasOwnProperty(neighborID)) {
						connectedToGraph = true;
						break;
					}
				}

				if(!connectedToGraph) {
					if(!historyItemAdded) {
						g.add(historySeed);
						historyItemAdded = true;
					}
					g.link(addedItem, historySeed, {settings: {alpha: 0, color: 0x0000dd, thickness: 0}});
					historyItemLinks++;
				}
			}
			
			//if(historyItemAdded && (historyItemLinks < 2)) {
			//	g.remove(historySeed);
			//}
		}
		
		private function arrayIncludes(list: Object, item: Object): Boolean {
			var result: Boolean = list.hasOwnProperty(item.id);
			return result;
		}

		private function addToGraph(item: Item, generation: int, graph: Graph): Boolean {
			//FlashConnect.trace("in addToGraph");
			if(itemCount > _itemLimit) return false;
			itemCount ++;
			graph.add(item);
			if(generation < _maxDistanceFromCurrent) {
				var neighbors: Object = fullGraph.neighbors(item.id);
				for(var neighborId: String in neighbors) {
					var neighbor: Item = fullGraph.find(neighborId)
					if(!addToGraph(neighbor, generation + 1, graph))
						return true;
					graph.link(item, neighbor, fullGraph.getLinkData(item, neighbor));	/* UPDATE PHILIPP HEIM 26.2.08 */
				}
			}
			return true;
		}			
		
		private function newCurrentItem(item: Item): void {
			trace("new current Item: " + item.id);
			//FlashConnect.trace("new current item");
			//trace("newcurrentitem");
			_currentItem = item;
			if(item != null) {
				allCurrentItems[item.id] = true;
				//if(historyCurrentlyViewed < (_history.length - 1)) {
				//	var temp: int = historyCurrentlyViewed + 1;
				//	_history.splice(temp);
				//}
				_history.push(item);
				historyCurrentlyViewed = _history.length - 1;
				trace("dispatchEvent");
				dispatchEvent(new Event("historyChange"));
			}
		}

		private var _currentItem: Item;
		private var _itemLimit: int = 50;
		private var fullGraph: Graph;
		private var _maxDistanceFromCurrent: int;		
		private var itemCount: int;
		private var allCurrentItems: Array = new Array();
		private var _showHistory: Boolean = false;	
		private var _history: Array = new Array();
		private var historyCurrentlyViewed: int = -1;
		private var _visibleHistoryItems: Object = null;		
		private var _tidyHistory: Boolean = true;
	}
}
