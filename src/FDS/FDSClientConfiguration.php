<?php
/**
 * Created by IntelliJ IDEA.
 * User: zhangjunbin
 * Date: 12/26/14
 * Time: 1:42 PM
 */

namespace FDS;

class FDSClientConfiguration {

  const URI_HTTP_PREFIX = "http://";
  const URI_HTTPS_PREFIX = "https://";
  const URI_FILES = "files";
  const URI_CDN = "cdn";
  const URI_FDS_SUFFIX = ".fds.api.xiaomi.com/";
  const URI_FDS_SSL_SUFFIX = ".fds-ssl.api.xiaomi.com/";
  const DEFAULT_RETRY_NUM = 3;

  private $regionName;
  private $enableHttps;
  private $enableCdnForUpload;
  private $enableCdnForDownload;
  private $enableDebug;
  private $retry;

  private $enableUnitTestMode;
  private $baseUriForUnitTest;

  public function __construct() {
    $this->enableHttps = true;
    $this->regionName = "";
    $this->enableCdnForUpload = false;
    $this->enableCdnForDownload = true;
    $this->enableDebug = false;

    $this->enableUnitTestMode = false;
    $this->baseUriForUnitTest = "";
  }

  public function getRegionName() {
    return $this->regionName;
  }

  public function setRegionName($regionName) {
    $this->regionName = $regionName;
  }

  public function isHttpsEnabled() {
    return $this->enableHttps;
  }

  public function enableHttps($enableHttps) {
    $this->enableHttps = $enableHttps;
  }

  public function isCdnEnabledForUpload() {
    return $this->enableCdnForUpload;
  }

  public function enableCdnForUpload($enableCdnForUpload) {
    $this->enableCdnForUpload = $enableCdnForUpload;
  }

  public function isCdnEnabledForDownload() {
    return $this->enableCdnForDownload;
  }

  public function enableCdnForDownload($enableCdnForDownload) {
    $this->enableCdnForDownload = $enableCdnForDownload;
  }

  public function isEnabledUnitTestMode() {
    return $this->enableUnitTestMode;
  }

  public function enableUnitTestMode($enableUnitTestMode) {
    $this->enableUnitTestMode = $enableUnitTestMode;
  }

  public function setBaseUriForUnitTest($baseUriForUnitTest) {
    $this->baseUriForUnitTest = $baseUriForUnitTest;
  }

  public function getBaseUri() {
    return $this->buildBaseUri(false);
  }

  public function getCdnBaseUri() {
    return $this->buildBaseUri(true);
  }

  public function getDownloadBaseUri() {
    return $this->buildBaseUri($this->enableCdnForDownload);
  }

  public function getUploadBaseUri() {
    return $this->buildBaseUri($this->enableCdnForUpload);
  }

  public function buildBaseUri($enableCdn) {
    if ($this->enableUnitTestMode) {
      return $this->baseUriForUnitTest;
    }

    $uri = $this->enableHttps ? self::URI_HTTPS_PREFIX : self::URI_HTTP_PREFIX;
    $uri .= $this->getBaseUriPrefix($enableCdn, $this->regionName);
    $uri .= $this->getBaseUriSuffix($enableCdn, $this->enableHttps);
    return $uri;
  }

  private function  getBaseUriPrefix($enableCdn, $regionName) {
    if (empty($regionName)) {
      if ($enableCdn) {
        return self::URI_CDN;
      }
      return self::URI_FILES;
    } else {
      if ($enableCdn) {
        return $regionName . '-' . self::URI_CDN;
      }
      return $regionName . '-' . self::URI_FILES;
    }
  }

  private function getBaseUriSuffix($enableCdn, $enableHttps) {
    if ($enableCdn && $enableHttps) {
      return self::URI_FDS_SSL_SUFFIX;
    }
    return self::URI_FDS_SUFFIX;
  }

  public function isDebugEnabled() {
    return $this->enableDebug;
  }

  public function enableDebug($enableDebug) {
    $this->enableDebug = $enableDebug;
  }

  public function setRetry($retry) {
    $this->retry = $retry;
  }

  public function getRetry() {
    return ($this->retry > 0 ? $this->retry : self::DEFAULT_RETRY_NUM);
  }
}