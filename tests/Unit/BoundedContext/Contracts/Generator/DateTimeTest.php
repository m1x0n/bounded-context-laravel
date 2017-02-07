<?php

class DateTimeTest extends PHPUnit_Framework_TestCase
{
    private $dateTime;
    private $currentTimezone;

    public function setUp()
    {
        parent::setUp();
        $this->dateTime = New \BoundedContext\Laravel\Generator\DateTime();
        $this->currentTimezone = date_default_timezone_get();

    }

    /**
     * @dataProvider  timezoneProvider
     */
    public function test_generator_with_right_timezone($timezone, $offset)
    {
        date_default_timezone_set($timezone);
        $generated = $this->dateTime->generate();
        $timezone = (new \DateTime($generated->value()))->getTimezone();

        $this->assertEquals($timezone->getName(), $offset);
    }

    public function timezoneProvider()
    {
        return [
            ['America/Los_Angeles', '-08:00'],
            ['Europe/Dublin', '+00:00'],
            ['Europe/Kiev', '+02:00'],
        ];
    }

    public function tearDown()
    {
        parent::tearDown();
        date_default_timezone_set($this->currentTimezone);
    }
}