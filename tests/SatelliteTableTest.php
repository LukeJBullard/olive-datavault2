<?php 
    /**
     *  Corresponding Class to test Data Vault 2.0 Satellite Table class
     *
     *  @author Luke Bullard
     */

    require_once("../src/Satellite.php");
    require_once("../src/SatelliteTable.php");

    class SatelliteTableTest extends PHPUnit_Framework_TestCase
    {
        /**
         * Test to ensure there are no syntax errors in the class
         */
        public function testSyntaxError()
        {
            $var = new SatelliteTable();
            $this->assertTrue(is_object($var));
        }

        /**
         * Test to ensure saveSatellite is working
         */
        public function testSaveSatellite()
        {
            $var = new SatelliteTable();
            $satellite = new Satellite("source","2014-01-20 20:04:12","hubhash",array("field" => "value"));
            
            //sha224 of: value
            $hash = "ad1b457f7b3a2263a079997a25f7253cd23f3c953e41842448d9c3fc";

            $this->assertEquals($var->saveSatellite($satellite), DV2_SUCCESS);

            $this->assertTrue($var->satelliteExists($hash));
        }

        /**
         * Test to ensure satelliteExists is working
         */
        public function testSatelliteExists()
        {
            $var = new SatelliteTable();
            $satellite = new Satellite("source","2014-01-20 20:04:12","hubhash",array("field" => "value"));

            //sha224 of: value
            $hash = "ad1b457f7b3a2263a079997a25f7253cd23f3c953e41842448d9c3fc";

            $var->saveSatellite($satellite);

            $this->assertTrue($var->satelliteExists($hash));
            $this->assertFalse($var->satelliteExists("abcd1234"));
        }

        /**
         * Test to ensure clearSatellite is working
         */
        public function testClearSatellite()
        {
            $var = new SatelliteTable();
            $satellite = new Satellite("source","2014-01-20 20:04:12","hubhash",array("field" => "value"));

            //sha224 of: value
            $hash = "ad1b457f7b3a2263a079997a25f7253cd23f3c953e41842448d9c3fc";

            $var->saveSatellite($satellite);

            $this->assertTrue($var->satelliteExists($hash));

            $this->assertEquals($var->clearSatellite($hash), DV2_SUCCESS);

            $this->assertFalse($var->satelliteExists($hash));
        }

        /**
         * Test to ensure getSatellite is working
         */
        public function testGetSatellite()
        {
            $var = new SatelliteTable();
            $satellite = new Satellite("source","2014-01-20 20:04:12","hubhash",array("field" => "value"));

            //sha224 of: value
            $hash = "ad1b457f7b3a2263a079997a25f7253cd23f3c953e41842448d9c3fc";

            $var->saveSatellite($satellite);

            $this->assertTrue($var->satelliteExists($hash));

            $this->assertEquals($var->getSatellite($hash), $satellite);

            $this->assertEquals($var->getSatellite("abcd1234"), DV2_ERROR);
        }
    }
?>