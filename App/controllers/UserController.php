<?php

namespace App\Controllers;

use Framework\Database;
use Framework\Validation;
use Framework\Session;

class UserController
{
  protected $db;

  public function __construct()
  {
    $config = require basePath('config/db.php');
    $this->db = new Database($config);
  }

  /**
   * Show the login page
   * 
   * @return void;
   * 
   */
  public function login()
  {
    loadView('users/login');
  }
  /**
   * Show the register page
   *
   * @return void
   */
  public function create()
  {
    loadView('users/create');
  }

  /**
   * Store user in the database
   * 
   * @return void
   */
  public function store()
  {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $password = $_POST['password'];
    $passwordConfirmation = $_POST['password_confirmation'];

    $errors = [];

    if (!Validation::email($email)) $errors['email'] = 'Please enter a valid email address';
    if (!Validation::string($name, 2, 50)) $errors['name'] = 'Name must be between 2 and 50 characters';
    if (!Validation::string($password, 6, 50)) $errors['password'] = 'Password must be 6 characters at least';
    if (!Validation::match($password, $passwordConfirmation)) $errors['passwordConfirmation'] = 'Passwords are not match';



    //Check if email exists
    if (empty($errors)) {
      $params = ['email' => $email];
      $user = $this->db->query('SELECT * FROM users WHERE email = :email', $params)->fetch();
      if ($user) $errors['email'] = 'This email is already exists';
    }

    if (!empty($errors)) {
      loadView('users/create', [
        'errors' => $errors,
        'user' => [
          'name' =>  $name,
          'email' => $email,
          'city' => $city,
          'state' => $state
        ]
      ]);
      exit;
    }
    //Create User account

    $params = [
      'name' =>  $name,
      'email' => $email,
      'city' => $city,
      'state' => $state,
      'password' => password_hash($password, PASSWORD_DEFAULT)
    ];
    $this->db->query('INSERT INTO users (name, email, city, state, password) VALUES (:name, :email, :city, :state, :password)', $params);

    //Get new User ID
    $userId = $this->db->conn->lastInsertId();
    Session::set('user', [
      'id' => $userId,
      'name' => $name,
      'email' => $email,
      'city' => $city,
      'state' => $state
    ]);
    redirect('/');
  }

  /**
   * User logout
   * 
   * @return void
   */
  public function logout()
  {
    Session::clearAll();
    $params = session_get_cookie_params();
    setcookie('PHPSESSID', '', time() - 86400, $params['path'], $params['domain']);
    redirect('/');
    exit;
  }

  /**
   * Authenticate a user with email and password
   * 
   * @return void
   */

  public function authenticate()
  {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $errors = [];

    //Validation 

    if (!Validation::email($email)) $errors['email'] = 'Please enter a valid email';
    if (!Validation::string($password, 6, 50)) $errors['email'] = 'Password must be at least 6 character';

    //Check the email
    $params = ['email' => $email];
    $user = $this->db->query('SELECT * FROM users WHERE email = :email', $params)->fetch();
    if (empty($errors) && !$user) $errors['email'] = 'Incorrect credentials';

    //Check the password
    if (!password_verify($password, $user->password)) $errors['email'] = 'Incorrect credentials';

    if (!empty($errors)) {
      loadView('users/login', ['errors' => $errors]);
      exit;
    }
    //Set user session
    Session::set('user', [
      'id' => $user->id,
      'name' => $user->name,
      'email' => $user->email,
      'city' => $user->city,
      'state' => $user->state
    ]);

    redirect('/');
  }
}
