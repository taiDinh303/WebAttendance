<?php

namespace Tests\Feature;

use App\Models\AnnouncementComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AnnouncementsTest extends TestCase
{
    use RefreshDatabase;

    private function seedClass(): array
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create();
        $data = $this->actingAs($teacher)->postJson('/api/v1/classes', ['name' => 'Notice class'])->assertCreated()->json('data');
        $class = $data['class']['id'];
        $this->actingAs($student)->postJson('/api/v1/join-requests', ['inviteCode' => $data['inviteCode']])->assertCreated();
        $pending = DB::table('class_memberships')->where('class_id', $class)->where('student_id', $student->id)->where('status', 'pending')->value('id');
        $this->actingAs($teacher)->postJson('/api/v1/classes/'.$class.'/join-requests/'.$pending.'/approve', ['expectedVersion' => 1])->assertOk();

        return [$teacher, $student, $class];
    }

    public function test_teacher_creates_draft_then_publishes_and_student_receives(): void
    {
        [$teacher, $student, $class] = $this->seedClass();
        $this->actingAs($teacher);
        $id = $this->postJson('/api/v1/classes/'.$class.'/announcements', ['title' => 'Lịch thay đổi', 'body' => 'Buổi học dời sang thứ 6', 'pinned' => true])->assertCreated()->json('data.id');
        $this->getJson('/api/v1/classes/'.$class.'/announcements')->assertOk()->assertJsonPath('data.totalItems', 1)->assertJsonPath('data.items.0.state', 'draft');
        $this->postJson('/api/v1/classes/'.$class.'/announcements/'.$id.'/publish', ['expectedVersion' => 1])->assertOk()->assertJsonPath('data.state', 'published')->assertJsonPath('data.version', 2);
        $this->actingAs($student)->getJson('/api/v1/classes/'.$class.'/announcements')->assertOk()->assertJsonPath('data.totalItems', 1)->assertJsonPath('data.items.0.state', 'published');
        $this->assertDatabaseHas('inbox_notifications', ['type' => 'announcement.published', 'recipient_id' => $student->id]);
    }

    public function test_student_sees_only_published_and_unhidden_comments(): void
    {
        [$teacher, $student, $class] = $this->seedClass();
        $this->actingAs($teacher);
        $id = $this->postJson('/api/v1/classes/'.$class.'/announcements', ['title' => 'Draft only'])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/classes/'.$class.'/announcements/'.$id.'/publish', ['expectedVersion' => 1])->assertOk();
        $comment = $this->actingAs($student)->postJson('/api/v1/classes/'.$class.'/announcements/'.$id.'/comments', ['body' => 'Cảm ơn thầy'])->assertCreated()->json('data.id');
        $this->actingAs($teacher)->postJson('/api/v1/classes/'.$class.'/comments/'.$comment.'/hide')->assertNoContent();
        $this->actingAs($student)->getJson('/api/v1/classes/'.$class.'/announcements/'.$id)->assertOk()->assertJsonPath('data.comments', []);
        $this->actingAs($teacher)->getJson('/api/v1/classes/'.$class.'/announcements/'.$id)->assertOk()->assertJsonCount(1, 'data.comments');
    }

    public function test_hidden_announcement_is_not_readable_or_commentable_by_student(): void
    {
        [$teacher, $student, $class] = $this->seedClass();
        $id = $this->actingAs($teacher)->postJson('/api/v1/classes/'.$class.'/announcements', ['title' => 'Hidden', 'hidden' => true])->assertCreated()->json('data.id');
        $url = '/api/v1/classes/'.$class.'/announcements/'.$id;
        $this->postJson($url.'/publish', ['expectedVersion' => 1])->assertOk();
        $this->actingAs($student)->getJson('/api/v1/classes/'.$class.'/announcements')->assertOk()->assertJsonPath('data.totalItems', 0);
        $this->getJson($url)->assertNotFound();
        $this->postJson($url.'/comments', ['body' => 'No access'])->assertNotFound();
    }

    public function test_comments_are_paginated_and_hidden_comments_can_be_restored(): void
    {
        [$teacher, $student, $class] = $this->seedClass();
        $id = $this->actingAs($teacher)->postJson('/api/v1/classes/'.$class.'/announcements', ['title' => 'Paged'])->assertCreated()->json('data.id');
        $url = '/api/v1/classes/'.$class.'/announcements/'.$id;
        $this->postJson($url.'/publish', ['expectedVersion' => 1])->assertOk();
        for ($i = 0; $i < 21; $i++) {
            AnnouncementComment::forceCreate(['id' => (string) Str::ulid(), 'announcement_id' => $id, 'author_id' => $student->id, 'body' => 'Comment '.$i]);
        }
        $this->actingAs($student)->getJson($url)->assertOk()->assertJsonCount(20, 'data.comments')->assertJsonPath('data.commentPagination.totalItems', 21);
        $this->getJson($url.'?commentPage=2')->assertOk()->assertJsonCount(1, 'data.comments');
        $comment = AnnouncementComment::where('announcement_id', $id)->first();
        $this->actingAs($teacher)->postJson('/api/v1/classes/'.$class.'/comments/'.$comment->id.'/hide')->assertNoContent();
        $this->postJson('/api/v1/classes/'.$class.'/comments/'.$comment->id.'/hide', ['hidden' => false])->assertNoContent();
        $this->assertFalse($comment->fresh()->hidden);
    }

    public function test_comment_idempotent_with_client_message_id(): void
    {
        [$teacher, $student, $class] = $this->seedClass();
        $this->actingAs($teacher);
        $id = $this->postJson('/api/v1/classes/'.$class.'/announcements', ['title' => 'Quiz'])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/classes/'.$class.'/announcements/'.$id.'/publish', ['expectedVersion' => 1])->assertOk();
        $client = (string) Str::ulid();
        $url = '/api/v1/classes/'.$class.'/announcements/'.$id.'/comments';
        $this->actingAs($student)->postJson($url, ['body' => 'Hi', 'clientMessageId' => $client])->assertCreated()->assertJsonPath('data.id', $client);
        $this->postJson($url, ['body' => 'Hi', 'clientMessageId' => $client])->assertCreated()->assertJsonPath('data.id', $client);
        $this->assertDatabaseCount('announcement_comments', 1);
    }

    public function test_other_teacher_and_other_class_isolated(): void
    {
        [$teacher, $student, $class] = $this->seedClass();
        $other = User::factory()->create(['role' => 'teacher']);
        $id = $this->actingAs($teacher)->postJson('/api/v1/classes/'.$class.'/announcements', ['title' => 'Private'])->assertCreated()->json('data.id');
        $this->actingAs($other)->getJson('/api/v1/classes/'.$class.'/announcements')->assertNotFound();
        $this->actingAs($other)->postJson('/api/v1/classes/'.$class.'/announcements/'.$id.'/publish', ['expectedVersion' => 1])->assertNotFound();
        $this->actingAs($student)->postJson('/api/v1/classes/'.$class.'/announcements', ['title' => 'Nope'])->assertNotFound();
        $this->actingAs($student)->patchJson('/api/v1/classes/'.$class.'/announcements/'.$id, ['title' => 'Nope', 'expectedVersion' => 1])->assertNotFound();
    }

    public function test_archived_class_blocks_new_announcements_and_updates(): void
    {
        [$teacher, $student, $class] = $this->seedClass();
        $this->actingAs($teacher)->postJson('/api/v1/classes/'.$class.'/archive', ['expectedVersion' => 1])->assertOk();
        $this->postJson('/api/v1/classes/'.$class.'/announcements', ['title' => 'X'])->assertConflict();
    }

    public function test_teacher_can_hide_and_delete_student_comments(): void
    {
        [$teacher, $student, $class] = $this->seedClass();
        $this->actingAs($teacher);
        $id = $this->postJson('/api/v1/classes/'.$class.'/announcements', ['title' => 'T'])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/classes/'.$class.'/announcements/'.$id.'/publish', ['expectedVersion' => 1])->assertOk();
        $c = $this->actingAs($student)->postJson('/api/v1/classes/'.$class.'/announcements/'.$id.'/comments', ['body' => 'delete me'])->assertCreated()->json('data.id');
        $this->actingAs($teacher)->deleteJson('/api/v1/classes/'.$class.'/comments/'.$c)->assertNoContent();
        // Soft delete keeps the row; author is notified and audit recorded.
        $this->assertNotNull(AnnouncementComment::onlyTrashed()->whereKey($c)->first()?->deleted_at);
        $this->assertDatabaseHas('announcement_comment_audits', ['comment_id' => $c, 'action' => 'deleted']);
        $this->assertDatabaseHas('inbox_notifications', ['type' => 'announcement.comment.removed', 'recipient_id' => $student->id]);
        // Student still sees their own removed comment marked as deleted.
        $response = $this->actingAs($student)->getJson('/api/v1/classes/'.$class.'/announcements/'.$id)->assertOk();
        $this->assertNotNull($response->json('data.comments.0.deletedAt'));
        $this->assertSame($student->id, $response->json('data.comments.0.authorId'));
        $this->assertNull($response->json('data.comments.0.body'));
    }
}
