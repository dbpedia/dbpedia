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
	import flash.events.Event;
	import flash.events.EventDispatcher;
	import flash.events.IEventDispatcher;

/** Translated and adapted to Flex/ActionScript 
  * from TouchGraph's original java code
  * by Mark Shepherd, Adobe FlexBuilder Engineering, 2006.
  * 
  * @author   Alexander Shapiro
  * @private
  */
public class Node implements IEventDispatcher {

	private var eventDispatcher:EventDispatcher;
	
	public function Node(): void {
		eventDispatcher = new EventDispatcher()
	}

	public var y: Number = 0;
	public var dy: Number = 0;
	public var repulsion: Number = 0;
	
	private var _fixed: Boolean = false;
	
	[Bindable(event="fixedChange")]
	public function get fixed():Boolean {
		return _fixed;
	}
	
	public function set fixed(value:Boolean):void {
		_fixed = value;
		dispatchEvent(new Event("fixedChange", true));
	}
	
	public function refresh(): void {}
	public function commit(): void {}

	private var _x: Number = 0;
	public function set x(n: Number): void {
		if(isNaN(n)) {
			n = n;
		}
		//trace("x has changed : " + _x+" item.id"+this.);
		_x = n;
	}
	public function get x(): Number {
		return _x;
	}

	public var dx: Number = 0;
	//private var _dx: Number = 0;
	//public function set dx(n: Number): void {
		//if(isNaN(n)) {
			//n = n;
		//}
			//
		//_dx = n;
	//}
	//public function get dx(): Number {
		//return _dx;
	//}
	
	//*** IEventDispatcher ***************************************************
	public function addEventListener(type:String, listener:Function,
		useCapture:Boolean = false, priority:int = 0, weakRef:Boolean = false):void{
		eventDispatcher.addEventListener(type, listener, useCapture, priority, weakRef);
	}
	
	public function dispatchEvent(event:Event):Boolean{
		return eventDispatcher.dispatchEvent(event);
	}
	
	public function hasEventListener(type:String):Boolean{
		return eventDispatcher.hasEventListener(type);
	}
	
	public function removeEventListener(type:String, listener:Function,
		useCapture:Boolean = false):void{
		eventDispatcher.removeEventListener(type, listener, useCapture);
	}
	
	public function willTrigger(type:String):Boolean {
		return eventDispatcher.willTrigger(type);
	}
	//************************************************************************
}
}