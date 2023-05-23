<?php

namespace Shisa\SimpleWeChatClient;

use Psr\SimpleCache\CacheInterface;

class DummyCache implements CacheInterface
{
    private $cache = [];

    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->cache[$key];
        } else {
            return $default;
        }
    }

    public function set($key, $value, $ttl = null)
    {
        $this->cache[$key] = $value;
        // Ignore TTL for a dummy cache
        return true;
    }

    public function delete($key)
    {
        unset($this->cache[$key]);
        return true;
    }

    public function clear()
    {
        $this->cache = [];
        return true;
    }

    public function getMultiple($keys, $default = null)
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }
        return $values;
    }

    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function has($key)
    {
        return isset($this->cache[$key]);
    }
}
