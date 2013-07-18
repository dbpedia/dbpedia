/*
 * TouchGraph LLC. Apache-Style Software License
 *
 * Copyright (c) 2001-2002 Alexander Shapiro. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer. 
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in
 *    the documentation and/or other materials provided with the
 *    distribution.
 *
 * 3. The end-user documentation included with the redistribution,
 *    if any, must include the following acknowledgment:  
 *       "This product includes software developed by 
 *        TouchGraph LLC (http://www.touchgraph.com/)."
 *    Alternately, this acknowledgment may appear in the software itself,
 *    if and wherever such third-party acknowledgments normally appear.
 *
 * 4. The names "TouchGraph" or "TouchGraph LLC" must not be used to endorse 
 *    or promote products derived from this software without prior written 
 *    permission.  For written permission, please contact 
 *    alex@touchgraph.com
 *
 * 5. Products derived from this software may not be called "TouchGraph",
 *    nor may "TouchGraph" appear in their name, without prior written
 *    permission of alex@touchgraph.com.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESSED OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED.  IN NO EVENT SHALL TOUCHGRAPH OR ITS CONTRIBUTORS BE 
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR 
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF 
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR 
 * BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, 
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE 
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, 
 * EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * ====================================================================
 *
 */

package com.adobe.flex.extras.controls.forcelayout {

import flash.utils.getTimer;

/**  TGLayout is the thread responsible for graph layout.  It updates
  *  the real coordinates of the nodes in the graphEltSet object.
  *  TGPanel sends it resetDamper commands whenever the layout needs
  *  to be adjusted.  After every adjustment cycle, TGLayout triggers
  *  a repaint of the TGPanel.
  *
  * ********************************************************************
  *  This is the heart of the TouchGraph application.  Please provide a
  *  Reference to TouchGraph.com if you are influenced by what you see
  *  below.  Your cooperation will insure that this code remains
  *  opensource.
  * ********************************************************************
  *
  * <p><b>
  *  Parts of this code build upon Sun's Graph Layout example.
  *  http://java.sun.com/applets/jdk/1.1/demo/GraphLayout/Graph.java
  * </b></p>
  *
  * Translated and adapted to Flex/ActionScript 
  * from TouchGraph's original java code
  * by Mark Shepherd, Adobe FlexBuilder Engineering, 2006.
  * 
  * @author   Alexander Shapiro
  * @version  1.21  $Id: TGLayout.java,v 1.20 2002/04/01 05:51:55 x_ander Exp $
  * @private
  */
public class ForceDirectedLayout implements IForEachEdge, IForEachNode, IForEachNodePair /*implements Runnable */ {

    /*private*/public var damper: Number=0.0;      // A low damper value causes the graph to move slowly
    /*private*/public var maxMotion: Number=0;     // Keep an eye on the fastest moving node to see if the graph is stabilizing
    /*private*/public var lastMaxMotion: Number=0;
    /*private*/public var motionRatio: Number = 0; // It's sort of a ratio, equal to lastMaxMotion/maxMotion-1
    /*private*/public var damping: Boolean = true; // When damping is true, the damper value decreases
    /*private*/public var rigidity: Number = 0.25;    // Rigidity has the same effect as the damper, except that it's a constant
                                    // a low rigidity value causes things to go slowly.
                                    // a value that's too high will cause oscillation
    /*private*/public var newRigidity: Number = 0.25;
	/*private*/public var dataProvider: IDataProvider;
    /*private*/public var dragNode: Node=null;
	/*private*/public var maxMotionA: Array;

  /** Constructor with a supplied TGPanel <tt>tgp</tt>.
    */
    public function ForceDirectedLayout( dataProvider: IDataProvider/*TGPanel tgp */): void {
    	this.dataProvider = dataProvider;
    }

    public function setRigidity(r: Number): void {
        newRigidity = r;  //update rigidity at the end of the relax() thread
    }

    public function setDragNode(n: Node): void {
        dragNode = n;
    }

    //relaxEdges is more like tense edges up.  All edges pull nodes closes together;
    private /*synchronized*/ function relaxEdges(): void {
         dataProvider.forAllEdges(this);
    }

/*
    private synchronized void avoidLabels() {
        for (int i = 0 ; i < graphEltSet.nodeNum() ; i++) {
            Node n1 = graphEltSet.nodeAt(i);
            Number dx = 0;
            Number dy = 0;

            for (int j = 0 ; j < graphEltSet.nodeNum() ; j++) {
                if (i == j) {
                    continue; // It's kind of dumb to do things this way. j should go from i+1 to nodeNum.
                }
                Node n2 = graphEltSet.nodeAt(j);
                Number vx = n1.x - n2.x;
                Number vy = n1.y - n2.y;
                Number len = vx * vx + vy * vy; // so it's length squared
                if (len == 0) {
                    dx += Math.random(); // If two nodes are right on top of each other, randomly separate
                    dy += Math.random();
                } else if (len <600*600) { //600, because we don't want deleted nodes to fly too far away
                    dx += vx / len;  // If it was sqrt(len) then a single node surrounded by many others will
                    dy += vy / len;  // always look like a circle.  This might look good at first, but I think
                                     // it makes large graphs look ugly + it contributes to oscillation.  A
                                     // linear function does not fall off fast enough, so you get rough edges
                                     // in the 'force field'

                }
            }
            n1.dx += dx*100*rigidity;  // rigidity makes nodes avoid each other more.
            n1.dy += dy*100*rigidity;  // I was surprised to see that this exactly balances multiplying edge tensions
                                       // by the rigidity, and thus has the effect of slowing the graph down, while
                                       // keeping it looking identical.

        }
    }
*/

    private /*synchronized*/ function avoidLabels(): void {
         dataProvider.forAllNodePairs(this);
    }

    public function startDamper(): void {
        damping = true;
    }

    public function stopDamper(): void {
        damping = false;
        damper = 1.0;     //A value of 1.0 means no damping
    }

    public function resetDamper(): void {  //reset the damper, but don't keep damping.
        damping = true;
        damper = 1.0;
    }

    public function stopMotion(): void {  // stabilize the graph, but do so gently by setting the damper to a low value
        damping = true;
        if (damper>0.3) 
            damper = 0.3;
        else
            damper = 0;
    }

	public static var motionLimit: Number = 0.01;
	
    public function damp(): void {
        if (damping) {
            if(motionRatio<=0.001) {  //This is important.  Only damp when the graph starts to move faster
                                      //When there is noise, you damp roughly half the time. (Which is a lot)
                                      //
                                      //If things are slowing down, then you can let them do so on their own,
                                      //without damping.

                //If max motion<0.2, damp away
                //If by the time the damper has ticked down to 0.9, maxMotion is still>1, damp away
                //We never want the damper to be negative though
                if ((maxMotion<0.2 || (maxMotion>1 && damper<0.9)) && damper > 0.01) damper -= 0.01;
                //If we've slowed down significanly, damp more aggresively (then the line two below)
                else if (maxMotion<0.4 && damper > 0.003) damper -= 0.003;
                //If max motion is pretty high, and we just started damping, then only damp slightly
                else if(damper>0.0001) damper -=0.0001;
            }
        }
        if(maxMotion<motionLimit && damping) {
            damper=0;
        }
    }
	
    private /*synchronized*/ function moveNodes(): void {
        lastMaxMotion = maxMotion;
        maxMotionA = new Array(); /* of Number */;
        maxMotionA[0]=0;

        dataProvider.forAllNodes(this);

        maxMotion=maxMotionA[0];
         if (maxMotion>0) motionRatio = lastMaxMotion/maxMotion-1; //subtract 1 to make a positive value mean that
         else motionRatio = 0;                                     //things are moving faster

        damp();
    }
	
    private /*synchronized*/ function relax(): void {
		//var startTime: int = getTimer();
		//trace("relax...");
    	dataProvider.forAllNodes(new Refresher());
        for (var i: int=0;i<5;i++) {
			//var startTime: int = getTimer();
			relaxEdges();
 			//var endTime: int = getTimer();
			//trace("relaxEdges: " + String(endTime - startTime) + " ms");
			
			//startTime = getTimer();
			avoidLabels();
			//endTime = getTimer();
			//trace("avoidLabels: " + String(endTime - startTime) + " ms");
			
			//startTime = getTimer();
			moveNodes();
			//endTime = getTimer();
			//trace("avoidLabels: " + String(endTime - startTime) + " ms");
        }
        if(rigidity!=newRigidity) rigidity= newRigidity; //update rigidity
        dataProvider.forAllNodes(new Committer());
 		//var endTime: int = getTimer();
		//trace("relax: " + String(endTime - startTime) + " ms");
	}

	public function tick(): Boolean {
		if (!(damper<0.1 && damping && maxMotion<motionLimit)) {
			//trace("relax " + getTimer());
			relax();
			//trace("relax done " + getTimer());
			return true;
		} else {
		   	//trace("don't relax");
		   	return false;
		}
	}
    
	public function forEachEdge(e: IEdge): void {
	
	    var vx: Number = e.getTo().x - e.getFrom().x;
	    var vy: Number = e.getTo().y - e.getFrom().y;
	    var len: Number = Math.sqrt(vx * vx + vy * vy);
	
		var length: int = e.getLength();
		var div: int = length * 100;
		
	    var dx: Number=vx*rigidity;  //rigidity makes edges tighter
		//if(isNaN(dx)) {
			//dx = dx;
		//}
	    var dy: Number=vy*rigidity;
		//if(isNaN(dy)) {
			//dy = dy;
		//}
	
	    dx = dx / div;
		//if(isNaN(dx)) {
			//dx = dx;
		//}
		
	    //var ddy: Number = dy;
		dy = dy / div;
	    //ddy /=(e.getLength()*100);
		//if(isNaN(dy)) {
			//dy = dy;
		//}
	
	    // Edges pull directly in proportion to the distance between the nodes. This is good,
	    // because we want the edges to be stretchy.  The edges are ideal rubberbands.  They
	    // They don't become springs when they are too short.  That only causes the graph to
	    // oscillate.
		
		var dxlen:Number = dx * len;
		var dylen:Number = dy * len;
	
	    //if (e.getTo().justMadeLocal || !e.getFrom().justMadeLocal) { always true, because justMadeLocal is always false
	        e.getTo().dx = e.getTo().dx - dxlen;
	        e.getTo().dy = e.getTo().dy - dylen;
	    //} else {
	    //    e.getTo().dx = e.getTo().dx - dx*len/10;
	    //    e.getTo().dy = e.getTo().dy - dy*len/10;
	    //}
	    //if (e.getFrom().justMadeLocal || !e.getTo().justMadeLocal) { // ditto
	        e.getFrom().dx = e.getFrom().dx + dxlen;
	        e.getFrom().dy = e.getFrom().dy + dylen;
	    //} else {
	    //    e.getFrom().dx = e.getFrom().dx + dx*len/10;
	    //    e.getFrom().dy = e.getFrom().dy + dy*len/10;
	    //}
	}

	 public function forEachNode(n: Node): void {
	    //var dx: Number = n.dx;
	    //var dy: Number = n.dy;
		
	    //dx*=damper;  //The damper slows things down.  It cuts down jiggling at the last moment, and optimizes
	    //dy*=damper;  //layout.  As an experiment, get rid of the damper in these lines, and make a
	                 //long straight line of nodes.  It wiggles too much and doesn't straighten out.
	//
	    //n.dx = dx/2;   //Slow down, but dont stop.  Nodes in motion store momentum.  This helps when the force
	    //n.dy = dy/2;   //on a node is very low, but you still want to get optimal layout.
		
		var damperHalf:Number = damper / 2;
		
		n.dx *= damperHalf;
		n.dy *= damperHalf;
		
	    var distMoved: Number = Math.sqrt(n.dx * n.dx + n.dy * n.dy); //how far did the node actually move?
	
	     if (!n.fixed && !(n==dragNode) ) {
	        //n.x = n.x + Math.max(-30, Math.min(30, n.dx)); //don't move faster then 30 units at a time.
	        //n.y = n.y + Math.max(-30, Math.min(30, n.dy)); //I forget when this is important.  Stopping severed nodes from flying away?
			
			n.x += n.dx;
			n.y += n.dy;
	     }
	     maxMotionA[0]=Math.max(distMoved,maxMotionA[0]);
	}
	
	public function forEachNodePair(n1: Node, n2: Node): void {
		//trace(Object(n1).item.id + "," + String(n1.x) + "," + String(n1.y) + " ... " + Object(n2).item.id + "," + String(n2.x) + "," + String(n2.y));
	    var dx: Number=0;
	    var dy: Number=0;
	    var vx: Number = n1.x - n2.x;
	    var vy: Number = n1.y - n2.y;
	    var len: Number = vx * vx + vy * vy; //so it's length squared
	    if (len == 0) {
	        dx = Math.random(); //If two nodes are right on top of each other, randomly separate
	        dy = Math.random();
	    } else if (len < 360000) { //600*600, because we don't want deleted nodes to fly too far away
	        dx = vx / len;  // If it was sqrt(len) then a single node surrounded by many others will
	        dy = vy / len;  // always look like a circle.  This might look good at first, but I think
	                        // it makes large graphs look ugly + it contributes to oscillation.  A
	                        // linear function does not fall off fast enough, so you get rough edges
	                        // in the 'force field'
	    }
	
	    //var repSum: Number = n1.repulsion * n2.repulsion/100;
	    var factor: Number = n1.repulsion * n2.repulsion / 100 * rigidity;
	
		var dxfac:Number = dx * factor;
		var dyfac:Number = dy * factor;
		
	    //if(n1.justMadeLocal || !n2.justMadeLocal) { always true, because justMadeLocal is always false
	        n1.dx += dxfac;
	        n1.dy += dyfac;
	    //}
	    //else {
	    //    n1.dx = n1.dx + dx*repSum*rigidity/10;
	    //    n1.dy = n1.dy + dy*repSum*rigidity/10;
	    //}
	    //if (n2.justMadeLocal || !n1.justMadeLocal) { always true, because justMadeLocal is always false
	        n2.dx -= dxfac;
	        n2.dy -= dyfac;
	    //}
	    //else {
	    //    n2.dx = n2.dx - dx*repSum*rigidity/10;
	    //    n2.dy = n2.dy - dy*repSum*rigidity/10;
	    //}
	}
	}
}

import com.adobe.flex.extras.controls.forcelayout.IForEachNode;
import com.adobe.flex.extras.controls.forcelayout.Node;

class Refresher implements IForEachNode {
	 public function forEachNode( n: Node ): void {
	 	n.refresh();
	 }
}

class Committer implements IForEachNode {
	 public function forEachNode( n: Node ): void {
	 	n.commit();
	 }
}
