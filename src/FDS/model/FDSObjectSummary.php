<?php
/**
 * Created by IntelliJ IDEA.
 * User: wuzesheng
 * Date: 3/24/14
 * Time: 10:20 AM
 */

namespace FDS\model;

class FDSObjectSummary {

  private $bucket_name;
  private $object_name;
  private $owner;
  private $size;

  public static function fromJson($json) {
    // Currently, we only support json object
    if (is_object($json)) {
      $summary = new FDSObjectSummary();
      if (isset($json->name)) {
        $summary->setObjectName($json->name);
      }

      if (isset($json->owner)) {
        $summary->setOwner(Owner::fromJson($json->owner));
      }

      if (isset($json->size)) {
        $summary->setSize($json->size);
      }
      return $summary;
    }
    return NULL;
  }

  public function getBucketName() {
    return $this->bucket_name;
  }

  public function setBucketName($bucket_name) {
    $this->bucket_name = $bucket_name;
  }

  public function  getObjectName() {
    return $this->object_name;
  }

  public function setObjectName($object_name) {
    $this->object_name = $object_name;
  }

  public function getOwner() {
    return $this->owner;
  }

  public function setOwner($owner) {
    $this->owner = $owner;
  }

  public function getSize() {
    return $this->size;
  }

  public function setSize($size) {
    $this->size = $size;
  }
}