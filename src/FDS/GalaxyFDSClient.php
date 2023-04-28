<?php
/**
 * Created by IntelliJ IDEA.
 * User: wuzesheng
 * Date: 3/24/14
 * Time: 12:00 AM
 */

namespace FDS;

use FDS\auth\Common;
use FDS\auth\signature\Signer;
use FDS\metrics\MetricsCollector;
use FDS\metrics\RequestMetrics;
use FDS\model\AccessControlList;
use FDS\model\AccessControlPolicy;
use FDS\model\Action;
use FDS\model\FDSBucket;
use FDS\model\FDSObject;
use FDS\model\FDSObjectListing;
use FDS\model\FDSObjectMetadata;
use FDS\model\FDSObjectSummary;
use FDS\model\Grant;
use FDS\model\Grantee;
use FDS\model\GrantType;
use FDS\model\InitMultipartUploadResult;
use FDS\model\Owner;
use FDS\model\Permission;
use FDS\model\PutObjectResult;
use FDS\model\SubResource;
use FDS\model\UploadPartResult;
use FDS\model\UserGroups;
use Httpful\Http;
use Httpful\Mime;
use Httpful\Request;

class GalaxyFDSClient implements GalaxyFDS {

  const DATE_FORMAT = 'D, d M Y H:i:s \G\M\T';
  const SIGN_ALGORITHM = "sha1";
  const HTTP_OK = 200;
  const HTTP_PARTIAL_OK = 206;
  const HTTP_NOT_FOUND = 404;
  const APPLICATION_OCTET_STREAM = "application/octet-stream";
  const LONG_MAX = 9223372036854775807;

  private $credential;
  private $fds_config;
  private $metrics_collector;
  private $delimiter = "/";

  public function __construct($credential, $fds_config_or_base_uri = "") {
    $this->credential = $credential;

    if (is_string($fds_config_or_base_uri)) {
      $this->fds_config = new FDSClientConfiguration();

      if (empty($fds_config_or_base_uri)) {
        $fds_config_or_base_uri = Common::DEFAULT_FDS_SERVICE_BASE_URI;
      }

      // Only considering the protocol used in fdsBaseUri, http(s), is enough
      // for compatibility.
      if (0 === strpos($fds_config_or_base_uri, "http://")) {
        $this->fds_config->enableHttps(false);
      }
    } else {
      $this->fds_config = $fds_config_or_base_uri;
    }

    if ($this->fds_config->isMetricsEnabled()) {
      $this->metrics_collector = new MetricsCollector($this);
      $this->metrics_collector->start();
    }
  }

  public function listBuckets() {
    $uri = $this->formatUri($this->fds_config->getBaseUri(), "");
    $headers = $this->prepareRequestHeader($uri, Http::GET, NULL);

    $response = $this->invoke(Action::ListBuckets, $uri, $headers, Http::GET,
        Mime::JSON, null);

    if ($response->code == self::HTTP_OK) {
      $buckets = array();
      if ($response->body != NULL) {
        $owner = Owner::fromJson($response->body->owner);
        foreach ($response->body->buckets as $key => $value) {
          $buckets[$key] = FDSBucket::fromJson($value);
          $buckets[$key]->setOwner($owner);
        }
      }
      return $buckets;
    } else {
      $message = "List buckets failed for current user, status=" .
        $response->code . ", reason=" . $response->raw_body;
      // TODO(wuzesheng) Write error log
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function createBucket($bucket_name) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(), $bucket_name);
    $headers = $this->prepareRequestHeader($uri, Http::PUT, Mime::JSON);

    $response = $this->invoke(Action::PutBucket, $uri, $headers, Http::PUT,
        null, "{}");

    if ($response->code != self::HTTP_OK) {
      $message = "Create bucket failed, status=" . $response->code .
        ", bucket_name=" . $bucket_name .
        ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function deleteBucket($bucket_name) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(), $bucket_name);
    $headers = $this->prepareRequestHeader($uri, Http::DELETE, NULL);

    $response = $this->invoke(Action::DeleteBucket, $uri, $headers,
        Http::DELETE, null, null);

    if ($response->code != self::HTTP_OK) {
      $message = "Delete bucket failed, status=" . $response->code .
        ", bucket_name=" . $bucket_name .
        ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function doesBucketExist($bucket_name) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(), $bucket_name);
    $headers = $this->prepareRequestHeader($uri, Http::HEAD, NULL);

    $response = $this->invoke(Action::HeadBucket, $uri, $headers, Http::HEAD,
        null, null);

    if ($response->code == self::HTTP_OK) {
      return true;
    } elseif ($response->code == self::HTTP_NOT_FOUND) {
      return false;
    } else {
      $message = "Check bucket existence failed, status=" . $response->code .
        ", bucket_name=" . $bucket_name .
        ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function getBucketAcl($bucket_name) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(), $bucket_name,
      SubResource::ACL);
    $headers = $this->prepareRequestHeader($uri, Http::GET, Mime::JSON);

    $response = $this->invoke(Action::GetBucketACL, $uri, $headers, Http::GET,
        Mime::JSON, null);

    if ($response->code == self::HTTP_OK) {
      $acp = AccessControlPolicy::fromJson($response->body);
      return $this->acpToAcl($acp);
    } else {
      $message = "Get bucket acl failed, status=" . $response->code .
        ", bucket_name=" . $bucket_name .
        ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function setBucketAcl($bucket_name, $acl) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(), $bucket_name, SubResource::ACL);
    $headers = $this->prepareRequestHeader($uri, Http::PUT, Mime::JSON);

    $response = $this->invoke(Action::PutBucketACL, $uri, $headers, Http::PUT,
        null, json_encode($this->aclToAcp($acl)));

    if ($response->code != self::HTTP_OK) {
      $message = "Set bucket acl failed, status=" . $response->code .
        ", bucket_name=" . $bucket_name .
        ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function listObjects($bucket_name, $prefix = "") {
    $uri = $this->formatUri($this->fds_config->getBaseUri(), $bucket_name, "prefix=" . $prefix,
      "delimiter=" . $this->delimiter);
    $headers = $this->prepareRequestHeader($uri, Http::GET, Mime::JSON);

    $response = $this->invoke(Action::ListObjects, $uri, $headers, Http::GET,
        Mime::JSON, null);

    if ($response->code == self::HTTP_OK) {
      $listing = FDSObjectListing::fromJson($response->body);
      return $listing;
    } else {
      $message = "List objects failed, status=" . $response->code .
        ", bucket_name=" . $bucket_name . ", prefix=" . $prefix .
        ", delimiter=" . $this->delimiter . ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function listTrashObjects($prefix = "") {
    return $this->listObjects("trash", $prefix);
  }

  public function listNextBatchOfObjects($previous_object_listing) {
    if (!$previous_object_listing->isTruncated()) {
      // TODO(wuzesheng) Log a warning message
      return NULL;
    }

    $bucket_name = $previous_object_listing->getBucketName();
    $prefix = $previous_object_listing->getPrefix();
    $delimiter = $previous_object_listing->getDelimiter();
    $marker = $previous_object_listing->getNextMarker();
    $uri = $this->formatUri($this->fds_config->getBaseUri(), $bucket_name,
      "prefix=" . $prefix, "delimiter=" . $delimiter, "marker=" . $marker);
    $headers = $this->prepareRequestHeader($uri, Http::GET, Mime::JSON);

    $response = $this->invoke(Action::ListObjects, $uri, $headers, Http::GET,
        Mime::JSON, null);

    if ($response->code == self::HTTP_OK) {
      $listing = FDSObjectListing::fromJson($response->body);
      return $listing;
    } else {
      $message = "List next batch of objects failed, status=" . $response->code
        . ", bucket_name=" . $bucket_name . ", prefix=" . $prefix .
        ", marker=" . $marker . ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function putObject($bucket_name, $object_name, $content,
                            $metadata = NULL) {
    $uri = $this->formatUri($this->fds_config->getUploadBaseUri(),
        $bucket_name . "/" . $object_name);
    if ($this->fds_config->isEnableMd5Calculate()) {
      if ($metadata == NULL) {
        $metadata = new FDSObjectMetadata();
      }
      $metadata->setContentMD5(md5($content));
    }
    $header = $this->prepareRequestHeader($uri, Http::PUT,
      self::APPLICATION_OCTET_STREAM, $metadata);

    $response = $this->invoke(Action::PutObject, $uri, $header, Http::PUT,
        null, $content);

    if ($response->code == self::HTTP_OK) {
      $result = PutObjectResult::fromJson($response->body);
      return $result;
    } else {
      $message = "Put object failed, status=" . $response->code .
        ", bucket_name=" . $bucket_name . ", object_name=" . $object_name .
        ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function postObject($bucket_name, $content, $metadata = NULL) {
    $uri = $this->formatUri($this->fds_config->getUploadBaseUri(),
        $bucket_name . "/");
    if ($this->fds_config->isEnableMd5Calculate()) {
      if ($metadata == NULL) {
        $metadata = new FDSObjectMetadata();
      }
      $metadata->setContentMD5(md5($content));
    }
    $header = $this->prepareRequestHeader($uri, Http::POST,
      self::APPLICATION_OCTET_STREAM, $metadata);

    $response = $this->invoke(Action::PostObject, $uri, $header, Http::POST,
        null, $content);

    if ($response->code == self::HTTP_OK) {
      $result = PutObjectResult::fromJson($response->body);
      return $result;
    } else {
      $message = "Post object failed, status=" . $response->code .
        ", bucket_name=" . $bucket_name . ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function getObject($bucket_name, $object_name, $start = 0, $end = self::LONG_MAX) {
    $uri = $this->formatUri($this->fds_config->getDownloadBaseUri(),
        $bucket_name . "/" . $object_name);
    $headers = $this->prepareRequestHeader($uri, Http::GET, NULL);
    if ($start != 0 && $end != self::LONG_MAX) {
      $headers[Common::RANGE] = "bytes=" . $start . "-" . $end;
    }

    $response = $this->invoke(Action::GetObject, $uri, $headers, Http::GET,
        self::APPLICATION_OCTET_STREAM, null);

    if ($response->code == self::HTTP_OK || $response->code == self::HTTP_PARTIAL_OK) {
      $object = new FDSObject();
      $object->setObjectContent($response->raw_body);

      $summary = new FDSObjectSummary();
      $summary->setBucketName($bucket_name);
      $summary->setObjectName($object_name);
      $summary->setSize($response->headers["Content-Length"]);
      $object->setObjectSummary($summary);
      $object->setObjectMetadata($this->parseObjectMetadataFromHeaders(
        $response->headers->toArray()));

      if ($this->fds_config->isDebugEnabled()) {
        $length = strlen($object->getObjectContent());
        if (!assert('$summary->getSize() == $length')) {
          echo "Assertion failed: Object content length doesn't match,
           expected:" . $summary->getSize() . ", actual:" . $length;
        }
      }
      return $object;
    } else {
      $message = "Get object failed, status=" . $response->code .
        ", bucket_name=" . $bucket_name . ", object_name=" . $object_name .
        ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function getObjectMetadata($bucket_name, $object_name) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(),
        $bucket_name . "/" . $object_name,
      SubResource::METADATA);
    $headers = $this->prepareRequestHeader($uri, Http::GET, Mime::JSON);

    $response = $this->invoke(Action::GetObjectMetadata, $uri, $headers,
        Http::GET, null, null);

    if ($response->code == self::HTTP_OK) {
      $metadata = $this->parseObjectMetadataFromHeaders(
        $response->headers->toArray());
      return $metadata;
    } else {
      $message = "Get object metadata failed, status=" . $response->code .
        ", bucket_name=" . $bucket_name . ", object_name=" . $object_name .
        ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function getObjectAcl($bucket_name, $object_name) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(),
        $bucket_name . "/" . $object_name,
      SubResource::ACL);
    $headers = $this->prepareRequestHeader($uri, Http::GET, Mime::JSON);

    $response = $this->invoke(Action::GetObjectACL, $uri, $headers, Http::GET,
        Mime::JSON, null);

    if ($response->code == self::HTTP_OK) {
      $acp = AccessControlPolicy::fromJson($response->body);
      $acl = $this->acpToAcl($acp);
      return $acl;
    } else {
      $message = "Get object acl failed, status=" . $response->code .
        ", bucket_name=" . $bucket_name . ", object_name=" . $object_name .
        ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function setObjectAcl($bucket_name, $object_name, $acl) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(),
        $bucket_name . "/" . $object_name,
      SubResource::ACL);
    $headers = $this->prepareRequestHeader($uri, Http::PUT, Mime::JSON);

    $response = $this->invoke(Action::PutObjectACL, $uri, $headers, Http::PUT,
        null, json_encode($this->aclToAcp($acl)));

    if ($response->code != self::HTTP_OK) {
      $message = "Set object acl failed, status=" . $response->code .
        ", bucket_name=" . $bucket_name . ", object_name=" . $object_name .
        ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function doesObjectExist($bucket_name, $object_name) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(),
        $bucket_name . "/" . $object_name);
    $headers = $this->prepareRequestHeader($uri, Http::HEAD, Mime::JSON);

    $response = $this->invoke(Action::HeadObject, $uri, $headers, Http::HEAD,
        null, null);

    if ($response->code == self::HTTP_OK) {
      return true;
    } elseif ($response->code == self::HTTP_NOT_FOUND) {
      return false;
    } else {
      $message = "Check existence of object failed, status=" . $response->code
        . ", bucket_name=" . $bucket_name . ", object_name=" . $object_name
        . ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function deleteObject($bucket_name, $object_name) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(),
        $bucket_name . "/" . $object_name);
    $headers = $this->prepareRequestHeader($uri, Http::DELETE, NULL);

    $response = $this->invoke(Action::DeleteObject, $uri, $headers,
        Http::DELETE, null, null);

    if ($response->code != self::HTTP_OK) {
      $message = "Delete object failed, status=" . $response->code .
        ", bucket_name=" . $bucket_name . ", object_name=" . $object_name .
        ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function deleteObjects($bucket_name, $object_name_list) {
    $batch_deletion_size = $this->fds_config->getBatchDeleteSize();
    if (count($object_name_list) > $batch_deletion_size) {
      throw new GalaxyFDSClientException("length of \$object_name_list("
          . strval(count($object_name_list))
          . ") exceeds limit(" . strval($batch_deletion_size) . ")");
    }

    $uri = $this->formatUri($this->fds_config->getBaseUri(),
        $bucket_name, "deleteObjects=");

    $headers = $this->prepareRequestHeader($uri, Http::PUT, Mime::JSON);

    $response = $this->invoke(Action::DeleteObjects, $uri, $headers,
        Http::PUT, null, json_encode($object_name_list));

    if ($response->code != self::HTTP_OK) {
      $message = "Delete objects failed, status=" . $response->code .
          ", bucket_name=" . $bucket_name .
          ", object_name_list=" . serialize($object_name_list) .
          ", reason=" . $response;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }

    return json_decode($response->raw_body);
  }

  private function objectSummaries2ObjectNames($object_summaries) {
    $object_names = array();
    foreach ($object_summaries as $object_summary) {
      array_push($object_names, $object_summary->getObjectName());
    }
    return $object_names;
  }

  public function deleteObjectsByPrefix($bucket_name, $object_name_prefix) {
    $result_list = array();
    $old_delimiter = self::getDelimiter();
    $batch_deletion_size = $this->fds_config->getBatchDeleteSize();
    try {
      self::setDelimiter("");
      // list object_name list in bucket
      $object_listing = self::listObjects($bucket_name, $object_name_prefix);
      while ($object_listing !== NULL) {
        $object_name_list =self::objectSummaries2ObjectNames($object_listing->getObjectSummaries());
        $object_2_delete_array = array_chunk($object_name_list, $batch_deletion_size);

        foreach ($object_2_delete_array as $object_2_delete) {
          try {
            $tmp_result_list = self::deleteObjects($bucket_name, $object_2_delete);
            $result_list = array_merge($result_list, $tmp_result_list);
          } catch (\Exception $e) {
            usleep(500*1000);
            // retry with smaller chunk
            $small_object_name_array = array_chunk($object_2_delete, max($batch_deletion_size / 10, 10));
            foreach ($small_object_name_array as $object_list) {
              $tmp_result_list = self::deleteObjects($bucket_name, $object_list);
              $result_list = array_merge($result_list, $tmp_result_list);
            }
          }
        }
        // get next object_name list
        $object_listing = self::listNextBatchOfObjects($object_listing);
      }
    } finally {
      self::setDelimiter($old_delimiter);
    }

    return $result_list;
  }

  public function restoreObject($bucket_name, $object_name) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(),
        $bucket_name . "/" . $object_name, "restore");
    $headers = $this->prepareRequestHeader($uri, Http::PUT,
      self::APPLICATION_OCTET_STREAM);

    $response = $this->invoke(Action::RestoreObject, $uri, $headers, Http::PUT,
        null, null);

    if ($response->code != self::HTTP_OK) {
      $message = "Restore object failed, status=" . $response->code .
        ", bucket_name=" . $bucket_name .
        ", object_name=" . $object_name .
        ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function renameObject($bucket_name, $src_object_name,
                               $dst_object_name) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(),
        $bucket_name . "/" . $src_object_name,
      "renameTo=" . $dst_object_name);
    $headers = $this->prepareRequestHeader($uri, Http::PUT,
      self::APPLICATION_OCTET_STREAM);

    $response = $this->invoke(Action::RenameObject, $uri, $headers, Http::PUT,
        null, null);

    if ($response->code != self::HTTP_OK) {
      $message = "Rename object failed, status=" . $response->code .
        ", bucket_name=" . $bucket_name .
        ", src_object_name=" . $src_object_name .
        ", dst_object_name=" . $dst_object_name .
        ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function generatePresignedUri($bucket_name, $object_name, $expiration,
                                       $http_method = "GET", $content_type = null) {
    $base_uri = $this->fds_config->getDownloadBaseUri();
    if ($http_method === "PUT" or $http_method === "POST") {
      $base_uri = $this->fds_config->getUploadBaseUri();
    } else if($http_method === "DELETE") {
      $base_uri = $this->fds_config->getBaseUri();
    }

    $uri = $this->formatUri($base_uri,
      $bucket_name . "/" . $object_name,
      Common::GALAXY_ACCESS_KEY_ID . "=" . $this->credential->getGalaxyAccessId(),
      Common::EXPIRES . "=" . $expiration);
    $headers = NULL;
    if (is_string($content_type)) {
      $headers = array();
      $headers[common::CONTENT_TYPE] = $content_type;
    }
    $signature = Signer::signToBase64($http_method, $uri, $headers,
      $this->credential->getGalaxyAccessSecret(), self::SIGN_ALGORITHM);

    $uri = $this->formatUri($base_uri,
        $bucket_name . "/" . $object_name,
        Common::GALAXY_ACCESS_KEY_ID . "=" . $this->credential->getGalaxyAccessId(),
        Common::EXPIRES . "=" . $expiration,
        Common::SIGNATURE . "=" . $signature);
    return $uri;
  }

  public function generateDownloadObjectUri($bucket_name, $object_name) {
     return $this->formatUri($this->fds_config->getDownloadBaseUri(),
        $bucket_name . "/" . $object_name);
  }

  public function getDelimiter() {
    return $this->delimiter;
  }

  public function setDelimiter($delimiter) {
    $this->delimiter = $delimiter;
  }

  private function getCurrentGMTTime() {
    return gmdate(self::DATE_FORMAT, time());
  }

  private function prepareRequestHeader($uri, $http_method, $media_type,
                                        $metadata = null) {
    $headers = array();

    // 1. Format date
    $date = $this->getCurrentGMTTime();
    $headers[Common::DATE] = $date;

    // 2. Set content type
    if ($media_type != NULL && !empty($media_type)) {
      $headers[Common::CONTENT_TYPE] = $media_type;
    }

    if ($metadata != null) {
      foreach ($metadata->getRawMetadata() as $key => $value) {
        $headers[$key] = $value;
      }
    }

    // 3. Set authorization information
    $signature = Signer::signToBase64($http_method, $uri, $headers,
      $this->credential->getGalaxyAccessSecret(), self::SIGN_ALGORITHM);
    $auth_string = "Galaxy-V2 " . $this->credential->getGalaxyAccessId()
      . ":" . $signature;
    $headers[Common::AUTHORIZATION] = $auth_string;
    return $headers;
  }

  private function formatUri() {
    $args_num = func_num_args();
    if ($args_num < 1) {
      throw new GalaxyFDSClientException("Invalid parameters for formatUri()");
    }

    $count = 0;
    $uri = "";
    $args = func_get_args();
    foreach ($args as $arg) {
      if ($count == 0) {
        $uri .= $arg;
      } else if ($count == 1) {
        $arrArg = explode("/", $arg, 2);
        if (count($arrArg) == 2) {
          $arrArg[1] = str_replace('%2F', '/',rawurlencode($arrArg[1]));
          $arg = implode("/", $arrArg);
        }
        $uri .= $arg;
      } else {
        $arrArg = explode("=", $arg, 2);
        if (count($arrArg) == 2) {
          $arrArg[1] = rawurlencode($arrArg[1]);
          $arg = implode("=", $arrArg);
        }
        if ($count == 2) {
          $uri .= "?" . $arg;
        } else {
          $uri .= "&" . $arg;
        }
      }
      ++$count;
    }
    return $uri;
  }

  private function acpToAcl($acp) {
    if ($acp != NULL) {
        $acl = new AccessControlList();
        foreach ($acp->getAccessControlList() as $key => $value) {
          $grantee_id = $value->grantee->id;
          $permission = $value->permission;
          $grant = new Grant(new Grantee($grantee_id), $permission);
          $acl->addGrant($grant);
        }
        return $acl;
    }
    return NULL;
  }

  private function aclToAcp($acl) {
    if ($acl != NULL) {
      $acp = new AccessControlPolicy();
      $owner = new Owner();
      $owner->setId($this->credential->getGalaxyAccessId());
      $acp->setOwner($owner);

      $access_control_list = $acl->getGrantList();
      $acp->setAccessControlList($access_control_list);
      return $acp;
    }
    return NULL;
  }

  private function parseObjectMetadataFromHeaders($headers) {
    $metadata = new FDSObjectMetadata();
    foreach (FDSObjectMetadata::$PRE_DEFINED_METADATA as $value) {
      if (array_key_exists($value, $headers)) {
        $metadata->addHeader($value, $headers[$value]);
      }
    }

    foreach ($headers as $key => $value) {
      if (Signer::stringStartsWith($key,
        FDSObjectMetadata::USER_DEFINED_METADATA_PREFIX)) {
        $metadata->addUserMetadata($key, $value);
      }
    }
    return $metadata;
  }

  public function getBucketQuota($bucket_name) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(), $bucket_name,
        SubResource::QUOTA);
    $headers = $this->prepareRequestHeader($uri, Http::GET, Mime::JSON);

    $response = $this->invoke(Action::GetBucketQuota, $uri, $headers, Http::GET,
        Mime::JSON, null);

    if ($response->code == self::HTTP_OK) {
      $policy = QuotaPolicy::fromJson($response->body);
      return $policy;
    } else {
      $message = "Get bucket quota failed, status=" . $response->code .
          ", bucket_name=" . $bucket_name .
          ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function setBucketQuota($bucket_name, $quota) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(), $bucket_name,
        SubResource::QUOTA);
    $headers = $this->prepareRequestHeader($uri, Http::PUT, Mime::JSON);

    $response = $this->invoke(Action::PutBucketQuota, $uri, $headers, Http::PUT,
        null, json_encode($quota));

    if ($response->code != self::HTTP_OK) {
      $message = "Set bucket quota failed, status=" . $response->code .
          ", bucket_name=" . $bucket_name .
          ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function putClientMetrics($clientMetrics) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(), "",
        SubResource::CLIENT_METRICS);
    $headers = $this->prepareRequestHeader($uri, Http::PUT, Mime::JSON);

    $response = Request::put($uri, json_encode($clientMetrics))
        ->addHeaders($headers)
        ->retry($this->fds_config->getRetry())
        ->timeout($this->fds_config->getConnectionTimeoutSecs())
        ->send();

    if ($response->code != self::HTTP_OK) {
      $message = "Put client metrics fialed, status=" . $response->code .
          ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function prefetchObject($bucket_name, $object_name) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(),
        $bucket_name . "/" . $object_name, SubResource::PREFETCH);
    $headers = $this->prepareRequestHeader($uri, Http::PUT, Mime::JSON);

    $response = Request::put($uri, "")
        ->addHeaders($headers)
        ->retry($this->fds_config->getRetry())
        ->timeout($this->fds_config->getConnectionTimeoutSecs())
        ->send();

    if ($response->code != self::HTTP_OK) {
      $message = "Prefetch object failed, status=" . $response->code .
          ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function refreshObject($bucket_name, $object_name) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(),
        $bucket_name . "/" . $object_name, SubResource::REFRESH);
    $headers = $this->prepareRequestHeader($uri, Http::PUT, Mime::JSON);

    $response = Request::put($uri, "")
        ->addHeaders($headers)
        ->retry($this->fds_config->getRetry())
        ->timeout($this->fds_config->getConnectionTimeoutSecs())
        ->send();

    if ($response->code != self::HTTP_OK) {
      $message = "Refresh object failed, status=" . $response->code .
          ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function setPublic($bucket_name, $object_name) {
    $acl = new AccessControlList();
    $grant = new Grant(new Grantee(UserGroups::ALL_USERS), Permission::READ);
    $grant->setType(GrantType::GROUP);
    $acl->addGrant($grant);
    $this->setObjectAcl($bucket_name, $object_name, $acl);
  }

  public function initMultipartUpload($bucket_name, $object_name) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(),
        $bucket_name . "/" . $object_name, SubResource::UPLOADS);
    $headers = $this->prepareRequestHeader($uri, Http::PUT, Mime::JSON);

    $response = $this->invoke(Action::InitMultipartUpload, $uri, $headers, Http::PUT,
        null, null);

    if ($response->code == self::HTTP_OK) {
      $result = InitMultipartUploadResult::fromJson($response->body);
      return $result;
    } else {
      $message = "Init multipart upload failed, status=" . $response->code .
          ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function initMultipartUploadCopy($bucket_name, $object_name) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(),
      $bucket_name . "/" . $object_name, SubResource::UPLOADS);
    $metadata = new FDSObjectMetadata();
    $metadata->addHeader(Common::MULITPART_UPLOAD_MODE, 'MULTI_BLOB');
    $headers = $this->prepareRequestHeader($uri, Http::PUT, Mime::JSON, $metadata);

    $response = $this->invoke(Action::InitMultipartUpload, $uri, $headers, Http::PUT,
      null, null);

    if ($response->code == self::HTTP_OK) {
      $result = InitMultipartUploadResult::fromJson($response->body);
      return $result;
    } else {
      $message = "Init multipart upload failed, status=" . $response->code .
        ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function uploadPart($bucket_name, $object_name, $upload_id,
      $part_number, $content) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(),
      $bucket_name . "/" . $object_name, "uploadId=" . $upload_id, "partNumber=" . $part_number);
    $headers = $this->prepareRequestHeader($uri, Http::PUT,
        self::APPLICATION_OCTET_STREAM);

    $response = $this->invoke(Action::UploadPart, $uri, $headers, Http::PUT,
        null, $content);

    if ($response->code == self::HTTP_OK) {
      $result = UploadPartResult::fromJson($response->body);
      return $result;
    } else {
      $message = "Upload part failed, status=" . $response->code .
          ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function uploadPartCopy($bucket_name, $object_name, $upload_id,
                                 $part_number, $source_bucket_name, $source_object_name,
                                 $start_byte = null, $end_byte = null) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(),
      $bucket_name . "/" . $object_name, "uploadId=" . $upload_id, "partNumber=" . $part_number);

    $metadata = new FDSObjectMetadata();
    $metadata->addHeader(Common::COPY_SOURCE, '/' . $source_bucket_name . '/' . rawurlencode($source_object_name));
    if ($start_byte !== null && $end_byte !== null) {
      $metadata->addHeader(Common::COPY_SOURCE_RANGE, 'bytes=' . $start_byte . '-' . $end_byte);
    }

    $headers = $this->prepareRequestHeader($uri, Http::PUT,
      self::APPLICATION_OCTET_STREAM, $metadata);

    $response = $this->invoke(Action::UploadPart, $uri, $headers, Http::PUT,
      null, null);

    if ($response->code == self::HTTP_OK) {
      $result = UploadPartResult::fromJson($response->body);
      return $result;
    } else {
      $message = "Upload part failed, status=" . $response->code .
        ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function completeMultipartUpload($bucket_name, $object_name,
      $upload_id, $metadata, $upload_part_result_list) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(),
      $bucket_name . "/" . $object_name, "uploadId=" . $upload_id);
    $headers = $this->prepareRequestHeader($uri, Http::PUT, self::APPLICATION_OCTET_STREAM, $metadata);

    $response = $this->invoke(Action::CompleteMultipartUpload, $uri, $headers,
        Http::PUT, null, json_encode($upload_part_result_list));

    if ($response->code == self::HTTP_OK) {
      $result = PutObjectResult::fromJson($response->body);
      return $result;
    } else {
      $message = "Complete multipart failed, status=" . $response->code .
          ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function abortMultipartUpload($bucket_name, $object_name, $upload_id) {
    $uri = $this->formatUri($this->fds_config->getBaseUri(),
      $bucket_name . "/" . $object_name, "uploadId=" . $upload_id);
    $headers = $this->prepareRequestHeader($uri, Http::DELETE, Mime::JSON);

    $response = $this->invoke(Action::AbortMultipartUpload, $uri, $headers,
        Http::DELETE, null, null);

    if ($response->code != self::HTTP_OK) {
      $message = "Abort multipart failed, status=" . $response->code .
          ", reason=" . $response->raw_body;
      $this->printResponse($response);
      throw new GalaxyFDSClientException($message);
    }
  }

  public function printResponse($response) {
    if ($this->fds_config->isDebugEnabled()) {
      print_r($response);
    }
  }

  private function invoke($action, $uri, $headers, $method, $expects, $payload) {
    if ($this->fds_config->isMetricsEnabled()) {
      $request_metrics = new RequestMetrics($action);
      $request_metrics->startEvent(RequestMetrics::EXECUTION_TIME);
    }

    $request = null;
    switch($method) {
      case Http::GET:
        $request = Request::get($uri);
        break;
      case Http::PUT:
        $request = Request::put($uri, $payload);
        break;
      case Http::POST:
        $request = Request::post($uri, $payload);
        break;
      case Http::DELETE:
        $request = Request::delete($uri);
        break;
      case Http::HEAD:
        $request = Request::head($uri);
    }
    if ($expects != null) {
      $request = $request->expects($expects);
    }

    $response = $request->addHeaders($headers)
        ->retry($this->fds_config->getRetry())
        ->timeout($this->fds_config->getConnectionTimeoutSecs())
        ->send();

    if ($this->fds_config->isMetricsEnabled()) {
      $request_metrics->endEvent(RequestMetrics::EXECUTION_TIME);
      $this->metrics_collector->collect($request_metrics);
    }

    return $response;
  }
}
