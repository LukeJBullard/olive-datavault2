<?php 
    /**
     *  Corresponding Class to test Data Vault 2.0 Utility class
     *
     *  @author Luke Bullard
     */

    require_once("../src/Utility.php");

    class UtilityTest extends PHPUnit_Framework_TestCase
    {
        /**
         * Test to ensure that calculateDV2Hash is working with an array as input
         */
        public function testCalculateDV2HashArray()
        {
            //hash of: data1Data2
            $hash = "3779ee8886e99d91ba67d71c622518e082ff98f9bb71d1a4b16c9010";

            //testing array- should only hash the values not the keys
            $array = array("name1" => "data1", "name2" => "Data2");

            $this->assertEquals(DV2_Utility::calculateDV2Hash($array), $hash);
        }

        /**
         * Test to ensure that calculateDV2Hash is working with a string as input
         */
        public function testCalculateDV2HashString()
        {
            //hash of: string
            $hash = "474b4afcaa4303cfc8f697162784293e812f12e2842551d726db8037";

            //testing string
            $string = "string";

            $this->assertEquals(DV2_Utility::calculateDV2Hash($string), $hash);
        }
    }
?>