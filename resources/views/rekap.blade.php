<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <title>Rekapitulasi Presensi Wajah Siswa</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen py-8 px-4">

    <div class="max-w-6xl mx-auto space-y-6">

        <!-- Header Navigasi -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-slate-800 p-5 rounded-2xl border border-slate-700 shadow-lg">
            <div>
                <h1 class="text-2xl font-black text-emerald-400">📊 REKAPITULASI PRESENSI</h1>
                <p class="text-xs text-slate-400 mt-1">Laporan Kehadiran, Integrasi WhatsApp & Ekspor Arsip Excel</p>
            </div>
            <div class="mt-4 md:mt-0 flex gap-2">
                <a href="/presensi" class="text-xs bg-emerald-600 hover:bg-emerald-500 px-3 py-2 rounded-lg font-bold transition flex items-center gap-1">
                    ⚡ Buka Scanner
                </a>
                <a href="/registrasi" class="text-xs bg-slate-700 hover:bg-slate-600 px-3 py-2 rounded-lg font-medium transition">
                    + Siswa Baru
                </a>
            </div>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="/rekap" class="bg-slate-800 p-4 rounded-xl border border-slate-700 flex flex-wrap gap-4 items-end">
            <div>
                <label class="text-xs text-slate-400 font-semibold block mb-1">TANGGAL:</label>
                <input type="date" name="date" value="<?php echo $date; ?>" class="bg-slate-700 border border-slate-600 text-sm rounded-lg px-3 py-2 focus:ring-emerald-500 text-white font-medium">
            </div>

            <div>
                <label class="text-xs text-slate-400 font-semibold block mb-1">KELAS:</label>
                <select name="class_id" class="bg-slate-700 border border-slate-600 text-sm rounded-lg px-3 py-2 focus:ring-emerald-500 text-white font-medium">
                    <option value="all" <?php echo ($classId === 'all') ? 'selected' : ''; ?>>🌟 Semua Kelas</option>
                    <?php if (!empty($classes)): ?>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo $c->id; ?>" <?php echo ($classId == $c->id) ? 'selected' : ''; ?>><?php echo $c->class_name; ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label class="text-xs text-slate-400 font-semibold block mb-1">SESI AKTIVITAS:</label>
                <input type="text" name="activity_type" value="<?php echo $activityType; ?>" class="bg-slate-700 border border-slate-600 text-sm rounded-lg px-3 py-2 text-white font-medium">
            </div>

            <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                🔍 Tampilkan
            </button>
        </form>

        <!-- Kartu Ringkasan Metrik (Summary Cards) -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-slate-800 border border-slate-700 p-4 rounded-xl text-center">
                <span class="text-xs text-slate-400 font-semibold">TOTAL SISWA</span>
                <p class="text-2xl font-bold text-white mt-1"><?php echo $summary['total']; ?></p>
            </div>
            <div class="bg-emerald-950/40 border border-emerald-500/40 p-4 rounded-xl text-center">
                <span class="text-xs text-emerald-400 font-semibold">HADIR</span>
                <p class="text-2xl font-bold text-emerald-400 mt-1"><?php echo $summary['hadir']; ?></p>
            </div>
            <div class="bg-amber-950/40 border border-amber-500/40 p-4 rounded-xl text-center">
                <span class="text-xs text-amber-400 font-semibold">SAKIT</span>
                <p class="text-2xl font-bold text-amber-400 mt-1"><?php echo $summary['sakit']; ?></p>
            </div>
            <div class="bg-indigo-950/40 border border-indigo-500/40 p-4 rounded-xl text-center">
                <span class="text-xs text-indigo-400 font-semibold">IZIN</span>
                <p class="text-2xl font-bold text-indigo-400 mt-1"><?php echo $summary['izin']; ?></p>
            </div>
            <div class="bg-rose-950/40 border border-rose-500/40 p-4 rounded-xl text-center col-span-2 md:col-span-1">
                <span class="text-xs text-rose-400 font-semibold">ALPA / BELUM</span>
                <p class="text-2xl font-bold text-rose-400 mt-1"><?php echo $summary['alpa']; ?></p>
            </div>
        </div>

        <!-- Tombol Aksi Ekspor & WhatsApp -->
        <div class="flex flex-wrap gap-3 justify-end items-center">
            <!-- Tombol Kirim WhatsApp -->
            <a href="<?php echo $waLink; ?>" target="_blank" class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold px-4 py-2.5 rounded-xl shadow-lg transition flex items-center gap-2 text-sm">
                <span>📲 Kirim Rekap ke WhatsApp</span>
            </a>

            <!-- Tombol Unduh Excel -->
            <a href="/rekap/export?date=<?php echo $date; ?>&class_id=<?php echo $classId; ?>&activity_type=<?php echo urlencode($activityType); ?>" class="bg-blue-600 hover:bg-blue-500 text-white font-bold px-4 py-2.5 rounded-xl shadow-lg transition flex items-center gap-2 text-sm">
                <span>📥 Unduh Excel (.xls)</span>
            </a>
        </div>

        <!-- Tabel Detail Presensi Siswa -->
        <div class="bg-slate-800 rounded-2xl border border-slate-700 overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-900/70 text-xs uppercase font-bold text-slate-400 border-b border-slate-700">
                        <tr>
                            <th class="py-3 px-4 text-center w-12">No</th>
                            <th class="py-3 px-4">NISN</th>
                            <th class="py-3 px-4">Nama Siswa</th>
                            <th class="py-3 px-4 text-center">Kelas</th>
                            <th class="py-3 px-4 text-center">Waktu Masuk</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Ubah Cepat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/60">
                        <?php if (!empty($reportData) && count($reportData) > 0): ?>
                            <?php $no = 1; foreach ($reportData as $row): ?>
                                <tr class="hover:bg-slate-700/30 transition">
                                    <td class="py-3 px-4 text-center font-mono text-xs text-slate-400"><?php echo $no++; ?></td>
                                    <td class="py-3 px-4 font-mono text-xs"><?php echo $row->nisn; ?></td>
                                    <td class="py-3 px-4 font-semibold text-white"><?php echo $row->full_name; ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="text-xs bg-slate-700 px-2 py-0.5 rounded font-medium"><?php echo $row->class_name; ?></span>
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono text-xs text-emerald-300"><?php echo $row->time; ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <?php if ($row->status === 'Hadir'): ?>
                                            <span class="bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-xs px-2.5 py-1 rounded-full font-bold">Hadir</span>
                                        <?php elseif ($row->status === 'Sakit'): ?>
                                            <span class="bg-amber-500/20 text-amber-400 border border-amber-500/30 text-xs px-2.5 py-1 rounded-full font-bold">Sakit</span>
                                        <?php elseif ($row->status === 'Izin'): ?>
                                            <span class="bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 text-xs px-2.5 py-1 rounded-full font-bold">Izin</span>
                                        <?php else: ?>
                                            <span class="bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs px-2.5 py-1 rounded-full font-bold">Alpa</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <select onchange="updateStudentStatus(<?php echo $row->student_id; ?>, <?php echo $row->class_id; ?>, this.value)" class="bg-slate-700 border border-slate-600 text-xs rounded-lg px-2 py-1 focus:ring-emerald-500 text-white font-medium cursor-pointer">
                                            <option value="Hadir" <?php echo ($row->status === 'Hadir') ? 'selected' : ''; ?>>Set Hadir</option>
                                            <option value="Sakit" <?php echo ($row->status === 'Sakit') ? 'selected' : ''; ?>>Set Sakit</option>
                                            <option value="Izin" <?php echo ($row->status === 'Izin') ? 'selected' : ''; ?>>Set Izin</option>
                                            <option value="Alpa" <?php echo ($row->status === 'Alpa') ? 'selected' : ''; ?>>Set Alpa</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-8 text-slate-400 text-sm">
                                    Belum ada data siswa di kelas yang dipilih.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        // Fungsi Ubah Cepat Status Kehadiran Siswa
        async function updateStudentStatus(studentId, classId, newStatus) {
            const date = "<?php echo $date; ?>";
            const activityType = "<?php echo $activityType; ?>";

            try {
                const response = await fetch('/rekap/update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        student_id: studentId,
                        class_id: classId,
                        date: date,
                        activity_type: activityType,
                        status: newStatus
                    })
                });

                const result = await response.json();
                if (result.success) {
                    // Muat ulang halaman agar angka metrik langsung sinkron
                    window.location.reload();
                } else {
                    alert("Gagal memperbarui status presensi.");
                }
            } catch (err) {
                console.error(err);
                alert("Terjadi kesalahan jaringan.");
            }
        }
    </script>
</body>
</html>