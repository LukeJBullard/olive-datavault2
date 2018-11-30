<?php 
    /**
     *  Corresponding Class to test Data Vault 2.0 Satellite class
     *
     *  @author Luke Bullard
     */

    require_once("../src/Satellite.php");

    class SatelliteTest extends PHPUnit_Framework_TestCase
    {
        /**
         * Test to ensure there are no syntax errors in the class
         */
        public function testSyntaxError()
        {
            $var = new Satellite("data_Source","2010-08-10 23:40:01","hubHash12345",array(
                "dataName" => "dataValue",
                "dataName2" => "dataValue2"
            ));
            $this->assertTrue(is_object($var));
        }

        /**
         * Test to ensure the hashing is working
         */
        public function testCalculateHashDiff($var)
        {
            $var = new Satellite("data_Source","2010-08-10 23:40:01","hubHash12345",array(
                "dataName" => "dataValue",
                "dataName2" => "dataValue2"
            ));

            //sha224 of: dataValuedataValue2
            $knownValue = "64b8c2b66fa5fb278a54d144140811603d198409b96b094886c2df64";
            $this->assertEquals($var->calculateHashDiff(),$knownValue);
        }

        /**
         * Test to ensure the hash is returned properly
         */
        public function testGetHashDiff()
        {
            $var = new Satellite("data_Source","2010-08-10 23:40:01","hubHash12345",array(
                "dataName" => "dataValue",
                "dataName2" => "dataValue2"
            ));

            //sha224 of: dataValuedataValue2
            $knownValue = "64b8c2b66fa5fb278a54d144140811603d198409b96b094886c2df64";
            $this->assertEquals($var->getHashDiff(),$knownValue);
        }

        /**
         * Test to ensure the date is returned properly.
         */
        public function testGetDate()
        {
            $var = new Satellite("data_Source","2010-08-10 23:40:01","hubHash12345",array(
                "dataName" => "dataValue",
                "dataName2" => "dataValue2"
            ));

            $this->assertEquals($var->getDate(),"2010-08-10 23:40:01");
        }

        /**
         * Test to ensure the source is returned properly.
         * @depends testSyntaxError
         */
        public function testGetSource()
        {
            $var = new Satellite("data_Source","2010-08-10 23:40:01","hubHash12345",array(
                "dataName" => "dataValue",
                "dataName2" => "dataValue2"
            ));

            $this->assertEquals($var->getSource(),"data_Source");
        }

        /**
         * Test to ensure the array of data can be retrieved properly.
         */
        public function testGetData()
        {
            $var = new Satellite("data_Source","2010-08-10 23:40:01","hubHash12345",array(
                "dataName" => "dataValue",
                "dataName2" => "dataValue2"
            ));
            
            $this->assertEquals($var->getData(),array(
                "dataName" => "dataValue",
                "dataName1" => "dataValue2"
            ));
        }
    }
?>