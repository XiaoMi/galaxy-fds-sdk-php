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
use FDS\model\Owner;
use FDS\model\Permission;
use FDS\model\PutObjectResult;
use FDS\model\SubResource;
use FDS\model\UserGroups;
use Httpful\Http;
use Httpful\Mime;
use Httpful\Request;

class GalaxyFDSClient implements GalaxyFDS {

  const DATE_FORMAT = 'D, d M Y H:i:s \G\M\T';
  const SIGN_ALGORITHM = "sha1";
  const HTTP_OK = 200;
  const HTTP_NOT_FOUND = 404;
  const APPLICATION_OCTET_STREAM = "application/octet-stream";

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

  public function listNextBatchOfObjects($previous_object_listing) {
    if (!$previous_object_listing->isTruncated()) {
      // TODO(wuzesheng) Log a warning message
      return NULL;
    }

    $bucket_name = $previous_object_listing->getBucketName();
    $prefix = $previous_object_listing->getPrefix();
    $marker = $previous_object_listing->getNextMarker();
    $uri = $this->formatUri($this->fds_config->getBaseUri(), $bucket_name,
      "prefix=" . $prefix, "marker=" . $marker);
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

  public function getObject($bucket_name, $object_name) {
    $uri = $this->formatUri($this->fds_config->getDownloadBaseUri(),
        $bucket_name . "/" . $object_name);
    $headers = $this->prepareRequestHeader($uri, Http::GET, NULL);

    $response = $this->invoke(Action::GetObject, $uri, $headers, Http::GET,
        self::APPLICATION_OCTET_STREAM, null);

    if ($response->code == self::HTTP_OK) {
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
                                       $http_method = "GET") {
    $uri = $this->formatUri($this->fds_config->getDownloadBaseUri(),
      $bucket_name . "/" . $object_name,
      Common::GALAXY_ACCESS_KEY_ID . "=" . $this->credential->getGalaxyAccessId(),
      Common::EXPIRES . "=" . $expiration);
    $signature = Signer::signToBase64($http_method, $uri, NULL,
      $this->credential->getGalaxyAccessSecret(), self::SIGN_ALGORITHM);
    $uri = $this->formatUri($this->fds_config->getDownloadBaseUri(),
      urlencode($bucket_name) . "/" . urlencode($object_name),
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
    $sign_uri = substr($uri, strpos($uri, "/", strpos($uri, ":") +3));
    $signature = Signer::signToBase64($http_method, $sign_uri, $headers,
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
      if ($count == 0 || $count == 1) {
        $uri .= $arg;
      } else if ($count == 2) {
        $uri .= "?" . $arg;
      } else {
        $uri .= "&" . $arg;
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

  public function setPublic($bucket_name, $object_name, $disable_prefetch = false) {
    $acl = new AccessControlList();
    $grant = new Grant(new Grantee(UserGroups::ALL_USERS), Permission::READ);
    $grant->setType(GrantType::GROUP);
    $acl->addGrant($grant);
    $this->setObjectAcl($bucket_name, $object_name, $acl);

    if (!$disable_prefetch) {
      $this->prefetchObject($bucket_name, $object_name);
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
