<?php
/**
 * Created by IntelliJ IDEA.
 * User: zhangjunbin
 * Date: 1/14/16
 * Time: 9:48 PM
 */

namespace FDS\model;

class InitMultipartUploadResult {

  private $bucket_name;
  private $object_name;
  private $upload_id;

  public static function fromJson($json) {
    if (is_object($json)) {
      $result = new InitMultipartUploadResult();
      if (isset($json->bucketName)) {
        $result->setBucketName($json->bucketName);
      }

      if (isset($json->objectName)) {
        $result->setObjectName($json->objectName);
      }

      if (isset($json->uploadId)) {
        $result->setUploadId($json->uploadId);
      }

      return $result;
    }
    return NULL;
  }

  public function setBucketName($bucket_name) {
    $this->bucket_name = $bucket_name;
  }

  public function getBucketName() {
    return $this->bucket_name;
  }

  public function setObjectName($object_name) {
    $this->object_name = $object_name;
  }

  public function getObjectName() {
    return $this->object_name;
  }

  public function setUploadId($upload_id) {
    $this->upload_id = $upload_id;
  }

  public function getUploadId() {
    return$this->upload_id;
  }
}
