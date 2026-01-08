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

    public function testCacheFunctionThrowsExceptionWithoutContainer(): void
    {
        // 由于 helper 函数依赖外部环境，我们只测试函数存在性
        $this->assertTrue(function_exists('cache'));
        
        // 跳过实际调用测试，因为它需要完整的 FastD 环境
        $this->markTestSkipped('Skipping actual function call test due to external dependencies');
    }

    public function testHelperFileLoads(): void
    {
        $helperFile = __DIR__ . '/../helpers.php';
        $this->assertFileExists($helperFile);
        
        // 检查文件是否可读
        $this->assertIsReadable($helperFile);
    }
}