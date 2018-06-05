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
        public function __constructor()
        {
            require_once("vendor/autoload.php");
        }
    }
?>