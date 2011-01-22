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
	import de.polygonal.ds.Iterator;
	import de.polygonal.ds.Collection;
	
	import de.polygonal.ds.DListNode;
	import de.polygonal.ds.DListIterator;
	
	import de.polygonal.ds.sort.dLinkedMergeSort;
	import de.polygonal.ds.sort.dLinkedMergeSortCmp;
	import de.polygonal.ds.sort.dLinkedInsertionSort;
	import de.polygonal.ds.sort.dLinkedInsertionSortCmp;
	import de.polygonal.ds.sort.compare.*;
	
	/**
	 * A doubly linked list.
	 * 
	 * <p>A doubly linked list stores a reference to the next
	 * and previous node which makes it possible to traverse
	 * the list in both directions.</p>
	 */
	public class DLinkedList implements Collection
	{
		private var _count:int;
		
		/**
		 * The head node being referenced.
		 */
		public var head:DListNode;
		
		/**
		 * The tail node being referenced.
		 */
		public var tail:DListNode;
		
		/**
		 * Initializes an empty list. You can add initial
		 * items by passing them as a comma-separated list.
		 * 
		 * @param args A list of comma-separated values to append.
		 */
		public function DLinkedList(...args)
		{
			head = tail = null;
			_count = 0;
			
			if (args.length > 0) append.apply(this, args);
		}
		
		/**
		 * Appends items to the list.
		 * 
		 * @param args A list of comma-separated values to append.
		 * @return A DListNode object wrapping the data. If multiple values are
		 *         added, the returned node represents the first argument.
		 */
		public function append(...args):DListNode
		{
			var k:int = args.length;
			
			var node:DListNode = new DListNode(args[0]);
			if (head)
			{
				tail.insertAfter(node);
				tail = tail.next;
			}
			else
				head = tail = node;
			
			if (k > 1)
			{
				var t:DListNode = node;
				for (var i:int = 1; i < k; i++)
				{
					node = new DListNode(args[i]);
					tail.insertAfter(node);
					tail = tail.next;
				}
				_count += k;
				return t;
			}
			
			_count++;
			return node;
		}
		
		/**
		 * Prepends items to the list.
		 * 
		 * @param args A list of one or more comma-separated values to prepend.
		 * @return A DListNode object wrapping the data. If multiple values are
		 *         added, the returned node represents the first argument.
		 */
		public function prepend(...args):DListNode
		{
			var k:int = args.length;
			var node:DListNode = new DListNode(args[int(k - 1)]);
			if (head)
			{
				head.insertBefore(node);
				head = head.prev;
			}
			else
				head = tail = node;
			
			if (k > 1)
			{
				var t:DListNode = node;
				for (var i:int = k - 2; i >= 0; i--)
				{
					node = new DListNode(args[i]);
					head.insertBefore(node);
					head = head.prev;
				}
				_count += k;
				return t;
			}
			
			_count++;
			return node;
		}
		
		/**
		 * Inserts an item after a given iterator or appends it
		 * if the iterator is invalid.
		 * 
		 * @param itr A doubly linked list iterator.
		 * @param obj The data.
		 * @return A doubly linked list node wrapping the data.
		 */
		public function insertAfter(itr:DListIterator, obj:*):DListNode
		{
			if (itr.list != this) return null;
			if (itr.node)
			{
				var node:DListNode = new DListNode(obj);
				itr.node.insertAfter(node);
				
				if (itr.node == tail)
					tail = itr.node.next;
				
				_count++;
				return node;
			}
			else
				return append(obj);
		}
		
		/**
		 * Inserts an item before a given iterator or appends it
		 * if the iterator is invalid.
		 * 
		 * @param itr A doubly linked list iterator.
		 * @param obj The data.
		 * @return A doubly linked list node wrapping the data.
		 */
		public function insertBefore(itr:DListIterator, obj:*):DListNode
		{
			if (itr.list != this) return null;
			if (itr.node)
			{
				var node:DListNode = new DListNode(obj);
				itr.node.insertBefore(node);
				if (itr.node == head)
					head = head.prev;
				
				_count++;
				return node;
			}
			else
				return prepend(obj);
		}
		
		/**
		* Removes the node the iterator is pointing
		* at and moves the iterator to the next node.
		* 
		* @return True if the removal succeeded, otherwise false.
		*/
		public function remove(itr:DListIterator):Boolean
		{
			if (itr.list != this || !itr.node) return false;
			
			var node:DListNode = itr.node;
			
			if (node == head) 
				head = head.next;
			else
			if (node == tail)
				tail = tail.prev;
			
			if (itr.node)
				itr.node = itr.node.next;
			
			if (node.prev) node.prev.next = node.next;
			if (node.next) node.next.prev = node.prev;
			node.next = node.prev = null;
			
			if (head == null) tail = null;
			
			_count--;
			return true;
		}
		
		/**
		 * Removes the head of the list and returns
		 * the head's data or null if the list is empty.
		 * 
		 * @return The data of the removed node.
		 */
		public function removeHead():*
		{
			if (head)
			{
				var obj:* = head.data;
				
				head = head.next;
				
				if (head)
					head.prev = null;
				else
					tail = null
				
				_count--;
				
				return obj;
			}
			return null;
		}
		
		/**
		 * Removes the tail of the list and returns
		 * the tail's data or null if the list is empty.
		 * 
		 * @return The data of the removed node.
		 */
		public function removeTail():*
		{
			if (tail)
			{
				var obj:* = tail.data;
				
				tail = tail.prev;
				
				if (tail)
					tail.next = null;
				else
					head = null;
				
				_count--;
				
				return obj;
			}
			return null;
		}
		
		/**
		 * Merges the current list with all lists specified in the paramaters.
		 * The list on which the method is called is modified to reflect the changes.
		 * Due to the rearrangement of the node pointers all passed lists become
		 * invalid and should be discarded.
		 * @see #concat
		 * 
		 * @param args  A list of one or more comma-separated DLinkedList objects.
		 */
		public function merge(...args):void
		{
			var c:DLinkedList = new DLinkedList();
			var a:DLinkedList;
			
			a = args[0];
			if (head)
			{
				tail.next = a.head;
				a.head.prev = tail;
				tail = a.tail;
			}
			else
			{
				head = a.head;
				tail = a.tail;
			}
			
			var k:int = args.length;
			for (var i:int = 1; i < k; i++)
			{
				a = args[i];
				tail.next = a.head;
				a.head.prev = tail;
				tail = a.tail;
				
				_count += a.size;
			}
		}
		
		/**
		 * Concatenates the current list with all lists specified
		 * in the parameters and returns a new linked list.
		 * The list on which the method is called and the passed lists
		 * are left unchanged.
		 * @see #merge
		 * 
		 * @param args A list of one or more comma-separated DLinkedList objects.
		 * @return An copy of the current list which also stores the values from the passed lists.
		 */
		public function concat(...args):DLinkedList
		{
			var c:DLinkedList = new DLinkedList();
			var a:DLinkedList, n:DListNode;
			
			n = head;
			while (n)
			{
				c.append(n.data);
				n = n.next;
			}
			var k:int = args.length;
			for (var i:int = 0; i < k; i++)
			{
				a = args[i];
				n = a.head;
				while (n)
				{
					c.append(n.data);
					n = n.next;
				}
			}
			return c;
		}
		
		/**
		 * Sorts the nodes in the list using the mergesort algorithm.<br/>
		 * See http://www.chiark.greenend.org.uk/~sgtatham/algorithms/listsort.html<br/>
		 * 
		 * If the LinkedList.INSERTION_SORT flag is used, the list is sorted using
		 * the insertionsort algorithm instead, which is much faster for nearly sorted lists.<br/>
		 * <ul>
		 * <li>default sort behaviour: mergesort, numeric, ascending</li>
		 * <li>sorting is ascending (for character-strings: a precedes z)</li>
		 * <li>sorting is case-sensitive: Z precedes a</li>
		 * <li>the list is directly modified to reflect the sort order</li>
		 * <li>multiple elements that have identical values are placed consecutively</li>
		 *   in the sorted array in no particular order</li></ul>
		 * 
		 * @param sortOptions
		 * 
		 * You pass an optional comparison function and/or one or more bitflags that determine the
		 * behavior of the sort.<br/>Syntax: DLinkedList.sort(compareFunction, flags)<br/><br/>
		 * <br/>
		 * compareFunction - A comparison function used to determine the sorting
		 *                   order of elements in an array (optional).<br/>
		 *                   It should take two arguments and return a result of
		 *                   -1 if A < B, 0 if A == B and 1 if A > B in the sorted sequence.
		 * <br/><br/>
		 * flags           - One or more numbers or defined constants, separated by the | (bitwise OR) operator,
		 *                   that change the behavior of the sort from the default:<br/>
		 *                   2  or SortOptions.INSERTION_SORT<br/>
		 *                   4  or SortOptions.CHARACTER_STRING<br/>
		 *                   8  or SortOptions.CASEINSENSITIVE<br/>
		 *                   16 or SortOptions.DESCENDING<br/>
		 **/
		public function sort(...sortOptions):void
		{
			if (_count <= 1) return;
			if (sortOptions.length > 0)
			{
				var b:int = 0;
				var cmp:Function = null;
				
				var o:* = sortOptions[0];
				if (o is Function)
				{
					cmp = o;
					if (sortOptions.length > 1)
					{
						o = sortOptions[1];
						if (o is int)
							b = o;
					}
				}
				else
				if (o is int)
					b = o;
				
				if (Boolean(cmp))
				{
					if (b & 2)
						head = dLinkedInsertionSortCmp(head, cmp, b == 18);
					else
						head = dLinkedMergeSortCmp(head, cmp, b == 16);
				}
				else
				{
					if (b & 2)
					{
						if (b & 4)
						{
							if (b == 22)
								head = dLinkedInsertionSortCmp(head, compareStringCaseSensitiveDesc);
							else
							if (b == 14)
								head = dLinkedInsertionSortCmp(head, compareStringCaseInSensitive);
							else
							if (b == 30)
								head = dLinkedInsertionSortCmp(head, compareStringCaseInSensitiveDesc);
							else
								head = dLinkedInsertionSortCmp(head, compareStringCaseSensitive);
						}
						else
						{
							head = dLinkedInsertionSort(head, b == 18);
						}
					}
					else
					{
						if (b & 4)
						{
							if (b == 20)
								head = dLinkedMergeSortCmp(head, compareStringCaseSensitiveDesc);
							else
							if (b == 12)
								head = dLinkedMergeSortCmp(head, compareStringCaseInSensitive);
							else
							if (b == 28)
								head = dLinkedMergeSortCmp(head, compareStringCaseInSensitiveDesc);
							else
								head = dLinkedMergeSortCmp(head, compareStringCaseSensitive);
						}
						else
						if (b & 16)
							head = dLinkedMergeSort(head, true);
					}
				}
			}
			else
				head = dLinkedMergeSort(head);
		}
		
		/**
		 * Searches for an item in the list by using strict equality (===) and returns
		 * and iterator pointing to the node containing the item or null if the
		 * item was not found.
		 * 
		 * @param  obj  The item to search for
		 * @param  from A DListIterator object pointing to the node in the list from which to start searching for the item.  
		 * @return An DListIterator object pointing to the node with the found item or null if no item exists matching the input data or the iterator is invalid.
		 */
		public function nodeOf(obj:*, from:DListIterator = null):DListIterator
		{
			if (from != null)
				if (from.list != this)
					return null;
			
			var node:DListNode = (from == null) ? head : from.node;
			while (node)
			{
				if (node.data === obj)
					return new DListIterator(this, node);
				node = node.next;
			}
			
			return null;
		}
		
		/**
		 * Searches for an item in the list, working backward from the last item,
		 * by using strict equality (===) and returns and iterator pointing to the node
		 * containing the item or null if the item wasn't found.
		 * 
		 * @param  obj  The item to search for
		 * @param  from A DListIterator object pointing to the node in the list from which to start searching for the item.  
		 * @return A DListIterator object pointing to the node with the found item or null if no item exists matching the input data or the iterator is invalid.
		 */
		public function lastNodeOf(obj:*, from:DListIterator = null):DListIterator
		{
			if (from != null)
				if (from.list != this)
					return null;
			
			var node:DListNode = (from == null) ? tail : from.node;
			while (node)
			{
				if (node.data === obj)
					return new DListIterator(this, node);
				node = node.prev;
			}
			
			return null;
		}
		
		/**
		 * Adds nodes to and removes nodes from the list. This method modifies the list.
		 * 
		 * @param start       A DListIterator object pointing to the node where the insertion or deletion begins. The iterator
		 *                    is updated so it still points to the original node, even if the node now belongs to another list.
		 * @param deleteCount An integer that specifies the number of nodes to be deleted. This number includes
		 *                    the node referenced by the iterator. If no value is specified for the deleteCount parameter,
		 *                    the method deletes all of the nodes from the start start iterator to the tail of the list.
		 *                    If the value is 0, no nodes are deleted.
		 * @param args        Specifies the values to insert into the list, starting at the iterator's node specified by the start parameter.
		 *                    Nodes
		 * 
		 * @param return      A DLinkedList object containing the nodes that were removed from the original list or null if the
		 *                    iterator is invalid.
		 */
		public function splice(start:DListIterator, deleteCount:uint = 0xffffffff, ...args):DLinkedList
		{
			if (start) if (start.list != this) return null;
			
			if (start.node)
			{
				var s:DListNode   = start.node;
				var t:DListNode   = start.node.prev;
				var c:DLinkedList = new DLinkedList();
				var i:int, k:int;
				
				if (deleteCount == 0xffffffff)
				{
					if (start.node == tail) return c;
					while (start.node)
					{
						c.append(start.node.data);
						start.remove();
					}
					start.list = c;
					start.node = s;
					return c;
				}
				else
				{
					for (i = 0; i < deleteCount; i++)
					{
						if (start.node)
						{
							c.append(start.node.data);
							start.remove();
						}
						else
							break;
					}
				}
				
				k = args.length;
				if (k > 0)
				{
					if (_count == 0)
					{
						for (i = 0; i < k; i++)
							append(args[i]);
					}
					else
					{
						var n:DListNode;
						if (t == null)
						{
							n = prepend(args[0]);
							for (i = 1; i < k; i++)
							{
								n.insertAfter(new DListNode(args[i]));
								if (n == tail) tail = n.next;
								n = n.next;
								_count++;
							}
						}
						else
						{
							n = t;
							for (i = 0; i < k; i++)
							{
								n.insertAfter(new DListNode(args[i]));
								if (n == tail) tail = n.next;
								n = n.next;
								_count++;
							}
						}
					}
					start.node = n;
				}
				else
					start.node = s;
				
				start.list = c;
				return c;
			}
			return null;
		}
		
		/**
		 * Removes and appends the head node to the tail.
		 */
		public function shiftUp():void
		{
			var t:DListNode = head;
			
			if (head.next == tail)
			{
				head = tail;
				head.prev = null;
				
				tail = t;
				tail.next = null;
				
				head.next = tail;
				tail.prev = head;
			}
			else
			{
				head = head.next;
				head.prev = null;
				
				tail.next = t;
				
				t.next = null;
				t.prev = tail;
				
				tail = t;
			}
		}
		
		/**
		 * Removes and prepends the tail node to the head.
		 */
		public function popDown():void
		{
			var t:DListNode = tail;
			
			if (tail.prev == head)
			{
				tail = head;
				tail.next = null;
				
				head = t;
				head.prev = null;
				
				head.next = tail;
				tail.prev = head;
			}
			else
			{
				tail = tail.prev;
				tail.next = null;
				
				head.prev = t;
				
				t.prev = null;
				t.next = head;
				
				head = t;
			}
		}
		
		/**
		 * Reverses the linked list in place.
		 */
		public function reverse():void
		{
			if (_count == 0) return;
			
			var mark:DListNode;
			var node:DListNode = tail;
			while (node)
			{
				mark = node.prev;
				
				if (!node.next)
				{
					node.next = node.prev;
					node.prev = null;
					head = node;
				}
				else
				if (!node.prev)
				{
					node.prev = node.next;
					node.next = null;
					tail = node;
				}
				else
				{
					var next:DListNode = node.next;
					node.next = node.prev;
					node.prev = next;
				}
				node = mark;
			}
		}
		
		/**
		 * Converts the node data in the linked list to strings,
		 * inserts the given separator between the elements,
		 * concatenates them, and returns the resulting string.
		 * 
		 * @return A string consisting of the nodes converted to
		 *         strings and separated by the specified parameter. 
		 */
		public function join(sep:*):String
		{
			if (_count == 0) return "";
			var s:String = "";
			var node:DListNode = head;
			while (node.next)
			{
				s += node.data + sep;
				node = node.next;
			}
			s += node.data;
			return s;
		}
		
		/**
		 * Checks if a given item exists.
		 * 
		 * @return True if the item is found, otherwise false.
		 */
		public function contains(obj:*):Boolean
		{
			var node:DListNode = head;
			while (node)
			{
				if (node.data == obj) return true;
				node = node.next;
			}
			return false;
		}
		
		/**
		 * Clears the list by unlinking all nodes
		 * from it. This is important to unlock
		 * the nodes for the garbage collector.
		 */
		public function clear():void
		{
			var node:DListNode = head;
			head = null;
			
			var next:DListNode;
			while (node)
			{
				next = node.next;
				node.next = node.prev = null;
				node = next;
			}
			_count = 0;
		}
		
		/**
		 * Creates an iterator object pointing
		 * at the first node in the list.
		 * 
		 * @returns An iterator object.
		 */
		public function getIterator():Iterator
		{
			return new DListIterator(this, head);
		}
		
		/**
		 * Creates a doubly linked iterator object pointing
		 * at the first node in the list.
		 * 
		 * @returns A DListIterator object.
		 */
		public function getListIterator():DListIterator
		{
			return new DListIterator(this, head);
		}
		
		/**
		 * The total number of nodes in the list.
		 */
		public function get size():int
		{
			return _count;
		}
		
		/**
		 * Checks if the list is empty.
		 */
		public function isEmpty():Boolean
		{
			return _count == 0;
		}
		
		/**
		 * Converts the linked list into an array.
		 * 
		 * @return An array.
		 */
		public function toArray():Array
		{
			var a:Array = [];
			var node:DListNode = head;
			while (node)
			{
				a.push(node.data);
				node = node.next;
			}
			return a;
		}
		
		/**
		 * Returns a string representing the current object.
		 */
		public function toString():String
		{
			return "[DLinkedList > has " + size + " nodes]";
		}
		
		/**
		 * Prints out all elements in the list (for debug/demo purposes).
		 */
		public function dump():String
		{
			if (head == null) return "DLinkedList, empty";
			
			var s:String = "DLinkedList, has " + _count + " node" + (_count == 1 ? "" : "s") + "\n|< Head\n";
			
			var itr:DListIterator = getListIterator();
			for (; itr.valid(); itr.forth())
				s += "\t" + itr.data + "\n";
			
			s += "Tail >|";
			
			return s;
		}
	}
}