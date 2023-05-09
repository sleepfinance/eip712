<?php

namespace Tests;
use SleepFinance\Eip712;
use Tests\TestCase;

class Eip712Test extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_it_throws_for_invalid_json_schema()
    {
        $invalidSchema = file_get_contents(__DIR__ . '/__fixtures__/invalid-schema.json');
        $this->expectExceptionMessage('Invalid Typed Data');
        new Eip712($invalidSchema);
    }
}
