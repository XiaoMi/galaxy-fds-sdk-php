<?php
/**
 * Created by IntelliJ IDEA.
 * User: wuzesheng
 * Date: 3/24/14
 * Time: 10:34 AM
 */

namespace FDS\model;

use FDS\auth\Common;
use FDS\GalaxyFDSClientException;

class FDSObjectMetadata {

  const USER_DEFINED_METADATA_PREFIX = "x-xiaomi-meta-";

  static $PRE_DEFINED_METADATA = array(
    Common::CACHE_CONTROL, Common::CONTENT_ENCODING,
    Common::CONTENT_LENGTH, Common::CONTENT_MD5,
    Common::CONTENT_TYPE
  );
  private $metadata = array();

  public function addHeader($key, $value) {
    $this->checkMetadata($key);
    $this->metadata[$key] = $value;
  }

  public function addUserMetadata($key, $value) {
    $this->checkMetadata($key);
    $this->metadata[$key] = $value;
  }

  public function setUserMetadata($user_metadata) {
    foreach ($user_metadata as $key => $value) {
      $this->checkMetadata($key);
      $this->metadata[$key] = $value;
    }
  }

  public function getCacheControl() {
    if (array_key_exists(Common::CACHE_CONTROL, $this->metadata)) {
      return $this->metadata[Common::CACHE_CONTROL];
    }
    return null;
  }

  public function setCacheControl($cache_control) {
    $this->metadata[Common::CACHE_CONTROL] = $cache_control;
  }

  public function getContentEncoding() {
    if (array_key_exists(Common::CONTENT_ENCODING, $this->metadata)) {
      return $this->metadata[Common::CONTENT_ENCODING];
    }
    return null;
  }

  public function setContentEncoding($content_encoding) {
    $this->metadata[Common::CONTENT_ENCODING] = $content_encoding;
  }

  public function getContentLength() {
    if (array_key_exists(Common::CONTENT_LENGTH, $this->metadata)) {
      return $this->metadata[Common::CONTENT_LENGTH];
    }
    return null;
  }

  public function setContentLength($content_length) {
    $this->metadata[Common::CONTENT_LENGTH] = $content_length;
  }

  public function getLastModified() {
    if (array_key_exists(Common::LAST_MODIFIED, $this->metadata)) {
      return $this->metadata[Common::LAST_MODIFIED];
    }
    return null;
  }

  public function setLastModified($last_modified) {
    $this->metadata[Common::LAST_MODIFIED] = $last_modified;
  }

  public function getContentMD5() {
    if (array_key_exists(Common::CONTENT_MD5, $this->metadata)) {
      return $this->metadata[Common::CONTENT_MD5];
    }
    return null;
  }

  public function setContentMD5($content_md5) {
    $this->metadata[Common::CONTENT_MD5] = $content_md5;
  }

  public function getContentType() {
    if (array_key_exists(Common::CONTENT_TYPE, $this->metadata)) {
      return $this->metadata[Common::CONTENT_TYPE];
    }
    return null;
  }

  public function setContentType($content_type) {
    $this->metadata[Common::CONTENT_TYPE] = $content_type;
  }

  public function getRawMetadata() {
    return $this->metadata;
  }

  private function checkMetadata($key) {
    $is_valid = $this->startsWith($key, self::USER_DEFINED_METADATA_PREFIX);

    if (!$is_valid) {
      $is_valid = in_array($key, self::$PRE_DEFINED_METADATA);
    }

    if (!$is_valid) {
      throw new GalaxyFDSClientException("Invalid metadata: " . $key);
    }
  }

  private function startsWith($haystack, $needle) {
    $len = strlen($needle);
    return (substr($haystack, 0, $len) === $needle);
  }
}