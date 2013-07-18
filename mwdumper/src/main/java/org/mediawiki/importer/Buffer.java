/*
 * MediaWiki import/export processing tools
 * Copyright (C) 2005 by Brion Vibber
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * $Id: Buffer.java 11515 2005-10-26 09:33:48Z vibber $
 */

package org.mediawiki.importer;

import java.util.IdentityHashMap;

public final class Buffer {

	private Buffer() {}

	private static final IdentityHashMap BUFFERS = new IdentityHashMap();

	private static Thread lastThread;
	private static char[] lastBuffer;

	public static synchronized char[] get(int capacity) {
		final Thread thread = Thread.currentThread();
		char[] buffer;

		if (lastThread == thread) {
			buffer = lastBuffer;
		} else {
			lastThread = thread;
			buffer = lastBuffer = (char[]) BUFFERS.get(thread);
		}

		if (buffer == null) {
			buffer = lastBuffer = new char[capacity];
			BUFFERS.put(thread, buffer);
		} else if (buffer.length < capacity) {
			int newsize = buffer.length * 2;
			if (newsize < capacity)
				newsize = capacity;
			/*
			// Debug!
			System.err.println("** Growing buffer to " + newsize);
			try {
				throw new RuntimeException("foo");
			} catch (RuntimeException e) {
				e.printStackTrace();
			}
			*/
			buffer = lastBuffer = new char[newsize];
			BUFFERS.put(thread, buffer);
		}

		return buffer;
	}
}
