<?php
/**
 * Created by IntelliJ IDEA.
 * User: wuzesheng
 * Date: 3/25/14
 * Time: 2:42 PM
 */

namespace FDS\Test;

require_once(dirname(dirname(dirname(__FILE__))) . "/bootstrap.php");

use FDS\auth\Common;
use FDS\credential\BasicFDSCredential;
use FDS\FDSClientConfiguration;
use FDS\GalaxyFDSClient;
use FDS\model\AccessControlList;
use FDS\model\FDSObjectMetadata;
use FDS\model\Grant;
use FDS\model\Grantee;
use FDS\model\Permission;

class GalaxyFDSClientTest extends \PHPUnit_Framework_TestCase {

  private static $credential;
  private static $fds_client;
  private static $bucket_name;

  public static function setUpBeforeClass() {
    $fdsConfig = new FDSClientConfiguration();
    $fdsConfig->setEnableMd5Calculate(true);
    $fdsConfig->enableUnitTestMode(true);
    $fdsConfig->setBaseUriforunittest("http://files.fds.api.xiaomi.com/");
    self::$credential = new BasicFDSCredential("access_key", "access_secret");
    self::$fds_client = new GalaxyFDSClient(self::$credential, $fdsConfig);
    self::$bucket_name = "test-php-sdk-bucket-name";
  }

  public static function tearDownAfterClass() {
    self::$fds_client->setDelimiter("");
    $listing = self::$fds_client->listObjects(self::$bucket_name);
    foreach ($listing->getObjectSummaries() as $summary) {
      $object = $summary->getObjectName();
      self::$fds_client->deleteObject(self::$bucket_name, $object);
    }
    self::$fds_client->deleteBucket(self::$bucket_name);
  }

  public function testCreateBucket() {
    if (self::$fds_client->doesBucketExist(self::$bucket_name)) {
      self::$fds_client->deleteBucket(self::$bucket_name);
    }

    self::$fds_client->createBucket(self::$bucket_name);
    $this->assertTrue(self::$fds_client->doesBucketExist(self::$bucket_name));
  }

  /**
   * @depends testCreateBucket
   */
  public function testListBuckets() {
    $buckets = self::$fds_client->listBuckets();
    $this->assertNotNull($buckets);
    $this->assertNotEmpty($buckets);
  }

  /**
   * @depends testCreateBucket
   */
  public function testBucketAcl() {
    $acl = self::$fds_client->getBucketAcl(self::$bucket_name);
    $this->assertNotNull($acl);
    $this->assertEquals(1, count($acl->getGrantList()));
    // $this->assertEquals($this->credential->getGalaxyAccessId(),
    // $acl->getGrantList()[0]->getGrantee()->getId());

    $to_set_acl = new AccessControlList();
    $to_set_acl->addGrant(new Grant(new Grantee("test"), Permission::READ));
    self::$fds_client->setBucketAcl(self::$bucket_name, $to_set_acl);
    $got_acl = self::$fds_client->getBucketAcl(self::$bucket_name);
    $this->assertNotNull($got_acl);
    $grants = $got_acl->getGrantList();
    $grantees =  array();
    foreach ($grants as $key => $value) {
      $grantees[$key] = $value->getGrantee()->getId();
    }
    sort($grantees);
    $this->assertEquals(2, count($grantees));
  }

  /**
   * @depends testCreateBucket
   */
  /*
  public function testBucketQuota() {
    $quota = new QuotaPolicy();
    $quota->addQuota(new Quota(Action::GetObject, QuotaType::QPS, 1000));
    self::$fds_client->setBucketQuota(self::$bucket_name, $quota);
    $got_quota = self::$fds_client->getBucketQuota(self::$bucket_name);
    $this->assertNotNull($got_quota);
    $quotaList = $got_quota->getQuotas();
    $this->assertEquals(1, count($quotaList));
    foreach ($quotaList as $key => $value) {
      $this->assertEquals(Action::GetObject, $value->getAction());
      $this->assertEquals(QuotaType::QPS, $value->getType());
      $this->assertEquals(1000, $value->getValue());
    }
  }
  */

  /**
   * @depends testCreateBucket
   */
  public function testListObjects() {
    $listing = self::$fds_client->listObjects(self::$bucket_name);
    $this->assertNotNull($listing);
  }

  /**
   * @depends testCreateBucket
   */
  public function testPutAndGetObject() {
    $object_name = "test.txt";
    $content = "hello world";
    $result = self::$fds_client->putObject(self::$bucket_name,
      $object_name, $content);
    $this->assertNotNull($result);
    $this->assertEquals($object_name, $result->getObjectName());
    $this->assertTrue(self::$fds_client->doesObjectExist(
      self::$bucket_name, $object_name));

    $object = self::$fds_client->getObject(self::$bucket_name, $object_name);
    $this->assertNotNull($object);
    $this->assertEquals($content, $object->getObjectContent());
  }

  /**
   * @depends testCreateBucket
   */
  public function testPostObject() {
    $content = "hello world";
    $result = self::$fds_client->postObject(self::$bucket_name, $content);
    $this->assertNotNull($result);
    $this->assertEquals(self::$bucket_name, $result->getBucketName());
  }

  /**
   * @depends testCreateBucket
   */
  public function testObjectMetadata() {
    $object_name = "test-object-metadata.txt";
    $content = "hello object!!";

    $metadata = new FDSObjectMetadata();
    $metadata->addUserMetadata("x-xiaomi-meta-" . "test", "test-metadata");
    $metadata->setCacheControl("max-age=86400");
    $metadata->setContentEncoding("abaaaaa");
    $metadata->setContentMD5("aaaaaaaaccccccc");

    self::$fds_client->putObject(self::$bucket_name, $object_name,
      $content, $metadata);

    $object = self::$fds_client->getObject(self::$bucket_name, $object_name);
    $this->assertNotNull($object);
    $object_metadata = $object->getObjectMetadata();
    $this->assertNotNull($object_metadata);

    $raw_metadata = $metadata->getRawMetadata();
    $raw_object_metadata = $object_metadata->getRawMetadata();
    foreach ($raw_metadata as $key => $value) {
      $this->assertTrue(array_key_exists($key, $raw_object_metadata));
      $this->assertEquals($value, $raw_object_metadata[$key]);
    }
    $this->assertEquals(md5($content), $object_metadata->getContentMD5());
  }

  /**
   * @depends testCreateBucket
   */
  public function testInvalidObjectMetadata() {
    $metadata = new FDSObjectMetadata();

    $metadata->addUserMetadata(
        FDSObjectMetadata::USER_DEFINED_METADATA_PREFIX . "test", "test-value");
    $metadata->addHeader(Common::CACHE_CONTROL, "no-cache");

    try {
      $metadata->addUserMetadata("test-meta-key", "test-meta-value");
      $this->fail("Expected an exception to be thrown due to invalid metadata");
    } catch (\Exception $e) {

    }
  }

  /**
   * @depends testPutAndGetObject
   */
  public function testObjectAcl() {
    $object_name = "test.txt";
    $acl = self::$fds_client->getObjectAcl(self::$bucket_name, $object_name);
    $this->assertNotNull($acl);
    // TODO(wuzesheng) Fix the delete bucket issue
    // $this->assertEquals(1, count($acl->getGrantList()));

    $acl_to_set = new AccessControlList();
    $acl_to_set->addGrant(new Grant(new Grantee("test"), Permission::READ));
    self::$fds_client->setObjectAcl(self::$bucket_name, $object_name, $acl_to_set);
    $got_acl = self::$fds_client->getObjectAcl(self::$bucket_name, $object_name);
    $this->assertNotNull($got_acl);
    $grants = $got_acl->getGrantList();
    $grantees = array();
    foreach ($grants as $key => $value) {
      $grantees[$key] = $value->getGrantee()->getId();
    }
    $this->assertEquals(2, count($grantees));
    sort($grantees);
  }

  /**
   * @depends testPutAndGetObject
   */
  public function testDeleteObject() {
    $object_name = "test.txt";
    self::$fds_client->deleteObject(self::$bucket_name, $object_name);
    $this->assertFalse(self::$fds_client->doesObjectExist(
      self::$bucket_name, $object_name));
  }

  /**
   * @depends testCreateBucket
   */
  public function testRenameObject() {
    $this->assertTrue(self::$fds_client->doesBucketExist(self::$bucket_name));
    $object_name = "object-name.txt";
    $content = "rename-test-" . time();
    self::$fds_client->putObject(self::$bucket_name, $object_name, $content);
    $this->assertTrue(self::$fds_client->doesObjectExist(
      self::$bucket_name, $object_name));

    $renamed_name = "renamed-oject-name.txt";
    self::$fds_client->renameObject(self::$bucket_name,
      $object_name,$renamed_name);
    $this->assertFalse(self::$fds_client->doesObjectExist(
      self::$bucket_name, $object_name));
    $this->assertTrue(self::$fds_client->doesObjectExist(
      self::$bucket_name, $renamed_name));
    $object = self::$fds_client->getObject(self::$bucket_name, $renamed_name);
    $this->assertNotNull($object);
    $this->assertEquals($content, $object->getObjectContent());
  }

  /**
   * @depends testCreateBucket
   */
  public function testListObjectsWithPrefixOfRoot() {
    $this->emptyBucket();
    $test_content = "test_content" . time();
    $object_names = array(
      "bar/bash",
      "bar/bang",
      "bar/bang/bang",
      "bar/baz",
      "bee",
      "boo",
      "bang/bang",
    );
    $expected_objects = array(
      "bee",
      "boo",
    );
    $expected_common_prefixes = array(
      "bang/",
      "bar/",
    );

    foreach ($object_names as $name) {
      self::$fds_client->putObject(self::$bucket_name, $name, $test_content);
    }
    $listing = self::$fds_client->listObjects(self::$bucket_name);

    sort($expected_objects);
    $index = 0;
    foreach ($expected_objects as $object) {
      $this->assertEquals($object,
        $listing->getObjectSummaries()[$index++]->getObjectName());
    }

    sort($expected_common_prefixes);
    $index = 0;
    foreach ($expected_common_prefixes as $prefix) {
      $this->assertEquals($prefix, $listing->getCommonPrefixes()[$index++]);
    }
  }

  public function testPresigedUri() {
    $object_name = "中文测试";
    $content = "presigned";
    self::$fds_client->putObject(self::$bucket_name, $object_name, $content);
    $uri = self::$fds_client->generatePresignedUri(self::$bucket_name,
        $object_name, time() * 1000 + 60000);
    $download = file_get_contents($uri);
    $this->assertEquals($content, $download);
  }

  private function emptyBucket() {
    $listing = self::$fds_client->listObjects(self::$bucket_name);
    foreach ($listing->getObjectSummaries() as $summary) {
      $object = $summary->getObjectName();
      self::$fds_client->deleteObject(self::$bucket_name, $object);
    }
  }
}
