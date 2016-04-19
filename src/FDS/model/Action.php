<?php
/**
 * Created by IntelliJ IDEA.
 * User: huang
 * Date: 5/21/14
 * Time: 2:25 PM
 */

namespace FDS\model;

final class Action {
  const GetStorageToken = "GetStorageToken";
  const ListBuckets = "ListBuckets";
  const PutBucket = "PutBucket";
  const HeadBucket = "HeadBucket";
  const DeleteBucket = "DeleteBucket";
  const ListObjects = "ListObjects";
  const PutObject = "PutObject";
  const PostObject = "PostObject";
  const HeadObject = "HeadObject";
  const DeleteObject = "DeleteObject";
  const DeleteObjects = "DeleteObjects";
  const RestoreObject = "RestoreObject";
  const GetObject = "GetObject";
  const GetBucketACL = "GetBucketACL";
  const PutBucketACL = "PutBucketACL";
  const GetObjectACL = "GetObjectACL";
  const PutObjectACL = "PutObjectACL";
  const GetBucketQuota = "GetBucketQuota";
  const PutBucketQuota = "PutBucketQuota";
  const RenameObject = "RenameObject";
  const GetMetrics = "GetMetrics";
  const GetObjectMetadata = "GetObjectMetadata";
  const InitMultipartUpload = "InitMultipartUpload";
  const UploadPart = "UploadPart";
  const CompleteMultipartUpload = "CompleteMultipartUpload";
  const AbortMultipartUpload = "AbortMultipartUpload";
  const Unknown = "Unknown";
}
