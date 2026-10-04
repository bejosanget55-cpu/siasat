<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index()
    {
        $classes = DB::table('classes')->get();
        return view('presensi', compact('classes'));
    }

    // Mengambil data vektor: Bisa per kelas atau SEMUA KELAS sekaligus
    public function getDescriptors($classId = 'all')
    {
        $query = DB::table('students')
            ->join('classes', 'students.class_id', '=', 'classes.id')
            ->select('students.*', 'classes.class_name');

        if ($classId !== 'all' && !empty($classId)) {
            $query->where('students.class_id', $classId);
        }

        $students = $query->get();
        $data = [];

        foreach ($students as $student) {
            $descriptors = DB::table('face_descriptors')
                ->where('student_id', $student->id)
                ->pluck('descriptor_json');

            $formattedDescriptors = [];
            foreach ($descriptors as $desc) {
                $decoded = json_decode($desc, true);
                if (is_array($decoded)) {
                    $formattedDescriptors[] = $decoded;
                }
            }

            if (!empty($formattedDescriptors)) {
                $data[] = [
                    'student_id'   => $student->id,
                    'full_name'    => $student->full_name,
                    'class_name'   => $student->class_name,
                    'nisn'         => $student->nisn,
                    'descriptors'  => $formattedDescriptors
                ];
            }
        }

        return response()->json($data);
    }

    // Menyimpan presensi otomatis
    public function store(Request $request)
    {
        $request->validate([
            'student_id'    => 'required',
            'activity_type' => 'required|string'
        ]);

        $today = date('Y-m-d');
        $timeNow = date('H:i:s');

        // Cari data siswa & kelasnya otomatis
        $student = DB::table('students')
            ->join('classes', 'students.class_id', '=', 'classes.id')
            ->where('students.id', $request->student_id)
            ->select('students.*', 'classes.class_name')
            ->first();

        if (!$student) {
            return response()->json(['status' => 'error', 'message' => 'Data siswa tidak ditemukan']);
        }

        $classId = $student->class_id;

        // Ambil ID Akun Petugas/Guru
        $user = DB::table('users')->first();
        $userId = $user ? $user->id : 1;

        // Cek apakah sudah absen hari ini
        $existing = DB::table('attendances')
            ->where('student_id', $request->student_id)
            ->where('date', $today)
            ->where('activity_type', $request->activity_type)
            ->first();

        if ($existing) {
            return response()->json([
                'status'  => 'already',
                'message' => "{$student->full_name} ({$student->class_name}) sudah absen pukul {$existing->time}."
            ]);
        }

        // Simpan log presensi
        DB::table('attendances')->insert([
            'student_id'    => $request->student_id,
            'class_id'      => $classId,
            'user_id'       => $userId,
            'date'          => $today,
            'time'          => $timeNow,
            'activity_type' => $request->activity_type,
            'status'        => 'Hadir',
            'note'          => 'Presensi Wajah AI',
            'created_at'    => now(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => "Hadir: {$student->full_name}",
            'data'    => [
                'name'       => $student->full_name,
                'class_name' => $student->class_name,
                'time'       => $timeNow
            ]
        ]);
    }
}