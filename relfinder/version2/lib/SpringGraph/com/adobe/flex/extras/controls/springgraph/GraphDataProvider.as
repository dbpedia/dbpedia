////////////////////////////////////////////////////////////////////////////////
//
//  Copyright (C) 2006 Adobe Macromedia Software LLC and its licensors.
//  All Rights Reserved. The following is Source Code and is subject to all
//  restrictions on such code as contained in the End User License Agreement
//  accompanying this product.
//
////////////////////////////////////////////////////////////////////////////////

package com.adobe.flex.extras.controls.springgraph {

import mx.core.UIComponent;
import com.adobe.flex.extras.controls.forcelayout.IDataProvider;
import com.adobe.flex.extras.controls.forcelayout.IForEachEdge;
import com.adobe.flex.extras.controls.forcelayout.IForEachNode;
import com.adobe.flex.extras.controls.forcelayout.IForEachNodePair;
import com.adobe.flex.extras.controls.forcelayout.IEdge;
import com.adobe.flex.extras.controls.forcelayout.Node;
import flash.geom.Rectangle;

 /** Manages the graph data for a SpringGraph
  * 
  * @author   Mark Shepherd
  * @private
  */
public class GraphDataProvider implements IDataProvider {
	private var nodeStore: Object/*{id: GraphNode}*/ = new Object();
	private var nodes: Array; /*{id: GraphNode}*/
	private var edges: Array;
	private var host: Object;	
	private var _layoutChanged: Boolean = false;
	private var _distance: int;
	public var boundary: Rectangle;

	private function makeGraphNode(item: Item): GraphNode {
		var result: GraphNode;
		if(nodeStore.hasOwnProperty(item.id)) {
			result = nodeStore[item.id];
			if(result.view.parent == null)
				host.addComponent(result.view);	
		} else {
			result = new GraphNode(host.newComponent(item), this, item);
			nodeStore[item.id] = result;
		}
		return result;
	}

	public function GraphDataProvider(host: Object): void {
		this.host = host;
	}

	public function forAllNodes(fen: IForEachNode): void {
		for each (var node: Node in nodes) {
			fen.forEachNode(node);
		}
	}
	
	public function forAllEdges(fee: IForEachEdge): void {
		for each (var edge: IEdge in edges) {
			fee.forEachEdge(edge);
		}
	}
	
	public function forAllNodePairs(fenp: IForEachNodePair): void {
		for each (var nodeI: Node in nodes) {
			for each (var nodeJ: Node in nodes) {
				if(nodeI != nodeJ) {
					fenp.forEachNodePair(nodeI, nodeJ);
				}
			}
		}
	}

	public function set graph(g: Graph): void {
		var newItems: Object = g.nodes;
		var newEdges: Object = g.edges;
		
		// re-create the list of nodes
		var oldNodes: Array = nodes;
		nodes = new Array();
		for each (var item: Item in newItems) {
			nodes.push(makeGraphNode(item));
		}
		if(oldNodes != null) {
			for each (var oldNode: GraphNode in oldNodes) {
				if(!g.hasNode(oldNode.item.id)) {
					// this node is not in the currently displayed set
					if(oldNode.view.parent != null)
						host.removeComponent(oldNode.view);
						delete nodeStore[oldNode.item.id];
						// !!@ how does it get re-added
				}
			}
		}

		// re-create the list of edges
		edges = new Array();
		for each (var edge: Array in newEdges) {
			edges.push(new GraphEdge(GraphNode(nodeStore[Item(edge[0]).id]), GraphNode(nodeStore[Item(edge[1]).id]), _distance));
		}
	}

	public function set distance(d: int): void {
		_distance = d;
	}
	
	public function get distance(): int {
		return _distance;
	}

	public function getEdges(): Array {
		return edges;
	}
	
	public function findNode(component: UIComponent): GraphNode {
		for (var i: int = 0; i < nodes.length; i++) {
			var node: GraphNode = GraphNode(nodes[i]);
			if(node.view == component)
				return node;
		}
		return null;
	}
	
	public function findNodeByItem(item:Item): GraphNode {
		for (var i: int = 0; i < nodes.length; i++) {
			var node: GraphNode = GraphNode(nodes[i]);
			if(node.item == item)
				return node;
		}
		return null;
	}

	public function get layoutChanged(): Boolean {
		return _layoutChanged;
	}
	
	public function set layoutChanged(b: Boolean): void{
		_layoutChanged = b;
	}
	
	public function get repulsionFactor(): Number {
		return SpringGraph(host)._repulsionFactor;
	}
	
	public function get defaultRepulsion(): Number {
		return SpringGraph(host).defaultRepulsion;
	}
	
	public function get hasNodes(): Boolean {
		return (nodes != null) && (nodes.length > 0);
	}
}
}
