<?php

namespace Tests\Unit\Models;

use App\Models\InternalNotification;
use PHPUnit\Framework\TestCase;

class InternalNotificationTest extends TestCase
{
    /**
     * Test the priority label attribute accessor.
     */
    public function test_get_priority_label_attribute(): void
    {
        $notification = new InternalNotification();

        // Test 'low' priority
        $notification->priority = 'low';
        $this->assertEquals('Rendah', $notification->priority_label);

        // Test 'normal' priority
        $notification->priority = 'normal';
        $this->assertEquals('Normal', $notification->priority_label);

        // Test 'high' priority
        $notification->priority = 'high';
        $this->assertEquals('Tinggi', $notification->priority_label);

        // Test default priority
        $notification->priority = 'unknown_priority';
        $this->assertEquals('-', $notification->priority_label);

        // Test null priority
        $notification->priority = null;
        $this->assertEquals('-', $notification->priority_label);
    }
}
