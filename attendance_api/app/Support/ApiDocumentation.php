<?php

namespace App\Support;

use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\RequestBodyObject;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\NumberType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;

/** Describes the shared envelope that static analysis cannot infer from generic paginators. */
final class ApiDocumentation
{
    public static function transform(OpenApi $api): void
    {
        $api->info->title = 'AttendanceWeb API';
        $api->info->version = '0.3.0';
        $api->info->description = 'Endpoints: accounts, classrooms, enrollment, calendar/weekly series, QR attendance, corrections/audit, assignments/private uploads/submissions/published grades and inbox. Local validation evidence and limits: docs/ASSIGNMENTS-GRADES.md and docs/evidence/learning-validation.md. Cookie session + CSRF: GET /sanctum/csrf-cookie then send X-XSRF-TOKEN and credentials. React frontend is implemented in atd_react; quizzes and chat are not implemented.';
        $api->secure(SecurityScheme::apiKey('cookie', config('session.cookie'))->as('sessionCookie'));
        $api->components->addSecurityScheme('csrfHeader', SecurityScheme::apiKey('header', 'X-XSRF-TOKEN')->as('csrfHeader'));
        $pageResources = ['classroom.index' => 'ClassroomResource', 'semester.index' => 'SemesterResource', 'course.index' => 'CourseResource',
            'enrollment.mine' => 'MembershipResource', 'enrollment.requests' => 'MembershipResource', 'enrollment.members' => 'MembershipResource',
            'schedule.calendar' => 'SessionResource', 'schedule.index' => 'SessionResource', 'attendance.index' => 'AttendanceRecordResource', 'attendance.mine' => 'AttendanceRecordResource',
            'assignment.index' => 'AssignmentResource', 'submission.index' => 'SubmissionResource', 'submission.mine' => 'SubmissionResource', 'grade.index' => 'GradePublicationResource'];
        foreach ($api->paths as $path) {
            foreach ($path->operations as $operation) {
                $public = in_array($operation->operationId, ['auth.register', 'auth.login', 'password.forgot', 'password.reset'], true);
                $mutation = ! in_array(strtolower($operation->method), ['get', 'head', 'options'], true);
                $requirements = $public ? [] : ['sessionCookie' => []];
                if ($mutation) {
                    $requirements['csrfHeader'] = [];
                }
                $operation->security = $requirements ? [new SecurityRequirement($requirements)] : [];
                if ($operation->operationId === 'submission.store') {
                    $operation->addParameters([Parameter::make('Idempotency-Key', 'header')->required(true)->setSchema(Schema::fromType((new StringType)->format('uuid')))]);
                }
                if ($operation->requestBodyObject instanceof RequestBodyObject && isset($operation->requestBodyObject->content['application/json'])) {
                    $body = $operation->requestBodyObject->getContent('application/json')->schema;
                    $body = $body instanceof Reference ? $body->resolve() : $body;
                    if ($body instanceof Schema && $body->type instanceof ObjectType) {
                        $type = $body->type->clone();
                        $prohibited = ['ownerId', 'teacherId', 'userId', 'studentId', 'accountStatus', 'emailVerifiedAt', 'authorId', 'maxScore'];
                        if ($operation->operationId === 'submission.store') {
                            $prohibited = [...$prohibited, 'score', 'idempotencyKey'];
                        }
                        if (str_starts_with((string) $operation->operationId, 'assignment.')) {
                            $prohibited = [...$prohibited, 'state', 'classId'];
                        }
                        if ($operation->operationId === 'grade.save') {
                            $prohibited = [...$prohibited, 'state', 'publishedAt'];
                            $type->properties['score'] = (new NumberType)->setMin(0)->setMax(10)->setDescription('Score on scale 10, at most two decimal places.');
                        }
                        if (str_starts_with((string) $operation->operationId, 'classroom.')) {
                            $prohibited[] = 'status';
                        }
                        if ($operation->operationId === 'me.update') {
                            $prohibited = [...$prohibited, 'role', 'email'];
                        }
                        foreach ($prohibited as $field) {
                            unset($type->properties[$field]);
                            $type->required = array_values(array_diff($type->required, [$field]));
                        }
                        if (in_array($operation->operationId, ['classroom.store', 'course.store', 'semester.store', 'schedule.store', 'assignment.store'], true)) {
                            unset($type->properties['expectedVersion']);
                            $type->required = array_values(array_diff($type->required, ['expectedVersion']));
                        }
                        if (in_array($operation->operationId, ['classroom.update', 'course.update', 'semester.update', 'schedule.update', 'assignment.update'], true)) {
                            $type->required = array_values(array_unique([...$type->required, 'expectedVersion']));
                            if ($operation->operationId === 'classroom.update') {
                                $type->required = ['expectedVersion'];
                            }
                        }
                        $operation->requestBodyObject->setContent('application/json', Schema::fromType($type));
                    }
                }
                foreach ($operation->responses ?? [] as $index => $response) {
                    if ($response instanceof Response && (int) $response->code >= 400) {
                        $operation->responses[$index] = self::errorResponse((int) $response->code);
                    } elseif ($response instanceof Reference && $response->referenceType === 'responses') {
                        $status = str_contains((string) $response->fullName, 'Validation') ? 422 : (str_contains((string) $response->fullName, 'Authentication') ? 401 : null);
                        if ($status) {
                            $operation->responses[$index] = self::errorResponse($status);
                        }
                    }
                    if (! $response instanceof Response || (int) $response->code !== 200 || ! isset($response->content['application/json'])) {
                        continue;
                    }
                    $schema = $response->getContent('application/json');
                    if (! $schema instanceof Schema || ! $schema->type instanceof ObjectType) {
                        continue;
                    }
                    $data = $schema->type->properties['data'] ?? null;
                    if ($data instanceof ObjectType && isset($data->properties['items'])) {
                        $item = isset($pageResources[$operation->operationId])
                            ? Reference::fromUri('#/components/schemas/'.$pageResources[$operation->operationId]) : new ObjectType;
                        $data->properties['items'] = (new ArrayType)->setItems($item);
                    }
                }
                if (! in_array(strtolower($operation->method), ['get', 'head', 'options'], true)) {
                    $operation->addResponse(self::errorResponse(419));
                }
                $operation->addResponse(self::errorResponse(409));
                $operation->addResponse(self::errorResponse(429));
            }
        }
    }

    private static function errorResponse(int $status): Response
    {
        $type = (new ObjectType)->addProperty('code', new StringType)->addProperty('message', new StringType)->addProperty('traceId', new StringType)->setRequired(['code', 'message', 'traceId']);
        if ($status === 422) {
            $type->addProperty('errors', new ObjectType);
        }

        return Response::make($status)->setDescription('API error envelope; see docs/15-api-contract.md for codes.')->setContent('application/json', Schema::fromType($type));
    }
}
