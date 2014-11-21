<?php
/**
 * Created by IntelliJ IDEA.
 * User: wuzesheng
 * Date: 3/24/14
 * Time: 10:05 AM
 */

namespace FDS\model;

class FDSBucket {

  private $name;
  private $creation_date;
  private $owner;

  public static function fromJson($json) {
    // Currently, we only support json object
    if (is_object($json)) {
      $bucket = new FDSBucket();
      if (isset($json->name)) {
        $bucket->setName($json->name);
      }

      if (isset($json->creation_date))  {
        $bucket->setCreationDate($json->creation_date);
      }

      if (isset($json->owner)) {
        $bucket->setOwner(Owner::fromJson($json->owner));
      }
      return $bucket;
    }
    return NULL;
  }

  public function getCreationDate() {
    return $this->creation_date;
  }

  public function setCreationDate($creation_date) {
    $this->creation_date = $creation_date;
  }

  public function getName() {
    return $this->name;
  }

  public function setName($name) {
    $this->name = $name;
  }

  public function getOwner() {
    return $this->owner;
  }

  public function setOwner($owner) {
    $this->owner = $owner;
  }
}