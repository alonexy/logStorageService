<?php

declare(strict_types=1);
/**
 * This file is part of log_store.
 *
 * @author     alonexy@qq.com
 */

namespace App\Filter;

use Carbon\Carbon;

class CrisisFilter extends \php_user_filter
{
    /**
     * 该方法内实现过滤.
     * @param resource $in 流来的桶队列
     * @param resource $out 流走的桶队列
     * @param int &$consumed 处理的字节数
     * @param bool $closing 是否是最后一个桶队列
     */
    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {//迭代每个桶
            $json = json_decode($bucket->data);
            if (isset($json->datetime)) {
                dump(Carbon::parse($json->datetime)->format('Y-m-d H:i:s.u'));
                dump(Carbon::parse($json->datetime)->getPreciseTimestamp(3));
            }
            //EMERGENCY
            if (isset($json->level_name) && $json->level_name == 'EMERGENCY') {
                dump($json);
            }
            $consumed += $bucket->datalen; //增加已经处理的数据量
            stream_bucket_append($out, $bucket); //将该桶对象放入流向下游的队列
        }
        return PSFS_PASS_ON;
    }
}
