<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ClassroomsTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_multiple_independent_classes_without_catalogs(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $first = $this->actingAs($teacher)->postJson('/api/v1/classes', ['name' => 'First'])->assertCreated()->assertHeader('Location')->assertJsonPath('data.class.version', 1);
        $second = $this->postJson('/api/v1/classes', ['name' => 'Second'])->assertCreated();
        $this->assertNotSame($first->json('data.class.id'), $second->json('data.class.id'));
        $this->getJson('/api/v1/classes?pageSize=1')->assertOk()->assertJsonPath('data.totalItems', 2)->assertJsonPath('data.hasNextPage', true)->assertJsonCount(1, 'data.items');
        $this->getJson('/api/v1/classes?pageSize=1&pageNumber=2')->assertOk()->assertJsonPath('data.hasNextPage', false)->assertJsonCount(1, 'data.items');
        $this->getJson('/api/v1/classes?q=First')->assertOk()->assertJsonPath('data.totalItems', 1);
    }

    public function test_other_teacher_cannot_read_update_archive_rotate_or_count_private_classes(): void
    {
        $owner = User::factory()->create(['role' => 'teacher']);
        $other = User::factory()->create(['role' => 'teacher']);
        $id = $this->actingAs($owner)->postJson('/api/v1/classes', ['name' => 'Private'])->json('data.class.id');
        $this->actingAs($other)->getJson('/api/v1/classes')->assertOk()->assertJsonPath('data.totalItems', 0);
        $this->getJson('/api/v1/classes/'.$id)->assertNotFound();
        $this->patchJson('/api/v1/classes/'.$id, ['name' => 'Stolen', 'expectedVersion' => 1])->assertNotFound();
        foreach (['archive', 'invite-code/rotate', 'invite-code/disable'] as $action) {
            $this->postJson('/api/v1/classes/'.$id.'/'.$action, ['expectedVersion' => 1])->assertNotFound();
        }
        $this->assertSame('Private', Classroom::findOrFail($id)->name);
    }

    public function test_student_cannot_create_classes_or_set_owner(): void
    {
        $student = User::factory()->create();
        $this->actingAs($student)->postJson('/api/v1/classes', ['name' => 'No'])->assertForbidden();
        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->actingAs($teacher)->postJson('/api/v1/classes', ['name' => 'No', 'ownerId' => $student->id])->assertUnprocessable();
        $this->assertDatabaseCount('classes', 0);
    }

    public function test_class_updates_require_version_and_archived_class_is_immutable(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $id = $this->actingAs($teacher)->postJson('/api/v1/classes', ['name' => 'Original'])->json('data.class.id');
        $this->patchJson('/api/v1/classes/'.$id, ['name' => 'Updated', 'expectedVersion' => 1])->assertOk()->assertJsonPath('data.version', 2);
        $this->patchJson('/api/v1/classes/'.$id, ['name' => 'Stale', 'expectedVersion' => 1])->assertConflict();
        $this->postJson('/api/v1/classes/'.$id.'/archive', ['expectedVersion' => 2])->assertOk()->assertJsonPath('data.status', 'archived');
        $this->patchJson('/api/v1/classes/'.$id, ['name' => 'No', 'expectedVersion' => 3])->assertConflict();
        $this->assertDatabaseHas('class_invites', ['class_id' => $id, 'enabled' => false]);
    }

    public function test_pagination_and_sort_reject_invalid_values_and_empty_page_is_consistent(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->actingAs($teacher);
        foreach (['pageSize=101', 'pageSize=0', 'pageNumber=0', 'sort=password'] as $query) {
            $this->getJson('/api/v1/classes?'.$query)->assertUnprocessable();
        }
        $this->getJson('/api/v1/classes?pageNumber=999')->assertOk()->assertJsonPath('data.totalItems', 0)->assertJsonPath('data.totalPages', 0)->assertJsonCount(0, 'data.items');
    }

    public function test_catalogs_are_owned_and_class_references_cannot_cross_owners(): void
    {
        $owner = User::factory()->create(['role' => 'teacher']);
        $other = User::factory()->create(['role' => 'teacher']);
        $semester = $this->actingAs($owner)->postJson('/api/v1/semesters', ['name' => 'Term', 'startDate' => '2026-09-01', 'endDate' => '2026-12-31'])->assertCreated()->json('data.id');
        $course = $this->postJson('/api/v1/courses', ['name' => 'Math', 'code' => 'MATH'])->assertCreated()->json('data.id');
        $id = $this->postJson('/api/v1/classes', ['name' => 'Math class', 'semesterId' => $semester, 'courseId' => $course])->assertCreated()->json('data.class.id');
        $this->actingAs($other)->getJson('/api/v1/semesters')->assertOk()->assertJsonPath('data.totalItems', 0);
        $this->getJson('/api/v1/courses/'.$course)->assertNotFound();
        $this->postJson('/api/v1/classes', ['name' => 'Wrong refs', 'semesterId' => $semester, 'courseId' => $course])->assertUnprocessable();
        $this->patchJson('/api/v1/semesters/'.$semester, ['name' => 'Stolen', 'startDate' => '2026-09-01', 'endDate' => '2026-12-31', 'expectedVersion' => 1])->assertNotFound();
        $this->assertDatabaseHas('classes', ['id' => $id, 'owner_id' => $owner->id]);
    }

    public function test_catalog_dates_unique_codes_versions_and_delete_in_use_are_enforced(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->actingAs($teacher)->postJson('/api/v1/semesters', ['name' => 'Bad', 'startDate' => '2026-12-31', 'endDate' => '2026-09-01'])->assertUnprocessable();
        $course = $this->postJson('/api/v1/courses', ['name' => 'Math', 'code' => 'MATH'])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/courses', ['name' => 'Math 2', 'code' => 'MATH'])->assertUnprocessable();
        $this->patchJson('/api/v1/courses/'.$course, ['name' => 'Renamed', 'code' => 'MATH', 'expectedVersion' => 1])->assertOk()->assertJsonPath('data.version', 2);
        $this->deleteJson('/api/v1/courses/'.$course, ['expectedVersion' => 1])->assertConflict();
        $this->postJson('/api/v1/classes', ['name' => 'Used', 'courseId' => $course])->assertCreated();
        $this->deleteJson('/api/v1/courses/'.$course, ['expectedVersion' => 2])->assertConflict()->assertJsonPath('code', 'RESOURCE_IN_USE');
    }
}
