<?php namespace Mreschke\Keystone\Console\Commands;

use Illuminate\Console\Command;
use Mreschke\Keystone\KeystoneInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Keystone console api
 * @copyright 2014 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
*/
class KeystoneCommand extends Command
{
    protected $name = 'keystone';
    protected $description = 'Keystone console application';
    protected $version = "1.0";
    protected $keystone;

    public function __construct(KeystoneInterface $keystone)
    {
        parent::__construct();
        $this->keystone = $keystone;
    }

    /**
     * Execute the console command.
     */
    public function fire()
    {
        $action = $this->argument('action');
        $key = $this->argument('key');

        $index = $this->option('index');
        $start = $this->option('start');
        $end = $this->option('end');
        $value = $this->option('value');
        $increment = $this->option('increment');
        $serialize = $this->option('serialize') ?: false;
        $unserialize = $this->option('unserialize') ?: false;

        $isAction = function ($actions) use ($action) {
            $actions = func_get_args();
            return (in_array($action, $actions));
        };

        $results = null;

        if ($isAction('get', 'pluck', 'exists')) {
            $results = $this->keystone->$action($key, $index);
        }

        if ($isAction('type', 'first', 'last', 'shift', 'pop')) {
            $results = $this->keystone->$action($key);
        }

        if ($isAction('range')) {
            $results = $this->keystone->$action($key, $start, $end);
        }

        if ($isAction('put', 'add')) {
            if (isset($index)) {
                // Trying to put only a hash key, same as push
                $this->keystone->push($key, [$index => $value]);
            } else {
                // Pushing an entire value
                if (substr($value, 0, 2) == "<?") {
                    $value = substr($value, 2);
                    $value = eval("return $value;");
                }
                $this->keystone->$action($key, $value, $serialize);
            }
            $results = $this->keystone->get($key);
        }

        if ($isAction('push')) {
            if (isset($index)) {
                // Pushing an new key/value into a hash
                $this->keystone->$action($key, [$index => $value]);
                $results = $this->keystone->get($key);
            } else {
                // Pushing a value onto a list (this won't append to strings)
                $this->keystone->$action($key, [$value]);
                $this->info("Pushed $value to $key");
            }
        }

        if ($isAction('increment')) {
            $this->keystone->$action($key, $increment);
            $results = $this->keystone->get($key);
        }

        if ($isAction('forget')) {
            if (isset($key)) {
                if ($this->keystone->exists($key)) {
                    $this->keystone->forget($key, $index);
                    if (isset($index)) {
                        $results = $this->keystone->get($key);
                        $this->info("Removed $index from $key");
                    } else {
                        $this->info("Deleted $key");
                    }
                } else {
                    $this->info("Key Not Found $key");
                }
            } else {
                // Delete from stdin, for piping
                // ex: keystone keys dynatron/metric::* | keystone forget
                while ($key = trim(fgets(STDIN))) {
                    if ($this->keystone->exists($key)) {
                        $this->keystone->forget($key);
                        $this->info("Deleted $key");
                    } else {
                        $this->info("Key Not Found $key");
                    }
                }
            }
        }

        if ($isAction('keys')) {
            $results = $this->keystone->$action($key);
            foreach ($results as $result) {
                // Plain text echo, no colors for piping
                echo $result."\n";
            }
            $results = null;
        }

        if ($isAction('values', 'where')) {
            $results = $this->keystone->$action($key, $index, $value);
        }

        if ($isAction('namespaces')) {
            $results = $this->keystone->namespaces();
        }

        // Output results
        if (isset($results)) {
            if ($this->isSerialized($results) && $unserialize) {
                // Data is serialized and we requested to unserialize
                dump($this->unserialize($results));
            } elseif ($unserialize) {
                // Data was not serialized, but unserialize was requested.
                // If data is array, loop array and attemp to unserialize each key
                if (is_array($results)) {
                    $newResults = [];
                    foreach ($results as $key => $value) {
                        $newResults[$key] = $this->unserialize($value);
                    }
                    dump($newResults);
                } else {
                    // Was not an array, can't unserialize, just print
                    $this->info($results);
                }
            } else {
                // Not serialized, and not requested to unserialize
                if (is_string($results)) {
                    $this->info($results);
                } else {
                    dump($results);
                }
            }
        }
    }

    /**
     * Unserialize data only if serialized
     * @param  data $value
     * @return mixed
     */
    private function unserialize($value)
    {
        $data = @unserialize($value);
        if ($value === 'b:0;' || $data !== false) {
            // Unserialization passed, return unserialized data
            return $data;
        } else {
            // Data was not serialized, return raw data
            return $value;
        }
    }

    /**
     * Check if data is serialized
     * @param  mixed $value
     * @return boolean
     */
    private function isSerialized($value)
    {
        $data = @unserialize($value);
        if ($value === 'b:0;' || $data !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('action', InputArgument::REQUIRED, 'Keystone action (get, put, pluck, range...)'),
            array('key', InputArgument::OPTIONAL, 'Keystone key (namespace optional)'),
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('index',     null, InputOption::VALUE_OPTIONAL, 'Index of assoc or hash', null),
            array('start',     null, InputOption::VALUE_OPTIONAL, 'Start of array range', 0),
            array('end',       null, InputOption::VALUE_OPTIONAL, 'End of array range', -1),
            array('value',     null, InputOption::VALUE_OPTIONAL, 'Value to put or where by', null),
            array('increment', null, InputOption::VALUE_OPTIONAL, 'Increment size', 1),

            array('serialize', null, InputOption::VALUE_NONE,     'Enable serialization on put requets'),
            array('unserialize', null, InputOption::VALUE_NONE,   'Unserialize result'),
        );
    }
}
