<?php

namespace App\Controllers;

use Framework\Database;
use Framework\Session;
use Framework\Validation;
use Framework\Authorization;

class ListingsController
{

  protected $db;
  public function __construct()
  {
    $config = require basePath('config/db.php');
    $this->db = new Database($config);
  }
  /**
   * Show all listings
   * 
   * @return void
   */

  public function index()
  {
    $listings = $this->db->query('SELECT * FROM listings ORDER BY created_at DESC')->fetchAll();

    loadView('listings/index', [
      'listings' => $listings
    ]);
  }
  /**
   * Show the create listing form
   * 
   * @return void
   */

  public function create()
  {
    loadView('listings/create');
  }
  /**
   * Show a single listing
   * 
   * @param array $params
   * @return void
   */
  public function show($params)
  {
    $listing = $this->db->query("SELECT * FROM listings WHERE id = :id", $params)->fetch();
    if (!$listing) {
      ErrorsController::notFound('Listing not found');
      return;
    }

    loadView(
      'listings/show',
      [
        'listing' => $listing
      ]
    );
  }

  /**
   * Store data in database
   * 
   * @return void
   */

  public function store()
  {

    $allowedFields = ['title', 'description', 'salary', 'tags', 'company', 'address', 'city', 'state', 'phone', 'email', 'requirements', 'benefits'];
    $requiredFields = ['title', 'description', 'salary', 'email', 'city', 'state'];

    $newListingData = array_intersect_key($_POST, array_flip($allowedFields));

    $errors = [];

    $file = $_FILES['image'] ?? null;

    if ($file && $file['name']) {

      $image = uploadImage($file);

      if (is_string($image)) {
        $errors['image'] = $image;
      } else $newListingData['image'] = $image['path'];
    } else $errors['image'] = 'Image is required';

    $newListingData['user_id'] = Session::get('user')["id"];

    $newListingData = array_map('sanitize', $newListingData);


    foreach ($requiredFields as $field) {
      if (empty($newListingData[$field]) || !Validation::string($newListingData[$field])) {
        $errors[$field] = ucfirst($field) . ' is required';
      }
    }

    if (!empty($errors)) {
      loadView('listings/create', [
        'errors' => $errors,
        'listing' => $newListingData
      ]);
    } else {
      $fields = [];
      $values = [];
      foreach ($newListingData as $field => $value) {
        $fields[] = $field;
        if ($value === '') {
          $newListingData[$field] = null;
        }
        $values[] = ':' . $field;
      }
      $fields = implode(', ', $fields);
      $values = implode(', ', $values);

      $query = "INSERT INTO listings ({$fields}) VALUES ({$values})";

      $this->db->query($query, $newListingData);

      redirect('/listings');
    }
  }
  /**
   * Delete a listing
   * 
   * @param array $params
   * @return void
   * 
   */
  public function destroy($params)
  {
    $listing = $this->db->query("SELECT * FROM listings WHERE id = :id", $params)->fetch();
    if (!$listing) {
      ErrorsController::notFound('Listing not found');
      return;
    }

    // Authorization
    if (!Authorization::isOwner($listing->user_id)) {
      Session::setFlashMessage('error_message', 'You are not authoirzed to update this listing');
      return redirect('/listings/' . $listing->id);
    }

    $this->db->query('DELETE FROM listings WHERE id = :id', $params);

    //Set a flash message
    Session::setFlashMessage('success_message', 'Listing deleted successfully');

    redirect('/listings');
  }

  /**
   * Show the listing edit form
   * 
   * @param array $params
   * @return void
   */

  public function edit($params)
  {
    $listing = $this->db->query("SELECT * FROM listings WHERE id = :id", $params)->fetch();
    if (!$listing) {
      ErrorsController::notFound('Listing not found');
      return;
    }
    // Authorization
    if (!Authorization::isOwner($listing->user_id)) {
      Session::setFlashMessage('error_message', 'You are not authoirzed to update this listing');
      return redirect('/listings/' . $listing->id);
    }

    loadView(
      'listings/edit',
      [
        'listing' => $listing
      ]
    );
  }

  /**
   * Update listing
   * 
   * @param string $params
   * 
   * @return void
   */

  public function update($params)
  {
    $id = $params['id'] ?? '';

    $listing = $this->db->query('SELECT * FROM listings WHERE id = :id', $params)->fetch();

    $oldImage = $listing->image ?? '';

    if (!$listing) {
      ErrorsController::notFound('Listing not found');
    }

    // Authorization

    if (!Authorization::isOwner($listing->user_id)) {
      Session::setFlashMessage('error_message', 'You are not authoirzed to update this listing');
      return redirect('/listings/' . $listing->id);
    }

    $allowedFields = ['title', 'description', 'salary', 'tags', 'company', 'address', 'city', 'state', 'phone', 'email', 'requirements', 'benefits'];

    $requiredFields = ['title', 'description', 'salary', 'email', 'city', 'state'];

    $updateValues = [];

    $updateValues = array_intersect_key($_POST, array_flip($allowedFields));

    $updateValues = array_map('sanitize', $updateValues);

    $errors = [];

    $file = $_FILES['image'] ?? null;

    //Check if file choosed and upload

    if ($file && $file['name']) {

      $image = uploadImage($file);

      if (is_string($image)) {
        $errors['image'] = $image;
      } else $updateValues['image'] = $image['path'];
    }

    $requiredFields = ['title', 'description', 'salary', 'email', 'city', 'state'];

    foreach ($requiredFields as $field) {
      if (empty($updateValues[$field]) || !Validation::string($updateValues[$field])) {
        $errors[$field] = ucfirst($field) . ' is required';
      }
    }

    if (!empty($errors)) {
      loadView('listings/edit', [
        'errors' => $errors,
        'listing' => $listing
      ]);
      exit;
    } else {
      //Submit to database
      $updateFields = [];
      foreach (array_keys($updateValues) as $field) {
        $updateFields[] = "$field = :{$field}";
      }
      $updateFields = implode(', ', $updateFields);

      $updateQuery = "UPDATE listings SET $updateFields WHERE id = :id";

      $updateValues['id'] = $id;

      $this->db->query($updateQuery, $updateValues);

      if (file_exists($oldImage) && $updateValues['image']) unlink($oldImage);

      redirect('/listings');
    }
  }
  /**
   * Search listings by keywords/location
   * 
   * @return void
   */
  public function search()
  {
    $keywords = isset($_GET['keywords']) ? trim($_GET['keywords']) : '';
    $location = isset($_GET['location']) ? trim($_GET['location']) : '';

    $query = "SELECT * FROM listings WHERE (title LIKE :keywords OR description LIKE :keywords OR tags LIKE :keywords OR company LIKE :keywords) AND (city LIKE :location OR state LIKE :location)";

    $params = [
      'keywords' => "%{$keywords}%",
      'location' => "%{$location}%"
    ];

    $listings = $this->db->query($query, $params)->fetchAll();

    loadView('/listings/index', [
      'listings' => $listings,
      'keywords' => $keywords,
      'location' => $location
    ]);
  }
};
