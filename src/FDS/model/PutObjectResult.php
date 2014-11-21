<?php
/**
 * Created by IntelliJ IDEA.
 * User: wuzesheng
 * Date: 3/24/14
 * Time: 3:32 PM
 */

namespace FDS\model;

use FDS\auth\Common;

class PutObjectResult {

  private $bucket_name;
  private $object_name;
  private $access_key_id;
  private $signature;
  private $expires;

  public static function fromJson($json) {
    // Currently, we only support json object
    if (is_object($json)) {
      $result = new PutObjectResult();
      if (isset($json->bucketName)) {
        $result->setBucketName($json->bucketName);
      }

      if (isset($json->objectName)) {
        $result->setObjectName($json->objectName);
      }

      if (isset($json->accessKeyId)) {
        $result->setAccessKeyId($json->accessKeyId);
      }

      if (isset($json->signature)) {
        $result->setSignature($json->signature);
      }

      if (isset($json->expires)) {
        $result->setExpires($json->expires);
      }
      return $result;
    }
    return NULL;
  }

  public function getAccessKeyId() {
    return $this->access_key_id;
  }

  public function setAccessKeyId($access_key_id) {
    $this->access_key_id = $access_key_id;
  }

  public function getBucketName() {
    return $this->bucket_name;
  }

  public function setBucketName($bucket_name) {
    $this->bucket_name = $bucket_name;
  }

  public function getExpires() {
    return $this->expires;
  }

  public function setExpires($expires) {
    $this->expires = $expires;
  }

  public function getObjectName() {
    return $this->object_name;
  }

  public function setObjectName($object_name) {
    $this->object_name = $object_name;
  }

  public function getSignature() {
    return $this->signature;
  }

  public function setSignature($signature) {
    $this->signature = $signature;
  }

  public function getRelativePreSignedUri() {
    return "/" . $this->bucket_name . "/" . $this->object_name . "?" .
        Common::GALAXY_ACCESS_KEY_ID . "=" . $this->access_key_id . "&" .
        Common::EXPIRES . $this->expires . "&" .
        Common::SIGNATURE . "=" . $this->signature;
  }

  public function getAbsolutePreSignedUri($fdsBaseServiceUri) {
    if ($fdsBaseServiceUri == null) {
      return rtrim(Common::DEFAULT_FDS_SERVICE_BASE_URI, '/') . $this->getRelativePreSignedUri();
    } else {
      return rtrim($fdsBaseServiceUri, '/') . $this->getRelativePreSignedUri();
    }
  }

  public function getCdnPreSignedUri($cdnServiceUri) {
    if ($cdnServiceUri == null) {
      return rtrim(Common::DEFAULT_CDN_SERVICE_URI, '/') . $this->getRelativePreSignedUri();
    } else {
      return rtrim($cdnServiceUri, '/') . $this->getRelativePreSignedUri();
    }
  }
}