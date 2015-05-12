<?php
/**
 * Created by IntelliJ IDEA.
 * User: wuzesheng
 * Date: 3/23/14
 * Time: 11:57 PM
 */

namespace FDS;

use FDS\model\AccessControlList;
use FDS\model\FDSObject;
use FDS\model\FDSObjectListing;
use FDS\model\FDSObjectMetadata;
use FDS\model\PutObjectResult;

interface GalaxyFDS {

  /**
   * Returns a list of all galaxy fds buckets that the authenticated sender
   * of the request owns.
   *
   * @return mixed A list of all galaxy fds buckets owned by the authenticated
   *               sender of the request
   * @throws GalaxyFDSClientException
   */
  public function listBuckets();

  /**
   * Creates a new fds bucket with the specified name.
   *
   * @param string $bucket_name The name of the bucket to create
   * @throws GalaxyFDSClientException
   */
  public function createBucket($bucket_name);

  /**
   * Deletes a fds bucket with the specified name.
   *
   * @param string $bucket_name The name of the bucket to delete
   * @throws GalaxyFDSClientException
   */
  public function deleteBucket($bucket_name);

  /**
   * Checks if the specified bucket exists.
   *
   * @param string $bucket_name The name of the bucket to check
   * @return bool The value true if the specified bucket exists, otherwise false
   * @throws GalaxyFDSClientException
   */
  public function doesBucketExist($bucket_name);

  /**
   * Gets the AccessControlList(ACL) of the specified fds bucket.
   *
   * @param string $bucket_name The name of the bucket whose ACL is being retrieved
   * @return AccessControlList The AccessControlList for the specified bucket
   * @throws GalaxyFDSClientException
   */
  public function getBucketAcl($bucket_name);

  /**
   * Sets the AccessControlList(ACL) of the specified fds bucket.
   *
   * @param string $bucket_name The name of the bucket whoses acl is being set
   * @param AccessControlList $acl The new AccessControlList for the specified bucket
   * @throws GalaxyFDSClientException
   */
  public function setBucketAcl($bucket_name, $acl);

  /**
   * Gets the QuotaPolicy(QUOTA) of the specified fds bucket.
   *
   * @param string $bucket_name name of the bucket
   * @return QuotaPolicy quota for the specified bucket
   * @throws GalaxyFDSClientException
   */
  public function getBucketQuota($bucket_name);

  /**
   * Sets the QuotaPolicy(QUOTA) of the specified fds bucket.
   *
   * @param string $bucket_name name of the bucket
   * @param QuotaPolicy $quota new QuotaPolicy for the specified bucket
   * @throws GalaxyFDSClientException
   */
  public function setBucketQuota($bucket_name, $quota);

  /**
   * Returns a list of summary information about the objects in the specified
   * fds bucket.
   *
   * Because buckets can contain a virtually unlimited number of keys, the
   * complete results of a list query can be extremely large. To manage large
   * result sets, galaxy fds uses pagination to split them into multiple
   * responses. Always check the {@link #FDSObjectListing.isTruncated()} method
   * to see if the returned listing is complete or if additional calls are
   * needed to get more results. Alternatively, use the
   * {@link #listNextBatchOfObjects(FDSObjectListing)} method as an easy way to
   * get the next page of object listings.
   *
   * @param string $bucket_name The name of the bucket to list
   * @param String $prefix An optional parameter restricting the response to keys
   *                       beginning with the specified prefix.
   * @return FDSObjectListing A listing of the objects in the specified bucket
   * @throws GalaxyFDSClientException
   */
  public function listObjects($bucket_name, $prefix = "");

  /**
   * Provides an easy way to continue a truncated object listing and retrieve
   * the next page of results.
   *
   * @param FDSObjectListing $previous_object_listing The previous truncated
   *                                                  ObjectListing
   * @return FDSObjectListing The next set of ObjectListing results, beginning
   *                          immediately after the last result in the specified
   *                          previous ObjectListing.
   * @throws GalaxyFDSClientException
   */
  public function listNextBatchOfObjects($previous_object_listing);

  /**
   * Uploads the specified file to galaxy fds with the specified object name
   * under the specified bucket.
   *
   * @param string $bucket_name The name of the bucket to put the object
   * @param string $object_name The name of the object to put
   * @param string $content The data content of the object to put
   * @param FDSObjectMetadata $metadata Additional metadata instructing fds how
   * @return PutObjectResult A {@link PutObjectResult} containing the information
   *                         returned by galaxy fds for the newly created object
   * @throws GalaxyFDSClientException
   */
  public function putObject($bucket_name, $object_name, $content,
                            $metadata = NULL);

  /**
   * Uploads the specified file to a galaxy fds bucket, an unique object name
   * will be returned after successfully uploading.
   *
   * @param string $bucket_name The name of the bucket to put the object
   * @param string $content The data content of the object to put
   * @param FDSObjectMetadata $metadata Additional metadata instructing fds how
   * @return PutObjectResult A {@link PutObjectResult} containing the information
   *                         returned by galaxy fds for the newly created object
   * @throws GalaxyFDSClientException
   */
  public function postObject($bucket_name, $content, $metadata = NULL);

  /**
   * Gets the object stored in galaxy fds with the specified name under the
   * specified bucket.
   *
   * @param string $bucket_name The name of the bucket where the object stores
   * @param string $object_name The name of the object to get
   * @return FDSObject The object stored in galaxy fds under the specified bucket
   * @throws GalaxyFDSClientException
   */
  public function getObject($bucket_name, $object_name);

  /**
   * Gets the meta information of object with the specified name under the
   * specified bucket.
   *
   * @param string $bucket_name The name of the bucket where the object stores
   * @param string $object_name The name of the object to get the meta information
   * @return FDSObjectMetadata The meta information of the object with the
   *                           specified name under the specified bucket
   * @throws GalaxyFDSClientException
   */
  public function getObjectMetadata($bucket_name, $object_name);

  /**
   * Gets the AccessControlList(ACL) of the specified fds object.
   *
   * @param string $bucket_name The name of the bucket where the object stores
   * @param string $object_name The name of the object to get acl
   * @return AccessControlList The AccessControlList of the specified object
   * @throws GalaxyFDSClientException
   */
  public function getObjectAcl($bucket_name, $object_name);

  /**
   * Sets the AccessControlList(ACL) of the specified fds object.
   *
   * @param string $bucket_name The name of the bucket where the object stores
   * @param string $object_name The name of the object to set acl
   * @param AccessControlList $acl The ACL to set for the specified object
   * @throws GalaxyFDSClientException
   */
  public function setObjectAcl($bucket_name, $object_name, $acl);

  /**
   * Checks if the object with the specified name under the specified bucket
   * exists.
   *
   * @param string $bucket_name The name of the bucket where the object stores
   * @param string $object_name The name of the object to check
   * @return bool The value true if the specified object exists, otherwise false
   * @throws GalaxyFDSClientException
   */
  public function doesObjectExist($bucket_name, $object_name);

  /**
   * Deletes the object with the specified name under the specified bucket.
   *
   * @param $bucket_name The name of the bucket where the object stores
   * @param $object_name The name of the object to delete
   * @throws GalaxyFDSClientException
   */
  public function deleteObject($bucket_name, $object_name);

  /**
   * Renames the object with the specified name under the specified bucket.
   *
   * @param string $bucket_name The name of the bucket where the object stores
   * @param string $src_object_name The name of the source object
   * @param string $dst_object_name The name of the destination object
   * @throws GalaxyFDSClientException
   */
  public function renameObject($bucket_name, $src_object_name,
                               $dst_object_name);

  /**
   * Prefetch the object to CDN
   *
   * @param string $bucket_name The name of the bucket where the object stores
   * @param string $object_name The name of the object
   * @throws GalaxyFDSClientException
   */
  public function prefetchObject($bucket_name, $object_name);

  /**
   * Refresh the cache of the object in CDN
   *
   * @param string $bucket_name The name of the bucket where the object stores
   * @param string $object_name The name of the object
   * @throws GalaxyFDSClientException
   */
  public function refreshObject($bucket_name, $object_name);

  /**
   * Set the object public to all users
   *
   * @param string $bucket_name The name of the bucket where the object stores
   * @param string $object_name The name of the object
   * @param bool $disable_prefetch Indicates whether to prefetch to object to CDN
   * @return mixed
   */
  public function setPublic($bucket_name, $object_name, $disable_prefetch = false);

  /**
   * Returns a pre-signed URI for accessing Galaxy FDS resource.
   *
   * @param string $bucket_name The name of the bucket containing the desired object
   * @param string $object_name The name of the desired object
   * @param long $expiration  The time at which the returned pre-signed URL will expire
   * @param string $http_method The HTTP method verb to use for this URL
   * @return string A pre-signed URL which expires at the specified time, and can
   *                be used to allow anyone to access the specified object from
   *                galaxy fds, without exposing the owner's Galaxy secret access
   *                key.
   * @throws GalaxyFDSClientException
   */
  public function generatePresignedUri($bucket_name, $object_name, $expiration,
                                       $http_method = "GET");

  /**
   * @param $bucket_name The name of the bucket containing the desired object
   * @param $object_name The name of the desired object
   * @returna A URI for downloading the desired object
   * @throws GalaxyFDSClientException
   */
  public function generateDownloadObjectUri($bucket_name, $object_name);
}