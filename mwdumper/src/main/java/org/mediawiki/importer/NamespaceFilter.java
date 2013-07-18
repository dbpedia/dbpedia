/*
 * MediaWiki import/export processing tools
 * Copyright 2005 by Brion Vibber
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
 * $Id: NamespaceFilter.java 23804 2007-07-06 22:14:33Z brion $
 */

package org.mediawiki.importer;

import java.util.HashMap;

public class NamespaceFilter extends PageFilter {
	boolean invert;
	HashMap matches;
	
	public NamespaceFilter(DumpWriter sink, String configString) {
		super(sink);
		
		invert = configString.startsWith("!");
		if (invert)
			configString = configString.substring(1);
		matches = new HashMap();
		
		String[] namespaceKeys = {
			"NS_MAIN",
			"NS_TALK",
			"NS_USER",
			"NS_USER_TALK",
			"NS_PROJECT",
			"NS_PROJECT_TALK",
			"NS_IMAGE",
			"NS_IMAGE_TALK",
			"NS_MEDIAWIKI",
			"NS_MEDIAWIKI_TALK",
			"NS_TEMPLATE",
			"NS_TEMPLATE_TALK",
			"NS_HELP",
			"NS_HELP_TALK",
			"NS_CATEGORY",
			"NS_CATEGORY_TALK" };
		
		String[] itemList = configString.trim().split(",");
		for (int i = 0; i < itemList.length; i++) {
			String keyString = itemList[i];
			String trimmed = keyString.trim();
			try {
				int key = Integer.parseInt(trimmed);
				matches.put(new Integer(key), trimmed);
			} catch (NumberFormatException e) {
				for (int key = 0; key < namespaceKeys.length; key++) {
					if (trimmed.equalsIgnoreCase(namespaceKeys[key]))
						matches.put(new Integer(key), trimmed);
				}
			}
		}
	}
	
	protected boolean pass(Page page) {
		return invert ^ matches.containsKey(page.Title.Namespace);
	}
}
