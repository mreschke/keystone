<?php namespace Mreschke\Keystone;

use Predis\Client as Predis;
use Mreschke\Helpers\Str;
use Predis\Collection\Iterator;

/**
 * Native Keystone Connection
 * @copyright 2014 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
*/
class NativeConnection implements ConnectionInterface
{
    protected $redis;
    protected $prefix;
    protected $rootNs;
    protected $metaNs;
    protected $path;
    protected $ns;
    protected $processingTransaction;
    protected $meta;

    public function __construct($config, Metadata $metadata)
    {
        $this->redis = new Predis([
            'scheme' => 'tcp',
            'host' => $config['host'],
            'port' => $config['port'],
            'database' => $config['database']
        ]);

        $this->prefix = $config['prefix'];
        $this->rootNs = $config['root_namespace'];
        $this->metaNs = $config['metadata_namespace'];
        $this->path = $config['path'];
        $this->maxRedisSize = $config['max_redis_size'];
        $this->processingTransaction = false;
        $this->meta = $metadata;
    }

    /**
     * Get unserialized value from keystone
     * If $index, get value by index (assoc, object...)
     * @param  string $key
     * @param  mixed $index = null optionally pluck by subkey
     * @return mixed
     */
    public function get($key, $index = null)
    {
        if (isset($index)) {
            // Pluck instead
            return $this->pluck($key, $index);
        } else {
            return $this->transaction($key, function ($key) {
                if ($info = $this->isFile($key)) {
                    // Get value from file backend
                    return $this->getFile($key, $info);
                } else {
                    // Get value from redis backend
                    return $this->getRedis($key);
                }
            });
        }
    }

    /**
     * Get value from redis backend
     * @param  string $key
     * @return mixed
     */
    private function getRedis($key)
    {
        $type = $this->redis->type($key);
        if ($type == 'list') {
            return $this->redis->lrange($key, 0, -1);
        } elseif ($type == 'set') {
            return $this->redis->smembers($key);
        } elseif ($type == 'hash') {
            // Do not attempt to unserialize hash content, leave as is
            return $this->redis->hgetall($key);
        } else {
            return Str::unserialize($this->redis->get($key));
        }
    }

    /**
     * Get value from file backend
     * @param  string $key
     * @return mixed
     */
    private function getFile($key, $info)
    {
        return Str::unserialize(file_get_contents($this->filePath($key, $info['keystonefile'])));
    }

    /**
     * Get file information if key is in filesystem
     * @param  string $key
     * @return mixed
     */
    public function fileInfo($key)
    {
        return $this->transaction($key, function ($key) {
            if ($info = $this->isFile($key)) {
                return $info;
            }
        });
    }

    /**
     * Get the first value from a list
     * @param  $key
     * @return mixed
     */
    public function first($key)
    {
        return $this->transaction($key, function ($key) {
            $type = $this->redis->type($key);
            if ($type == 'list') {
                return $this->redis->lindex($key, 0);
            }
        });
    }

    /**
     * Get the last value from a list
     * @param  $key
     * @return mixed
     */
    public function last($key)
    {
        return $this->transaction($key, function ($key) {
            $type = $this->redis->type($key);
            if ($type == 'list') {
                return $this->redis->lindex($key, -1);
            }
        });
    }

    /**
     * Pluck single/multi value from an associative array, single array
     * Works with serialized values too
     * @param  $key
     * @param  mixed $index
     * @return mixed
     */
    public function pluck($key, $index)
    {
        return $this->transaction($key, function ($key) use ($index) {
            $type = $this->redis->type($key);
            if ($type == 'hash') {
                // Assoc array, get directly from hash
                if (is_array($index)) {
                    // Return multiple values
                    $return = array();
                    foreach ($index as $item) {
                        // Do not attempt to unserialize hash content, leave as is
                        $return[$item] = $this->redis->hget($key, $item);
                    }
                    return $return;
                } else {
                    // Return single value
                    // Do not attempt to unserialize hash content, leave as is
                    return $this->redis->hget($key, $index);
                }
            } elseif ($type == 'list') {
                // Array, get directly from list
                // Return single value, use ->range if you want more
                return $this->redis->lindex($key, $index);
            } else {
                $value = $this->get($key);
                if ($this->isAssoc($value)) {
                    // String is serialized assoc array
                    if (is_array($index)) {
                        // Return multiple values
                        $return = array();
                        foreach ($index as $item) {
                            $return[$item] = $value[$item];
                        }
                        return $return;
                    } else {
                        // Return single value
                        return $value[$index];
                    }
                } elseif (is_object($value)) {
                    // String is serialized object
                    if (is_array($index)) {
                        // Return multiple values
                        $return = array();
                        foreach ($index as $item) {
                            if (property_exists($value, $item)) {
                                $return[$item] = $value->$item;
                            }
                        }
                        return $return;
                    } else {
                        // Return single value
                        if (property_exists($value, $index)) {
                            return $value->$index;
                        }
                    }
                }
                return null; //cannot pluck from unserialized string or serialized array
            }
        });
    }

    /**
     * Remove and get the first element in a list (LPOP)
     * @param  $key
     * @return mixed
     */
    public function shift($key)
    {
        return $this->transaction($key, function ($key) {
            $type = $this->redis->type($key);
            if ($type == 'list') {
                return $this->redis->lpop($key);
            }
        });
    }

    /**
     * Remove and get the last element in a list (RPOP)
     * @param  $key
     * @return mixed
     */
    public function pop($key)
    {
        return $this->transaction($key, function ($key) {
            $type = $this->redis->type($key);
            if ($type == 'list') {
                return $this->redis->rpop($key);
            }
        });
    }

    /**
     * Get a range from an array
     * @param  $key
     * @param  $start starts on 0, can be negative
     * @param  $end can use negative numbers
     * @return mixed
     */
    public function range($key, $start = 0, $end = -1)
    {
        return $this->transaction($key, function ($key) use ($start,$end) {
            if ($this->redis->type($key) == 'list') {
                return $this->redis->lrange($key, $start, $end);
            }
        });
    }

    /**
     * Put a value in keystone (automatic redis or file backend based on type and size)
     * Lists and hashes are always stored in redis regardless of size since speed is paramount
     * Strings will be sent to the filesystem of over a designated size limit
     * @param string $key
     * @param mixed $value
     * @param boolean $serialize = false
     */
    public function put($key, $value, $serialize = false)
    {
        $this->transaction($key, function ($key) use ($value,$serialize) {

            // Use serialized value if defined or if PHP object
            if ($serialize || is_object($value)) {
                $value = serialize($value);
            }

            if (is_string($value) && strlen($value) > $this->maxRedisSize) {
                // Strings over $maxRedisSize are sent to the filesystem backend
                $this->meta->forget($key);
                $this->putFile($key, $value);
            } else {
                // Smaller strings and all arrays (lists) and
                // associative arrays (hashes) are stored in redis
                $this->meta->forget($key);
                $this->forgetFile($key);
                $this->putRedis($key, $value);
            }
        });
    }

    /**
     * Put a value into file backend
     * @param  string $key
     * @param  mixed $value
     * @param boolean $serialize = false
     * @return void
     */
    private function putFile($key, $value, $serialize = false)
    {
        if ($info = $this->isFile($key)) {
            // Key exists, use existing filename
            $filename = $info['keystonefile'];
        } else {
            // Key not found, first time insert
            $filename = Str::getMd5(); //random 32char string
            $info = array(
                'keystonefile' => $filename
            );
            $this->redis->set($key, serialize($info));
            $this->meta->put($key, false);
        }
        file_put_contents($this->filePath($key, $filename), $value);
    }

    /**
     * Put a value into redis backend
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    private function putRedis($key, $value)
    {
        if ($this->isAssoc($value)) {
            // Use redis hash to store this associative array
            $this->redis->del($key);
            foreach ($value as $item => $data) {
                $this->redis->hset($key, $item, $data);
            }
        } elseif (is_array($value)) {
            // Use redis lists to store this array
            $this->redis->del($key);
            foreach ($value as $item) {
                $this->redis->rpush($key, $item);
            }
        } else {
            // Use redis strings to store this object
            $this->redis->set($key, $value);
        }
        $this->meta->put($key);
    }

    /**
     * Add value to keystone only if key does not already exists
     * @param string $key
     * @param mixed $value
     * @param mixed $serialize = false
     * @return boolean
     */
    public function add($key, $value, $serialize = false)
    {
        if (!$this->exists($key)) {
            $this->put($key, $value, $serialize);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Put a serialized value in keystone
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function serialize($key, $value)
    {
        $this->put($key, $value, true);
    }

    /**
     * Append to array or string
     * Works with serialized values too
     * @param  $key
     * @param  string|array $value
     */
    public function push($key, $value)
    {
        if ($this->exists($key)) {
            $originalType = $this->type($key);

            $this->transaction($key, function ($key) use ($value) {
                $type = $this->redis->type($key);
                if ($type == 'list') {
                    // Push to end of array
                    if (is_string($value)) {
                        $value = [$value];
                    } // convert to array
                    foreach ($value as $item) {
                        $this->redis->rpush($key, $item);
                    }
                } elseif ($type == 'hash' && is_array($value)) {
                    // Append to hash by key(s)
                    foreach ($value as $item => $data) {
                        // Do not attempt to unserialize hash content, leave as is
                        $this->redis->hset($key, $item, $data);
                    }
                } elseif ($type == 'string') {
                    $original = $this->get($key);
                    if (is_array($original)) {
                        if ($this->isAssoc($original)) {
                            // Was a serialized assoc array, add to array
                            foreach ($value as $item => $data) {
                                $original[$item] = $data;
                            }
                        } else {
                            // Was a serialized array, append to end of array
                            if (is_string($value)) {
                                $value = [$value];
                            } // convert to array
                            foreach ($value as $item) {
                                $original[] = $item;
                            }
                        }
                        $this->serialize($key, $original);
                    } elseif (is_object($original) && is_array($value)) {
                        // Was a serialized object, append properties
                        foreach ($value as $item => $data) {
                            $original->$item = $data;
                        }
                        $this->serialize($key, $original);
                    } elseif (is_string($original) && is_string($value)) {
                        // Just a string
                        $this->put($key, $original.$value);
                    }
                }
                // all other invalid function usage are silently ignored
            });
        } else {
            // New key, use put instead
            $this->put($key, $value);
        }
    }

    /**
     * Increment an integer by a number
     * @param  $key
     * @param  integer $increment = 1
     * @return integer new value
     */
    public function increment($key, $increment = 1)
    {
        return $this->transaction($key, function ($key) use ($increment) {
            if (!$this->isFile($key)) {
                if ($this->redis->type($key) == 'string') {
                    $this->redis->incrby($key, $increment);
                    return $this->redis->get($key);
                }
            }
        });
        return null;
    }

    /**
     * Remove an entire key, or items in a key from keystone
     * Works with serialized values too
     * @param  string $key
     * @param  array $index = null
     */
    public function forget($key, $index = null)
    {
        $this->transaction($key, function ($key) use ($index) {
            if (isset($index)) {
                // Remove item(s) from a key
                if (is_string($index)) {
                    $index = [$index];
                } // convert to array
                $type = $this->redis->type($key);
                if ($type == 'list') {
                    // Remove from list
                    foreach ($index as $item) {
                        $this->redis->lrem($key, 1, $item);
                    }
                } elseif ($type == 'hash') {
                    // Remove from hash
                    foreach ($index as $item) {
                        $this->redis->hdel($key, $item);
                    }
                } else {
                    // Remove from serialized string
                    $original = $this->get($key);
                    if (is_array($original)) {
                        if ($this->isAssoc($original)) {
                            // A serialized assoc array
                            foreach ($index as $item) {
                                unset($original[$item]);
                            }
                        } else {
                            // A serialized array
                            foreach ($index as $item) {
                                if (($deleteIndex = array_search($item, $original)) !== false) {
                                    array_splice($original, $deleteIndex, 1);
                                }
                            }
                        }
                        $this->serialize($key, $original);
                    } elseif (is_object($original)) {
                        // A serialized object
                        foreach ($index as $item) {
                            unset($original->$item);
                        }
                        $this->serialize($key, $original);
                    }
                    // If just a normal unserialized string, do nothing, cannot forget an index of a string
                }
            } else {
                if (!str_contains($key, $this->metaNs)) {
                    // Remove entire key (if not in meta namespace)
                    $this->forgetFile($key); #first
                    $this->redis->del($key); #second
                    $this->meta->forget($key);
                }
            }
        });
    }

    /**
     * Delete a file from the backend
     * @param  string $key
     * @return void
     */
    private function forgetFile($key)
    {
        if ($info = $this->isFile($key)) {
            $path = $this->filePath($key, $info['keystonefile']);
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Check if an entire key exists, or items in a key
     * Works with serialized values too
     * @param  string $key
     * @param  string $index = null
     * @return boolean
     */
    public function exists($key, $index = null)
    {
        return $this->transaction($key, function ($key) use ($index) {
            if (isset($index)) {
                // Check if key index exists (hash or serialized assoc/object)
                $type = $this->redis->type($key);
                if ($type == 'hash') {
                    return $this->redis->hexists($key, $index);
                } elseif ($type == 'string') {
                    $value = $this->get($key);
                    if ($this->isAssoc($value)) {
                        return isset($value[$index]);
                    } elseif (is_object($value)) {
                        return property_exists($value, $index);
                    }
                }
                // ignore unserialized strings and lists, no exists feature by index
                return false;
            } else {
                // Check if entire key exists
                return $this->redis->exists($key);
            }
        });
    }

    /**
     * Get stored object type (string, list, hash, file)
     * @param  string $key
     * @return string
     */
    public function type($key)
    {
        return $this->transaction($key, function ($key) {
            $type = $this->redis->type($key);
            if ($type == 'string') {
                if ($this->isFile($key)) {
                    return 'file';
                } else {
                    return 'string';
                }
            } else {
                return $type;
            }
        });
    }

    /**
     * Get all keys in the current ns
     * @param  string $filter
     * @return array
     */
    public function keys($filter = '*')
    {
        $key = null;
        if (str_contains($filter, '::')) {
            $key = $filter;
        }
        return $this->transaction($key, function ($ns) use ($filter) {
            // I store all keystone keys in a keys:all SET.
            // Redis docs say its best NOT to simply use the KEYS function in production.
            // Best to use SCAN to scan KEYS or SSCAN to scan a SET.  But SSCAN only
            // return 10 results and requires itteration.  Predis has an itterator
            // but it seems double slow from simply pulling all smemebers to using PHP
            // to filter results.

            // Benchmark, return about 1200 iam/client keys
            #$this->redis->keys("$ns$filter"); //25ms
            #$itterator = new Iterator\SetKey($this->redis, $metaKey, "$ns$filter"); //95ms
            #return $this->redis->sscan($metaKey, 0, ['MATCH' => "$ns$filter"])[1]; //only returns 10, need Itterator instead
            #return $this->redis->smembers($metaKey);//26ms but has no filter capability

            // Use predis itterator instead of $this->redis->sscan($metaKey, 0, ['MATCH' => "$ns$filter"])[1];
            // because plain sscan returns only top 10, you would have to itterate manually

            // Do NOT simply use$this->redis->keys("$ns$filter"); as this is not good in production ???? but why ???? can't remember
            // redis docs do say DONT use KEYS in production...says use SCAN (reads from KEYS), SSCAN (reas from any SET)
            // but only return top 10, which is why you need the Itterator below

            // thus the sscan
            // See http://stackoverflow.com/questions/28545549/how-to-use-scan-with-the-match-option-in-predis
            $keys = [];
            if (str_contains($filter, '::')) {
                $filter = null;
            }
            $metaKey = $this->prefix.$this->metaNs.'::keys:all';

            // Smembers and PHP preg is faster, but perhaps not for large sets ???
            $method = "smembers";
            if ($method == 'smembers') {
                // About 45ms vs predis itterator below
                // Return all keys from keys:all set and filter in PHP
                // This is faster than the itterator, by almost double
                // Though maybe trouble if you get millions of keys?
                $results = $this->redis->smembers($metaKey);
                foreach ($results as $key) {
                    $search = "^".preg_replace("'\*'", "(.*)", "$ns$filter")."$";
                    if (preg_match("'$search'", $key)) {
                        $keys[] = $key;
                    }
                }
                return $keys;
            } else {
                // About 98ms vs smembers and PHP preg
                // OR use predis itterator, which has a filter
                // Filtering on keys:all set, use predis sscan itterator
                $itterator = new Iterator\SetKey($this->redis, $metaKey, "$ns$filter");
                foreach ($itterator as $sscanRow) {
                    $keys[] = $sscanRow;
                }
            }

            return $keys;
        });
    }

    /**
     * Get all values of all keys in the current namespace
     * @param  string  $filter
     * @param  string $index = null
     * @param  string $value = null return value if index result matches value
     * @return mixed
     */
    public function where($filter = '*', $index = null, $value = null)
    {
        // 3 methods to pull values
        // Get keys, loop keys in PHP, call redis again = 1300ms
        // Full lua script, one call, loops all keys, gets values = 4500ms (strange so slow?)
        // Piping+lua combination, get keys in PHP, each key into pipe with lue to determint type = 400ms (winner)
        // BUT, when results are returned via LUA they are not from predis, so they are in raw redis format, NOT php arrays :(

        $key = null;
        if (str_contains($filter, '::')) {
            $key = $filter;
        }

        return $this->transaction($key, function ($ns) use ($filter, $index,$value) {
            if (str_contains($filter, '::')) {
                $filter = null;
            }
            $keys = $this->keys("$ns$filter");

            $method = "php";

            if ($method == "piping+lua") {

                // Use a pipeline to get all values
                $results = $this->redis->pipeline(function ($pipe) use ($keys) {
                    foreach ($keys as $key) {
                        $pipe->eval("
                            local type = redis.call('TYPE', '$key')['ok']
                            if type == 'hash' then
                                return redis.call('HGETALL', '$key')
                            elseif type == 'list' then
                                return redis.call('LRANGE', '$key', 0, -1)
                            elseif type == 'set' then
                                return redis.call('SMEMBERS', '$key')
                            end

                        ", 0);
                    }
                });

                // The $results will be in exact order of $keys, so use both to make final
                // $values associative array
                $values = [];
                for ($i = 0; $i < count($results); $i++) {
                    if (str_contains($filter, '*')) {
                        $search = preg_replace("'\*'", "(.*)", "$ns$filter");
                        preg_match("'$search'", $keys[$i], $matches);
                        $values[$matches[1]] = $results[$i];
                    } else {
                        $values[$keys[$i]] = $results[$i];
                    }
                }
                return $values;
            } elseif ($method == "lua") {

                // Other method was to use lua for everything, no piping
                // This was actually VERY slow, like 4sec vs the 400ms piping + lua above
                $lua = "
                    local matches = redis.call('KEYS', '$ns$filter')

                    local data = {}

                    for _, key in ipairs(matches) do
                        -- data[key] = key
                        local type = redis.call('TYPE', key)['ok']
                        if type == 'hash' then
                            data[key] = redis.call('HGETALL', key)
                        elseif type == 'list' then
                            data[key] = redis.call('LRANGE', key, 0, -1)
                        elseif type == 'set' then
                            data[key] = redis.call('SMEMBERS', key)
                        end
                    end

                    return cjson.encode(data)
                    --return cmsgpack.pack(data)
                ";
                return $this->redis->eval($lua, 0);
            } elseif ($method == "php") {

                // Original method, simply loop each key in PHP and call redis again
                // This was about 1300ms for 1200 keys vs the above piping+lua at 400ms
                $values = [];
                foreach ($keys as $key) {
                    if (isset($index) && isset($value)) {
                        // Querying by index AND value, show all records that match
                        $result = $this->get($key);
                        if ($result[$index] != $value) {
                            $result = null;
                        }
                    } else {
                        // Querying only an index, show ONLY the inedex entry that matched, not entire record
                        $result = $this->get($key, $index);
                    }

                    if (isset($result)) {
                        if ($key == "$ns$filter") {
                            // One result
                            $values = $result;
                        } else {
                            // Many results, add * regex as key
                            if (str_contains("$ns$filter", '*')) {
                                $search = preg_replace("'\*'", "(.*)", "$ns$filter");
                                preg_match("'$search'", $key, $matches);
                                $values[$matches[1]] = $result;
                            } else {
                                $values[$key] = $result;
                            }
                        }
                    }
                }
                return $values;
            }
        });
    }

    /**
     * Alias to where
     */
    public function values($filter = '*', $index = null, $value = null)
    {
        return $this->where($filter, $index, $value);
    }

    /**
     * Get all keystone namespaces
     * @return array
     */
    public function namespaces()
    {
        $key = $this->prefix.$this->metaNs.'::namespaces';
        return $this->redis->smembers($key);
    }

    /**
     * Show the readme files
     * @return string
     */
    public function readme()
    {
        if ($file = realpath(__DIR__."/README.md")) {
            return file_get_contents($file);
        } else {
            return "Readme $file not found";
        }
    }

    /**
     * Set the keystone key expiration in minutes
     * @param  string $key
     * @param  int $minutes
     * @return void
     */
    /*public function expire($key, $minutes)
    {
        $this->transaction($key, function($key) use($minutes) {
            $this->redis->expire($key, $minutes * 60);
        });
    }*/
    // FIXME when you get expire for filebackend working

    /**
     * Remove any expiration from the key
     * @param  string $key
     * @return void
     */
    /*public function persist($key)
    {
        $this->transaction($key, function($key) {
            $this->redis->persist($key);
        });
    }*/
    // FIXME when you get persist for filebackend working

    /**
     * Class transaction helper to prep keys and reset properties to defaults
     * @param  string $key
     * @param  callback $transaction
     * @return mixed
     */
    private function transaction($key, $transaction)
    {
        $this->processingTransaction = true;

        // Use default ns if none given
        if (is_null($this->ns)) {
            $this->ns = $this->rootNs;
        }

        // Prepend ns to key
        if (!str_contains($key, '::')) {
            $key = $this->ns.'::'.$key;
        }

        // Prepend redis prefix to key
        if (substr($key, 0, strlen($this->prefix)) != $this->prefix) {
            $key = $this->prefix.$key;
        }

        // Perform the transaction
        $value = call_user_func($transaction, $key);

        // Reset transaction defaults
        $this->ns = $this->rootNs;
        $this->processingTransaction = false;
        return $value;
    }

    /**
     * Set the keystone namespace
     * @param  string $ns
     * @return self
     */
    public function ns($ns)
    {
        $this->ns = $ns;
        return $this;
    }

    /**
     * Return this instance
     * @return self
     */
    public function getInstance()
    {
        return $this;
    }

    /**
     * Get file path from key
     * @param  string $key
     * @return string
     */
    private function filePath($key, $filename)
    {
        $root = $this->path;
        $folder = substr($key, strpos($key, ':') + 1, strpos($key, '::') - strpos($key, ':') - 1);
        #$filename = substr($key, strpos($key, '::') + 2);
        if (!is_dir("$root/$folder")) {
            mkdir("$root/$folder", 0777, true);
        }
        $path = "$root/$folder/$filename";
        return $path;
    }

    /**
     * Check if object is an associative array
     * @param  array $arr
     * @return boolean
     */
    private function isAssoc($arr)
    {
        return (is_array($arr) && array_keys($arr) !== range(0, count($arr) - 1));
    }

    /**
     * Check if this key is a file backend
     * @param  string  $key
     * @return false|string
     */
    private function isFile($key)
    {
        if ($this->redis->type($key) == 'string') {
            $info = Str::unserialize($this->redis->get($key));
            if (is_array($info) && isset($info['keystonefile'])) {
                return $info;
            }
        }
        return false;
    }
}
