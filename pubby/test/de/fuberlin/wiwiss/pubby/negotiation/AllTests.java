package de.fuberlin.wiwiss.pubby.negotiation;

import junit.framework.Test;
import junit.framework.TestSuite;

public class AllTests {

	public static Test suite() {
		TestSuite suite = new TestSuite(
				"Test for de.fuberlin.wiwiss.pubby.negotiation");
		//$JUnit-BEGIN$
		suite.addTestSuite(MediaRangeSpecTest.class);
		suite.addTestSuite(ContentTypeNegotiatorTest.class);
		suite.addTestSuite(PubbyNegotiatorTest.class);
		//$JUnit-END$
		return suite;
	}
}
