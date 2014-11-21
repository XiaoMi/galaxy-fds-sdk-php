<?php
/**
 * Created by IntelliJ IDEA.
 * User: wuzesheng
 * Date: 3/24/14
 * Time: 3:42 PM
 */

namespace FDS\model;

abstract class Permission {
  // The READ permission: when it applies to buckets it means
  // allow the grantee to list the objects in the bucket; when
  // it applies to objects it means allow the grantee to read
  // the object data and metadata.
  const READ  = 0x01;

  // The WRITE permission: when it applies to buckets it means
  // allow the grantee to create, overwrite and delete any
  // object in the bucket; it is not applicable for objects.
  const WRITE = 0x02;

  // The FULL_CONTROL permission: allows the grantee the READ
  // and WRITE permission on the bucket/object.
  const FULL_CONTROL = 0xff;

  public static function toString($permission) {
    switch ($permission) {
      case self::READ:
        return "READ";
      case self::WRITE:
        return "WRITE";
      case self::FULL_CONTROL:
        return "FULL_CONTROL";
      default:
        return "";
    }
  }

  public static function valueOf($permission) {
    if ($permission == "READ") {
      return self::READ;
    } elseif ($permission == "WRITE") {
      return self::WRITE;
    } elseif ($permission == "FULL_CONTROL") {
      return self::FULL_CONTROL;
    }
    return 0;
  }
}

abstract class UserGroups {

  const ALL_USERS = "ALL_USERS";

  const AUTHENTICATED_USERS = "AUTHENTICATED_USERS";
}

class Grantee {
  public $id;
  public $displayName;

  public function __construct($id) {
    $this->id= $id;
  }

  public function getDisplayName() {
    return $this->displayName;
  }

  public function setDisplayName($display_name) {
    $this->displayName = $display_name;
  }

  public function getId() {
    return $this->id;
  }

  public function setId($id) {
    $this->id = $id;
  }
}

abstract class GrantType {
  const USER = "USER";
  const GROUP = "GROUP";
}

class Grant {
  public $grantee;
  public $permission;
  public $type;
  private $int_perm;

  public function __construct($grantee, $permission) {
    $this->grantee = $grantee;
    $this->setPermission($permission);
    $this->type = GrantType::USER;
  }

  public function getPermission() {
    return $this->int_perm;
  }

  public function setPermission($permission) {
    if (!is_string($permission)) {
      $this->permission = Permission::toString($permission);
      $this->int_perm = $permission;
    } else {
      $this->permission = $permission;
      $this->int_perm = Permission::valueOf($permission);
    }
  }

  public function getGrantee() {
    return $this->grantee;
  }

  public function setGrantee($grantee) {
    $this->grantee = $grantee;
  }

  public function getType() {
    return $this->type;
  }

  public function setType($type) {
    $this->type = $type;
  }
}

class AccessControlList {

  private $acl;

  public function __construct() {
    $this->acl = array();
  }

  public function addGrant($grant) {
    $this->acl[$grant->getGrantee()->getId() . ":" .$grant->getType()] = $grant;
  }

  public function getGrantList() {
    $grants = array();
    $index = 0;
    foreach ($this->acl as $key => $value) {
      $grants[$index++] = $value;
    }
    return $grants;
  }
}
