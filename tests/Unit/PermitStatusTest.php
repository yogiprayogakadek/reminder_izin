<?php

namespace Tests\Unit;

use App\Enums\PermitStatus;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class PermitStatusTest extends TestCase
{
    public function test_a_permit_more_than_thirty_days_away_is_active(): void
    {
        $status = PermitStatus::fromDates(
            CarbonImmutable::parse('2026-12-31'),
            CarbonImmutable::parse('2026-11-21'),
        );

        $this->assertSame(PermitStatus::Active, $status);
    }

    public function test_a_permit_within_thirty_days_is_expiring(): void
    {
        $status = PermitStatus::fromDates(
            CarbonImmutable::parse('2026-12-10'),
            CarbonImmutable::parse('2026-11-21'),
        );

        $this->assertSame(PermitStatus::Expiring, $status);
    }

    public function test_a_permit_expiring_today_is_still_expiring(): void
    {
        $status = PermitStatus::fromDates(
            CarbonImmutable::parse('2026-11-21'),
            CarbonImmutable::parse('2026-11-21'),
        );

        $this->assertSame(PermitStatus::Expiring, $status);
    }

    public function test_a_past_permit_is_expired(): void
    {
        $status = PermitStatus::fromDates(
            CarbonImmutable::parse('2026-11-20'),
            CarbonImmutable::parse('2026-11-21'),
        );

        $this->assertSame(PermitStatus::Expired, $status);
    }

    public function test_renewal_and_archive_override_the_date_status(): void
    {
        $expiresAt = CarbonImmutable::parse('2026-11-18');
        $today = CarbonImmutable::parse('2026-11-21');

        $this->assertSame(
            PermitStatus::Renewing,
            PermitStatus::fromDates($expiresAt, $today, isRenewing: true),
        );

        $this->assertSame(
            PermitStatus::Archived,
            PermitStatus::fromDates($expiresAt, $today, isArchived: true),
        );
    }

    public function test_labels_are_in_indonesian(): void
    {
        $this->assertSame('Aktif', PermitStatus::Active->label());
        $this->assertSame('Akan kedaluwarsa', PermitStatus::Expiring->label());
        $this->assertSame('Kedaluwarsa', PermitStatus::Expired->label());
        $this->assertSame('Sedang diperpanjang', PermitStatus::Renewing->label());
        $this->assertSame('Diarsipkan', PermitStatus::Archived->label());
    }
}
