<?php
/**
 * Created by IntelliJ IDEA.
 * User: wuzesheng
 * Date: 3/24/14
 * Time: 7:32 PM
 */

namespace FDS\model;

abstract class SubResource {
  // Following are amazon S3 supported subresources:
  // acl, lifecycle, location, logging, notification, partNumber,
  // policy, requestPayment, torrent, uploadId, uploads, versionId,
  // versioning, versions and website

  // Currently, we only support a subset of the above subresources:
  const ACL = "acl";
  const QUOTA = "quota";
  const UPLOADS = "uploads";
  const PART_NUMBER = "partNumber";
  const UPLOAD_ID = "uploadId";
  const STORAGE_ACCESS_TOKEN = "storageAccessToken";
  const METADATA = "metadata";
  const CLIENT_METRICS = "clientMetrics";
  const PREFETCH = "prefetch";
  const REFRESH = "refresh";

  public static function getAllSubresources() {
    return array(SubResource::ACL,
      SubResource::QUOTA,
      SubResource::UPLOADS,
      SubResource::PART_NUMBER,
      SubResource::UPLOAD_ID,
      SubResource::STORAGE_ACCESS_TOKEN,
      SubResource::METADATA
    );
  }
}