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

    public function getStudentsByClass($classId)
    {
        $students = DB::table('students')
            ->where('class_id', $classId)
            ->orderBy('full_name', 'asc')
            ->get();

        $result = [];
        foreach ($students as $s) {
            $hasFace = DB::table('face_descriptors')->where('student_id', $s->id)->exists();
            $result[] = [
                'id'           => $s->id,
                'class_id'     => $s->class_id,
                'nisn'         => $s->nisn,
                'full_name'    => $s->full_name,
                'gender'       => $s->gender,
                'parent_phone' => $s->parent_phone,
                'has_face'     => $hasFace
            ];
        }

        return response()->json($result);
    }

    public function downloadTemplate()
    {
        $filename = "template_siswa_madrasah.csv";
        $headers = [
            "Content-Type"        => "text/csv; charset=utf-8",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
            "Pragma"              => "no-cache",
            "Expires"             => "0"
        ];

        $callback = function() {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['nisn', 'full_name', 'gender', 'parent_phone'], ';');
            fputcsv($handle, ['202603', 'Muhammad Zaki', 'L', '081234567890'], ';');
            fputcsv($handle, ['202604', 'Siti Nur Aisyah', 'P', '085712345678'], ';');
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importCsv(Request $request)
    {
        $request->validate([
            'class_id' => 'required',
            'file_csv' => 'required|file'
        ]);

        $file = $request->file('file_csv');
        $handle = fopen($file->getRealPath(), 'r');

        $firstLine = fgets($handle);
        $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
        rewind($handle);

        $rowNumber = 0;
        $imported = 0;
        $classId = $request->class_id;

        while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
            $rowNumber++;
            if ($rowNumber === 1) continue;

            $nisn = trim($data[0] ?? '');
            $fullName = trim($data[1] ?? '');
            $gender = strtoupper(trim($data[2] ?? 'L'));
            $phone = trim($data[3] ?? '');

            if (empty($nisn) || empty($fullName)) continue;

            $exists = DB::table('students')->where('nisn', $nisn)->first();
            if (!$exists) {
                DB::table('students')->insert([
                    'class_id'     => $classId,
                    'nisn'         => $nisn,
                    'full_name'    => $fullName,
                    'gender'       => in_array($gender, ['L', 'P']) ? $gender : 'L',
                    'parent_phone' => $phone,
                    'created_at'   => now(),
                ]);
                $imported++;
            }
        }
        fclose($handle);

        return response()->json([
            'success' => true,
            'message' => "Alhamdulillah, berhasil mengimpor {$imported} data siswa baru!"
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_id'     => 'required',
            'nisn'         => 'required',
            'full_name'    => 'required|string|max:100',
            'gender'       => 'required|in:L,P',
            'descriptors'  => 'required|array|min:3'
        ]);

        DB::beginTransaction();
        try {
            $studentId = $request->student_id;
            if (!$studentId) {
                $student = DB::table('students')->where('nisn', $request->nisn)->first();
                if ($student) {
                    $studentId = $student->id;
                } else {
                    $studentId = DB::table('students')->insertGetId([
                        'class_id'     => $request->class_id,
                        'nisn'         => $request->nisn,
                        'full_name'    => $request->full_name,
                        'gender'       => $request->gender,
                        'parent_phone' => $request->parent_phone,
                        'created_at'   => now(),
                    ]);
                }
            }

            // Ganti 3 pose biometrik baru
            DB::table('face_descriptors')->where('student_id', $studentId)->delete();

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
                'success'    => true, 
                'student_id' => $studentId,
                'message'    => "Biometrik wajah {$request->full_name} berhasil disimpan!"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false, 
                'message' => 'Gagal menyimpan: ' . $e->getMessage()
            ], 500);
        }
    }

    // 1. EDIT BIODATA SISWA
    public function updateStudent(Request $request)
    {
        $request->validate([
            'id'        => 'required',
            'nisn'      => 'required',
            'full_name' => 'required',
            'gender'    => 'required|in:L,P'
        ]);

        DB::table('students')->where('id', $request->id)->update([
            'nisn'         => $request->nisn,
            'full_name'    => $request->full_name,
            'gender'       => $request->gender,
            'parent_phone' => $request->parent_phone,
        ]);

        return response()->json(['success' => true, 'message' => 'Biodata siswa berhasil diperbarui!']);
    }

    // 2. HAPUS SISWA & DATA BIOMETRIKNYA
    public function deleteStudent($id)
    {
        DB::beginTransaction();
        try {
            DB::table('face_descriptors')->where('student_id', $id)->delete();
            DB::table('attendances')->where('student_id', $id)->delete();
            DB::table('students')->where('id', $id)->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Data siswa berhasil dihapus bersih!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal menghapus siswa: ' . $e->getMessage()], 500);
        }
    }

    // 3. RESET FOTO WAJAH SAJA (Jika ingin rekam ulang)
    public function resetFace($id)
    {
        DB::table('face_descriptors')->where('student_id', $id)->delete();
        return response()->json(['success' => true, 'message' => 'Data biometrik dihapus. Siswa siap difoto ulang!']);
    }
}