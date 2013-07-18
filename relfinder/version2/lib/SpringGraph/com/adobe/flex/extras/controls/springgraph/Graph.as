////////////////////////////////////////////////////////////////////////////////
//
//  Copyright (C) 2006 Adobe Macromedia Software LLC and its licensors.
//  All Rights Reserved. The following is Source Code and is subject to all
//  restrictions on such code as contained in the End User License Agreement
//  accompanying this product.
//
////////////////////////////////////////////////////////////////////////////////

package com.adobe.flex.extras.controls.springgraph
{
	import mx.controls.Alert;
	import flash.events.Event;
	import flash.events.EventDispatcher;
	import mx.controls.Button;
	import mx.controls.HSlider;
	
	/**
	 *  Dispatched when there is any change to the nodes and/or links of this graph.
	 *
	 *  @eventType flash.events.Event
	 */
	[Event(name="changed", type="flash.events.Event")]
	
	/**
	 *  A Graph is a collection of items that can be linked to each other.
	 * 
	  * @author   Mark Shepherd
	 */
 	public class Graph extends EventDispatcher
	{
		public static const CHANGE:String = "change";

		public function Graph(): void {
		}
		
		private var _nodes: Object = new Object(); // map of id -> Item
		private var _edges: Object = new Object(); // map of id -> (map of id -> 0)
		private var nodeArray: Array/*of Item*/; 
		private var edgeArray: Array/*of [Item, Item]*/;
		private var _distinguishedItem: Item;
		
	    /**
	     *  Creates a graph from XML. The XML you provide should contain 2 kinds of elements<br>
	     *  &lt;Node id="xxx" anything-else..../&gt;<br>
	     *  and<br>
	     *  &lt;Edge fromID="xxx" toID="yyy"/&gt;<br><br>
	     * <p>You can have additional tags, and/or nest the tags any way you like; this will not
	     * have any effect. We create a graph where each Item corresponds to a single node. The item's
	     * id will come from the Node's id attribute (make sure this is unique). The item's data will
	     * be the Node, and will be of type XML. The &lt;Edge&gt; elements must come *after* the corresponding
	     * &lt;Node&gt; elements have appeared. Edges are not directional, you can interchange fromID and toID
	     * with no effect.
	     *
	     *  @param xml an XML document containing Node and Edge elements
	     *  @param strings the XML element and attribute names to use when parsing an XML dataProvider.
		   The array must have 4 elements:
		   <ul>
		   <li> the element name that defines nodes
		   <li> the element name that defines edges
		   <li> the edge attribute name that defines the 'from' node
		   <li> the edge attribute name that defines the 'to' node
		   </ul>
	     *  @return a graph that corresponds to the Node and Edge elements in the input
	     */
		public static function fromXML(xml: XML, strings: Array): Graph {
			var nodeName: String = "Node";
			var edgeName: String = "Edge";
			var fromIDName: String = "fromID";
			var toIDName: String = "toID";

			if(strings != null) {
				nodeName = strings[0];
				edgeName = strings[1];
				fromIDName = strings[2];
				toIDName = strings[3];
			}
			
			var graph: Graph = new Graph();
			for each (var node: XML in xml.descendants(nodeName)) {
				var item: Item = new Item(node.@id);
				item.data = node;
				graph.add(item);
			}
			
			for each (var edge: XML in xml.descendants(edgeName)) {
				var fromItem: Item = graph.find(edge.attribute(fromIDName));
				var toItem: Item = graph.find(edge.attribute(toIDName));
				if((fromItem != null) && (toItem != null))
					graph.link(fromItem, toItem);
			}
			
			return graph;
		}

	    /**
	     *  Removes an item from the graph.
	     *
	     *  @param item The item that you want to remove from the graph.
	     */
		public function remove(item:Item):void
		{
			//trace("has item in graph? :"+this.hasNode(item.id));
			trace("remove item from graph, "+item.id);
			delete _nodes[item.id];
			delete _edges[item.id];
			
			//trace("has item in graph? :"+this.hasNode(item.id));
			
			for (var id: String in _edges) {
				var friends: Object = _edges[id];
				delete friends[item.id];
			}
			
			nodeArray = null;
			edgeArray = null;
			changed();
		}
		
	    /**
	     *  Remove the link between 2 items.
	     *
	     *  @param item1 an item in the graph that is linked to item2
	     *  @param item2 an item in the graph that is linked to item1
	     */
		public function unlink(item1:Item, item2:Item):void
		{
			var friends: Object = _edges[item1.id];
			delete friends[item2.id];
			
			friends = _edges[item2.id];
			delete friends[item1.id];
			
			edgeArray = null;
			changed();
		}
		
	    /**
	     *  An array of all the links in the graph.
	     *  Each array element is an array of 2 strings, 
	     *  which are the ids of two items that are linked.
	     */
		public function get edges():Array
		{
			if(edgeArray == null) {
				edgeArray = new Array();
				var done: Object = new Object();
				for (var id: String in _edges) {
					done[id] = true;
					var friends: Object = _edges[id];
					for (var friendID: String in friends) {
						if(!done.hasOwnProperty(friendID))
							edgeArray.push([_nodes[id], nodes[friendID]]);
					}
				}
			}
			return edgeArray;
		}
		
	    /**
	     *  An associative array of all the items in the graph.
	     *  The key is the id, the value is the Item.
	     */
		public function get nodes():Object
		{
			return _nodes;
		}
		
	    /**
	     *  True if this graph has any nodes at all.
	     */
		public function get hasNodes(): Boolean
		{
			for each (var item: Item in _nodes) {
				return true;
			}
			return false;
		}
		
	    /**
	     *  How many items are in this graph.
	     */
		public function get nodeCount(): int
		{
			var result:  int = 0;
			for each (var item: Item in _nodes) {
				result++;
			}
			return result;
		}
		
	    /**
	     *  Link 2 items. This has no effect if the 2 items are already
	     *  linked. Links are not directional: link(a,b) is equivalent to
	     *  link(b,a).
	     *
	     *  @param item1 an item in the graph
	     *  @param item2 an item in the graph
	     *  @param data any data you like, or null. The Graph doesn't ever look at this, 
	     * but you may find it convenient to store here. 
	     *  You can use getLinkData to retrieve this data later.
	     */
		public function link(item1:Item, item2:Item, data: Object = null):void
		{
			//FlashConnect.trace("link, "+item1.id+" - "+item2.id);
			if(data == null) data = 0;
			//trace("link, data: " + data);
			var friends: Object = _edges[item1.id];
			friends[item2.id] = data;
			
			friends = _edges[item2.id];
			//trace(friends[item1.id]);
			friends[item1.id] = data;
			
			edgeArray = null;
			changed();
		}
		
	    /**
	     *  Add an item to the graph.
	     *
	     *  @param item an item to add to the graph
	     */
		public function add(item:Item):void
		{
			//trace("add item: " + item.id);
			if(_distinguishedItem == null)
				_distinguishedItem = item;
				
			if(_nodes.hasOwnProperty(item.id)) {
				return;
			}
			
			_nodes[item.id] = item;
			_edges[item.id] = new Object();
			changed();
		}
		
	    /**
	     *  Find out if two items are linked.
	     *
	     *  @param item1 an item in the graph
	     *  @param item2 an item in the graph
	     *
	     *  @return true if the two items are linked to each other.
	     */
		public function linked(item1:Item, item2:Item):Boolean
		{
			var friends: Object = _edges[item1.id];
			return (friends != null) && friends.hasOwnProperty(item2.id);
		}
		
	    /**
	     *  retrieve the data that is associated with a link.
	     *
	     *  @param item1 an item in the graph
	     *  @param item2 an item in the graph
	     *
	     *  @return Object the data that was associated with the link between the two items.
	     *  If no data, or null, was associated with the link, we return 0. If there is no link
	     *  between the items, we return null.
	     */
		public function getLinkData(item1:Item, item2:Item):Object
		{
			var friends: Object = _edges[item1.id];
			
			if ((friends != null) && friends.hasOwnProperty(item2.id)) {
				return friends[item2.id];
			}else
				return null;
		}
		
	    /**
	     *  Find out how many items are linked to a given item.
	     *
	     *  @param item an item in the graph
	     *
	     *  @return thes number of items to which this item is linked.
	     */
		public function numLinks(item: Item): int {
			var friends: Object = _edges[item.id];
			var result: int = 0;
			for (var i: String in friends) { 
				result++; 
			}
			return result;
		}
		
	    /**
	     *  Find out if an item with a given id exists in the graph.
	     *
	     *  @param id any String
	     *
	     *  @return true if there is an item in the graph with the given id,
	     *  false otherwise.
	     */
		public function hasNode(id: String): Boolean {
			return _nodes.hasOwnProperty(id);
		}
		
	    /**
	     *  Find an item in the graph by id.
	     *
	     *  @param id any String
	     *
	     *  @return the item in the graph that has the given id,
	     *  or null if there is no such item.
	     */
		public function find(id: String): Item {
			if(_nodes.hasOwnProperty(id))
				return _nodes[id];
			else
				return null;
		}
		
	    /**
	     *  Get an array of all the items that a given item is linked to.
	     *
	     *  @param id any String
	     *
	     *  @return an array of Items
	     */
		public function neighbors(id: String): Object {
			return _edges[id];
		}
		
	    /** Sometimes it's handy for the graph to remember one particular item.
	     * You can use this for any purpose you like, it's not used internally by the Graph.
	     * By default, the distinguished item is the first item that was added to this graph.
	     */
		public function get distinguishedItem(): Item {
			return _distinguishedItem;
		}
		
		public function set distinguishedItem(item: Item): void {
			_distinguishedItem = item;
		}
		
		/** Remove all items from the graph. */
		public function empty(): void {
			_nodes = new Object();
			_edges = new Object();
			nodeArray = null;
			edgeArray = null;
			_distinguishedItem = null;
			changed();	
		}
		
		private function changed(): void {
			dispatchEvent(new Event(CHANGE));
		}
	}
}