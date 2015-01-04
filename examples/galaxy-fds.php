<?php
/**
 * Created by IntelliJ IDEA.
 * User: wuzesheng
 * Date: 4/15/14
 * Time: 5:28 PM
 */

require(__DIR__ . "/../bootstrap.php");

// Construct the FDS client
$access_key = "your_access_key";
$access_secret = "your_access_secret";
$credential = new \FDS\credential\BasicFDSCredential($access_key, $access_secret);
$fds_config = new \FDS\FDSClientConfiguration();
$fds_config->enableHttps(true);
$fds_config->enableCdnForUpload(false);
$fds_config->enableCdnForDownload(true);

$fds_client = new \FDS\GalaxyFDSClient($credential, $fds_config);
$bucket_name = "php-sdk-example-bucket-name";

// Check and create the bucket
if (!$fds_client->doesBucketExist($bucket_name)) {
  $fds_client->createBucket($bucket_name);
}

// Put an object
$object_name = "test.txt";
$object_content = "Hello world!";
$result = $fds_client->putObject($bucket_name, $object_name, $object_content);
print_r($result);

// Check the object existence
$exist = $fds_client->doesObjectExist($bucket_name, $object_name);
assert($exist);

// Get the object and check content
$object = $fds_client->getObject($bucket_name, $object_name);
$content = $object->getObjectContent();
print_r($content);

// Delete the object
$fds_client->deleteObject($bucket_name, $object_name);

// Delete the bucket
$fds_client->deleteBucket($bucket_name);

// Check bucket existence
$exist = $fds_client->doesBucketExist($bucket_name);
assert(!$exist);
