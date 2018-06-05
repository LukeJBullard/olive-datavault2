<?php
    /**
     * Data Vault 2.0 Module for OliveWeb
     * Hub Table backed by AWS DynamoDB for Storage
     * 
     * @author Luke Bullard
     */

    namespace Lbullard\Datavault2;

    use Aws\DynamoDb\DynamoDbClient;

    /**
     * A HubTable that saves Hubs to a AWS DynamoDB Table
     */
    class DynamoHubTable extends HubTable
    {
        private $m_tableName;
        private $m_dataFieldName;
        private $m_sourceFieldName;
        private $m_loadDateFieldName;
        private $m_hashKeyFieldName;
        protected $m_dynamo;

        /**
         * Constructor for DynamoHubTable
         * 
         * @param String $a_tableName The name of the DynamoDB table to store the Hubs in
         * @param String $a_dataFieldName The name of the column in the DynamoDB that stores the unique identifying data of the Hub
         * @param String $a_sourceFieldName The name of the column in the DynamoDB that stores the initial source of the Hub
         * @param String $a_loadDateFieldName The name of the column in the DynamoDB that stores the initial load date of the Hub
         * @param String $a_hashKeyFieldName The name of the column in the DynamoDB that stores the hash of the Hub (Primary key)
         */
        public function __construct($a_tableName,$a_dataFieldName, $a_sourceFieldName, $a_loadDateFieldName, $a_hashKeyFieldName)
        {
            $this->m_tableName = $a_tableName;
            $this->m_dataFieldName = $a_dataFieldName;
            $this->m_sourceFieldName = $a_sourceFieldName;
            $this->m_loadDateFieldName = $a_loadDateFieldName;
            $this->m_hashKeyFieldName = $a_hashKeyFieldName;

            $aws = Modules::getInstance()['aws'];
            $this->m_dynamo = $aws->getDynamoDB();
        }

        /**
         * Saves the hub to a DynamoDB. If the Hub already exists, skips it.
         * 
         * @param Hub $a_hub The Hub to save to the DynamoDB
         * @return Int DV2_SUCCESS or DV2_ERROR
         */
        public function saveHub($a_hub)
        {
            //get the hub data into an array to send to dynamo
            $item = array(
                $this->m_hashKeyFieldName => array("S" => $a_hub->getHashKey()),
                $this->m_dataFieldName => array("S" => $a_hub->getData()),
                $this->m_sourceFieldName => array("S" => $a_hub->getSource()),
                $this->m_loadDateFieldName => array("S" => date("Y-m-d H:i:s"))
            );

            try {
                $result = $this->m_dynamo->putItem(array(
                    'TableName' => $this->m_tableName,
                    'Item' => $item,
                    'ConditionExpression' => "attribute_not_exists(" . $this->m_hashKeyFieldName . ")"
                ));
            } catch (Aws\DynamoDb\Exception\ConditionalCheckFailedException $e) {
                //skip, do nothing
            } catch (Exception $e) {
                echo "Exception: " . $e->getMessage() . "<br />";
                die("Error Saving Hub: " . $a_hub->getData());
            }

            return DV2_SUCCESS;
        }

        /**
         * Retrieves if the hub already exists in the DynamoDB
         * 
         * @param String $a_hashKey The Hash of the hub to look for
         * @return Boolean If the hub exists
         */
        public function hubExists($a_hashKey)
        {
            return $this->m_dynamo->getIterator('Query',array(
                'TableName' => $this->m_tableName,
                'Limit' => 1,
                'KeyConditions' => array(
                    $this->m_hashKeyFieldName => array(
                        'AttributeValueList' => array(
                            array('S' => $a_hashKey)
                        ),
                        'ComparisonOperator' => 'EQ'
                    )
                )
            ))->count() > 0;
        }

        /**
         * Retrieves a hub from the DynamoDB
         * 
         * @param String $a_hashKey The hash of the Hub to look for
         * @return Hub The retrieved Hub from the DynamoDB
         * @return Int DV2_ERROR If the Hub could not be retrieved or does not exist
         */
        public function getHub($a_hashKey)
        {
            $result = $this->m_dynamo->getIterator('Query',array(
                'TableName' => $this->m_tableName,
                'Limit' => 1,
                'KeyConditions' => array(
                    $this->m_hashKeyFieldName => array(
                        'AttributeValueList' => array(
                            array('S' => $a_hashKey)
                        )
                    )
                )
            ));
            
            foreach ($result as $item)
            {
                $data = "";
                $loadDate = "";

                foreach ($item as $fieldName => $field)
                {
                    switch ($fieldName)
                    {
                        case $this->m_dataFieldName:
                            foreach ($field as $key => $val)
                            {
                                $data = $val;
                                break;
                            }
                            continue;
                        case $this->m_loadDateFieldName:
                            foreach ($field as $key => $val)
                            {
                                $loadDate = $val;
                                break;
                            }
                            continue;
                        case $this->m_sourceFieldName:
                            foreach ($field as $key => $val)
                            {
                                $sourceField = $val;
                                break;
                            }
                            continue;
                    }
                }
                return new Hub(
                    $this,
                    $source,
                    $date,
                    $data
                );
            }

            return DV2_ERROR;
        }

        /**
         * Deletes a Hub from the DynamoDB
         * 
         * @param String $a_hashKey The Hash of the Hub to delete
         * @return Int DV2_SUCCESS or DV2_ERROR
         */
        public function clearHub($a_hashKey)
        {
            $result = $this->m_dynamo->getIterator('Query',array(
                'TableName' => $this->m_tableName,
                'Limit' => 1,
                'KeyConditions' => array(
                    $this->m_hashKeyFieldName => array(
                        'AttributeValueList' => array(
                            array('S' => $a_hashKey)
                        )
                    )
                )
            ));

            foreach ($result as $item)
            {
                $date = $item[$this->m_loadDateFieldName]["S"];
                break;
            }

            if (!isset($date))
            {
                return DV2_SUCCESS;
            }

            $this->m_dynamo->deleteItem(array(
                'TableName' => $this->m_tableName,
                'Key' => array(
                    $this->m_hashKeyFieldName => $a_hashKey,
                    $this->m_loadDateFieldName => $date
                )
            ));
            return DV2_SUCCESS;
        }
    }
?>