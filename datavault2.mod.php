<?php
    /**
     * Data Vault 2.0 Module for OliveWeb
     * 
     * @author Luke Bullard
     */

    //make sure we are included securely
    if (!defined("INPROCESS")) { header("HTTP/1.0 403 Forbidden"); exit(0); }

    /**
     * The DataVault 2.0 OliveWeb Module
     */
    class MOD_datavault2
    {
        public function __construct()
        {
            require_once("src/Utility.php");
            require_once("src/Hub.php");
            require_once("src/Satellite.php");
            require_once("src/Link.php");
            require_once("src/HubTable.php");
            require_once("src/SatelliteTable.php");
            require_once("src/LinkTable.php");
            require_once("src/DynamoHubTable.php");
            require_once("src/DynamoLinkTable.php");
            require_once("src/DynamoSatelliteTable.php");
        }
    }
?>