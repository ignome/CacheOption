<?php

namespace Ignome\Library;

use Closure;


class CacheOption
{

    /**
     * 时间缓存
     * @param $key
     * @param $seconds
     * @param Closure $func
     * @return void
     */
    public static function remember($key, $ttl, Closure $callback)
    {
        $value = static::getRedis($key);
        if (!empty($value)) {
            return $value;
        }
        $value = $callback();
        if (!empty($value)) {
            static::putRedis($key, $value, $ttl);
        }
        return $value;
    }

    /**
     * 永久缓存
     * @param $key
     * @param Closure $func
     * @return void
     */
    public static function rememberForever($key, Closure $callback)
    {
        $value = static::getRedis($key);
        if (!empty($value)) {
            return $value;
        }
        $value = $callback();
        if (!empty($value)) {
            static::foreverRedis($key, $value);
        }
        return $value;
    }


    /**
     * HASH 多个处理
     * @param $redisKey
     * @param Closure $func
     * @param $field
     * @param $expire
     * @return void
     */
    public static function rememberHashMap($key, Closure $callback, $fields = [], $expire = null)
    {
        if (!empty($fields)) {
            $data = static::hashMGetRedis($key,$fields);
            $missingFields = array_filter($data, function ($value) {
                return $value === false || $value === null;
            });
            if (empty($missingFields)) {
                return $data;
            }
        } else {
            $data = static::hashMGetAllRedis($key);
            if (!empty($data)) {
                return $data;
            }
        }

        $value = $callback();
        static::hashMSetRedis($key, $value, $expire);
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
     * @param $expire
     * @return mixed
     */
    public static function rememberHash($key, $field, Closure $callback, $expire)
    {
        $data = static::hashGetRedis($key, $field);
        if (!empty($data)) {
            return $data;
        }
        $value = $callback();
        static::hashSetRedis($key, $field, $value, $expire);
        return $value;
    }


    /**
     * 清理更新数据
     * @param $key
     * @param Closure $callback
     * @return false|string
     */
    public static function clearRemember($key, Closure $callback)
    {
        $value = $callback();
        static::forgetRedis($key);
        return json_encode($value, true);
    }

    /**
     * 存入数据库
     * @param $key
     * @param $value
     * @param $ttl
     * @return bool|null
     */
    private static function putRedis($key, $value, $ttl = null)
    {
        if ($ttl === null) {
            return static::foreverRedis($key, $value);  //永久缓存
        }
        if (!is_numeric($ttl) || $ttl <= 0) {
            return static::forgetRedis($key); //删除缓存
        }
        //存入数所库
        return static::putSetRedis($key, $value, $ttl);
    }


    /**
     * 放入redis
     * @param $key
     * @param $value
     * @param $ttl
     * @return bool
     */
    private static function putSetRedis($key, $value, $ttl)
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

    private static function foreverRedis($key, $value)
    {
        return RedisOptions::set($key, $value, null);
    }

    /**
     * 清理缓存
     * @param $key
     * @return void
     */
    private static function forgetRedis($key)
    {
        return RedisOptions::del($key);
    }


    /**
     * 获取缓存
     * @param $key
     * @return void
     */
    private static function getRedis($key)
    {
        return RedisOptions::get($key);
    }

    /**
     * HASH 多个处理
     * @param $key
     * @param $fields
     * @return mixed
     */
    private static function hashMGetRedis($key, $fields)
    {
        return RedisOptions::hMGet($key, $fields);
    }

    /**
     * HASH 多个处理
     * @param $key
     * @return array
     */
    private static function hashMGetAllRedis($key)
    {
        return RedisOptions::hGetAll($key);
    }

    /**
     * HASH 多个处理
     * @param $key
     * @param $fields
     * @param $expire
     * @return mixed
     */
    private static function hashMSetRedis($key, $fields, $expire = null)
    {
        return RedisOptions::hMSet($key, $fields, $expire);
    }

    /**
     * HASH 单个处理
     * @param $key
     * @param $field
     * @return array|false|string
     */
    private static function hashGetRedis($key, $field)
    {
        return RedisOptions::hGet($key, $field);
    }

    /**
     * HASH 单个处理
     * @param $key
     * @param $field
     * @param $value
     * @param $expire
     * @return mixed
     */
    private static function hashSetRedis($key, $field, $value, $expire = null)
    {
        return RedisOptions::hSet($key, $field, $value, $expire);
    }

    /**
     * 设置缓存过期时间
     * @param $key
     * @param $expire
     * @return bool
     */
    private static function expire($key, $expire)
    {
        return RedisOptions::expire($key, $expire);
    }
}