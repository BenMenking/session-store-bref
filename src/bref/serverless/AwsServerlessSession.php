<?php

namespace benmenking\bref\serverless;

class AwsServerlessSession implements \SessionHandlerInterface {
    private $db;
    private $enabled;
    private $tableName;
    private $data;
    private $marshaler;
    private $logger;

    public function __construct($tablename, $region = 'us-east-1', $log = null) {

        $this->db = new \Aws\DynamoDb\DynamoDbClient([
            'region'=>$region,
            'version'=>'latest'
        ]);

        $this->tableName = $tablename;
        $this->enabled = false;

        $this->marshaler = new \Aws\DynamoDb\Marshaler();
        $this->logger = $log;
    }

    public function setup() {
        session_set_cookie_params(2629799);
        session_set_save_handler($this, true);
        session_start();
    }

    public function close(): bool {
        return true;
    }

    public function destroy($id): bool {
        if( !$this->enabled ) return false;

        try {
            $result = $this->db->deleteItem([
                'TableName'=>$this->tableName,
                'Key'=>[
                    'id'=>[
                        'S'=>$id
                    ]
                ]
            ]);
            return true;
        }
        catch(\Exception $e) {
            return false;
        }
    }

    public function gc($max_lifetime): int|false {
        $this->log("gc($max_lifetime)");
        return false;
    }

    public function open($path, $name): bool {
        try {
            $table = $this->db->describeTable([
                'TableName'=>$this->tableName
            ]);

            $this->enabled = true;
        }
        catch(\Exception $e) {
            // table doesn't exist... just don't care right now?
        }

        return $this->enabled;
    }

    public function read($id): string|false {
        if( !$this->enabled ) return false;

        try {
            $result = $this->db->getItem([
                'TableName'=>$this->tableName,
                'Key'=>[
                    'id'=>[
                        'S'=>$id
                    ]
                ]
            ]);

            if( isset($result['Item']) > 0 ) {
                $this->data = $this->marshaler->unMarshalItem($result['Item']);
            }
            else {
                $this->data['payload'] = '';
            }

            return $this->data['payload'];
        }
        catch(\Exception $e) {
            return false;
        }

    }

    public function write($id, $data): bool {
        if( !$this->enabled ) return false;

        $this->data['id'] = $id;
        $this->data['payload'] = $data;
        $this->data['lastModified'] = time();

        try {
            $result = $this->db->putItem([
                'TableName'=>$this->tableName,
                'Item'=>$this->marshaler->marshalItem($this->data)
            ]);
            return true;
        }
        catch(\Exception $e) {
            return false;
        }
    }

    private function log($txt) {
        if( !is_null($this->logger) ) $this->logger->log($txt);
    }
}

class SessionPayload {
    public $id;
    public $lastModified;
    public $payload;
}