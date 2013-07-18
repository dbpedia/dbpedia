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
	import flash.events.TimerEvent;
	import flash.utils.Timer;
	
	public class GivenNode extends MyNode {
		
		public function GivenNode(_id:String, _element:Element) {
			super(_id, _element);
			element.isGiven = true;
			timer = new Timer(100);
			timer.addEventListener(TimerEvent.TIMER, timerMove);
		}
		
		private var timer:Timer = new Timer(5);
		
		private var xTo:Number;
		private var yTo:Number;
		
		private var stepX:Number;
		private var stepY:Number;
		private var steps:int;
		private var stepsDone:int;
		
		private var stepLength:Number = 20;
		
		public function moveToPosition(x:Number, y:Number):void {
			
			timer.stop();
			
			xTo = x;
			yTo = y;
			
			stepsDone = 0;
			
			var length:Number = Math.sqrt(Math.pow(getX() - xTo , 2) + Math.pow(getY() - yTo , 2));
			
			steps = length / stepLength;
			stepX = (xTo - getX()) / steps;
			stepY = (yTo - getY()) / steps;
			
			timer.start();
		}
		
		private function timerMove(event:TimerEvent):void {
			stepsDone++;
			
			if (stepsDone >= steps) {
				timer.stop();
				setPosition(xTo, yTo);
			}else {
				setPosition(getX() + stepX, getY() + stepY);
			}
		}
		
	}
	
}