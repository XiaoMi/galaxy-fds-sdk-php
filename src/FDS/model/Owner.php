<?php
/**
 * Created by IntelliJ IDEA.
 * User: wuzesheng
 * Date: 3/24/14
 * Time: 10:05 AM
 */

namespace FDS\model;

class Owner {

  public $id;
  public $displayName;

  public static function fromJson($json) {
    // Currently, we only support json object
    if (is_object($json)) {
      $owner = new Owner();
      if (isset($json->id)) {
        $owner->setId($json->id);
      }

      if (isset($json->displayName)) {
        $owner->setDispalyName($json->displayName);
      }
      return $owner;
    }
    return NULL;
  }

  public function getId() {
    return $this->id;
  }

  public function  setId($id) {
    $this->id = $id;
  }

  public function  getDisplayName() {
    return $this->displayName;
  }

  public function setDispalyName($display_name) {
    $this->displayName = $display_name;
  }
}