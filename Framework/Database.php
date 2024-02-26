<?php

namespace Framework;

use PDO;
use PDOException;
use Exception;

class Database
{
  public $conn;
  /**
   * Constructor for a Database class
   * 
   * @param array $config
   */

  public function __construct($config)
  {
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}";
    $options = [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ];

    try {
      $this->conn = new PDO($dsn, $config['username'], $config['password'], $options);
    } catch (PDOException $e) {
      throw new Exception("Database connection faild: {$e->getMessage()}");
    }
  }

  /** Query the Database
   * 
   * @param string $query
   * 
   * @return PDOStatement
   * @throws PDOException
   */
  public function query($query, $params = [])

  {
    try {
      $sth = $this->conn->prepare($query);
      foreach ($params as $param => $value) {
        $sth->bindValue(':' . $param, $value);
      }
      $sth->execute();
      return $sth;
    } catch (PDOException $e) {
      throw new Exception('Query faild to execute: ' . $e->getMessage());
    }
  }
}
