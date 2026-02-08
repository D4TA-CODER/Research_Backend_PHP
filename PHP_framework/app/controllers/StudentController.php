<?php
namespace App\Controllers;

use Framework\BaseController;
use App\Models\Student;

class StudentController extends BaseController
{
    private $studentModel;

    public function __construct($db)
    {
        $this->studentModel = new Student($db);
    }




    public function login()
    {
        // Just render the login page
        // If user is already logged in, maybe redirect them?
        if (isset($_SESSION['user'])) {
            header('Location: index.php?action=schedule');
            exit;
        }
        $this->render('Login');
    }



    public function processLogin()
    {
        session_start();

        $email = $_POST['Email']    ?? '';
        $pass  = $_POST['Password'] ?? '';
        
        $errors = [];

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['Email'] = "Invalid email format!";
        }

        // Validate password format
        if (!$this->validatePassword($pass)) {
            $errors['Password'] = "Invalid password format (must be ≥8 chars, capital first letter, at least 1 digit).";
        }

        // If we have formatting errors, re-render the login
        if (!empty($errors)) {
            $this->render('Login', [
                'errors'  => $errors,
                'oldData' => $_POST
            ]);
            return;
        }

        // Now check if user actually exists in DB
        $student = $this->studentModel->findByEmail($email);
        if (!$student) {
            $errors['Email'] = "No such user found.";
            $this->render('Login', [
                'errors'  => $errors,
                'oldData' => $_POST
            ]);
            return;
        }

        // Compare password with DB
        if ($student['password'] !== $pass) {
            $errors['Password'] = "Incorrect password!";
            $this->render('Login', [
                'errors'  => $errors,
                'oldData' => $_POST
            ]);
            return;
        }

        // If correct, store session data
        $_SESSION['user'] = [
            'firstName' => $student['firstName'],
            'lastName'  => $student['lastName'],
            'email'     => $student['emailAddress'],
            'studentID' => $student['studentID']
        ];

        // Then redirect to schedule
        header("Location: index.php?action=schedule");
        exit;
    }
    
    // Helper function to validate password by your specified rules
    private function validatePassword($pass) {
        // (a) At least 8 chars
        if (strlen($pass) < 8) return false;
        // (b) Starts with a capital letter
        if (!preg_match('/^[A-Z]/', $pass)) return false;
        // (c) Contains at least 1 digit
        if (!preg_match('/\d/', $pass)) return false;
    
        return true;
    }



    // Show a default page—maybe your login page
    public function index()
    {
        // If you want, you can fetch all students
        $students = $this->studentModel->getAll();
        // Pass them to the Login.html (for demonstration)
        $this->render('Login', ['students' => $students]);
    }

    // Show the registration page
    public function create()
    {
        // 1) Query each table for data
        //    - 'gender' table
        //    - 'school' table
        //    - 'formgradelevel' table
        //    - 'subject' table

        $genders = $this->studentModel->getAllGenders();   // returns array of rows
        $schools = $this->studentModel->getAllSchools();
        $grades  = $this->studentModel->getAllFormGradeLevels();
        $subjects = $this->studentModel->getAllSubjects();

        // 2) Render the Registration page, passing these arrays
        $this->render('Registration', [
            'genders'  => $genders,
            'schools'  => $schools,
            'grades'   => $grades,
            'subjects' => $subjects,
            'errors'    => [],
            'oldData'   => []
        ]);
    }

    // Handle the registration form POST
    public function store()
    {
        // Step 1: Gather data from $_POST
        $data = $_POST;  // or map individually

        // Step 2: Validate
        $errors = $this->validateRegistration($data);

        if (!empty($errors)) {
            // If we have errors, we need to re-render Registration with the errors
            // Re-fetch dropdown data
            $genders  = $this->studentModel->getAllGenders();
            $schools  = $this->studentModel->getAllSchools();
            $grades   = $this->studentModel->getAllFormGradeLevels();
            $subjects = $this->studentModel->getAllSubjects();

            // Pass errors + dropdown data back
            $this->render('Registration', [
                'errors'   => $errors,
                'genders'  => $genders,
                'schools'  => $schools,
                'grades'   => $grades,
                'subjects'  => $subjects,
                'errors'    => $errors,
                'oldData'   => $data
            ]);
            return;
        }

        // Step 3: If valid, create the student + subject links
        $newStudentId = $this->studentModel->createStudent($data);
        if (!empty($data['Subjects'])) {
            $this->studentModel->addStudentSubjects($newStudentId, $data['Subjects']);
        }

        // Step 4: Redirect somewhere, e.g. Profile page
        header("Location: index.php?action=profile&id=$newStudentId");
        exit;
    }



    private function validateRegistration($data)
    {
        $errors = [];

        // 1) Required fields (example)
        $required = [
            'FirstName','LastName','DOB','Gender','Email','HomeNumber',
            'MobileNumber','StreetAdd1','City','Parish','SchoolID',
            'GradeLevelID','Month','Year','Password'
        ];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[$field] = "ERROR: Missing Information";
            }
        }

        // 2) First/Last Name: letters & apostrophes only, no consecutive apostrophes
        // e.g.: ^[A-Za-z]+(?:'[A-Za-z]+)*$
        if (!empty($data['FirstName']) && 
            !preg_match("/^[A-Za-z]+(?:'[A-Za-z]+)*$/", $data['FirstName'])) {
            $errors['FirstName'] = "ERROR: Invalid First Name!";
        }
        if (!empty($data['LastName']) && 
            !preg_match("/^[A-Za-z]+(?:'[A-Za-z]+)*$/", $data['LastName'])) {
            $errors['LastName'] = "ERROR: Invalid Last Name!";
        }

        if (empty($data['DOB'])) {
            $errors['DOB'] = "ERROR: Missing Date of Birth!";
        } 

        // 3) Validate Email
        if (!empty($data['Email']) && 
            !filter_var($data['Email'], FILTER_VALIDATE_EMAIL)) {
            $errors['Email'] = "ERROR: Invalid Email Address!";
        }

        // 4) Phone format: XXX-XXX-XXXX
        if (!empty($data['HomeNumber']) && 
            !preg_match("/^\d{3}-\d{3}-\d{4}$/", $data['HomeNumber'])) {
            $errors['HomeNumber'] = "ERROR: Invalid Home Number!";
        }
        if (!empty($data['MobileNumber']) &&
            !preg_match("/^\d{3}-\d{3}-\d{4}$/", $data['MobileNumber'])) {
            $errors['MobileNumber'] = "ERROR: Invalid Mobile Number!";
        }

        // 5) Street Address/City/Parish rules: alphanumeric, spaces, hyphens, periods, no consecutive . or -
        // Simple pattern example: "^(?!.*--)(?!.*\.\.)[A-Za-z0-9.\- ]+$"
        $addrPattern = "/^(?!.*--)(?!.*\.\.)[A-Za-z0-9.\- ]+$/";
        if (!empty($data['StreetAdd1']) && !preg_match($addrPattern, $data['StreetAdd1'])) {
            $errors['StreetAdd1'] = "ERROR: Invalid Street Address!";
        }
        if (!empty($data['StreetAdd2']) && !preg_match($addrPattern, $data['StreetAdd2'])) {
            $errors['StreetAdd2'] = "ERROR: Invalid Street Address!";
        }
        if (!empty($data['City']) && !preg_match($addrPattern, $data['City'])) {
            $errors['City'] = "ERROR: Invalid Entry!";
        }
        if (!empty($data['Parish']) && !preg_match($addrPattern, $data['Parish'])) {
            $errors['Parish'] = "ERROR: Invalid Entry!";
        }

        // 6) ZIP code = 5 digits
        if (!empty($data['ZIPcode']) && !preg_match("/^\d{5}$/", $data['ZIPcode'])) {
            $errors['ZIPcode'] = "ERROR: Invalid ZIP Code!";
        }

        // 7) Postal code = XXXX-XXX, custom UK-like rule
        if (!empty($data['PostalCode']) &&
            !preg_match("/^[A-Za-z][A-Za-z0-9][A-Za-z0-9][A-Za-z0-9]-\d[A-Za-z0-9][A-Za-z0-9]$/", $data['PostalCode'])) {
            $errors['PostalCode'] = "ERROR: Invalid Postal Code!";
        }

        // 8) At least 1 Subject from an array "Subjects[]"
        // If user picks 0 or they are all empty, show error
        if (empty($data['Subjects']) || count(array_filter($data['Subjects'])) == 0) {
            $errors['Subjects'] = "ERROR: Please select at least one Subject!";
        }

        // 9) Password: >=8 chars, begins capital, at least 1 digit
        $pass = $data['Password'] ?? '';
        if (strlen($pass) < 8 || !preg_match('/^[A-Z]/', $pass) || !preg_match('/\d/', $pass)) {
            $errors['Password'] = "ERROR: Invalid Password!";
        }

        return $errors;
    }

    public function schedule()
    {
        // 1) Check if user is logged in
        session_start();
        if (empty($_SESSION['user'])) {
            // Not logged in → redirect to login
            header("Location: index.php?action=login");
            exit;
        }

        // 2) Retrieve the student's schedule from DB
        //    Suppose the user info is stored in $_SESSION['user']['studentID']
        $studentID = $_SESSION['user']['studentID']; 
        // Example: $schedule = $this->studentModel->getSchedule($studentID);
        // The model might join subject, day, time, instructor, etc.
        // For now, let's assume you have some method returning an array of rows:
        $schedule = $this->studentModel->getScheduleForStudent($studentID);

        // 3) Render the schedule page, passing the schedule array and user data
        $this->render('Schedule', [
            'schedule' => $schedule,
            'user'     => $_SESSION['user'] // has firstName, lastName, email, etc.
        ]);
    }

    public function logout()
    {
        session_start();
        session_destroy();
        header("Location: index.php?action=login");
        exit;
    }


    // Display a profile
    public function profile()
    {
        session_start();
        // 1) Check if user is logged in
        if (empty($_SESSION['user'])) {
            // Not logged in → redirect
            header("Location: index.php?action=login");
            exit;
        }

        // 2) Grab the studentID from the session. 
        //    (If you only stored email or name in session, 
        //     you might need to do a quick query to find the ID.)
        $studentID = $_SESSION['user']['studentID'];

        // 3) Fetch full profile data via a model method
        $studentData = $this->studentModel->getFullProfile($studentID);

        // 4) Render Profile page with that data
        $this->render('Profile', [
            'student' => $studentData  // or any variable name you like
        ]);
    }

    public function updateProfileForm() {
        session_start();
        if (empty($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit;
        }
        $studentID = $_SESSION['user']['studentID'];
        $currentData = $this->studentModel->getFullProfile($studentID);

        $genders  = $this->studentModel->getAllGenders();
        $schools  = $this->studentModel->getAllSchools();
        $grades   = $this->studentModel->getAllFormGradeLevels();
        $subjects = $this->studentModel->getAllSubjects();

        $this->render('UpdateProfile', [
            'currentData' => $currentData,
            'genders'     => $genders,
            'schools'     => $schools,
            'grades'      => $grades,
            'subjects'    => $subjects,
            'errors'      => [],
            'oldData'     => []
        ]);
    }


    public function processUpdateProfile()
    {
        session_start();
        if (empty($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit;
        }

        $studentID = $_SESSION['user']['studentID'];

        // Gather the new data from $_POST
        $data = $_POST; 
        // e.g., $data['FirstName'], $data['Email'], etc.

        // Validate only if not empty
        // Same validation logic, but skip required checks if field is blank.
        $errors = $this->validateUpdateData($data);

        if (!empty($errors)) {
            // Re-render the update form with errors
            $currentData = $this->studentModel->getFullProfile($studentID);
            $this->render('UpdateProfile', [
                'currentData' => $currentData,
                'errors'      => $errors,
                'oldData'     => $data
            ]);
            return;
        }

        // If valid, update the DB
        $this->studentModel->updateStudent($studentID, $data);

        // Redirect back to profile (or schedule)
        header("Location: index.php?action=profile");
        exit;
    }



    private function validateUpdateData($data)
    {
        $errors = [];

        // For each field, if it's not empty, validate it with the same rules 
        // as registration. e.g., phone format, email filter, password rules, etc.

        if (!empty($data['FirstName'])) {
            // check letters, apostrophes, etc.
            if (!preg_match("/^[A-Za-z]+(?:'[A-Za-z]+)*$/", $data['FirstName'])) {
                $errors['FirstName'] = "Invalid First Name!";
            }
        }

        if (!empty($data['Email'])) {
            if (!filter_var($data['Email'], FILTER_VALIDATE_EMAIL)) {
                $errors['Email'] = "Invalid Email Address!";
            }
        }

        // ... repeat for the other fields ...
        // If field is blank, we skip.

        return $errors;
    }


















    public function confirmDelete()
    {
        session_start();
        if (empty($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit;
        }

        // Show "Are you sure?" page
        $this->render('ConfirmDelete');
    }

    public function deleteProfileStep2()
    {
        session_start();
        if (empty($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit;
        }

        // Show the page that prompts for Email/Password
        $this->render('DeleteProfileCheck');
    }

    public function processDeleteProfile()
    {
        session_start();
        if (empty($_SESSION['user'])) {
            header("Location: index.php?action=login");
            exit;
        }

        // 1) Compare the posted Email/Password with session's user
        $inputEmail    = $_POST['Email']    ?? '';
        $inputPassword = $_POST['Password'] ?? '';

        // Check if it matches what's in session
        $sessionEmail = $_SESSION['user']['email'];
        $sessionPassword = $_SESSION['user']['password']; 
        // or if you have hashed passwords, do a verify check

        if ($inputEmail !== $sessionEmail || $inputPassword !== $sessionPassword) {
            echo "Email/Password do not match!";
            return;
        }

        // 2) If matched, remove from DB
        // This means removing from `student`, maybe from `studentsubjects`, etc.
        $studentID = $_SESSION['user']['studentID'];
        $this->studentModel->deleteStudentCompletely($studentID);

        // 3) Destroy session, redirect to login
        session_destroy();
        header("Location: index.php?action=login");
        exit;
    }

    
}