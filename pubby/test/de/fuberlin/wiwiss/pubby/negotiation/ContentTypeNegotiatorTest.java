package de.fuberlin.wiwiss.pubby.negotiation;

import java.util.regex.Pattern;

import junit.framework.TestCase;

public class ContentTypeNegotiatorTest extends TestCase {
	private ContentTypeNegotiator negotiator;
	
	public void setUp() {
		negotiator = new ContentTypeNegotiator();
	}
	
	public void testNoVariantOnServer() {
		assertNull(negotiator.getBestMatch("a/b"));
	}
	
	public void testMatchSimple() {
		negotiator.addVariant("a/b");
		assertEquals("a/b", negotiator.getBestMatch("a/b").getMediaType());
	}
	
	public void testNoMatch() {
		negotiator.addVariant("a/b");
		assertNull(negotiator.getBestMatch("z/z"));
	}
	
	public void testUseDefaultIfNoMatch() {
		negotiator.addVariant("a/b").makeDefault();
		assertEquals("a/b", negotiator.getBestMatch("z/z").getMediaType());
	}
	
	public void testUseDefaultIfTwoEqualOptions() {
		negotiator.addVariant("a/b");
		negotiator.addVariant("c/d").makeDefault();
		assertEquals("c/d", negotiator.getBestMatch("z/z").getMediaType());
	}
	
	public void testPickFirstIfTwoEqualOptions() {
		negotiator.addVariant("a/b");
		negotiator.addVariant("c/d");
		assertEquals("a/b", negotiator.getBestMatch("*/*").getMediaType());
	}
	
	public void testDefaultToHigherQuality() {
		negotiator.addVariant("a/b;q=0.6");
		negotiator.addVariant("c/d;q=0.8");
		assertEquals("c/d", negotiator.getBestMatch("*/*").getMediaType());
	}
	
	public void testPickCorrectMatchSimple() {
		negotiator.addVariant("a/b");
		negotiator.addVariant("c/d");
		assertEquals("c/d", negotiator.getBestMatch("c/d").getMediaType());
	}
	
	public void testPickCorrectMatchWithClientQuality() {
		negotiator.addVariant("a/b");
		negotiator.addVariant("c/d");
		assertEquals("a/b", negotiator.getBestMatch("a/b;q=0.8,c/d;q=0.6").getMediaType());
		assertEquals("c/d", negotiator.getBestMatch("a/b;q=0.6,c/d;q=0.8").getMediaType());
	}
	
	public void testPickCorrectMatchWithServerQuality1() {
		negotiator.addVariant("a/b;q=0.8");
		negotiator.addVariant("c/d;q=0.6");
		assertEquals("a/b", negotiator.getBestMatch("a/b,c/d").getMediaType());
	}
	
	public void testPickCorrectMatchWithServerQuality2() {
		negotiator.addVariant("a/b;q=0.6");
		negotiator.addVariant("c/d;q=0.8");
		assertEquals("c/d", negotiator.getBestMatch("a/b,c/d").getMediaType());
	}
	
	public void testNoMatchIfBestEqualsZero() {
		negotiator.addVariant("a/b");
		assertNull(negotiator.getBestMatch("a/*;q=0"));
	}
	
	public void testQualityMultiplication() {
		negotiator.addVariant("a/b;q=0.1");
		negotiator.addVariant("c/d;q=0.9");
		negotiator.addVariant("e/f;q=0.8");
		assertEquals("e/f", negotiator.getBestMatch("a/b;q=0.9;c/d;q=0.1;e/f;q=0.8").getMediaType());
	}
	
	public void testEmptyHeader() {
		negotiator.addVariant("a/b");
		assertEquals("a/b", negotiator.getBestMatch("").getMediaType());
		assertEquals("a/b", negotiator.getBestMatch(null).getMediaType());
		assertEquals("a/b", negotiator.getBestMatch("junk").getMediaType());
	}
	
	public void testEmptyHeaderPickBestQuality() {
		negotiator.addVariant("a/b;q=0.2");
		negotiator.addVariant("c/d");
		assertEquals("c/d", negotiator.getBestMatch(null).getMediaType());
	}
	
	public void testAcceptAlias() {
		negotiator.addVariant("a/b").addAliasMediaType("c/d");
		assertEquals("a/b", negotiator.getBestMatch("c/d").getMediaType());
	}
	
	public void testUseAliasQuality() {
		negotiator.addVariant("a/b;q=0.5");
		negotiator.addVariant("c/d").addAliasMediaType("e/f;q=0.1");
		assertEquals("a/b", negotiator.getBestMatch("a/b,e/f").getMediaType());
	}
	
	public void testSpecifyDefaultAccept() {
		negotiator.addVariant("a/b");
		negotiator.addVariant("c/d;q=0.5");
		negotiator.setDefaultAccept("c/d");
		assertEquals("c/d", negotiator.getBestMatch(null).getMediaType());
	}
	
	public void testUserAgentOverrideSimple() {
		negotiator.addVariant("a/b");
		negotiator.addVariant("c/d;q=0.5");
		negotiator.addUserAgentOverride(Pattern.compile(""), null, "c/d");
		assertEquals("c/d", negotiator.getBestMatch("a/b", null).getMediaType());
	}
}
