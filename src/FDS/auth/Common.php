<?php
/**
 * Created by IntelliJ IDEA.
 * User: wuzesheng
 * Date: 3/24/14
 * Time: 7:50 PM
 */

namespace FDS\auth;

abstract class Common {

  const XIAOMI_HEADER_PREFIX = "x-xiaomi-";
  const XIAOMI_HEADER_DATE = "x-xiaomi-date";

  // Required query parameters for pre-signed uri
  const GALAXY_ACCESS_KEY_ID = "GalaxyAccessKeyId";
  const SIGNATURE = "Signature";
  const EXPIRES = "Expires";

  // Http headers used for authentication
  const AUTHORIZATION = "authorization";
  const CONTENT_MD5 = "content-md5";
  const CONTENT_TYPE = "content-type";
  const DATE = "date";

  const REQUEST_TIME_LIMIT = 900000; // 15min

  // Pre-defined object metadata headers
  const CACHE_CONTROL = "cache-control";
  const CONTENT_ENCODING = "content-encoding";
  const CONTENT_LENGTH = "content-length";
  const LAST_MODIFIED = "last-modified";

  const DEFAULT_FDS_SERVICE_BASE_URI = "http://files.fds.api.xiaomi.com/";
  const DEFAULT_CDN_SERVICE_URI = "http://cdn.fds.api.xiaomi.com/";
}