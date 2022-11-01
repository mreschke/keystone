<?php

include_once __DIR__ . '/TestCase.php';

class KeystoneTest extends TestCase
{
    public function createApplication()
    {
        return createApplication();
    }

    public $prefix = 'keystone:';
    public $rootNamespace = 'dynatron/framework';
    public $testNamespace = 'keystone/unittest';
    public $key = 'unit_test_temp_key';

    /** @test */
    public function put_string_in_redis()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $keystonePath = Config::get('dynatron.keystone_path');
        $keystone->put($this->key, 'pass');
        $this->assertEquals($keystone->get($this->key), 'pass');
        $this->assertFalse(file_exists("$keystonePath/dynatron/framework/$this->key"));
    }

    /** @test */
    public function push_string_in_redis()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $this->assertEquals($keystone->get($this->key), 'pass');
        $keystone->push($this->key, 'again');
        $this->assertEquals($keystone->get($this->key), 'passagain');
    }

    /** @test */
    public function forget_string_in_redis()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $this->assertTrue($keystone->exists($this->key));
        $keystone->forget($this->key);
        $this->assertFalse($keystone->exists($this->key));
    }

    /** @test */
    public function push_string_without_key_in_redis()
    {
        // Push to a key that does not exist, should route to ->put()
        $keystone = $this->app->make('Mreschke\Keystone');
        $keystone->push($this->key, 'pass');
        $this->assertEquals($keystone->get($this->key), 'pass');
    }

    /** @test */
    public function put_string_in_filesystem()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $keystonePath = Config::get('dynatron.keystone_path');
        $keystone->put($this->key, str_repeat('test ', 5000));

        // Verify key is in filesystem
        $info = $keystone->fileInfo($this->key);
        $this->assertTrue(isset($info['keystonefile']) && file_exists("$keystonePath/dynatron/framework/$info[keystonefile]"));

        // Verify key is in redis also
        $this->assertTrue($keystone->exists($this->key));
    }

    /** @test */
    public function push_string_in_filesystem()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $this->assertEquals(strlen($keystone->get($this->key)), 25000);
        $keystone->push($this->key, 'appended!');
        $this->assertEquals(strlen($keystone->get($this->key)), 25009);
    }

    /** @test */
    public function forget_string_in_filesysten()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $keystonePath = Config::get('dynatron.keystone_path');
        $info = $keystone->fileInfo($this->key);

        $this->assertTrue($keystone->exists($this->key));
        $this->assertTrue(isset($info['keystonefile']) && file_exists("$keystonePath/dynatron/framework/$info[keystonefile]"));

        $keystone->forget($this->key);

        $this->assertFalse($keystone->exists($this->key));
        $this->assertFalse(isset($info['keystonefile']) && file_exists("$keystonePath/dynatron/framework/$info[keystonefile]"));
    }

    /** @test */
    public function put_string_in_root_namespace()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $keystone->ns($this->rootNamespace)->put($this->key, 'pass');
        $this->assertTrue($keystone->exists("$this->rootNamespace::$this->key"));
        $this->assertEquals($keystone->ns($this->rootNamespace)->get($this->key), 'pass');
        $keystone->ns($this->rootNamespace)->forget($this->key);
        $this->assertFalse($keystone->exists("$this->rootNamespace::$this->key"));
    }

    /** @test */
    public function put_string_in_test_namespace()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $keystone->ns($this->testNamespace)->put($this->key, 'pass');
        $this->assertTrue($keystone->exists("$this->testNamespace::$this->key"));
        $this->assertEquals($keystone->ns($this->testNamespace)->get($this->key), 'pass');
        $keystone->ns($this->testNamespace)->forget($this->key);
        $this->assertFalse($keystone->exists("$this->testNamespace::$this->key"));
    }

    /** @test */
    public function put_array_in_redis()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $keystone->put($this->key, [
            'one', 'two', 'three', 'four', 'five'
        ]);
        $this->assertEquals($keystone->type($this->key), 'list');
        $this->assertTrue(is_array($keystone->get($this->key)));
        // Verify exists doesn't work for arrays
        $this->assertFalse($keystone->exists($this->key, 0));
        $this->assertFalse($keystone->exists($this->key, 'one'));
    }

    /** @test */
    public function push_array_in_redis()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $this->assertEquals(cnt($keystone->get($this->key)), 5);
        $keystone->push($this->key, 'six');
        $this->assertEquals(cnt($keystone->get($this->key)), 6);
    }

    /** @test */
    public function get_array_range_in_redis()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $this->assertEquals($keystone->range($this->key, -2)[0], 'five');
        $this->assertEquals($keystone->range($this->key, -2)[1], 'six');
    }

    /** @test */
    public function forget_array_item_by_value_from_redis()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $this->assertEquals(cnt($keystone->get($this->key)), 6);
        $keystone->forget($this->key, 'six');
        $this->assertEquals(cnt($keystone->get($this->key)), 5);
        $keystone->forget($this->key, ['one', 'three']);
        $this->assertEquals(cnt($keystone->get($this->key)), 3);
    }

    /** @test */
    public function put_huge_array_in_redis()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $keystone->put($this->key, range(1, 5000));
        $this->assertEquals(cnt($keystone->get($this->key)), 5000);
        $this->assertEquals($keystone->type($this->key), 'list');
    }


    /** @test */
    public function put_hash_in_redis()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $keystone->put($this->key, [
            'one' => 'one here',
            'two' => 'two here',
            'three' => 'three here',
            'four' => 'four here',
            'five' => 'five here'
        ]);
        $this->assertEquals($keystone->type($this->key), 'hash');
        $this->assertTrue($keystone->exists($this->key, 'one'));
        $this->assertFalse($keystone->exists($this->key, 'notfound'));
    }

    /** @test */
    public function pluck_hash_in_redis()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $this->assertEquals($keystone->pluck($this->key, 'two'), 'two here');
        $this->assertEquals($keystone->get($this->key, 'two'), 'two here');
        $this->assertEquals(cnt($keystone->pluck($this->key, ['three', 'one'])), 2);
    }

    /** @test */
    public function push_to_hash_in_redis()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $this->assertEquals(cnt($keystone->get($this->key)), 5);
        $keystone->push($this->key, ['six' => 'six here']);
        $this->assertEquals(cnt($keystone->get($this->key)), 6);
    }

    /** @test */
    public function forget_hash_key_from_redis()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $this->assertEquals(cnt($keystone->get($this->key)), 6);
        $keystone->forget($this->key, 'six');
        $this->assertFalse($keystone->exists($this->key, 'six'));
        $this->assertEquals(cnt($keystone->get($this->key)), 5);
        $keystone->forget($this->key, ['one', 'three']);
        $this->assertEquals(cnt($keystone->get($this->key)), 3);
    }

    /** @test */
    public function put_object_in_redis()
    {
        $keystone = $this->app->make('Mreschke\Keystone');

        $o = new stdClass();
        $o->first = 'Matthew';
        $o->last = 'Reschke';

        $keystone->put($this->key, $o);
        $o2 = $keystone->get($this->key);
        $this->assertTrue(is_object($o2));
        $this->assertTrue($keystone->exists($this->key, 'first'));
        $this->assertFalse($keystone->exists($this->key, 'notfound'));
    }

    /** @test */
    public function pluck_from_object_in_redis()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $this->assertEquals($keystone->pluck($this->key, 'first'), 'Matthew');
        $this->assertEquals($keystone->get($this->key, 'first'), 'Matthew');
        $this->assertEquals(cnt($keystone->pluck($this->key, ['last', 'first'])), 2);
    }

    /** @test */
    public function push_to_object_in_redis()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $keystone->push($this->key, ['email' => 'mreschke@dynatronsoftware.com']);
        $this->assertEquals($keystone->pluck($this->key, 'email'), 'mreschke@dynatronsoftware.com');
        $this->assertEquals(cnt($keystone->pluck($this->key, ['last', 'email'])), 2);
    }

    /** @test */
    public function forget_a_property_from_object_in_redis()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $this->assertEquals($keystone->get($this->key, 'first'), 'Matthew');
        $keystone->forget($this->key, 'first');
        $this->assertTrue(is_null($keystone->get($this->key, 'first')));
    }

    /** @test */
    public function put_object_in_filesystem()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $keystonePath = Config::get('dynatron.keystone_path');

        $o = new stdClass();
        $o->first = 'Matthew';
        $o->last = 'Reschke';
        for ($i = 0; $i < 1000; $i++) {
            $o->{"test$i"} = "value $i";
        }
        $keystone->put($this->key, $o);
        $o2 = $keystone->get($this->key);
        $this->assertTrue(is_object($o2));
        $this->assertTrue($keystone->exists($this->key));

        $info = $keystone->fileInfo($this->key);
        $this->assertTrue(isset($info['keystonefile']) && file_exists("$keystonePath/dynatron/framework/$info[keystonefile]"));
    }

    /** @test */
    public function pluck_from_object_in_filesystem()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $this->assertEquals($keystone->pluck($this->key, 'test999'), 'value 999');
        $this->assertEquals($keystone->get($this->key, 'test999'), 'value 999');
    }

    /** @test */
    public function push_to_object_in_filesystem()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $keystone->push($this->key, ['test1000' => 'value 1000']);
        $keystone->push($this->key, ['test999' => 'value 999-updated']);
        $this->assertEquals($keystone->pluck($this->key, 'test1000'), 'value 1000');
        $this->assertEquals($keystone->pluck($this->key, 'test999'), 'value 999-updated');
    }

    /** @test */
    public function put_serialized_assoc_in_redis()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $keystone->serialize($this->key, [
            'one' => 'one here',
            'two' => 'two here',
            'three' => 'three here',
            'four' => 'four here',
            'five' => 'five here'
        ]);
        $this->assertEquals($keystone->type($this->key), 'string');
        $this->assertTrue(is_array($keystone->get($this->key)));
        $this->assertTrue($keystone->exists($this->key, 'four'));
        $this->assertFalse($keystone->exists($this->key, 'onehundred'));
    }

    /** @test */
    public function pluck_from_serialized_assoc_in_redis()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $this->assertEquals($keystone->pluck($this->key, 'three'), 'three here');
        $this->assertEquals(cnt($keystone->pluck($this->key, ['two', 'four'])), 2);
    }

    /** @test */
    public function push_to_serialized_assoc_in_redis()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $keystone->push($this->key, ['six' => 'six here']);
        $keystone->push($this->key, ['one' => 'one here-updated']);
        $this->assertEquals($keystone->pluck($this->key, 'six'), 'six here');
        $this->assertEquals($keystone->pluck($this->key, 'one'), 'one here-updated');
    }

    /** @test */
    public function forget_assoc_key_from_serialized_assoc_in_redis()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $this->assertEquals(cnt($keystone->get($this->key)), 6);
        $keystone->forget($this->key, 'six');
        $this->assertEquals(cnt($keystone->get($this->key)), 5);
        $keystone->forget($this->key, ['one', 'two']);
        $this->assertEquals(cnt($keystone->get($this->key)), 3);
    }

    /** @test */
    public function put_serialized_array_in_redis()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $keystone->serialize($this->key, [
            'one', 'two', 'three', 'four', 'five'
        ]);
        $this->assertEquals($keystone->type($this->key), 'string');
        $this->assertTrue(is_array($keystone->get($this->key)));
    }

    /** @test */
    public function forget_a_value_from_serialized_array_in_redis()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $this->assertEquals(cnt($keystone->get($this->key)), 5);
        $keystone->forget($this->key, 'one');
        $this->assertEquals(cnt($keystone->get($this->key)), 4);
        $keystone->forget($this->key, ['three', 'five']);
        $this->assertEquals(cnt($keystone->get($this->key)), 2);
    }

    /** @test */
    public function increment_key()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $keystone->put($this->key, 10);
        $this->assertEquals($keystone->get($this->key), 10);
        $keystone->increment($this->key);
        $this->assertEquals($keystone->get($this->key), 11);
        $this->assertEquals($keystone->increment($this->key, 10), 21);
    }

    /** @test */
    public function list_keys()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $keystone->ns($this->testNamespace)->put('dealer:1:goal', 'one');
        $keystone->ns($this->testNamespace)->put('dealer:1:red', 'two');
        $keystone->ns($this->testNamespace)->put('dealer:1:green', 'three');
        $keys = $keystone->ns($this->testNamespace)->keys();
        sort($keys);
        $this->assertEquals($keys[1], 'keystone:keystone/unittest::dealer:1:green');
        $this->assertEquals(cnt($keys), 3);
    }

    /** @test */
    public function list_values()
    {
        $keystone = $this->app->make('Mreschke\Keystone');

        $values = $keystone->ns($this->testNamespace)->where();
        $this->assertEquals(cnt($values), 3);

        $values = $keystone->where($this->testNamespace."::*");
        $this->assertEquals(cnt($values), 3);

        $values = $keystone->ns($this->testNamespace)->values();
        $this->assertEquals(cnt($values), 3);

        $values = $keystone->ns($this->testNamespace)->where('dealer:1:*');
        $this->assertEquals(cnt($values), 3);
        $this->assertEquals($values['red'], 'two');

        $values = $keystone->ns($this->testNamespace)->values('dealer:1:*');
        $this->assertEquals(cnt($values), 3);
        $this->assertEquals($values['red'], 'two');
    }

    /** @test */
    public function cleanup()
    {
        $keystone = $this->app->make('Mreschke\Keystone');
        $keystone->forget($this->key);
        $keystone->ns($this->testNamespace)->forget('dealer:1:goal');
        $keystone->ns($this->testNamespace)->forget('dealer:1:red');
        $keystone->ns($this->testNamespace)->forget('dealer:1:green');
    }
}
