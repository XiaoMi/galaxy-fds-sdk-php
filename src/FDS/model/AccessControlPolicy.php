<?php
/**
 * Created by IntelliJ IDEA.
 * User: wuzesheng
 * Date: 3/26/14
 * Time: 10:08 AM
 */

namespace FDS\model;

class AccessControlPolicy {

  public $owner;
  public $accessControlList;

  public static function fromJson($json) {
    // Currently, we only support json object
    if (is_object($json)) {
      $acp = new AccessControlPolicy();
      if (isset($json->owner)) {
        $acp->setOwner(Owner::fromJson($json->owner));
      }

      if (isset($json->accessControlList)) {
        $acp->setAccessControlList($json->accessControlList);
      }
      return $acp;
    }
    return NULL;
  }

  public function getOwner() {
    return $this->owner;
  }

  public function setOwner($owner) {
    $this->owner = $owner;
  }

  public function getAccessControlList() {
    return $this->accessControlList;
  }

  public function setAccessControlList($accessControlList) {
    $this->accessControlList = $accessControlList;
  }
}
