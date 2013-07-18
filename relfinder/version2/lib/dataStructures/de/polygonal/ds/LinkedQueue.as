/**
 * DATA STRUCTURES FOR GAME PROGRAMMERS
 * Copyright (c) 2007 Michael Baczynski, http://www.polygonal.de
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
package de.polygonal.ds
{
	import de.polygonal.ds.SLinkedList;
	
	/**
	 * A linked queue.
	 * 
	 * <p>A queue is a FIFO structure (First In, First Out).</p>
	 */
	public class LinkedQueue
	{
		private var _list:SLinkedList;
		
		/**
		 * Initializes an empty linked queue.
		 * You can pass an existing singly linked list
		 * to provide queue-like access.
		 * 
		 * @param list An existing list to use as a queue.
		 */
		public function LinkedQueue(list:SLinkedList = null)
		{
			if (list == null)
				_list = new SLinkedList();
			else
				_list = list;
		}
		
		/**
		 * The total number of items in the queue.
		 */
		public function get size():int
		{
			return _list.size;
		}
		
		/**
		 * Indicates the front item.
		 * 
		 * @return The front item or null if the
		 *         queue is empty.
		 */
		public function peek():*
		{
			if (_list.size > 0)
				return _list.head.data;
			return null;
		}
		
		/**
		 * Indicates the most recently added item.
		 * 
		 * @return The last item in the queue or
		 *         null if the queue is empty.
		 */
		public function back():*
		{
			if (_list.size > 0)
				return _list.tail.data;
			return null
		}
		
		/**
		 * Clears all elements.
		 */
		public function clear():void
		{
			_list.clear();
		}
		
		/**
		 * Enqueues some data.
		 * 
		 * @param obj The data.
		 */
		public function enqueue(obj:*):void
		{
			_list.append(obj);
		}
		
		/**
		 * Dequeues the front item.
		 */
		/**
		 * Dequeues and returns the front item.
		 * 
		 * @return The front item or null if the queue is empty.
		 */
		public function dequeue():*
		{
			if (_list.size > 0)
			{
				var front:* = _list.head.data;
				_list.removeHead();
				return front;
			}
			return null;
		}
		
		/**
		 * Returns a string representing the current object.
		 */
		public function toString():String
		{
			return "[LinkedQueue > " + _list + "]";
		}
		
		/**
		 * Prints out all elements in the queue (for debug/demo purposes).
		 */
		public function dump():String
		{
			return "LinkedQueue:\n" + _list.dump();
		}
	}
}