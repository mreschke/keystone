# Keystone


Keystone is a mrcore Laravel module that provides a key/value store wrapper on top of Redis.  PHP values are stored into native redis values (SETS, LISTS, HASHES etc..).  These native values provide more speed over basic serialization.  Values are returned from redis into proper PHP objects.


## Organization and Convention

Keystone is a key/value data store and therefore stores all information using string based keys.  These keys are organized into namespaces.  Namespaces follow the PHP `vendor/package` namespace convention. If no namespace is defined in the cofnig when storing a key, the default namespace of `mreschke/foundation` is used. It is up to you to use the proper namespaces when assigning keys.  See Proper Key Layouts below for details.  Keystone values can be `strings`, `lists (arrays)`, `hashes (associative arrays)` or `PHP serialized strings` of any object or item.  Because files are strings, keystone can also stores files of any size and type!  If a value is above a certain byte limit, the value will be stored as an index file on the filesystem.

It is always best to try to store values as native redis formats, so arrays or associative arrays are best. Only use serialization when necessary as it is a PHP only format.

When storing many similar values such as `app/api` configuration information, try to use a single `hash` to hold all the values instead of a million individual keys.


## Instance

To gain access to keystone via PHP you may use any of these methods.  These are in order of preference.  Proper PHP code states dependency injection is always the best method.

- Dependency Injection - `public function __construct(Mreschke\Keystone\KeystoneInterface $keystone)`
- IoC - `App::make('Mreschke\Keystone')` or `App::make('Mreschke\Keystone\KeystoneInterface')`
- Facade - `Mreschke::keystone()->get('mykey')` or `$keystone = Mreschke::keystone()` or `$keystone = Mreschke::keystone()->getInstance()`


When storing values, I do not force or use compression.  If you want to compress your data with PHP use `gzencode() and gzdecode()` instead of the other `gz*` PHP functions. `gzencode()` is a unix gzip compatible function so you can view the compressed file using all gz standards like `zcat /myfile` or `gzunzip /myfile`.


## PHP API Usage

```php
# Namespace Usage
############################################################################
# If no namespace is defined in the config, the default mreschke/foundation namespace is used.
# Nearly every keystone method chain can include a ->ns('custom/namespace') to change the namespace.
# You can also specify the namespace in the key itself using :: as the separator

$keystone->ns('app/dashboard')->put('mykey', 'test');
$keystone->put('app/dashboard::mykey', 'test');




# Values and types
############################################################################
# Keystone can store simple strings, lists (arrays), hashes (associative arrays)
# or PHP objects as serialized strings.  The value is automatically stored
# using the best method.  Values are automatically converted back in to their
# proper types on ->get()...no need to manually unserialize or convert to arrays.
# You can see a values type by running ->type().
# It is RECOMMENDED to use native redis types where possible, so lists and hashes
# over serialized objects.  This keeps them simple and fast in redis and allows
# other non-php applications to read their values.  Serialization is PHP ONLY.
# It is best if you can represent your values as simple one level associative arrays (redis hash)!

# Stored as redis string (unserialized).  Integers/Decimals are just strings in redis
$keystone->put($key, 'hi there');
$keystone->put($key, 42);

# Stored as redis string (but PHP serialized).  PHP objects are automatically serialized
$keystone->put($key, $existingPHPObject);
$keystone->serialize('mykey', 'this is forced serial, use for complex/nested arrays or items');

# Stored as redis lists (arrays)
$keystone->put($key, array('one', 'two', 'three');

# Stored as redis hash (associative array)
# Can only be one level of arrays, no complex/nested arrays allowed here
$keystone->put($key, array('first' => 'Matthew', 'last' => 'Reschke'));

# Get the values type (string, list, hash, file)
echo $keystone->type($key);




# Storage
############################################################################
# Keystone has two backends; redis and flat file.  Keystone automatically
# determines the storage backend based on type and size.  If the value is a
# string > 4096 bytes, it is stored in the filesystem.  All other values
# are stored in redis.  Lists and arrays, no matter their size, are stored in redis
# for speed purposes. These files are stored in /store/data/Production/data/Keystone
# in folders by namespace. These files are given a unique UUID filename, they
# also have a matching redis key. The matching redis key has metadata about the
# file like its filename.  You can view the metadata with the ->fileInfo() function.
# Keystone automatically retrieves from the right source, just use ->get().

$keystone->put($key, 'this goes in redis');
$keystone->put($key, str_repeat('this goes in the filesystem becuase its > 4096 bytes', 1000));
$keystone->put($key, gzencode('some huge, compressed file here, goes in filesystem'));
echo $keystone->fileInfo($key);




# Smart actions for get, pluck, append, forget
############################################################################
# Keystone can perform "smart" and automatic work on stored values.  If you call
# ->get($key) it returns the entire value, but if you run ->get($key, 'firstname')
# it returns only the 'firstname' hash index value, or if serialized, it automatically
# unserializes the object and returns only the associative index or object property desired.
# So if you ->put($key, $largeObject), you could "pluck" individual items from that object
# even though it's serialized...instead of taking the entire object.  Keystone does this
# automatically based on the stored type...it will automatically unserialize,
# pluck/append/forget, then automatically reserialize.  This enables you to work on
# objects like they are arrays; pushing, appending, removing...all without retrieving
# back the entire object only to put it right back again.

# Aliases
# -------
# ->get($key, 'index') is the same as ->pluck($key, 'index');

# Add instead of Put
# ------------------
# For any value type, you can use ->add() instead of ->put() to put the new key
# ONLY if not already exists.

# Smart actions on an array (native redis list)
# This also works if the value is a serialized array!
# Serialized items will be unserialized and reserialized automatically!
# ---------------------------------------------------------------------
	# Put entire array into keystone
	$keystone->put($key, $myArray);

	# Append to an array
	$keystone->push($key, 'one item');
	$keystone->push($key, ['multiple', 'items', 'pushed']);

	# Get entire array
	echo $keystone->get($key);

	# Get an array entry by index (0-n)
	echo $keystone->pluck($key, 20);
	echo $keystone->get($key, 20);

	# Get an array range
	echo $keystone->range($key, 0, -1) #all
	echo $keystone->range($key, 0, 5)  #first 5
	echo $keystone->range($key, -5)    #last 5

	# Delete entire key or just one/multi values by array value (NOT index)
	$keystone->forget($key);
	$keystone->forget($key, 'one');
	$keystone->forget($key, ['delete', 'multiple']);

	# Check if entire key exists
	$keystone->exists($key);

# Smart actions on an associative array (native redis hash)
# This works if the value is a serialized assoc array too!
# This works if the value is a serialized object too!
# Serialized items will be unserialized and reserialized automatically!
# ---------------------------------------------------------------------
	# Put entire assoc array into keystone
	$keystone->put($key, $myAssocArray);

	# Append to an assoc array or object (same syntax for both)
	$keystone->push($key, ['new index' => 'new value', 'another index' => 'another value']);

	# Get entire assoc or object
	echo $keystone->get($key);

	# Get one or multiples values from assoc array or object
	echo $keystone->pluck($key, 'email');
	echo $keystone->get($key, 'email');
	echo $keystone->pluck($key, ['first', 'last', 'email']);
	echo $keystone->get($key, ['first', 'last', 'email']);

	# Delete entire object or just one/multi values from assoc or object
	$keystone->forget($key);
	$keystone->forget($key, 'one');
	$keystone->forget($key, ['delete', 'multiple']);

	# Check if entire key exists, or assoc/object indexes and properties
	$keystone->exists($key);
	$keystone->exists($key, 'email');




# Finding Keys and Values
############################################################################
# Keystone can return a list of keys based on a given query/filter.  It can
# also return the values within those keys.  If returning values, the return
# is an associative array whos indexes are the common denominator of keys.

# Aliases
# ->values() is an alias to ->where()

echo $keystone->keys('user:179:*');
echo $keystone->where('user:179:*');
echo $keystone->where(); # all in default namespace
echo $keystone->ns('app/api')->where(); # all in app/api namespace


# Other features
############################################################################
# Increment an integer by a number
$keystone->increment($key);
$keystone->increment($key, 10);
$keystone->increment($key, -1);
```


# CLI Usage

Keystone comes with a laravel console application available at
`./artisan keystone`

I generally make a bash script in {{/usr/local/bin/keystone}} with
```bash
#!/bin/bash
/var/www/mrcore5/System/artisan keystone "$@"
```

Commands parallel PHP API, so view that documentation above for more detail on these items.


```bash
keystone get key
keystone get key --index=subkey
keystone pluck key --index=subkey
keystone get key --unserialize
keystone get key --index=subkey --unserialize

keystone range key --start=3 --end=19

keystone put key --value='this is a string'
keystone put key --value='<? [1,2,3]'
keystone put key --value='<? [1,2,3]' --serialize
keystone put key --value='<? serialize([1,2,3])'
keystone put mreschke/test::test --value='<? Sso::user()->find(179)'
keystone put mreschke/test::test --value='<? Sso::user()->find(179)'
keystone put key --value='<? ["first" => "Matthew", "last" => "Reschke"]'
keystone add key --value='same as put but only add if not exist'

keystone push key --value='append new array item'
keystone push key --index=description --value='set key in a hash'

keystone increment mreschke/test::test --value='1'
keystone increment mreschke/test::test --value='1' --increment=-1
keystone increment mreschke/test::test --value='1' --increment=100

keystone exists mreschke/test::test
keystone exists mreschke/test::test --index='green'

keystone type mreschke/test::test

keystone keys 'mreschke/metric::*'

keystone values 'mreschke/metric::*'
keystone values "mreschke/alerts::*" --index=enabled
keystone values "mreschke/alerts::*" --index=enabled --value=1
keystone where "remember where is alias to values"

keystone forget mreschke/test::test
keystone forget mreschke/test::test --index='green'

# You can pipe keys into forget to delete multiple keys! Yikes!
keystone keys 'mreschke/metric::*' | keystone forget
```
