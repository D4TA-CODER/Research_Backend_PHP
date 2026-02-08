<?php
namespace App\Models;

use Framework\Database;
use PDO;

class Student
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function createStudent(array $data)
    {
        // Insert a new student record (now that lastName is VARCHAR(25), we can store a real string)
        $sql = "INSERT INTO student (
                    firstName,
                    lastName,
                    dob,
                    genderID,
                    emailAddress,
                    homeNumber,
                    mobileNumber,
                    streetAddress1,
                    streetAddress2,
                    cityTownVillage,
                    parishStateProvidence,
                    zipCode,
                    postalCode,
                    schoolID,
                    formGradeLevelID,
                    examSittingRecord,
                    password
                ) VALUES (
                    :firstName,
                    :lastName,
                    :dob,
                    :genderID,
                    :emailAddress,
                    :homeNumber,
                    :mobileNumber,
                    :streetAddress1,
                    :streetAddress2,
                    :cityTownVillage,
                    :parishStateProvidence,
                    :zipCode,
                    :postalCode,
                    :schoolID,
                    :formGradeLevelID,
                    :examSittingRecord,
                    :password
                )";

        // For real-world use, you want password hashing. But your DB only has VARCHAR(15).
        // Example: store plain text or a short hash. (Better fix is to expand the column.)
        $examSitting = $data['Month'].' '.$data['Year'];

        $params = [
            'firstName'             => $data['FirstName'],
            'lastName'              => $data['LastName'],  // Now a string
            'dob'                   => $data['DOB'],
            'genderID'              => $data['Gender'],     // e.g., 1 or 2
            'emailAddress'          => $data['Email'],
            'homeNumber'            => $data['HomeNumber'],
            'mobileNumber'          => $data['MobileNumber'],
            'streetAddress1'        => $data['StreetAdd1'],
            'streetAddress2'        => $data['StreetAdd2'],
            'cityTownVillage'       => $data['City'],
            'parishStateProvidence' => $data['Parish'],
            'zipCode'               => $data['ZIPcode'],
            'postalCode'            => $data['PostalCode'],
            'schoolID'              => $data['SchoolID'],       // Provide valid IDs
            'formGradeLevelID'      => $data['GradeLevelID'],   // Provide valid IDs
            'examSittingRecord'     => $data['Month'].' '.$data['Year'],
            'password'              => $data['Password'],  // Plain text if the column is too short
        ];

        $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }




    public function addStudentSubjects($studentID, array $subjectIDs)
    {
        foreach ($subjectIDs as $sub) {
            if (!empty($sub)) {
                $this->db->query("
                    INSERT INTO studentsubjects (studentID, subjectID)
                    VALUES (:studentID, :subjectID)
                ", [
                    'studentID' => $studentID,
                    'subjectID' => $sub
                ]);
            }
        }
    }

    public function getAll()
    {
        $sql = "SELECT * FROM student";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $sql = "SELECT * FROM student WHERE studentID = :id";
        $stmt = $this->db->query($sql, ['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    
    public function findByEmail($email) {
        $sql = "SELECT * FROM student WHERE emailAddress = :email";
        $stmt = $this->db->query($sql, ['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public function getAllGenders()
    {
        $sql = "SELECT genderID, gender FROM gender";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllSchools()
    {
        $sql = "SELECT schoolID, schoolName FROM school";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllFormGradeLevels()
    {
        $sql = "SELECT formGradeLevelID, GradeLevel FROM formgradelevel";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllSubjects()
    {
        $sql = "SELECT subjectID, subjectName FROM subject";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }





    public function getScheduleForStudent($studentID)
    {
        // This depends on how your schedule data is stored.
        // Possibly you have a "schedule" table or store day/time in "subject" table with an instructor ref.

        // Example join:
        $sql = "
        SELECT s.subjectName, s.day, s.time, s.instructorName
        FROM studentsubjects ss
        JOIN subject s ON ss.subjectID = s.subjectID
        WHERE ss.studentID = :studentID
        ";
        // This is just an example if there's a 'schedule' table linking subjectID to day/time/instructor.

        $stmt = $this->db->query($sql, ['studentID' => $studentID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }




    public function getFullProfile($studentID)
    {
        $sql = "
        SELECT s.*,
                g.gender AS genderName,
                sch.schoolName,
                f.GradeLevel AS gradeName
        FROM student s
        LEFT JOIN gender g 
            ON s.genderID = g.genderID
        LEFT JOIN school sch
            ON s.schoolID = sch.schoolID
        LEFT JOIN formgradelevel f
            ON s.formGradeLevelID = f.formGradeLevelID
        WHERE s.studentID = :id
        ";
        $stmt = $this->db->query($sql, ['id' => $studentID]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($profile) {
            // fetch subjects:
            $profile['subjects'] = $this->getSubjects($studentID);
        }
        return $profile;
    }




    private function getSubjects($studentID)
    {
        $sql = "SELECT sub.subjectName
                FROM studentsubjects ss
                JOIN subject sub ON ss.subjectID = sub.subjectID
                WHERE ss.studentID = :id";
        $stmt = $this->db->query($sql, ['id' => $studentID]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN); // returns an array of subjectName
    }








    public function updateStudent($studentID, array $newData)
    {
        // 1) Fetch the current row
        $sql = "SELECT * FROM student WHERE studentID = :id";
        $stmt = $this->db->query($sql, ['id' => $studentID]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            // No student found
            return false;
        }

        // 2) Build an UPDATE statement dynamically for each field user wants to change
        $fields = [];
        $params = [];

        // If FirstName is not empty, set it
        if (!empty($newData['FirstName'])) {
            $fields[] = "firstName = :firstName";
            $params['firstName'] = $newData['FirstName'];
        }
        // Repeat for LastName, Email, etc.

        // If no fields to update, do nothing
        if (empty($fields)) {
            return false; // means user didn't change anything
        }

        // 3) Construct the final UPDATE
        $sql = "UPDATE student SET " . implode(", ", $fields) . " WHERE studentID = :id";
        $params['id'] = $studentID;

        $this->db->query($sql, $params);
        return true;
    }








    public function deleteStudentCompletely($studentID)
    {
        // 1) Delete from studentsubjects
        $this->db->query("DELETE FROM studentsubjects WHERE studentID = :id", [
            'id' => $studentID
        ]);

        // 2) Delete from student
        $this->db->query("DELETE FROM student WHERE studentID = :id", [
            'id' => $studentID
        ]);

        // If there are other references, remove them too.
        return true;
    }



    // If needed, add update() or delete() methods here
}