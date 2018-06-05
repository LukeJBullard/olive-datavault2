<?php
    /**
     * Data Vault 2.0 Module for OliveWeb
     * Hub Definition
     * 
     * @author Luke Bullard
     */

    namespace Lbullard\Datavault2;

    /**
     * A Data Vault 2.0 Hub
     */
    class Hub
    {
        private $m_source;
        private $m_loadDate;
        private $m_hashKey;
        private $m_dataFieldValue;

        /**
         * Constructor for a Hub
         * 
         * @param String $a_source The data source that this hub was originally loaded from
         * @param String $a_loadDate The date that the hub was originally loaded on.
         * @param String $a_data The unique data that defines this Hub.
         */
        public function __construct($a_source="", $a_loadDate="", $a_data="")
        {

            $this->m_source = $a_source;
            $this->m_loadDate = $a_loadDate;
            $this->m_dataFieldValue = $a_data;
            $this->calculateHash();
        }

        /**
         * Retrieves the hash of the Hub
         * 
         * @return String The hash of the Hub
         */
        public function getHashKey()
        {
            return $this->m_hashKey;
        }

        /**
         * Retrieves the Hub's unique data
         * 
         * @return String The Hub's unique data
         */
        public function getData()
        {
            return $this->m_dataFieldValue;
        }

        /**
         * Retrieves the original source of the Hub
         * 
         * @return String The Hub's original source
         */
        public function getSource()
        {
            return $this->m_source;
        }

        /**
         * Retrieves the original date the Hub was loaded
         * 
         * @return String The date the Hub was originally loaded
         */
        public function getLoadDate()
        {
            return $this->m_loadDate;
        }

        /**
         * Regenerates the SHA224 Hash of the hub
         * 
         * @return String The hub's regenerated hash
         */
        public function calculateHash()
        {
            $this->m_hashKey = Utility::calculateDV2Hash($this->m_dataFieldValue);
            return $this->m_hashKey;
        }

        public function loadSatellite($a_index=SATELLITE_LATEST) { return DV2_NOT_IMPLEMENTED; }
        public function numberOfSatellites() { return DV2_NOT_IMPLEMENTED; }
    }
?>