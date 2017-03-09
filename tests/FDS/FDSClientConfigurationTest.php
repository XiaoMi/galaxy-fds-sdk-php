<?php
/**
 * Created by IntelliJ IDEA.
 * User: zhangjunbin
 * Date: 12/29/14
 * Time: 1:35 PM
 */

namespace FDS\Test;

require_once(dirname(dirname(dirname(__FILE__))) . "/bootstrap.php");

use FDS\FDSClientConfiguration;

class FDSClientConfigurationTest extends \PHPUnit_Framework_TestCase {

  const URI_SUFFIX = "fds.api.xiaomi.com";
  const URI_CDN_SUFFIX = "fds.api.mi-img.com";

  public function testDefaultConfigurationValue() {
    $fds_config = new FDSClientConfiguration();
    $this->assertEquals("cnbj0", $fds_config->getRegionName());
    $this->assertEquals(true, $fds_config->isHttpsEnabled());
    $this->assertEquals(false, $fds_config->isCdnEnabledForUpload());
    $this->assertEquals(true, $fds_config->isCdnEnabledForDownload());
    $this->assertEquals(false, $fds_config->isEnabledUnitTestMode());
    $this->assertEquals(3, $fds_config->getRetry());
    $this->assertEquals(30, $fds_config->getDefaultConnectionTimeoutSecs());
  }

  public function testCdnChosen() {
    $fdsConfig = new FDSClientConfiguration();
    $fdsConfig->setRegionName("regionName");
    $fdsConfig->enableHttps(true);

    // Test flag enableCdnForUpload
    $fdsConfig->enableCdnForUpload(false);
    $this->assertEquals("https://regionName." . self::URI_SUFFIX . '/',
        $fdsConfig->getUploadBaseUri());
    $fdsConfig->enableCdnForUpload(true);
    $this->assertEquals("https://cdn.regionName." . self::URI_CDN_SUFFIX . '/',
        $fdsConfig->getUploadBaseUri());
    $fdsConfig->enableHttps(false);
    $this->assertEquals("http://cdn.regionName." . self::URI_CDN_SUFFIX . '/',
        $fdsConfig->getUploadBaseUri());

    // Test flag enableCdnForDownload
    $fdsConfig->enableCdnForDownload(false);
    $this->assertEquals("http://regionName." . self::URI_SUFFIX . '/',
        $fdsConfig->getDownloadBaseUri());
    $fdsConfig->enableCdnForDownload(true);
    $this->assertEquals("http://cdn.regionName." . self::URI_CDN_SUFFIX . '/',
        $fdsConfig->getDownloadBaseUri());
    $fdsConfig->enableHttps(true);
    $this->assertEquals("https://cdn.regionName." . self::URI_CDN_SUFFIX . '/',
        $fdsConfig->getDownloadBaseUri());
  }

  public function testBuildBaseUri() {
    $region_name = "regionName";

    $fds_config = new FDSClientConfiguration();

    // Test against flag enable https.
    $fds_config->setRegionName($region_name);
    $fds_config->enableHttps(true);
    $this->assertEquals("https://" . $region_name . '.' . self::URI_SUFFIX . '/',
        $fds_config->buildBaseUri(false));
    $fds_config->enableHttps(false);
    $this->assertEquals("http://" . $region_name . '.' . self::URI_SUFFIX . '/',
        $fds_config->buildBaseUri(false));

    $endpoint = "cnbj1.api.xiaomi.net";
    $fds_config->enableHttps(false);
    $fds_config->setEndpoint($endpoint);
    $this->assertEquals("http://" . $endpoint . "/", $fds_config->buildBaseUri(false));
    $this->assertEquals("http://" . $endpoint . "/", $fds_config->buildBaseUri(true));
    $fds_config->enableHttps(true);
    $this->assertEquals("https://" . $endpoint . "/", $fds_config->buildBaseUri(false));
    $this->assertEquals("https://" . $endpoint . "/", $fds_config->buildBaseUri(true));
  }
}