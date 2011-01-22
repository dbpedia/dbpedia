package de.fuberlin.wiwiss.pubby.negotiation;

import java.util.Arrays;
import java.util.List;

import junit.framework.TestCase;

public class MediaRangeSpecTest extends TestCase {

	public void testSimpleSyntax() {
		MediaRangeSpec spec = MediaRangeSpec.parseRange("a/b");
		assertEquals("a", spec.getType());
		assertEquals("b", spec.getSubtype());
		assertEquals("a/b", spec.getMediaType());
		assertNull(spec.getParameter("c"));
		assertTrue(spec.getParameterNames().isEmpty());
	}

	public void testGetType() {
		assertEquals("foo-123_x", MediaRangeSpec.parseRange("foo-123_x/bar").getType());
	}
	
	public void testGetSubtype() {
		assertEquals("foo-123_x", MediaRangeSpec.parseRange("bar/foo-123_x").getSubtype());
	}
	
	public void testGetMediaType() {
		assertEquals("foo-123_x/foo-123_y", 
				MediaRangeSpec.parseRange("foo-123_x/foo-123_y").getMediaType());
	}

	public void testSimpleParameters() {
		MediaRangeSpec spec = MediaRangeSpec.parseRange("a/b;c=d;e=f");
		assertEquals("a", spec.getType());
		assertEquals("b", spec.getSubtype());
		assertEquals("a/b;c=d;e=f", spec.getMediaType());
		assertEquals("d", spec.getParameter("c"));
		assertEquals("f", spec.getParameter("e"));
		assertEquals(Arrays.asList(new String[]{"c", "e"}), spec.getParameterNames());
	}
	
	public void testWithParameters() {
		assertEquals("a/b;c=d", MediaRangeSpec.parseRange("a/b ; c=d").getMediaType());
		assertEquals("a/b;c=d;e=f", MediaRangeSpec.parseRange("a/b ; c=d ; e=f").getMediaType());
	}

	public void testCaseInsensitive() {
		assertEquals("a/b;c=D", MediaRangeSpec.parseRange("A/B;C=D").getMediaType());
	}
	
	public void testQuotedParameter() {
		assertEquals("a/b;c=d", MediaRangeSpec.parseRange("a/b;c=\"d\"").getMediaType());
		assertEquals("a/b;c=\"d e\"", MediaRangeSpec.parseRange("a/b;c=\"d e\"").getMediaType());
	}
	
	public void testParameterValueEscaping() {
		assertEquals("", MediaRangeSpec.parseRange("a/b;c=\"\"").getParameter("c"));
		assertEquals("a", MediaRangeSpec.parseRange("a/b;c=\"\\a\"").getParameter("c"));
		assertEquals("\\", MediaRangeSpec.parseRange("a/b;c=\"\\\\\"").getParameter("c"));
		assertEquals("\"", MediaRangeSpec.parseRange("a/b;c=\"\\\"\"").getParameter("c"));
		assertEquals(" \\ \" a ",
				MediaRangeSpec.parseRange("a/b;c=\" \\\\ \\\" \\a \"").getParameter("c"));
	}
	
	public void testParameterValueEscapingRoundTrip() {
		assertEquals("a/b;c=\" \\\\ \\\" a \"",
				MediaRangeSpec.parseRange("a/b;c=\" \\\\ \\\" \\a \"").getMediaType());
	}
	
	public void testSimpleQuality() {
		MediaRangeSpec m = MediaRangeSpec.parseRange("a/b;q=0.5");
		assertNotNull(m);
		assertNull(m.getParameter("q"));
		assertEquals(0.5, m.getQuality(), 0.00001);
	}
	
	public void testQualityAfterParameter() {
		MediaRangeSpec m = MediaRangeSpec.parseRange("a/b;c=d;q=0.5");
		assertNotNull(m);
		assertEquals("d", m.getParameter("c"));
		assertEquals(0.5, m.getQuality(), 0.00001);
	}
	
	public void testParametersAfterQualityAreIgnored() {
		MediaRangeSpec m = MediaRangeSpec.parseRange("a/b;q=0.5;c=d");
		assertNotNull(m);
		assertNull(m.getParameter("c"));
		assertEquals(0.5, m.getQuality(), 0.00001);
	}
	
	public void testQualityValue() {
		assertEquals(1, MediaRangeSpec.parseRange("a/b").getQuality(), 0.00001);
		assertEquals(1, MediaRangeSpec.parseRange("a/b;q=1").getQuality(), 0.00001);
		assertEquals(1, MediaRangeSpec.parseRange("a/b;q=1.").getQuality(), 0.00001);
		assertEquals(1, MediaRangeSpec.parseRange("a/b;q=1.0").getQuality(), 0.00001);
		assertEquals(1, MediaRangeSpec.parseRange("a/b;q=1.00").getQuality(), 0.00001);
		assertEquals(1, MediaRangeSpec.parseRange("a/b;q=1.000").getQuality(), 0.00001);
		assertEquals(0, MediaRangeSpec.parseRange("a/b;q=0").getQuality(), 0.00001);
		assertEquals(0, MediaRangeSpec.parseRange("a/b;q=0.").getQuality(), 0.00001);
		assertEquals(0, MediaRangeSpec.parseRange("a/b;q=0.0").getQuality(), 0.00001);
		assertEquals(0, MediaRangeSpec.parseRange("a/b;q=0.00").getQuality(), 0.00001);
		assertEquals(0, MediaRangeSpec.parseRange("a/b;q=0.000").getQuality(), 0.00001);
		assertEquals(0.5, MediaRangeSpec.parseRange("a/b;q=0.5").getQuality(), 0.00001);
		assertEquals(0.55, MediaRangeSpec.parseRange("a/b;q=0.55").getQuality(), 0.00001);
		assertEquals(0.555, MediaRangeSpec.parseRange("a/b;q=0.555").getQuality(), 0.00001);
	}

	public void testIllegalQualityValue() {
		assertEquals(1, MediaRangeSpec.parseRange("a/b;q=\"\"").getQuality(), 0.00001);
		assertEquals(1, MediaRangeSpec.parseRange("a/b;q=a").getQuality(), 0.00001);
		assertEquals(1, MediaRangeSpec.parseRange("a/b;q=2").getQuality(), 0.00001);
		assertEquals(1, MediaRangeSpec.parseRange("a/b;q=0.1000").getQuality(), 0.00001);
		assertEquals(1, MediaRangeSpec.parseRange("a/b;q=-0.1").getQuality(), 0.00001);
	}
	
	public void testIllegalSyntax() {
		assertIllegalMediaTypeSyntax("a");
		assertIllegalMediaTypeSyntax(" a/b ");
		assertIllegalMediaTypeSyntax("a / b");
		assertIllegalMediaTypeSyntax("Š/b");
		assertIllegalMediaTypeSyntax("a\na/b");
		assertIllegalMediaTypeSyntax("a/b;c");
		assertIllegalMediaTypeSyntax("a/b;c=");
		assertIllegalMediaTypeSyntax("a/b;c = d");
		assertIllegalMediaTypeSyntax("a/b;c=d e");
		assertIllegalMediaTypeSyntax("a/b;c=\"d\"e");
		assertIllegalMediaTypeSyntax("a/b;c=\"d\"\"");
	}
	
	public void testNoWildcard() {
		assertFalse(MediaRangeSpec.parseRange("a/b").isWildcardType());
		assertFalse(MediaRangeSpec.parseRange("a/b").isWildcardSubtype());
	}
	
	public void testWildcard() {
		assertTrue(MediaRangeSpec.parseRange("*/*").isWildcardType());
		assertFalse(MediaRangeSpec.parseRange("a/*").isWildcardType());
		assertTrue(MediaRangeSpec.parseRange("a/*").isWildcardSubtype());
	}

	public void testIllegalWildcard() {
		assertNull(MediaRangeSpec.parseRange("*/b"));
	}
	
	public void testNoWildcardsInMediaType() {
		assertNull(MediaRangeSpec.parseType("*/*"));
		assertNull(MediaRangeSpec.parseType("a/*"));
	}
	
	public void testAcceptOneRange() {
		List accept = MediaRangeSpec.parseAccept("a/b");
		assertNotNull(accept);
		assertEquals(1, accept.size());
		assertMediaRangeInList(accept, 0, "a/b");
	}
	
	public void testAcceptTwoRanges() {
		List accept = MediaRangeSpec.parseAccept("a/b,c/d");
		assertEquals(2, accept.size());
		assertMediaRangeInList(accept, 0, "a/b");
		assertMediaRangeInList(accept, 1, "c/d");
	}
	
	public void testAcceptIgnoreWSAndJunk() {
		List accept = MediaRangeSpec.parseAccept("a/b , asdf , c/d");
		assertEquals(2, accept.size());
		assertMediaRangeInList(accept, 0, "a/b");
		assertMediaRangeInList(accept, 1, "c/d");
	}

	public void testAcceptWithQuality() {
		List accept = MediaRangeSpec.parseAccept("a/b;q=0.6,c/d;q=0.8");
		assertEquals(2, accept.size());
		assertMediaRangeInList(accept, 0, "a/b");
		assertMediaRangeInList(accept, 1, "c/d");
		assertEquals(0.6, ((MediaRangeSpec) accept.get(0)).getQuality(), 0.00001);
		assertEquals(0.8, ((MediaRangeSpec) accept.get(1)).getQuality(), 0.00001);
	}
	
	public void testGetPrecedence() {
		MediaRangeSpec m = MediaRangeSpec.parseType("a/b;c=d;e=f");
		assertEquals(0, m.getPrecedence(MediaRangeSpec.parseRange("z/b;c=d;e=f")));
		assertEquals(0, m.getPrecedence(MediaRangeSpec.parseRange("a/z;c=d;e=f")));
		assertEquals(0, m.getPrecedence(MediaRangeSpec.parseRange("a/b;x=y")));
		assertEquals(0, m.getPrecedence(MediaRangeSpec.parseRange("a/b;c=z;e=f")));
		assertEquals(0, m.getPrecedence(MediaRangeSpec.parseRange("a/b;c=z;e=z")));
		assertEquals(0, m.getPrecedence(MediaRangeSpec.parseRange("a/b;x=y;c=d")));
		assertEquals(0, m.getPrecedence(MediaRangeSpec.parseRange("a/b;c=d;e=z")));
		assertEquals(1, m.getPrecedence(MediaRangeSpec.parseRange("*/*;c=d;e=f")));
		assertEquals(2, m.getPrecedence(MediaRangeSpec.parseRange("a/*;c=d;e=f")));
		assertEquals(3, m.getPrecedence(MediaRangeSpec.parseRange("a/b")));
		assertEquals(4, m.getPrecedence(MediaRangeSpec.parseRange("a/b;c=d")));
		assertEquals(4, m.getPrecedence(MediaRangeSpec.parseRange("a/b;e=f")));
		assertEquals(5, m.getPrecedence(MediaRangeSpec.parseRange("a/b;e=f;c=d")));
	}

	public void testCaseInsensitiveMatch() {
		assertEquals(4, MediaRangeSpec.parseType("a/b;c=d").getPrecedence(
				MediaRangeSpec.parseType("A/B;C=d")));
	}
	
	public void testGetBestMatchSameQuality() {
		List accept = MediaRangeSpec.parseAccept("*/*,a/*,a/b,a/b;c=d");
		assertEquals("*/*", MediaRangeSpec.parseType("z/b").getBestMatch(accept).getMediaType());
		assertEquals("a/*", MediaRangeSpec.parseType("a/z").getBestMatch(accept).getMediaType());
		assertEquals("a/b", MediaRangeSpec.parseType("a/b").getBestMatch(accept).getMediaType());
		assertEquals("a/b", MediaRangeSpec.parseType("a/b;c=z").getBestMatch(accept).getMediaType());
		assertEquals("a/b", MediaRangeSpec.parseType("a/b;z=d").getBestMatch(accept).getMediaType());
		assertEquals("a/b;c=d", MediaRangeSpec.parseType("a/b;c=d").getBestMatch(accept).getMediaType());
		assertEquals("a/b;c=d", MediaRangeSpec.parseType("a/b;c=d;z=z").getBestMatch(accept).getMediaType());
	}
	
	private void assertIllegalMediaTypeSyntax(String mediaType) {
		assertNull(MediaRangeSpec.parseRange(mediaType));
	}
	
	private void assertMediaRangeInList(List list, int index, String mediaType) {
		MediaRangeSpec range = (MediaRangeSpec) list.get(index);
		assertEquals(mediaType, range.getMediaType());
	}
}
