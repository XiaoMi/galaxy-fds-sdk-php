<?php
/**
 * Created by IntelliJ IDEA.
 * User: wuzesheng
 * Date: 3/25/14
 * Time: 2:42 PM
 */

namespace FDS\Test;

require_once(dirname(dirname(dirname(__FILE__))) . "/bootstrap.php");

use FDS\credential\BasicFDSCredential;
use FDS\GalaxyFDSClient;
use FDS\model\AccessControlList;
use FDS\model\Action;
use FDS\model\FDSObjectMetadata;
use FDS\model\Grant;
use FDS\model\Grantee;
use FDS\model\Permission;
use FDS\model\Quota;
use FDS\model\QuotaPolicy;
use FDS\model\QuotaType;

class GalaxyFDSClientTest extends \PHPUnit_Framework_TestCase {

  private $fds_server_base_uri;
  private $credential;
  private $fds_client;
  private $bucket_name;
  private $nonce;


  public function __construct() {
    $this->nonce = time();
  }

  public function setUp() {
    $this->fds_server_base_uri = "http://hh-hadoop-fds03.bj:22701/fds/";
    $this->credential = new BasicFDSCredential("test-php-sdk" . $this->nonce,
      "test-php-sdk" . $this->nonce);
    $this->fds_client = new GalaxyFDSClient($this->credential,
      $this->fds_server_base_uri);
    $this->bucket_name = "test-php-sdk-bucket-" . $this->nonce;
    $this->assertNotNull($this->fds_client);
  }

  public function tearDown() {
  }

  public function testCreateBucket() {
    if ($this->fds_client->doesBucketExist($this->bucket_name)) {
      $this->fds_client->deleteBucket($this->bucket_name);
    }

    $this->fds_client->createBucket($this->bucket_name);
    $this->assertTrue($this->fds_client->doesBucketExist($this->bucket_name));
  }

  /**
   * @depends testCreateBucket
   */
  public function testListBuckets() {
    $buckets = $this->fds_client->listBuckets();
    $this->assertNotNull($buckets);
    $this->assertEquals(1, count($buckets));
    $this->assertEquals($this->bucket_name, $buckets[0]->getName());
  }

  /**
   * @depends testCreateBucket
   */
  public function testBucketAcl() {
    $acl = $this->fds_client->getBucketAcl($this->bucket_name);
    $this->assertNotNull($acl);
    $this->assertEquals(1, count($acl->getGrantList()));
    $this->assertEquals($this->credential->getGalaxyAccessId(),
      $acl->getGrantList()[0]->getGrantee()->getId());

    $to_set_acl = new AccessControlList();
    $to_set_acl->addGrant(new Grant(new Grantee("test"), Permission::READ));
    $this->fds_client->setBucketAcl($this->bucket_name, $to_set_acl);
    $got_acl = $this->fds_client->getBucketAcl($this->bucket_name);
    $this->assertNotNull($got_acl);
    $grants = $got_acl->getGrantList();
    $grantees =  array();
    foreach ($grants as $key => $value) {
      $grantees[$key] = $value->getGrantee()->getId();
    }
    sort($grantees);
    $this->assertEquals(2, count($grantees));
    $this->assertEquals("test", $grantees[0]);
    $this->assertEquals($this->credential->getGalaxyAccessId(), $grantees[1]);
  }

  /**
   * @depends testCreateBucket
   */
  /*
  public function testBucketQuota() {
    $quota = new QuotaPolicy();
    $quota->addQuota(new Quota(Action::GetObject, QuotaType::QPS, 1000));
    $this->fds_client->setBucketQuota($this->bucket_name, $quota);
    $got_quota = $this->fds_client->getBucketQuota($this->bucket_name);
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
    $listing = $this->fds_client->listObjects($this->bucket_name);
    $this->assertNotNull($listing);
  }

  /**
   * @depends testCreateBucket
   */
  public function testPutAndGetObject() {
    $object_name = "test.txt";
    $content = "hello world";
    $result = $this->fds_client->putObject($this->bucket_name,
      $object_name, $content);
    $this->assertNotNull($result);
    $this->assertEquals($object_name, $result->getObjectName());
    $this->assertTrue($this->fds_client->doesObjectExist(
      $this->bucket_name, $object_name));

    $object = $this->fds_client->getObject($this->bucket_name, $object_name);
    $this->assertNotNull($object);
    $this->assertEquals($content, $object->getObjectContent());
  }

  /**
   * @depends testCreateBucket
   */
  public function testPostObject() {
    $content = "hello world";
    $result = $this->fds_client->postObject($this->bucket_name, $content);
    $this->assertNotNull($result);
    $this->assertEquals($this->bucket_name, $result->getBucketName());
  }

  /**
   * @depends testCreateBucket
   */
  public function testObjectMetadata() {
    $object_name = "test-object-metadata.txt";
    $content = "hello object!!";

    $metadata = new FDSObjectMetadata();
    $metadata->addUserMetadata("x-xiaomi-meta-" . "test", "test-metadata");
    $metadata->setCacheControl("max-age=1234343");
    $metadata->setContentEncoding("abaaaaa");
    $metadata->setContentMD5("aaaaaaaaccccccc");

    $this->fds_client->putObject($this->bucket_name, $object_name,
      $content, $metadata);

    $object = $this->fds_client->getObject($this->bucket_name, $object_name);
    $this->assertNotNull($object);
    $object_metadata = $object->getObjectMetadata();
    $this->assertNotNull($object_metadata);

    $raw_metadata = $metadata->getRawMetadata();
    $raw_object_metadata = $object_metadata->getRawMetadata();
    foreach ($raw_metadata as $key => $value) {
      $this->assertTrue(array_key_exists($key, $raw_object_metadata));
      $this->assertEquals($value, $raw_object_metadata[$key]);
    }
  }

  /**
   * @depends testPutAndGetObject
   */
  public function testObjectAcl() {
    $object_name = "test.txt";
    $acl = $this->fds_client->getObjectAcl($this->bucket_name, $object_name);
    $this->assertNotNull($acl);
    // TODO(wuzesheng) Fix the delete bucket issue
    // $this->assertEquals(1, count($acl->getGrantList()));

    $acl_to_set = new AccessControlList();
    $acl_to_set->addGrant(new Grant(new Grantee("test"), Permission::READ));
    $this->fds_client->setObjectAcl($this->bucket_name, $object_name, $acl_to_set);
    $got_acl = $this->fds_client->getObjectAcl($this->bucket_name, $object_name);
    $this->assertNotNull($got_acl);
    $grants = $got_acl->getGrantList();
    $grantees = array();
    foreach ($grants as $key => $value) {
      $grantees[$key] = $value->getGrantee()->getId();
    }
    $this->assertEquals(2, count($grantees));
    sort($grantees);
    $this->assertEquals("test", $grantees[0]);
  }

  /**
   * @depends testPutAndGetObject
   */
  public function testDeleteObject() {
    $object_name = "test.txt";
    $this->fds_client->deleteObject($this->bucket_name, $object_name);
    $this->assertFalse($this->fds_client->doesObjectExist(
      $this->bucket_name, $object_name));
  }

  /**
   * @depends testCreateBucket
   */
  public function testRenameObject() {
    $this->assertTrue($this->fds_client->doesBucketExist($this->bucket_name));
    $object_name = "object-name.txt";
    $content = "rename-test-" . time();
    $this->fds_client->putObject($this->bucket_name, $object_name, $content);
    $this->assertTrue($this->fds_client->doesObjectExist(
      $this->bucket_name, $object_name));

    $renamed_name = "renamed-oject-name.txt";
    $this->fds_client->renameObject($this->bucket_name,
      $object_name,$renamed_name);
    $this->assertFalse($this->fds_client->doesObjectExist(
      $this->bucket_name, $object_name));
    $this->assertTrue($this->fds_client->doesObjectExist(
      $this->bucket_name, $renamed_name));
    $object = $this->fds_client->getObject($this->bucket_name, $renamed_name);
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
      $this->fds_client->putObject($this->bucket_name, $name, $test_content);
    }
    $listing = $this->fds_client->listObjects($this->bucket_name);

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

  private function emptyBucket() {
    $listing = $this->fds_client->listObjects($this->bucket_name);
    foreach ($listing->getObjectSummaries() as $summary) {
      $object = $summary->getObjectName();
      $this->fds_client->deleteObject($this->bucket_name, $object);
    }
  }
}
