<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

// 在测试开始前加载 helper 文件
require_once __DIR__ . '/../helpers.php';

class HelpersTest extends TestCase
{
    public function testCacheFunctionExists(): void
    {
        $this->assertTrue(function_exists('cache'));
    }


    public function testHelperFileLoads(): void
    {
        $helperFile = __DIR__ . '/../helpers.php';
        $this->assertFileExists($helperFile);
        
        // 检查文件是否可读
        $this->assertIsReadable($helperFile);
    }
}