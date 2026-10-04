<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecapController extends Controller
{
    // Menampilkan halaman rekapitulasi presensi
    public function index(Request $request)
    {
        $classes = DB::table('classes')->get();
        $date = $request->get('date', date('Y-m-d'));
        $classId = $request->get('class_id', 'all');
        $activityType = $request->get('activity_type', 'Presensi Pagi');

        // Ambil daftar siswa
        $studentsQuery = DB::table('students')
            ->join('classes', 'students.class_id', '=', 'classes.id')
            ->select('students.*', 'classes.class_name');

        if ($classId !== 'all' && !empty($classId)) {
            $studentsQuery->where('students.class_id', $classId);
        }

        $students = $studentsQuery->orderBy('students.full_name', 'asc')->get();

        // Ambil riwayat presensi di hari & sesi terkait
        $attendances = DB::table('attendances')
            ->where('date', $date)
            ->where('activity_type', $activityType)
            ->get()
            ->keyBy('student_id');

        $summary = [
            'total' => count($students),
            'hadir' => 0,
            'sakit' => 0,
            'izin'  => 0,
            'alpa'  => 0,
        ];

        $reportData = [];
        $absentList = [];

        foreach ($students as $s) {
            $att = $attendances->get($s->id);
            $status = $att ? $att->status : 'Alpa';
            $time = $att ? $att->time : '-';
            $note = $att ? $att->note : '';

            if ($status === 'Hadir') {
                $summary['hadir']++;
            } elseif ($status === 'Sakit') {
                $summary['sakit']++;
                $absentList[] = "{$s->full_name} ({$s->class_name}) - Sakit";
            } elseif ($status === 'Izin') {
                $summary['izin']++;
                $absentList[] = "{$s->full_name} ({$s->class_name}) - Izin";
            } else {
                $summary['alpa']++;
                $absentList[] = "{$s->full_name} ({$s->class_name}) - Belum Presensi/Alpa";
            }

            $reportData[] = (object)[
                'student_id' => $s->id,
                'nisn'       => $s->nisn,
                'full_name'  => $s->full_name,
                'class_name' => $s->class_name,
                'class_id'   => $s->class_id,
                'status'     => $status,
                'time'       => $time,
                'note'       => $note,
            ];
        }

        // Susun teks pesan WhatsApp otomatis
        $classNameLabel = 'Semua Kelas';
        if ($classId !== 'all') {
            $foundClass = $classes->firstWhere('id', $classId);
            if ($foundClass) $classNameLabel = "Kelas {$foundClass->class_name}";
        }

        $waText = "*LAPORAN PRESENSI SISWA*\n";
        $waText .= "------------------------------------\n";
        $waText .= "📅 Tanggal : {$date}\n";
        $waText .= "📌 Sesi     : {$activityType}\n";
        $waText .= "🏫 Rombel   : {$classNameLabel}\n";
        $waText .= "------------------------------------\n";
        $waText .= "👥 Total Siswa : {$summary['total']}\n";
        $waText .= "✅ Hadir       : {$summary['hadir']}\n";
        $waText .= "🤒 Sakit       : {$summary['sakit']}\n";
        $waText .= "✉️ Izin        : {$summary['izin']}\n";
        $waText .= "❌ Alpa/Belum  : {$summary['alpa']}\n";
        $waText .= "------------------------------------\n";

        if (!empty($absentList)) {
            $waText .= "*Catatan Tidak Hadir:*\n";
            foreach ($absentList as $idx => $abs) {
                $nomor = $idx + 1;
                $waText .= "{$nomor}. {$abs}\n";
            }
        } else {
            $waText .= "✨ Alhamdulillah seluruh siswa hadir lengkap!\n";
        }
        $waText .= "------------------------------------\n";
        $waText .= "_Dicatat otomatis via Presensi Biometrik Wajah Cerdas_";

        $waLink = "https://api.whatsapp.com/send?text=" . urlencode($waText);

        return view('rekap', compact('classes', 'date', 'classId', 'activityType', 'reportData', 'summary', 'waLink'));
    }

    // Ubah status cepat secara manual (Sakit / Izin / Alpa / Hadir)
    public function updateStatus(Request $request)
    {
        $request->validate([
            'student_id'    => 'required',
            'class_id'      => 'required',
            'date'          => 'required',
            'activity_type' => 'required',
            'status'        => 'required|in:Hadir,Sakit,Izin,Alpa',
        ]);

        $user = DB::table('users')->first();
        $userId = $user ? $user->id : 1;

        DB::table('attendances')->updateOrInsert(
            [
                'student_id'    => $request->student_id,
                'date'          => $request->date,
                'activity_type' => $request->activity_type,
            ],
            [
                'class_id'   => $request->class_id,
                'user_id'    => $userId,
                'time'       => date('H:i:s'),
                'status'     => $request->status,
                'note'       => $request->note ?? 'Update Manual Guru',
                'created_at' => now(),
            ]
        );

        return response()->json(['success' => true]);
    }

    // Ekspor data ke format file Excel (.xls)
    public function exportExcel(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));
        $classId = $request->get('class_id', 'all');
        $activityType = $request->get('activity_type', 'Presensi Pagi');

        $studentsQuery = DB::table('students')
            ->join('classes', 'students.class_id', '=', 'classes.id')
            ->select('students.*', 'classes.class_name');

        if ($classId !== 'all' && !empty($classId)) {
            $studentsQuery->where('students.class_id', $classId);
        }

        $students = $studentsQuery->orderBy('students.full_name', 'asc')->get();

        $attendances = DB::table('attendances')
            ->where('date', $date)
            ->where('activity_type', $activityType)
            ->get()
            ->keyBy('student_id');

        $filename = "Rekap_Presensi_{$date}_{$activityType}.xls";

        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo "<table border='1'>";
        echo "<tr><th colspan='6' style='background:#10b981; color:#fff; font-size:16px; padding:10px;'>REKAPITULASI PRESENSI SISWA</th></tr>";
        echo "<tr><td colspan='6'><strong>Tanggal:</strong> {$date} | <strong>Sesi:</strong> {$activityType}</td></tr>";
        echo "<tr>
                <th style='background:#e2e8f0;'>No</th>
                <th style='background:#e2e8f0;'>NISN</th>
                <th style='background:#e2e8f0;'>Nama Lengkap</th>
                <th style='background:#e2e8f0;'>Kelas</th>
                <th style='background:#e2e8f0;'>Status</th>
                <th style='background:#e2e8f0;'>Waktu Masuk</th>
              </tr>";

        $no = 1;
        foreach ($students as $s) {
            $att = $attendances->get($s->id);
            $status = $att ? $att->status : 'Alpa';
            $time = $att ? $att->time : '-';

            $color = match($status) {
                'Hadir' => '#dcfce7',
                'Sakit' => '#fef3c7',
                'Izin'  => '#e0e7ff',
                default => '#fee2e2'
            };

            echo "<tr>
                    <td align='center'>{$no}</td>
                    <td>'{$s->nisn}</td>
                    <td>{$s->full_name}</td>
                    <td align='center'>{$s->class_name}</td>
                    <td align='center' style='background:{$color}; font-weight:bold;'>{$status}</td>
                    <td align='center'>{$time}</td>
                  </tr>";
            $no++;
        }
        echo "</table>";
        exit;
    }
}