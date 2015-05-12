<?php
/**
 * Created by IntelliJ IDEA.
 * User: wuzesheng
 * Date: 3/24/14
 * Time: 7:24 PM
 */

namespace FDS\auth\signature;

use FDS\auth\Common;
use FDS\GalaxyFDSClientException;
use FDS\model\SubResource;

class Signer {

  static $s_sub_resources;

  /**
   * Sign the specified http request.
   *
   * @param string $http_method  The http request method
   * @param string $uri The uri string
   * @param array $http_headers The http request headers
   * @param string $access_secret The user's app secret
   * @param string $algorithm   The sign algorithm
   * @return string The signed result
   * @throws GalaxyFDSClientException
   */
  public static function sign($http_method, $uri, $http_headers,
                              $access_secret, $algorithm) {
    $supported_algo = hash_algos();
    if (!array_search($algorithm, $supported_algo)) {
      throw new GalaxyFDSClientException(
        "Unsupported Hmac algorithm: " . $algorithm);
    }

    $string_to_sign = self::constructStringToSign($http_method,
      $uri, $http_headers);
    $result = hash_hmac($algorithm, $string_to_sign, $access_secret, true);
    return $result;
  }

  /**
   * A handy version of sign(), generates base64 encoded sign result.
   */
  public static function signToBase64($http_method, $uri, $http_headers,
                                      $access_secret, $algorithm) {
    $sign_result = self::sign($http_method, $uri, $http_headers,
      $access_secret, $algorithm);
    $encoded_result = base64_encode($sign_result);
    return $encoded_result;
  }

  static function constructStringToSign($http_method, $uri,
                                               $http_headers) {
    $result = "";
    $result .= $http_method . "\n";
    $result .= self::getHeaderValue($http_headers, Common::CONTENT_MD5) . "\n";
    $result .= self::getHeaderValue($http_headers, Common::CONTENT_TYPE) . "\n";

    if (($expires = self::getExpires($uri)) > 0) {
      $result .= $expires . "\n";
    } else {
      $xiaomi_date = self::getHeaderValue($http_headers,
        Common::XIAOMI_HEADER_DATE);
      $date = "";
      if (empty($xiaomi_date)) {
        $date = self::getHeaderValue($http_headers, Common::DATE);
      }
      $result .= $date . "\n";
    }

    $result .= self::canonicalizeXiaomiHeaders($http_headers);
    $result .= self::canonicalizeResource($uri);
    return $result;
  }

  static function canonicalizeXiaomiHeaders($headers) {
    if ($headers == NULL || empty($headers)) {
      return "";
    }

    // 1. Sort the header and merge the values
    $canonicalizedHeaders = array();
    foreach ($headers as $key => $value) {
      if (self::stringStartsWith($key, Common::XIAOMI_HEADER_PREFIX)) {
        if (is_array($value)) {
          $canonicalizedHeaders[$key] = join(",", $value);
        } else {
          $canonicalizedHeaders[$key] = $value;
        }
      }
    }
    ksort($canonicalizedHeaders);

    // 2. TODO(wuzesheng) Unfold multiple lines long header

    // 3. Generate the canonicalized result
    $result = "";
    foreach ($canonicalizedHeaders as $key => $value) {
      $result .= $key . ":" . $value . "\n";
    }
    return $result;
  }

  static function canonicalizeResource($uri) {
    $result = "";
    $result .= parse_url($uri, PHP_URL_PATH);

    // 1. Parse and sort subresource
    $sorted_params = array();
    $query = parse_url($uri, PHP_URL_QUERY);
    $params = array();
    parse_str($query, $params);
    foreach ($params as $key => $value) {
      if (self::$s_sub_resources != null) {
        if (array_search($key, self::$s_sub_resources) !== false) {
          $sorted_params[$key] = $value;
        }
      }
    }
    ksort($sorted_params);

    // 2. Generate the canonicalized result
    if (!empty($sorted_params)) {
      $result .= "?";
      $first = true;
      foreach ($sorted_params as $key => $value) {
        if ($first) {
          $first = false;
          $result .= $key;
        } else {
          $result .= "&" . $key;
        }

        if (!empty($value)) {
          $result .= "=" . $value;
        }
      }
    }
    return $result;
  }

  static function getHeaderValue($headers, $name) {
    if ($headers != NULL && array_key_exists($name, $headers)) {
      if (is_array($headers[$name])) {
        return $headers[$name][0];
      } else {
        return $headers[$name];
      }
    }
    return "";
  }

  static function getExpires($uri) {
    $query = parse_url($uri, PHP_URL_QUERY);
    if ($query != NULL and !empty($query)) {
      $params = array();
      parse_str($query, $params);
      if (array_key_exists(Common::EXPIRES, $params)) {
        return intval($params[Common::EXPIRES]);
      }
    }
    return 0;
  }

  static function stringStartsWith($haystack, $needle) {
    return $needle == "" || strpos($haystack, $needle) === 0;
  }
}

Signer::$s_sub_resources = SubResource::getAllSubresources();

