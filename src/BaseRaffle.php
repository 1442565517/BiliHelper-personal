<?php


/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  License: The MIT License
 *  Email: Useri@live.cn
 *  Updated: 2019
 */

namespace lkeme\BiliHelper;

abstract class BaseRaffle
{
    const ACTIVE_TITLE = '';
    const ACTIVE_SWITCH = '';

    public static $lock;
    public static $rw_lock;

    protected static $wait_list;
    protected static $finish_list;
    protected static $all_list;

    public static function run()
    {
        if (getenv(static::ACTIVE_SWITCH) == 'false') {
            return;
        }
        if (static::$lock > time()) {
            return;
        }
        static::startLottery();
    }

    /**
     * 抽奖逻辑
     * @return bool
     */
    protected static function startLottery(): bool
    {
        $max_num = mt_rand(5, 10);
        while ($max_num) {
            $raffle = array_shift(static::$wait_list);
            if (is_null($raffle)) {
                break;
            }
            Live::goToRoom($raffle['room_id']);
            Statistics::addJoinList(static::ACTIVE_TITLE);
            static::lottery($raffle);
            $max_num--;
        }
        return true;
    }


    /**
     * 检查抽奖列表
     * @param $rid
     * @return bool
     */
    abstract protected static function check($rid): bool;


    /**
     * @use 请求抽奖
     * @param array $data
     * @return bool
     */
    abstract protected static function lottery(array $data): bool;


    /**
     * 重复检测
     * @param int $lid
     * @return bool
     */
    protected static function toRepeatLid(int $lid): bool
    {
        if (in_array($lid, static::$all_list)) {
            return true;
        }
        if (count(static::$all_list) > 2000) {
            static::$all_list = [];
        }
        array_push(static::$all_list, $lid);

        return false;
    }

    /**
     * 数据推入队列
     * @param array $data
     * @return bool
     */
    public static function pushToQueue(array $data): bool
    {
        if (getenv(static::ACTIVE_SWITCH) == 'false') {
            return false;
        }

        if (Live::fishingDetection($data['rid'])) {
            return false;
        }
        static::check($data['rid']);
        $wait_num = count(static::$wait_list);
        if ($wait_num > 2) {
            Log::info("当前队列中共有 {$wait_num} 个" . static::ACTIVE_TITLE . "待抽奖");
        }
        return true;
    }

}

