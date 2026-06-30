<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use OiLab\OiLaravelAi\Tests\TestCase;

uses(TestCase::class)->in('Unit');
uses(TestCase::class, RefreshDatabase::class)->in('Feature');
