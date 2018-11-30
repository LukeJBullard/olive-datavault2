<?php 
    /**
     *  Corresponding Class to test Data Vault 2.0 HubTable class
     *
     *  @author Luke Bullard
     */

    require_once("../src/Hub.php");
    require_once("../src/HubTable.php");

    class HubTableTest extends PHPUnit_Framework_TestCase
    {
        /**
         * Test to ensure there are no syntax errors in the class
         */
        public function testSyntaxError()
        {
            $var = new HubTable();
            $this->assertTrue(is_object($var));
        }

        /**
         * Test to ensure saveHub is working
         */
        public function testSaveHub()
        {
            $var = new HubTable();
            $hub = new Hub("source","2010-08-10 23:59:40","data");

            $this->assertEquals($var->saveHub($hub),DV2_SUCCESS);
        }

        /**
         * Test to ensure clearHub is working
         */
        public function testClearHub()
        {
            $var = new HubTable();
            $hub = new Hub("source","2010-08-10 23:59:40","data");
            $var->saveHub($hub);

            //sha224 of: data
            $knownValue = "f4739673acc03c424343b452787ee23dd62999a8a9f14f4250995769";

            $this->assertEquals($var->clearHub($knownValue),DV2_SUCCESS);
        }

        /**
         * Test to ensure getHub is working
         */
        public function testGetHub()
        {
            $var = new HubTable();
            $hub = new Hub("source","2018-05-10 10:01:24","moredata");
            
            //sha224 hash of: moredata
            $hash = "0fae60991d0ca4dca90410c7fab8a2ee476af178469ec9a071a326c1";

            $var->saveHub($hub);
            $this->assertEquals($var->getHub($hash),$hub);
        }

        /**
         * Test to ensure hubExists is working
         */
        public function testHubExists()
        {
            $var = new HubTable();
            $hub = new Hub("source","2018-05-05 05:12:24","data123");

            //sha224 hash of: data123
            $hash = "9c698bcbaadde0513a137d36b6587b58eae45a14f2dd6a7e27e7d1f5";

            $var->saveHub($hub);
            $this->assertTrue($var->hubExists($hash));
        }

        /**
         * Test to ensure getFirstHub is working
         */
        public function testGetFirstHub()
        {
            $var = new HubTable();
            $hub = new Hub("source","2014-12-31 01:04:20","data123");
            $hub2 = new Hub("source2","2018-03-20 11:21:02","data456");

            $var->saveHub($hub);
            $var->saveHub($hub2);

            $this->assertEquals($var->getFirstHub(),$hub);
        }

        /**
         * Test to ensure numberOfHubs is working
         */
        public function testNumberOfHubs()
        {
            $var = new HubTable();
            $hub = new Hub("source","2014-12-31 01:04:20","data123");
            $hub2 = new Hub("source2","2018-03-20 11:21:02","data456");

            $var->saveHub($hub);
            $var->saveHub($hub2);

            $this->assertEquals($var->numberOfHubs(), 2);
        }
    }
?>