<?php

namespace App\Console\Commands;

use App\Actions\Accounts\SeedVisualDemo;
use App\Models\ClassMembership;
use App\Models\Classroom;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class SeedVisualDemoCommand extends Command
{
    protected $signature = 'demo:visual';

    protected $description = 'Populate the approved synthetic 3 teacher / 9 class / 180 student cloud dataset';

    public function handle(SeedVisualDemo $seed): int
    {
        $result = $seed->handle();
        $sizes = array_map(fn ($id) => Classroom::where('owner_id', $id)->whereIn('id', $result['classIds'])->get()->map(fn ($class) => ClassMembership::where('class_id', $class->id)->where('status', 'active')->count())->sort()->values()->all(), $result['teacherIds']);
        $records = DB::table('attendance_records')->whereIn('session_id', DB::table('class_sessions')->select('id')->whereIn('class_id', $result['classIds']))->count();
        $this->info(json_encode(['created' => $result['created'], 'sizesByTeacher' => $sizes, 'attendanceRecords' => $records, 'teacherCount' => count($result['teacherIds']), 'classCount' => count($result['classIds']), 'studentCount' => count($result['studentIds'])], JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}
