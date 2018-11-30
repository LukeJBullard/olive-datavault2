<?php 
    /**
     *  Corresponding Class to test Data Vault 2.0 Hub class
     *
     *  @author Luke Bullard
     */

    require_once("../src/Hub.php");

    class HubTest extends PHPUnit_Framework_TestCase
    {
        
        /**
         * Test to ensure there are no syntax errors in the class
         */
        public function testSyntaxError()
        {
            $var = new Hub("data_Source","2010-08-10 23:40:01","exampleData123@");
            $this->assertTrue(is_object($var));
        }

        /**
         * Test to ensure the hashing is working
         */
        public function testCalculateHash()
        {
            $var = new Hub("data_Source","2010-08-10 23:40:01","exampleData123@");

            //sha224 of: exampleData123@
            $knownValue = "c67141e1a10e909b977be38b0f2a388497dfc8f2d66072035e349ae9";
            $this->assertEquals($var->calculateHash(),$knownValue);
        }

        /**
         * Test to ensure the hash is returned properly
         */
        public function testGetHashKey()
        {
            $var = new Hub("data_Source","2010-08-10 23:40:01","exampleData123@");

            //sha224 of: exampleData123@
            $knownValue = "c67141e1a10e909b977be38b0f2a388497dfc8f2d66072035e349ae9";
            $this->assertEquals($var->getHashKey(),$knownValue);
        }

        /**
         * Test to ensure the date is returned properly.
         */
        public function testGetDate()
        {
            $var = new Hub("data_Source","2010-08-10 23:40:01","exampleData123@");

            $this->assertEquals($var->getLoadDate(),"2010-08-10 23:40:01");
        }

        /**
         * Test to ensure the source is returned properly.
         */
        public function testGetSource()
        {
            $var = new Hub("data_Source","2010-08-10 23:40:01","exampleData123@");

            $this->assertEquals($var->getSource(),"data_Source");
        }

        /**
         * Test to ensure the data is retrieved properly.
         */
        public function testGetData($var)
        {
            $var = new Hub("data_Source","2010-08-10 23:40:01","exampleData123@");
            
            $this->assertEquals($var->getData(),"exampleData123@");
        }
    }
?>