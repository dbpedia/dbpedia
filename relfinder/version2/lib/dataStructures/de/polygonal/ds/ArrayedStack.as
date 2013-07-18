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
	import de.polygonal.ds.Collection;
	
	/**
	* An arrayed stack.
	* <p>A stack is a LIFO structure (Last In, First Out).</p>
	*/
	public class ArrayedStack implements Collection
	{
		private var _stack:Array;
		private var _size:int;
		private var _top:int;
		
		/**
		 * Initializes a stack to match the given size.
		 * 
		 * @param size The total number of elements the stack can store.
		 */
		public function ArrayedStack(size:int)
		{
			_size = size;
			clear();
		}
		
		/**
		 * Indicates the top item.
		 *
		 * @return The top item.
		 */
		public function peek():*
		{
			return _stack[int(_top - 1)];
		}
		
		/**
		 * Pushes data onto the stack.
		 * 
		 * @param obj The data.
		 */
		public function push(obj:*):Boolean
		{
			if (_size != _top)
			{
				_stack[_top++] = obj;
				return true;
			}
			return false;
		}
		
		/**
		 * Pops data off the stack.
		 * 
		 * @return A reference to the top item
		 *         or null if the stack is empty.
		 */
		public function pop():*
		{
			if (_top > 0)
				return _stack[--_top];
			return null;
		}
		
		/**
		 * Reads an item at a given index.
		 * 
		 * @param i The index.
		 * @return The item at the given index.
		 */
		public function getAt(i:int):*
		{
			if (i >= _top) return null;
			return _stack[i];
		}
		
		/**
		 * Writes an item at a given index.
		 * 
		 * @param i   The index.
		 * @param obj The data.
		 */
		public function setAt(i:int, obj:*):void
		{
			if (i >= _top) return;
			_stack[i] = obj;
		}
		
		/**
		 * Checks if a given item exists.
		 * 
		 * @return True if the item is found, otherwise false.
		 */
		public function contains(obj:*):Boolean
		{
			for (var i:int = 0; i < _top; i++)
			{
				if (_stack[i] === obj)
					return true;
			}
			return false;
		}
		
		/**
		 * Clears the stack.
		 */
		public function clear():void
		{
			_stack = new Array(_size);
			_top = 0;
		}
		
		/**
		 * Creates a new iterator pointing to the top item.
		 */
		public function getIterator():Iterator
		{
			return new ArrayedStackIterator(this);
		}
		
		/**
		 * The total number of items in the stack.
		 */
		public function get size():int
		{
			return _top;
		}
		
		/**
		 * Checks if the stack is empty.
		 */
		public function isEmpty():Boolean
		{
			return _top == 0;
		}
		
		/**
		 * The maximum allowed size.
		 */
		public function get maxSize():int
		{
			return _size;
		}
		
		/**
		 * Converts the structure into an array.
		 * 
		 * @return An array.
		 */
		public function toArray():Array
		{
			return _stack.concat();
		}
		
		/**
		 * Returns a string representing the current object.
		 */
		public function toString():String
		{
			return "[ArrayedStack, size= " + _top + "]";
		}
		
		/**
		 * Prints out all elements in the queue (for debug/demo purposes).
		 */
		public function dump():String
		{
			var s:String = "[ArrayedStack]";
			if (_top == 0) return s;
			
			var k:int = _top - 1;
			s += "\n\t" + _stack[k--] + " -> front\n";
			for (var i:int = k; i >= 0; i--)
				s += "\t" + _stack[i] + "\n";
			return s;
		}
	}
}

import de.polygonal.ds.Iterator;
import de.polygonal.ds.ArrayedStack;

internal class ArrayedStackIterator implements Iterator
{
	private var _stack:ArrayedStack;
	private var _cursor:int;
	
	public function ArrayedStackIterator(stack:ArrayedStack)
	{
		_stack = stack;
		start();
	}
	
	public function get data():*
	{
		return _stack.getAt(_cursor);
	}
	
	public function set data(obj:*):void
	{
		_stack.setAt(_cursor, obj);
	}
	
	public function start():void
	{
		_cursor = _stack.size - 1;
	}
	
	public function hasNext():Boolean
	{
		return _cursor >= 0;
	}
	
	public function next():*
	{
		if (_cursor >= 0)
			return _stack.getAt(_cursor--);
		return null;
	}
}