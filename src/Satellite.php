<?php
    /**
     * Data Vault 2.0 Module for OliveWeb
     * Satellite Definition
     *
     * @author Luke Bullard
     */

    require_once("Utility.php");

    /**
     * A structure to model a Data Vault 2.0 satellite.
     */
    class Satellite
    {
        private $m_source;
        private $m_loadDate;
        private $m_hashDiff;
        private $m_data;
        private $m_hubHash;

        /**
         * Constructor for Satellite
         * 
         * @param String $a_source The data source that the Satellite was loaded from.
         * @param String $a_loadDate The date that the Satellite was loaded into the Vault.
         * @param String $a_hubHash The hash of the Hub that this Satellite is linked to.
         * @param Array $a_data An associative array with the hub's data in it.
         */
        public function __construct($a_source="",$a_loadDate="",$a_hubHash="",$a_data=array())
        {
            $this->m_source = $a_source;
            $this->m_loadDate = $a_loadDate;
            $this->m_hubHash = $a_hubHash;
            $this->m_data = $a_data;
            $this->calculateHashDiff();
        }

        /**
         * Regenerates the hash for the Satellite based on it's data.
         * 
         * @return String The Satellite's Hash
         */
        public function calculateHashDiff()
        {
            $this->m_hashDiff = DV2_Utility::calculateDV2Hash($this->m_data);
            return $this->m_hashDiff;
        }

        /**
         * Retrieves the source that the Satellite was loaded from first.
         * 
         * @return String The source that the Satellite was loaded from.
         */
        public function getSource()
        {
            return $this->m_source;
        }

        /**
         * Retrieves the date that the Satellite was loaded first.
         * 
         * @return String The date that the Satellite was loaded.
         */
        public function getDate()
        {
            return $this->m_loadDate;
        }

        /**
         * Retrieves the current hash for the Satellite.
         * 
         * @return String The Satellite's hash
         */
        public function getHashDiff()
        {
            return $this->m_hashDiff;
        }

        /**
         * Retrieves the hash of the Hub that the Satellite is linked to.
         * 
         * @return String The parent Hub's hash
         */
        public function getHubHash()
        {
            return $this->m_hubHash;
        }

        /**
         * Retrieves the data of the Satellite.
         * 
         * @return Array An associative array of the Satellite's data
         */
        public function getData()
        {
            return $this->m_data;
        }
    }
?>