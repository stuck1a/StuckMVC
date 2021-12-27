<?php
/*
 *  This file is part of StuckMVC <https://stuck1a.de/coding/stuckmvc>,
 *  Copyright (c) 2021.
 *  The StuckMVC framework is free software: you can redistribute it and/or modify it
 *  under the terms of the GNU General Public License version 3 as published by
 *  the Free Software Foundation.
 *  
 *  It is distributed in the hope that it will be useful, but without any warranty;
 *  without even the implied warranty of merchantability of fitness for a
 *  particular purpose. See the GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License along with
 *  StuckMVC. If not, see <https://www.gnu.org/licenses/>. 
 *
 *  FOR A SOMEWHAT FREER WORLD.
 */

namespace Stuck1A\StuckMVC\Core;

use DateInterval;
use DateTime;
use Traversable;
use Stuck1A\StuckMVC\Core\Exception\InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;


/**
 * Manager und Accessor for the PSR-16 compliant cache system of the framework<br><br>
 *
 * @package StuckMVC
 */
class CacheAdapter extends Singleton implements CacheInterface {
  /**
   * The name of the file which stores the keys of existing cache items.
   */
  private const KEYFILE = '.cache';
  
  /**
   * Meta to cache items. Keys are identifiers and its values are TTL values.
   * @var int[]
   */
  private $items = [];
  
  /**
   * Location of the top-level directory of this cache system
   * @var string
   */
  private $dir;
  
  /**
   * Prefetched realpath to the caches persistent key storage
   * @var string
   */
  private $keyfile;
  
  
  /**
   * Initializes the adapter for the caching system, by restoring any cached item key.
   * 
   * @param string $dir Directory the cache system will use as file cache location.
   */
  public static function init($dir) {
    $self = self::getInstance();
    $dir = realpath($dir);
    $self->dir = $dir . DIRECTORY_SEPARATOR;
    // Search for key file and restore used item keys from it
    if ( is_file($self->keyfile = $self->dir . $self::KEYFILE) ) {
      $self->items = unserialize(FileSystemAdapter::readFile($self->keyfile));
    }
  }
  
  
  /**
   * Ensures to store any existing item keys on shutdown.
   */
  public function __destruct() {
    @file_put_contents($this->dir . self::KEYFILE, serialize($this->items));
  }
  
  
  /**
   * Fetches a value from the cache.
   *
   * @param string  $key      The unique key of this item in the cache.
   * @param mixed   $default  Default value to return if the key does not exist.
   *
   * @return mixed            The value of the item from the cache, or $default in case of cache miss.
   *
   * @throws InvalidArgumentException  If $key is no string value.
   */
  public function get($key, $default = null) {
    if ( is_string($key) ) {
      if ( array_key_exists($key, $this->items) ) {
        if ( $this->items < (new DateTime())->getTimestamp() ) {
          return unserialize(file_get_contents($this->dir . $key));
        }
        /* expired */
        unlink($this->dir . $key);
        unset($this->items[$key]);
      }
      return $default;
    }
    throw new InvalidArgumentException();
  }
  
  
  /**
   * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
   *
   * @param string                $key    The key of the item to store.
   * @param mixed                 $value  The value of the item to store. Must be serializable.
   * @param null|int|DateInterval $ttl    [optional]<br>
   *                                      The TTL value of this item. If not set or null, the item is stored unlimited.
   *
   * @return bool True on success and false on failure.
   *
   * @throws InvalidArgumentException If $key is no string value.
   */
  public function set($key, $value, $ttl = null) {
    if ( is_string($key) ) {
      // precalculate expiration timestamp
      if ( $ttl instanceof DateInterval ) {
        $ttl = (new DateTime('now'))->add($ttl)->getTimestamp();
      }
      elseif ( is_int($ttl) ) {
        if ( $ttl < 1 ) {
          return false;
        }
        $ttl = (new DateTime('now'))->add(new DateInterval("PT{$ttl}S"))->getTimestamp();
      }
      // write to cache
      if ( strlen($key < 65 && Base::isAlphanumeric($key, ['_', '.'])) ) {
        file_put_contents($this->dir . $key, serialize($value));
        $this->items[$key] = $ttl;
        return true;
      }
      return false;
    }
    throw new InvalidArgumentException();
  }
  
  
  /**
   * Delete an item from the cache by its unique key.
   *
   * @param string $key The unique cache key of the item to delete.
   *
   * @return bool True if the item was successfully removed. False if there was an error.
   *
   * @throws InvalidArgumentException If $key is no string value.
   */
  public function delete($key) {
    if ( is_string($key) ) {
      if ( unlink($this->dir . $key) ) {
        return false;
      }
      return true;
    }
    throw new InvalidArgumentException();
  }
  
  
  /**
   * Wipes clean the entire cache's keys and also the data linked to them.
   *
   * @return bool True on success and false on failure.
   */
  public function clear() {
    foreach ( $this->items as $key => $ttl ) {
      if ( unlink($this->dir . $key) ) {
        return false;
      }
      unset($this->items[$key]);
    }
    $this->items = [];
    return true;
  }
  
  
  /**
   * Obtains multiple cache items by their unique keys.
   *
   * @param iterable $keys    A list of keys that can obtained in a single operation.
   * @param mixed    $default Default value to return for keys that do not exist.
   *
   * @return iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
   *
   * @throws InvalidArgumentException If $key is neither an array nor a Traversable,
   *                                   or if any of the $keys is no string value.
   */
  public function getMultiple($keys, $default = null) {
    if ( is_array($keys) || $keys instanceof Traversable ) {
      $result = [];
      foreach ( $keys as $key ) {
        $result[$key] = $this->get($key);
      }
      return $result;
    }
    throw new InvalidArgumentException();
  }
  
  
  /**
   * Persists a set of key => value pairs in the cache, with an optional TTL.
   *
   * @param iterable              $values  A list of key => value pairs for a multiple-set operation.
   * @param null|int|DateInterval $ttl     [optional]<br>
   *                                       The TTL value of the item. If no value is set, item will be cached unlimited.
   *
   * @return bool                          True on success and false on failure.
   *
   * @throws InvalidArgumentException     If $key is neither an array nor a Traversable,
   *                                       or if any of the $values are invalid.
   */
  public function setMultiple($values, $ttl = null) {
    if ( is_array($values) || $values instanceof Traversable ) {
      foreach ( $values as $key => $value ) {
        if ( !$this->set($key, $value, $ttl) ) {
          return false;
        }
      }
      return true;
    }
    throw new InvalidArgumentException();
  }
  
  
  /**
   * Deletes multiple cache items in a single operation.
   *
   * @param iterable $keys A list of string-based keys to be deleted.
   *
   * @return bool True if the items were successfully removed. False if there was an error.
   *
   * @throws InvalidArgumentException If $key is neither an array nor a Traversable,
   *                                   or if any of the $keys is no string value.
   */
  public function deleteMultiple($keys) {
    if ( is_array($keys) || $keys instanceof Traversable) {
      foreach ( $keys as $key ) {
        if ( unlink($this->dir . $key) ) {
          return false;
        }
        unset($this->items[$key]);
      }
      return true;
    }
    throw new InvalidArgumentException();
  }
  
  
  /**
   * Determines whether an item is present in the cache.
   *
   * @param string $key The cache item key.
   *
   * @return bool True if a items cached under $key exists.
   *
   * @throws InvalidArgumentException If $key is no string value.
   */
  public function has($key) {
    if ( is_string($key) || $key instanceof Traversable ) {
      return array_key_exists($key, $this->items);
    }
    throw new InvalidArgumentException();
  }
  
}
