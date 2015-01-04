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

  const URI_FDS_SUFFIX = ".fds.api.xiaomi.com/";

  public function testDefaultConfigurationValue() {
    $fdsConfig = new FDSClientConfiguration();
    $this->assertEquals("", $fdsConfig->getRegionName());
    $this->assertEquals(true, $fdsConfig->isHttpsEnabled());
    $this->assertEquals(false, $fdsConfig->isCdnEnabledForUpload());
    $this->assertEquals(true, $fdsConfig->isCdnEnabledForDownload());
    $this->assertEquals(false, $fdsConfig->isEnabledUnitTestMode());
  }

  public function testCdnChosen() {
    $fdsConfig = new FDSClientConfiguration();
    $fdsConfig->setRegionName("");
    $fdsConfig->enableHttps(true);

    // Test flag enableCdnForUpload
    $fdsConfig->enableCdnForUpload(false);
    $this->assertEquals("https://files" . self::URI_FDS_SUFFIX,
        $fdsConfig->getUploadBaseUri());
    $fdsConfig->enableCdnForUpload(true);
    $this->assertEquals("https://cdns" . self::URI_FDS_SUFFIX,
        $fdsConfig->getUploadBaseUri());
    $fdsConfig->enableHttps(false);
    $this->assertEquals("http://cdn" . self::URI_FDS_SUFFIX,
        $fdsConfig->getUploadBaseUri());

    // Test flag enableCdnForDownload
    $fdsConfig->enableCdnForDownload(false);
    $this->assertEquals("http://files" . self::URI_FDS_SUFFIX,
        $fdsConfig->getDownloadBaseUri());
    $fdsConfig->enableCdnForDownload(true);
    $this->assertEquals("http://cdn" . self::URI_FDS_SUFFIX,
        $fdsConfig->getDownloadBaseUri());
    $fdsConfig->enableHttps(true);
    $this->assertEquals("https://cdns" . self::URI_FDS_SUFFIX,
        $fdsConfig->getDownloadBaseUri());
  }

  public function testBuildBaseUri() {
    $regionName = "regionName";
    $regionNameSuffix = "regionNameSuffix";

    $fdsConfig = new FDSClientConfiguration();

    // Test against flag enable https.
    $fdsConfig->setRegionName("");
    $fdsConfig->enableHttps(true);
    $this->assertEquals("https://" . $regionNameSuffix . self::URI_FDS_SUFFIX,
        $fdsConfig->buildBaseUri($regionNameSuffix));
    $fdsConfig->enableHttps(false);
    $this->assertEquals("http://" . $regionNameSuffix . self::URI_FDS_SUFFIX,
        $fdsConfig->buildBaseUri($regionNameSuffix));

    // Test against region name.
    $fdsConfig->setRegionName($regionName);
    $fdsConfig->enableHttps(true);
    $this->assertEquals("https://" . $regionName . "-" . $regionNameSuffix .
        self::URI_FDS_SUFFIX , $fdsConfig->buildBaseUri($regionNameSuffix));
  }
}