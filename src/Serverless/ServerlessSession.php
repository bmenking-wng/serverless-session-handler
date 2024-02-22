<?php

namespace WorldNewsGroup\Serverless;

class ServerlessSession implements \SessionHandlerInterface {
    private $db;
    private $enabled;
    private $tableName;
    private $data;
    private $marshaler;
    public $last_error;

    /**
     * 
     * @param mixed $tablename 
     * @param string $region 
     * @return void 
     */
    public function __construct($tablename, $region = 'us-east-1') {

        $this->db = new \Aws\DynamoDb\DynamoDbClient([
            'region'=>$region,
            'version'=>'latest'
        ]);

        $this->tableName = $tablename;
        $this->enabled = false;

        $this->marshaler = new \Aws\DynamoDb\Marshaler();

        session_set_save_handler($this, true);
        return session_start();        
    }

    /**
     * 
     * @return bool 
     */
    public function close(): bool {
        return true;
    }

    /**
     * 
     * @param string $id 
     * @return bool 
     * @throws ServerlessSessionStorageFault 
     */
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
            throw new ServerlessSessionStorageFault();
        }
    }

    /**
     * 
     * @param int $max_lifetime 
     * @return int|false 
     */
    public function gc($max_lifetime): int|false  {
        return false;
    }

    /**
     * 
     * @param string $path 
     * @param string $name 
     * @return bool 
     * @throws ServerlessSessionStorageFault 
     */
    public function open($path, $name): bool {
        try {
            $table = $this->db->describeTable([
                'TableName'=>$this->tableName
            ]);

            $this->enabled = true;
        }
        catch(\Exception $e) {
            throw new ServerlessSessionStorageFault();
        }

        return $this->enabled;
    }

    /**
     * 
     * @param string $id 
     * @return string|false 
     * @throws ServerlessSessionStorageFault 
     */
    public function read($id): string|false  {
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
            throw new ServerlessSessionStorageFault();
        }

        return false;
    }

    /**
     * 
     * @param string $id 
     * @param string $data 
     * @return bool 
     */
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
}

class SessionPayload {
    public $id;
    public $lastModified;
    public $payload;
}