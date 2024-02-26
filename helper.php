<?php

/**
 * Get the base path

 * @param string $path
 * 
 * @return string
 */

function basePath($path = '')
{
  return __DIR__ . '/' . $path;
}

/**
 * @param string $name
 * 
 * @return void
 */

function loadView($name, $data = [])
{
  $viewPath = basePath('App/views/' . $name . '.view.php');
  if (file_exists($viewPath)) {
    extract($data);
    require $viewPath;
  } else  echo "View '{$name}' not found!";
}
/**
 * Load a partial
 * 
 * @param string $name
 * 
 * @return void
 */

function loadPartial($name, $data = [])
{
  $partialPath = basePath("App/views/partials/{$name}.php");
  if (file_exists($partialPath)) {
    extract($data);
    require $partialPath;
  } else  echo "Partials '{$name}' not found!";
}

/**
 * Inspect a value
 * 
 * @param mixed $value
 * 
 * @return void
 */

function inspect($value)
{
  echo '<pre>';
  var_dump($value);
  echo '</pre>';
}

/**
 * Inspect a value and die
 * 
 * @param mixed $value
 * 
 * @return void
 */

function inspectAndDie($value)
{
  echo '<pre>';
  die(var_dump($value));
  echo '</pre>';
}

/**
 * Format Salary
 * 
 * @param number $salary
 * 
 * @return string Formatted Salary
 */

function formatSalary($salary)
{
  return '$' . number_format(floatval($salary));
}

/**
 * Sanitize data
 * 
 * @param string $dirty
 * @return string
 * 
 */

function sanitize($dirty)
{
  return filter_var(trim($dirty), FILTER_SANITIZE_SPECIAL_CHARS);
}

/**
 * Redirect to a given url
 * 
 * @return void
 */
function redirect($url)
{
  header('Location: ' . $url);
  exit;
}

/**
 * Upload image
 * 
 * @param file $file
 * @return mixed
 */

function uploadImage($file)
{
  if ($file['error'] === UPLOAD_ERR_OK) {
    //Specify Where to upload
    $uploadDir = 'uploads/';
    //Check or create dir
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    //Create filename
    $filename = uniqid() . '-' . $file['name'];

    //Check file type
    $allowedExtensions = ['jpg', 'jpeg', 'png'];
    $fileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    //Make sure extension is allowed
    if (in_array($fileExtension, $allowedExtensions)) {
      //Upload file
      if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return ['message' => 'File Uploaded', 'path' => $uploadDir . $filename];
      } else {
        return 'File Uploaded Error: ' . $file['error'];
      }
    } else echo 'Invalid File Type';
  }
}
