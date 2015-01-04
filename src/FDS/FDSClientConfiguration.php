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
  const URI_CDNS = "cdns";
  const URI_FDS_SUFFIX = ".fds.api.xiaomi.com/";

  private $regionName;
  private $enableHttps;
  private $enableCdnForUpload;
  private $enableCdnForDownload;

  private $enableUnitTestMode;
  private $baseUriForUnitTest;

  public function __construct() {
    $this->enableHttps = true;
    $this->regionName = "";
    $this->enableCdnForUpload = false;
    $this->enableCdnForDownload = true;

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
    return $this->buildBaseUri(self::URI_FILES);
  }

  public function getCdnBaseUri() {
    return $this->buildBaseUri($this->getCdnRegionNameSuffix());
  }

  public function getDownloadBaseUri() {
    if ($this->enableCdnForDownload) {
      return $this->buildBaseUri($this->getCdnRegionNameSuffix());
    }
    else {
      return $this->buildBaseUri(self::URI_FILES);
    }
  }

  public function getUploadBaseUri() {
    if ($this->enableCdnForUpload) {
      return $this->buildBaseUri($this->getCdnRegionNameSuffix());
    }
    else {
      return $this->buildBaseUri(self::URI_FILES);
    }
  }

  public function buildBaseUri($regionNameSuffix) {
    if ($this->enableUnitTestMode) {
      return $this->baseUriForUnitTest;
    }

    $uri = $this->enableHttps ? self::URI_HTTPS_PREFIX : self::URI_HTTP_PREFIX;
    if (!empty($this->regionName)) {
      $uri .= $this->regionName . "-";
    }
    $uri .= $regionNameSuffix;
    $uri .= self::URI_FDS_SUFFIX;
    return $uri;
  }

  private function getCdnRegionNameSuffix() {
    return $this->enableHttps ? self::URI_CDNS : self::URI_CDN;
  }
}