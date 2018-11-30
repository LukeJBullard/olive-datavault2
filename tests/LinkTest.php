<?php 
    /**
     *  Corresponding Class to test Data Vault 2.0 Link class
     *
     *  @author Luke Bullard
     */

    require_once("../src/Link.php");

    class LinkTest extends PHPUnit_Framework_TestCase
    {
        /**
         * Test to ensure there are no syntax errors in the class
         */
        public function testSyntaxError()
        {
            $var = new Link("data_Source","2010-08-10 23:40:01",array(
                "hub" => "value",
                "hub2" => "value2"
            ));
            $this->assertTrue(is_object($var));
        }

        /**
         * Test to ensure the hashing is working
         */
        public function testCalculateHash()
        {
            $var = new Link("data_Source","2010-08-10 23:40:01",array(
                "hub" => "value",
                "hub2" => "value2"
            ));

            //sha224 of: valuevalue2
            $knownValue = "00bdc34690cbabac14f22c218eea72016f54fd7612c11883bd0f375d";
            $this->assertEquals($var->calculateHash(),$knownValue);
        }

        /**
         * Test to ensure the hash is returned properly
         */
        public function testGetHashKey()
        {
            $var = new Link("data_Source","2010-08-10 23:40:01",array(
                "hub" => "value",
                "hub2" => "value2"
            ));

            //sha224 of: valuevalue2
            $knownValue = "00bdc34690cbabac14f22c218eea72016f54fd7612c11883bd0f375d";
            $this->assertEquals($var->getHashKey(),$knownValue);
        }

        /**
         * Test to ensure the date is returned properly.
         */
        public function testGetDate()
        {
            $var = new Link("data_Source","2010-08-10 23:40:01",array(
                "hub" => "value",
                "hub2" => "value2"
            ));

            $this->assertEquals($var->getDate(),"2010-08-10 23:40:01");
        }

        /**
         * Test to ensure the source is returned properly.
         */
        public function testGetSource()
        {
            $var = new Link("data_Source","2010-08-10 23:40:01",array(
                "hub" => "value",
                "hub2" => "value2"
            ));

            $this->assertEquals($var->getSource(),"data_Source");
        }

        /**
         * Test to ensure the array of links are retrieved properly.
         */
        public function testGetLinks()
        {
            $var = new Link("data_Source","2010-08-10 23:40:01",array(
                "hub" => "value",
                "hub2" => "value2"
            ));

            $this->assertEquals($var->getLinks(),array(
                "hub" => "value",
                "hub2" => "value2"
            ));
        }

        /**
         * Test to ensure links are retrieved properly.
         */
        public function testGetLink()
        {
            $var = new Link("data_Source","2010-08-10 23:40:01",array(
                "hub" => "value",
                "hub2" => "value2"
            ));
            
            $this->assertEquals($var->getLink("hub"),"value");
            $this->assertEquals($var->getLink("hub2"),"value2");
        }
    }
?>