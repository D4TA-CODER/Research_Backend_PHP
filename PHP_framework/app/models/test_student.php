<?php
require_once 'framework/Autoloader.php';

use Framework\Database;
use App\Models\Student;

$config = require 'config/database.php';
$db = new Database($config);
$studentModel = new Student($db);

// Minimal test data:
$data = [
  'FirstName' => 'John',
  'LastName' => 'Smith',
  'DOB' => '2000-01-01',
  'Gender' => 1,
  'Email' => 'john@example.com',
  'HomeNumber' => '1234567',
  'MobileNumber' => '9999999999',
  'StreetAdd1' => 'Street 1',
  'StreetAdd2' => 'Street 2',
  'City' => 'MyCity',
  'Parish' => 'MyParish',
  'ZIPcode' => '4001',
  'PostalCode' => 'X123',
  'SchoolID' => 1,        // ensure a matching ID in `school`
  'GradeLevelID' => 1,    // ensure a matching ID in `formgradelevel`
  'Month' => 'May',
  'Year' => '2025',
  'Password' => '123456',
];

$newID = $studentModel->create($data);
echo "New Student ID: " . $newID;