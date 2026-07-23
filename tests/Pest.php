<?php

use Zvizvi\FilamentColumnFilters\Tests\Fixtures\TestCaseWithoutPlugin;
use Zvizvi\FilamentColumnFilters\Tests\TestCase;

uses(TestCase::class)->in(__DIR__ . '/Feature');

// Tests that must run with the plugin NOT registered on a panel live here.
uses(TestCaseWithoutPlugin::class)->in(__DIR__ . '/WithoutPlugin');
