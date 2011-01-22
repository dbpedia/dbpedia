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
	import de.polygonal.ds.DLinkedList;
	
	/**
	 * A linked stack.
	 * <p>A stack is a LIFO structure (Last In, First Out)</p>.
	 */
	public class LinkedStack
	{
		private var _list:DLinkedList;
		
		/**
		 * Initializes a linked stack.
		 * You can pass an existing doubly linked list
		 * to provide stack-like access.
		 * 
		 * @param list An existing list to use as a stack.
		 */
		public function LinkedStack(list:DLinkedList = null)
		{
			if (list == null)
				_list = new DLinkedList();
			else
				_list = list;
		}

		/**
		 * The total number of items in the stack.
		 */
		public function get size():int
		{
			return _list.size;
		}

		/**
		 * Indicates the top item.
		 *
		 * @return The top item.
		 */
		public function peek():*
		{
			if (_list.size > 0)
				return _list.tail.data;
			else
				return null;
		}

		/**
		 * Pushes data onto the stack.
		 * 
		 * @param obj The data to insert.
		 */
		public function push(obj:*):void
		{
			_list.append(obj);
		}

		/**
		 * Pops data off the stack.
		 * 
		 * @return A reference to the top item
		 *         or null if the stack is empty.
		 */
		public function pop():*
		{
			var o:* = null;
			if (_list.size > 0)
				o = _list.tail.data;
			_list.removeTail();
			return o;
		}

		/**
		 * Returns a string representing the current object.
		 */
		public function toString():String
		{
			return "[LinkedStack > " + _list + "]";
		}
		
		/**
		 * Prints out all elements in the stack (for debug/demo purposes).
		 */
		public function dump():String
		{
			return "LinkedStack:\n" + _list.dump();
		}
	}
}