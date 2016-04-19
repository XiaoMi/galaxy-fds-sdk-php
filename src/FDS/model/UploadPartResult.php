<?php
/**
 * Created by IntelliJ IDEA.
 * User: zhangjunbin
 * Date: 1/18/16
 * Time: 5:36 PM
 */

namespace FDS\model;

class UploadPartResult {

  public $partNumber;
  public $etag;
  public $partSize;

  public static function fromJson($json) {
    if (is_object($json)) {
      $result = new UploadPartResult();
      if (isset($json->partNumber)) {
        $result->setPartNumber($json->partNumber);
      }
      if (isset($json->etag)) {
        $result->setEtag($json->etag);
      }
      if (isset($json->partSize)) {
        $result->setPartSize($json->partSize);
      }

      return $result;
    }
    return NULL;
  }

  public function getPartNumber() {
    return $this->partNumber;
  }

  public function setPartNumber($partNumber) {
    $this->partNumber = $partNumber;
  }

  public function getEtag() {
    return $this->etag;
  }

  public function setEtag($etag) {
    $this->etag = $etag;
  }

  public function getPartSize() {
    return $this->partSize;
  }

  public function setPartSize($partSize) {
    $this->partSize = $partSize;
  }
}

