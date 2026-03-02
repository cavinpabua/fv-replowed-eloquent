<?php

require_once ('config.php');

class Database {

    private $host = DB_SERVER;
    private $username = DB_USERNAME;
    private $password = DB_PASSWORD;
    private $dbname = DB_NAME;
    private $db = null;

    public function __construct() {

    }

    public function getDb() {
        if ($this->db === null) {
            $this->db = new mysqli($this->host, $this->username, $this->password, $this->dbname);

            if($this->db->connect_error){
                die("ERROR: Could not connect to database. " . $this->db->connect_error);
            }
        }

        return $this->db;
    }

    public function destroy(){
    }
}
?>
