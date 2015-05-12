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
  const DEFAULT_CONNECTION_TIMEOUT_SECS = 30;

  private $region_name;
  private $enable_https;
  private $enable_cdn_for_upload;
  private $enable_cdn_for_download;
  private $enable_md5_calculate;
  private $enable_debug;
  private $enable_metrics;
  private $retry;
  private $connection_timeout_secs;

  private $enable_unit_test_mode;
  private $base_uri_for_unit_test;

  public function __construct() {
    $this->enable_https = true;
    $this->region_name = "";
    $this->enable_cdn_for_upload = false;
    $this->enable_cdn_for_download = true;
    $this->enable_md5_calculate = false;
    $this->enable_debug = false;
    $this->enable_metrics = false;

    $this->enable_unit_test_mode = false;
    $this->base_uri_for_unit_test = "";
    $this->connection_timeout_secs = self::DEFAULT_CONNECTION_TIMEOUT_SECS;
  }

  public function getRegionName() {
    return $this->region_name;
  }

  public function setRegionName($region_name) {
    $this->region_name = $region_name;
  }

  public function isHttpsEnabled() {
    return $this->enable_https;
  }

  public function enableHttps($enable_https) {
    $this->enable_https = $enable_https;
  }

  public function isCdnEnabledForUpload() {
    return $this->enable_cdn_for_upload;
  }

  public function enableCdnForUpload($enable_cdn_for_upload) {
    $this->enable_cdn_for_upload = $enable_cdn_for_upload;
  }

  public function isCdnEnabledForDownload() {
    return $this->enable_cdn_for_download;
  }

  public function enableCdnForDownload($enable_cdn_for_download) {
    $this->enable_cdn_for_download = $enable_cdn_for_download;
  }

  public function isEnableMd5Calculate() {
    return $this->enable_md5_calculate;
  }

  public function setEnableMd5Calculate($enable_md5_calculate) {
    $this->enable_md5_calculate = $enable_md5_calculate;
  }

  public function isEnabledUnitTestMode() {
    return $this->enable_unit_test_mode;
  }

  public function enableUnitTestMode($enable_unit_test_mode) {
    $this->enable_unit_test_mode = $enable_unit_test_mode;
  }

  public function setBaseUriforunittest($base_uri_for_unit_test) {
    $this->base_uri_for_unit_test = $base_uri_for_unit_test;
  }

  public function getBaseUri() {
    return $this->buildBaseUri(false);
  }

  public function getCdnBaseUri() {
    return $this->buildBaseUri(true);
  }

  public function getDownloadBaseUri() {
    return $this->buildBaseUri($this->enable_cdn_for_download);
  }

  public function getUploadBaseUri() {
    return $this->buildBaseUri($this->enable_cdn_for_upload);
  }

  public function buildBaseUri($enableCdn) {
    if ($this->enable_unit_test_mode) {
      return $this->base_uri_for_unit_test;
    }

    $uri = $this->enable_https ? self::URI_HTTPS_PREFIX : self::URI_HTTP_PREFIX;
    $uri .= $this->getBaseUriPrefix($enableCdn, $this->region_name);
    $uri .= $this->getBaseUriSuffix($enableCdn, $this->enable_https);
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
    return $this->enable_debug;
  }

  public function enableDebug($enableDebug) {
    $this->enable_debug = $enableDebug;
  }

  public function isMetricsEnabled() {
    return $this->enable_metrics;
  }

  public function enableMetrics($enableMetrics) {
    $this->enable_metrics = $enableMetrics;
  }

  public function setRetry($retry) {
    $this->retry = $retry;
  }

  public function getRetry() {
    return ($this->retry > 0 ? $this->retry : self::DEFAULT_RETRY_NUM);
  }

  public function setConnectionTimeoutSecs($timeout) {
    $this->connection_timeout_secs = $timeout;
  }

  public function getConnectionTimeoutSecs() {
    return $this->connection_timeout_secs;
  }

  public function  getDefaultConnectionTimeoutSecs() {
    return self::DEFAULT_CONNECTION_TIMEOUT_SECS;
  }
}