<?php
namespace App\Models;

use Framework\Database;
use PDO;

class Subject
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getAll()
    {
        $stmt = $this->db->query("SELECT * FROM subject");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}