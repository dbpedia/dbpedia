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
 * $Id: Title.java 11268 2005-10-10 06:57:30Z vibber $
 */

package org.mediawiki.importer;

public class Title {
	public Integer Namespace;
	public String Text;
	
	private NamespaceSet namespaces;
	
	public Title(Integer namespaceKey, String text, NamespaceSet namespaces) {
		this.namespaces = namespaces;
		Namespace = namespaceKey;
		Text = text;
	}
	
	public Title(String prefixedTitle, NamespaceSet namespaces) {
		this.namespaces = namespaces;
		int colon = prefixedTitle.indexOf(':');
		if (colon > 0) {
			String prefix = prefixedTitle.substring(0, colon);
			if (namespaces.hasPrefix(prefix)) {
				Namespace = namespaces.getIndex(prefix);
				Text = prefixedTitle.substring(colon + 1);
				return;
			}
		}
		Namespace = new Integer(0);
		Text = prefixedTitle;
	}
	
	public static String ValidateTitleChars(String text) {
		// FIXME
		return text;
	}
	
	public String toString() {
		String prefix = namespaces.getPrefix(Namespace);
		if (Namespace.intValue() == 0)
			return prefix.concat(Text);
		return prefix + ':' + Text;
	}
	
	public boolean isSpecial() {
		return Namespace.intValue() < 0;
	}
	
	public boolean isTalk() {
		return !isSpecial() && (Namespace.intValue() % 2 == 1);
	}
	
	public Title talkPage() {
		if (isTalk())
			return this;
		else if (isSpecial())
			return null;
		else
			return new Title(new Integer(Namespace.intValue() + 1), Text, namespaces);
	}
	
	public Title subjectPage() {
		if (isTalk())
			return new Title(new Integer(Namespace.intValue() - 1), Text, namespaces);
		else
			return this;
	}
	
	public int hashCode() {
		return Namespace.hashCode() ^ Text.hashCode();
	}
	
	public boolean equals(Object other) {
		if (other == this)
			return true;
		if (other instanceof Title) {
			Title ot = (Title)other;
			return Namespace.equals(ot.Namespace) &&
				Text.equals(ot.Text);
		}
		return false;
	}
}
