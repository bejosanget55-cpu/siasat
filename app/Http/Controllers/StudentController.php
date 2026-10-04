<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function index()
    {
        $classes = DB::table('classes')->get();
        return view('registrasi', compact('classes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_id'     => 'required',
            'nisn'         => 'required|unique:students,nisn',
            'full_name'    => 'required|string|max:100',
            'gender'       => 'required|in:L,P',
            'parent_phone' => 'nullable|string',
            'descriptors'  => 'required|array|min:3'
        ]);

        DB::beginTransaction();
        try {
            $studentId = DB::table('students')->insertGetId([
                'class_id'     => $request->class_id,
                'nisn'         => $request->nisn,
                'full_name'    => $request->full_name,
                'gender'       => $request->gender,
                'parent_phone' => $request->parent_phone,
                'created_at'   => now(),
            ]);

            $angles = ['front', 'left', 'right'];
            foreach ($request->descriptors as $index => $descriptor) {
                DB::table('face_descriptors')->insert([
                    'student_id'      => $studentId,
                    'photo_angle'     => $angles[$index] ?? 'front',
                    'descriptor_json' => json_encode($descriptor),
                    'created_at'      => now(),
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true, 
                'message' => 'Siswa dan 3 vektor biometrik berhasil disimpan!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false, 
                'message' => 'Gagal menyimpan: ' . $e->getMessage()
            ], 500);
        }
    }
}