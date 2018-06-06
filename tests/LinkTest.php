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
         * 
         * @return Link Returns testing Link if successful
         */
        public function testSyntaxError()
        {
            $var = new Link("data_Source","2010-08-10 23:40:01",array(
                "hub" => "value",
                "hub2" => "value2"
            ));
            $this->assertTrue(is_object($var));

            return $var;
        }

        /**
         * Test to ensure the hashing is working
         * 
         * @param Link $var The link to test with. Exported from testSyntaxError.
         * @return Link Returns testing Link if successful
         * @depends testSyntaxError
         */
        public function testCalculateHash($var)
        {
            //sha224 of: valuevalue2
            $knownValue = "00bdc34690cbabac14f22c218eea72016f54fd7612c11883bd0f375d";
            $this->assertEquals($var->calculateHash(),$knownValue);

            return $var;
        }

        /**
         * Test to ensure the hash is returned properly
         * 
         * @param Link $var The link to test with. Exported from testCalculateHash.
         * @depends testCalculateHash
         */
        public function testGetHashKey($var)
        {
            //sha224 of: valuevalue2
            $knownValue = "00bdc34690cbabac14f22c218eea72016f54fd7612c11883bd0f375d";
            $this->assertEquals($var->getHashKey(),$knownValue);
        }

        /**
         * Test to ensure the date is returned properly.
         * 
         * @param Link $var The link to test with
         * @depends testSyntaxError
         */
        public function testGetDate($var)
        {
            $this->assertEquals($var->getDate(),"2010-08-10 23:40:01");
        }

        /**
         * Test to ensure the source is returned properly.
         * 
         * @param Link $var The link to test with
         * @depends testSyntaxError
         */
        public function testGetSource($var)
        {
            $this->assertEquals($var->getSource(),"data_Source");
        }

        /**
         * Test to ensure the array of links are retrieved properly.
         * 
         * @param Link $var The link to test with
         * @depends testSyntaxError
         * @return Link Returns testing link if successful
         */
        public function testGetLinks($var)
        {
            $this->assertEquals($var->getLinks(),array(
                "hub" => "value",
                "hub2" => "value2"
            ));

            return $var;
        }

        /**
         * Test to ensure links are retrieved properly.
         * 
         * @param Link $var The link to test with
         * @depends testGetLinks
         */
        public function testGetLink($var)
        {
            $this->assertEquals($var->getLink("hub"),"value");
            $this->assertEquals($var->getLink("hub2"),"value2");
        }
    }
?>