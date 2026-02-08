<?php
namespace Framework;

class BaseController
{
    protected function requireLogin()
    {
        // Start the session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user'])) {
            header('Location: index.php?action=login');
            exit;
        }
    }

    protected function render($view, $data = [])
    {
        extract($data);
        include __DIR__ . "/../public/{$view}.php";

        /*
        if (file_exists($htmlFile)) {
            include $htmlFile;  // if there's a .html version
        } elseif (file_exists($phpFile)) {
            include $phpFile;   // otherwise, .php version
        } else {
            echo "View not found: {$view}";
        }*/
    }
}