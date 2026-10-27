<?php

use App\Http\Controllers\Api\V1\AssignmentController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClassroomController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\EnrollmentController;
use App\Http\Controllers\Api\V1\FileController;
use App\Http\Controllers\Api\V1\GradeController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PasswordController;
use App\Http\Controllers\Api\V1\ScheduleController;
use App\Http\Controllers\Api\V1\SemesterController;
use App\Http\Controllers\Api\V1\SubmissionController;
use App\Http\Controllers\Api\V1\VerificationController;
use Illuminate\Support\Facades\Route;

// All first-party JSON endpoints use cookie sessions and CSRF, including login.
Route::prefix('v1')->middleware('web')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('auth/forgot-password', [PasswordController::class, 'forgot'])->middleware('throttle:recovery');
    Route::post('auth/reset-password', [PasswordController::class, 'reset'])->middleware('throttle:recovery');

    Route::middleware(['auth:sanctum', 'account.active'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('me', [MeController::class, 'show']);
        Route::patch('me/profile', [MeController::class, 'update']);
        Route::post('me/change-password', [PasswordController::class, 'change'])->middleware('throttle:recovery');
        Route::post('auth/verification/resend', [VerificationController::class, 'resend'])->middleware('throttle:verification');
        Route::get('auth/verify-email/{id}/{hash}', [VerificationController::class, 'verify'])->middleware(['signed', 'throttle:verification'])->name('verification.verify');
        Route::middleware('email.verified')->group(function () {
            Route::get('classes/{classroom}/assignments', [AssignmentController::class, 'index']);
            Route::post('classes/{classroom}/assignments', [AssignmentController::class, 'store']);
            Route::get('classes/{classroom}/assignments/{assignmentId}', [AssignmentController::class, 'show']);
            Route::patch('classes/{classroom}/assignments/{assignmentId}', [AssignmentController::class, 'update']);
            Route::post('classes/{classroom}/assignments/{assignmentId}/publish', [AssignmentController::class, 'publish']);
            Route::post('classes/{classroom}/assignments/{assignmentId}/close', [AssignmentController::class, 'close']);
            Route::post('classes/{classroom}/files', [FileController::class, 'upload'])->middleware('throttle:upload');
            Route::get('files/{fileId}/download', [FileController::class, 'download']);
            Route::delete('files/{fileId}', [FileController::class, 'destroy']);
            Route::post('classes/{classroom}/assignments/{assignmentId}/submissions', [SubmissionController::class, 'store'])->middleware('throttle:submit');
            Route::get('classes/{classroom}/assignments/{assignmentId}/submissions', [SubmissionController::class, 'index']);
            Route::get('me/submissions', [SubmissionController::class, 'mine']);
            Route::get('submissions/{submissionId}', [SubmissionController::class, 'show']);
            Route::get('submissions/{submissionId}/grade-events', [SubmissionController::class, 'gradeEvents']);
            Route::put('submission-revisions/{revisionId}/grade', [GradeController::class, 'save']);
            Route::get('submission-revisions/{revisionId}/grade', [GradeController::class, 'show']);
            Route::post('grades/{gradeId}/publish', [GradeController::class, 'publish']);
            Route::get('classes/{classroom}/grades', [GradeController::class, 'index']);
            Route::get('classes/{classroom}/grade-average', [GradeController::class, 'average']);
            Route::get('classes/{classroom}/gradebook', [GradeController::class, 'gradebook']);
            Route::get('calendar', [ScheduleController::class, 'calendar']);
            Route::get('classes/{classroom}/sessions', [ScheduleController::class, 'index']);
            Route::post('classes/{classroom}/sessions', [ScheduleController::class, 'store']);
            Route::get('classes/{classroom}/sessions/{sessionId}', [ScheduleController::class, 'show']);
            Route::patch('classes/{classroom}/sessions/{sessionId}', [ScheduleController::class, 'update']);
            Route::post('classes/{classroom}/sessions/{sessionId}/cancel', [ScheduleController::class, 'cancel']);
            Route::post('classes/{classroom}/session-series/preview', [ScheduleController::class, 'preview']);
            Route::post('classes/{classroom}/session-series', [ScheduleController::class, 'series']);
            Route::post('classes/{classroom}/sessions/{sessionId}/attendance-windows', [AttendanceController::class, 'open']);
            Route::get('classes/{classroom}/sessions/{sessionId}/attendance-window', [AttendanceController::class, 'window']);
            Route::get('attendance-windows/{windowId}/qr', [AttendanceController::class, 'qr']);
            Route::get('attendance-windows/{windowId}/roster', [AttendanceController::class, 'roster']);
            Route::post('attendance-windows/{windowId}/close', [AttendanceController::class, 'close']);
            Route::post('attendance/check-ins', [AttendanceController::class, 'checkIn'])->middleware('throttle:check-in');
            Route::get('classes/{classroom}/attendance', [AttendanceController::class, 'index']);
            Route::get('classes/{classroom}/attendance-summary', [AttendanceController::class, 'summary']);
            Route::get('classes/{classroom}/attendance/{recordId}/audit', [AttendanceController::class, 'audit']);
            Route::put('classes/{classroom}/sessions/{sessionId}/attendance/{studentId}', [AttendanceController::class, 'correct']);
            Route::get('me/attendance', [AttendanceController::class, 'mine']);
            Route::apiResource('semesters', SemesterController::class);
            Route::apiResource('courses', CourseController::class);
            Route::get('classes', [ClassroomController::class, 'index']);
            Route::post('classes', [ClassroomController::class, 'store']);
            Route::get('classes/{classroom}', [ClassroomController::class, 'show']);
            Route::patch('classes/{classroom}', [ClassroomController::class, 'update']);
            Route::post('classes/{classroom}/archive', [ClassroomController::class, 'archive']);
            Route::post('classes/{classroom}/invite-code/rotate', [ClassroomController::class, 'rotate']);
            Route::post('classes/{classroom}/invite-code/disable', [ClassroomController::class, 'disable']);
            Route::post('join-requests', [EnrollmentController::class, 'join'])->middleware('throttle:join');
            Route::get('me/join-requests', [EnrollmentController::class, 'mine']);
            Route::post('join-requests/{membershipId}/cancel', [EnrollmentController::class, 'cancel']);
            Route::get('classes/{classroom}/join-requests', [EnrollmentController::class, 'requests']);
            Route::get('classes/{classroom}/members', [EnrollmentController::class, 'members']);
            foreach (['approve', 'reject', 'allow-rejoin'] as $operation) {
                Route::post('classes/{classroom}/join-requests/{membershipId}/'.$operation, [EnrollmentController::class, 'ownerChange'])->defaults('operation', $operation);
            }
            Route::post('classes/{classroom}/members/{studentId}/remove', [EnrollmentController::class, 'remove']);
            Route::post('classes/{classroom}/leave', [EnrollmentController::class, 'leave']);
            Route::get('notifications', [NotificationController::class, 'index']);
            Route::get('notifications/unread-count', [NotificationController::class, 'count']);
            Route::put('notifications/{notificationId}/read', [NotificationController::class, 'read']);
        });
    });
});
