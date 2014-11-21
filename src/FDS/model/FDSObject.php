<?php
/**
 * Created by IntelliJ IDEA.
 * User: wuzesheng
 * Date: 3/24/14
 * Time: 10:15 AM
 */

namespace FDS\model;

class FDSObject {

  private $object_summary;
  private $object_metadata;
  private $object_content;

  public static function fromJson($json) {
    // Currently, we only support json object
    if (is_object($json)) {
      $object = new FDSObject();
      if (isset($json->objectSummary)) {
        $object->setObjectSummary(
          FDSObjectSummary::fromJson($json->objectSummary));
      }

      if (isset($json->objectMetadata)) {
        $object->setObjectMetadata(
          FDSObjectMetadata::fromJson($json->objectMetadata));
      }

      if (isset($json->objectContent)) {
        $object->setObjectContent($json->objectContent);
      }
      return $object;
    }
    return NULL;
  }

  public function getObjectSummary() {
    return $this->object_summary;
  }

  public function setObjectSummary($object_summary) {
    $this->object_summary = $object_summary;
  }

  public function getObjectMetadata() {
    return $this->object_metadata;
  }

  public function setObjectMetadata($object_metadata) {
    $this->object_metadata = $object_metadata;
  }

  public  function getObjectContent() {
    return $this->object_content;
  }

  public function setObjectContent($object_content) {
    $this->object_content = $object_content;
  }
}