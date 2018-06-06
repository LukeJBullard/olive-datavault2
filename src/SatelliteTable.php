<?php
    /**
     * Data Vault 2.0 Module for OliveWeb
     * Satellite Table Definition
     * 
     * @author Luke Bullard
     */

    /**
     * A collection of Satellites with in-memory storage for runtime.
     * This class would usually be built upon for use with persistent storage systems.
     */
    class SatelliteTable
    {
        private $m_satellites;

        public function __construct()
        {
            $this->m_satellites = array();
        }

        /**
         * Returns if the Satellite exists in the SatelliteTable
         * 
         * @param String $a_hashDiff The Hash of the Satellite to check for
         * @return Boolean If the Satellite was found in the table
         */
        public function satelliteExists($a_hashDiff)
        {
            return array_key_exists($a_hashDiff,$this->m_satellites);
        }

        /**
         * Saves the Satellite in the Table
         * 
         * @param Satellite $a_satellite The Satellite to save
         * @return Int
         *      DV2_EXISTS if the Satellite already exists
         *      DV2_SUCCESS if the Satellite was added to the Table successfully
         */
        public function saveSatellite($a_satellite)
        {
            //if the satellite already exists
            if ($this->satelliteExists($a_satellite->getHashDiff()))
            {
                return DV2_EXISTS;
            }
            
            array_push($this->m_satellites,$a_satellite);

            return DV2_SUCCESS;
        }

        /**
         * Removes a Satellite from the Table
         * 
         * @param String $a_hashKey The hash of the Satellite to remove from the Table
         * @return Int DV2_SUCCESS or DV2_ERROR
         */
        public function clearSatellite($a_hashKey)
        {
            unset($this->m_satellites[$a_hashKey]);
            return DV2_SUCCESS;
        }

        /**
         * Retrieves a Satellite from the Table
         * 
         * @param String $a_hashKey The hash of the Satellite to retrieve
         * @return Satellite The Satellite retrieved from the Table
         * @return Int DV2_ERROR If the Satellite could not be retrieved
         */
        public function getSatellite($a_hashKey)
        {
            if (isset($this->m_satellites[$a_hashKey]))
            {
                return $this->m_satellites[$a_hashKey];
            }
            return DV2_ERROR;
        }
    }
?>