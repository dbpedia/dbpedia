////////////////////////////////////////////////////////////////////////////////
//
//  Copyright (C) 2006 Adobe Macromedia Software LLC and its licensors.
//  All Rights Reserved. The following is Source Code and is subject to all
//  restrictions on such code as contained in the End User License Agreement
//  accompanying this product.
//
////////////////////////////////////////////////////////////////////////////////

package com.adobe.flex.extras.controls.forcelayout {

/**
 *  @private
 */
public interface IDataProvider {
	function forAllNodes(fen: IForEachNode): void;
	function forAllEdges(fee: IForEachEdge): void;
	function forAllNodePairs(fenp: IForEachNodePair): void;
}
}