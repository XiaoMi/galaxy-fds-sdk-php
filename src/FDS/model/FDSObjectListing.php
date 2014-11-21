<?php
/**
 * Created by IntelliJ IDEA.
 * User: wuzesheng
 * Date: 3/24/14
 * Time: 3:24 PM
 */

namespace FDS\model;

class FDSObjectListing {

  private $bucket_name;
  private $prefix;
  private $marker;
  private $next_marker;
  private $max_keys;
  private $truncated;
  private $object_summaries;
  private $common_prefixes;

  public static function fromJson($json) {
    // Currently, we only support json object
    if (is_object($json)) {
      $listing = new FDSObjectListing();
      if (isset($json->name)) {
        $listing->setBucketName($json->name);
      }

      if (isset($json->prefix)) {
        $listing->setPrefix($json->prefix);
      }

      if (isset($json->marker)) {
        $listing->setMarker($json->marker);
      }

      if (isset($json->nextMarker)) {
        $listing->setNextMarker($json->nextMarker);
      }

      if (isset($json->maxKeys)) {
        $listing->setMaxKeys($json->maxKeys);
      }

      if (isset($json->truncated)) {
        $listing->setTruncated($json->truncated);
      }


      if (isset($json->objects)) {
        $summaries = array();
        foreach ($json->objects as $key => $object) {
          $summary = FDSObjectSummary::fromJson($object);
          $summary->setBucketName($listing->getBucketName());
          $summaries[$key] = $summary;
        }
        $listing->setObjectSummaries($summaries);
      }

      if (isset($json->commonPrefixes)) {
        $listing->setCommonPrefixes($json->commonPrefixes);
      }
      return $listing;
    }
    return NULL;
  }

  public function getBucketName() {
    return $this->bucket_name;
  }

  public function setBucketName($bucket_name) {
    $this->bucket_name = $bucket_name;
  }

  public function getCommonPrefixes() {
    return $this->common_prefixes;
  }

  public function setCommonPrefixes($common_prefixes) {
    $this->common_prefixes = $common_prefixes;
  }

  public function getMarker() {
    return $this->marker;
  }

  public function setMarker($marker) {
    $this->marker = $marker;
  }

  public function getMaxKeys() {
    return $this->max_keys;
  }

  public function setMaxKeys($max_keys) {
    $this->max_keys = $max_keys;
  }

  public function getNextMarker() {
    return $this->next_marker;
  }

  public function setNextMarker($next_marker) {
    $this->next_marker = $next_marker;
  }

  public function getObjectSummaries() {
    return $this->object_summaries;
  }

  public function setObjectSummaries($object_summaries) {
    $this->object_summaries = $object_summaries;
  }

  public function getPrefix() {
    return $this->prefix;
  }

  public function setPrefix($prefix) {
    $this->prefix = $prefix;
  }

  public function isTruncated() {
    return $this->truncated;
  }

  public function setTruncated($truncated) {
    $this->truncated = $truncated;
  }
}