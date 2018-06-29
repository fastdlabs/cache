<?php
/**
 * @author: RunnerLee
 * @email: runnerleer@gmail.com
 * @time: 2018-06
 */

function fuck(array &$arr)
{
    ksort($arr);
    array_walk(
        $arr,
        function (&$arr) {
            if (is_array($arr)) {
                fuck($arr);
            }
        }
    );
}

$arr = [
    'b' => [
        'c' => 'f',
        'a' => [
            'g' => '',
            'a' => '',
        ],
    ],
    'a' => '1',
];

fuck($arr);

print_r($arr);