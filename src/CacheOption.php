<?php

namespace Ignome\Library;

use Closure;


trait CacheOption
{

    /**
     * 时间缓存
     * @param $key
     * @param $seconds
     * @param Closure $func
     * @return void
     */
    public function remember($key, $ttl, Closure $callback)
    {
        $value = $this->getRedis($key);
        if (!empty($value)) {
            return $value;
        }
        $value = $callback();
        if (!empty($value)) {
            $this->putRedis($key, $value, value($ttl, $value));
        }
        return $value;
    }

    /**
     * 永久缓存
     * @param $key
     * @param Closure $func
     * @return void
     */
    public function rememberForever($key, Closure $callback)
    {
        $value = $this->getRedis($key);
        if (!empty($value)) {
            return $value;
        }
        $value = $callback();
        if (!empty($value)) {
            $this->foreverRedis($key, $value);
        }
        return $value;
    }


    /**
     * HASH 多个处理
     * @param $redisKey
     * @param Closure $func
     * @param $field
     * @return void
     */
    public function rememberHashMap($key, Closure $callback, $fields = [])
    {
        if (!empty($fields)) {
            $data = $this->hashMGetRedis($key);
            $missingFields = array_filter($data, function ($value) {
                return $value === false || $value === null;
            });
            if (empty($missingFields)) {
                return $data;
            }
        } else {
            $data = $this->hashMGetAllRedis($key);
            if (!empty($data)) {
                return $data;
            }
        }

        $value = $callback();
        $this->hashMSetRedis($key, $value);
        if (!empty($fields)) {
            return array_intersect_key($value, array_flip($fields));
        } else {
            return $value;
        }
    }


    /**
     * HASH 单个处理
     * @param $key
     * @param Closure $callback
     * @param $fields
     * @return mixed
     */
    public function rememberHash($key, $field, Closure $callback)
    {
        $data = $this->hashGetRedis($key, $field);
        if (!empty($data)) {
            return $data;
        }
        $value = $callback();
        $this->hashSetRedis($key, $field, $value);
        return $value;
    }


    /**
     * 清理更新数据
     * @param $key
     * @param Closure $callback
     * @return false|string
     */
    public function clearRemember($key, Closure $callback)
    {
        $value = $callback();
        $this->forgetRedis($key);
        return json_encode($value, true);
    }

    /**
     * 存入数据库
     * @param $key
     * @param $value
     * @param $ttl
     * @return bool|null
     */
    public function putRedis($key, $value, $ttl = null)
    {
        if ($ttl === null) {
            return $this->foreverRedis($key, $value);  //永久缓存
        }
        if (!is_numeric($ttl) || $ttl <= 0) {
            return $this->forgetRedis($key); //删除缓存
        }
        //存入数所库
        return $this->putSetRedis($key, $value, $ttl);
    }


    /**
     * 放入redis
     * @param $key
     * @param $value
     * @param $ttl
     * @return bool
     */
    public function putSetRedis($key, $value, $ttl)
    {
        $odds = mt_rand(100, 200) / 100;
        $ttl = (int)($ttl * $odds);
        return RedisOptions::set($key, $value, $ttl);
    }

    /**
     * 永久缓存
     * @param $key
     * @param $value
     * @return void
     */

    public function foreverRedis($key, $value)
    {
        return RedisOptions::set($key, $value, null);
    }

    /**
     * 清理缓存
     * @param $key
     * @return void
     */
    public function forgetRedis($key)
    {
        return RedisOptions::del($key);
    }


    /**
     * 获取缓存
     * @param $key
     * @return void
     */
    public function getRedis($key)
    {
        return RedisOptions::get($key);
    }

    /**
     * HASH 多个处理
     * @param $key
     * @param $fields
     * @return mixed
     */
    public function hashMGetRedis($key, $fields)
    {
        return RedisOptions::hMGet($key, $fields);
    }

    /**
     * HASH 多个处理
     * @param $key
     * @return array
     */
    public function hashMGetAllRedis($key)
    {
        return RedisOptions::hGetAll($key);
    }

    /**
     * HASH 多个处理
     * @param $key
     * @param $fields
     * @return bool
     */
    public function hashMSetRedis($key, $fields)
    {
        return RedisOptions::hMSet($key, $fields);
    }

    /**
     * HASH 单个处理
     * @param $key
     * @param $field
     * @return array|false|string
     */
    public function hashGetRedis($key, $field)
    {
        return RedisOptions::hGet($key, $field);
    }

    /**
     * HASH 单个处理
     * @param $key
     * @param $field
     * @param $value
     * @return bool|int
     */
    public function hashSetRedis($key, $field, $value)
    {
        return RedisOptions::hSet($key, $field, $value);
    }
}