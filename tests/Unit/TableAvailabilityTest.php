<?php

namespace Tests\Unit;

use App\Models\SeatingArea;
use App\Modules\Reservation\Support\TableAvailability;
use Tests\TestCase;

class TableAvailabilityTest extends TestCase
{
    public function test_a_slot_is_a_two_hour_range(): void
    {
        $this->assertSame('11:00–13:00', TableAvailability::slotRange('11:00'));
        $this->assertSame('21:00–23:00', TableAvailability::slotRange('21:00'));
    }

    public function test_a_party_must_fit_the_area_capacity(): void
    {
        $area = new SeatingArea(['min_guests' => 4, 'max_guests' => 8]);
        $availability = new TableAvailability;

        $this->assertFalse($availability->fitsParty($area, 3));
        $this->assertTrue($availability->fitsParty($area, 4));
        $this->assertTrue($availability->fitsParty($area, 8));
        $this->assertFalse($availability->fitsParty($area, 9));
    }
}
