<?php

namespace Tests\Feature;

use App\Actions\Accounts\PortfolioDemo;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PortfolioDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_baseline_is_idempotent_and_expired_reset_preserves_unrelated_data(): void
    {
        config(['demo.portfolio_enabled' => true, 'mail.default' => 'array']);
        $other = User::factory()->create(['role' => 'teacher']);
        $class = Classroom::forceCreate(['owner_id' => $other->id, 'name' => 'Outside demo', 'timezone' => 'Asia/Ho_Chi_Minh']);
        $demo = app(PortfolioDemo::class);
        $first = $demo->handle();
        $this->assertCount(3, $first['classIds']);
        $this->assertEquals($first, $demo->handle());
        $teacher = User::findOrFail($first['teacherId']);
        $this->actingAs($teacher)->getJson('/api/v1/dashboard')->assertOk();
        $this->postJson('/api/v1/me/change-password', [])->assertForbidden()->assertJsonPath('code', 'DEMO_RESTRICTED');
        $this->postJson('/api/v1/classes/'.$first['classIds'][0].'/files', [])->assertForbidden();
        $first['resetAt'] = now()->subDays(2)->toISOString();
        DB::table('demo_datasets')->where('name', PortfolioDemo::DATASET)->update(['manifest' => json_encode($first)]);
        $second = $demo->handle();
        $this->assertSame($first['userIds'], $second['userIds']);
        $this->assertNotSame($first['classIds'], $second['classIds']);
        $this->assertDatabaseHas('classes', ['id' => $class->id, 'name' => 'Outside demo']);
        $this->assertDatabaseMissing('classes', ['id' => $first['classIds'][0]]);
        $this->assertDatabaseHas('attendance_records', ['approved_by' => $teacher->id]);
        $this->assertDatabaseHas('class_memberships', ['class_id' => $second['classIds'][0], 'status' => 'pending']);
    }

    public function test_quota_blocks_writes_and_maintenance_recovers_without_touching_other_accounts(): void
    {
        config(['demo.portfolio_enabled' => true, 'mail.default' => 'array']);
        $demo = app(PortfolioDemo::class);
        $first = $demo->handle();
        $teacher = User::findOrFail($first['teacherId']);
        foreach (range(1, 4) as $index) {
            Classroom::forceCreate(['owner_id' => $teacher->id, 'name' => 'Trial '.$index, 'timezone' => 'Asia/Ho_Chi_Minh']);
        }
        $this->actingAs($teacher)->postJson('/api/v1/classes', ['name' => 'Spam'])->assertStatus(429)->assertJsonPath('code', 'DEMO_LIMIT');
        $second = $demo->handle();
        $this->assertCount(3, $second['classIds']);
        $this->assertFalse($demo->overLimit($second));
        $this->postJson('/api/v1/classes', ['name' => 'Recruiter trial'])->assertCreated();
    }

    public function test_existing_email_collision_is_fail_closed(): void
    {
        config(['demo.portfolio_enabled' => true, 'mail.default' => 'array']);
        User::factory()->create(['email' => 'teacher@demo.attendance.test', 'role' => 'teacher']);
        $this->expectException(\RuntimeException::class);
        app(PortfolioDemo::class)->handle();
    }
}
