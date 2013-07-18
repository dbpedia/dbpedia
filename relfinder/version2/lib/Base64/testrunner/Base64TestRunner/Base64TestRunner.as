import com.dynamicflash.util.tests.Base64Test;
import flexunit.framework.TestSuite;
			
private function onCreationComplete():void {
	testRunner.test = createSuite();
	testRunner.startTest();	
}

private function createSuite():TestSuite {
	var ts:TestSuite = new TestSuite();

	ts.addTestSuite(Base64Test);
	
	return ts;
}
