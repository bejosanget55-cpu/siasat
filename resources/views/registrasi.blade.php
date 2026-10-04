<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <title>Perekaman Biometrik & Manajemen Siswa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen py-6 px-4">

    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Header Navigasi -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-slate-800 p-5 rounded-2xl border border-slate-700 shadow-lg gap-4">
            <div>
                <h1 class="text-2xl font-black text-emerald-400">⚡ MANAJEMEN SISWA & PEREKAMAN WAJAH</h1>
                <p class="text-xs text-slate-400 mt-1">Perekaman Wajah AI, Edit Biodata, dan Hapus Data Siswa Terpadu</p>
            </div>
            
            <div class="flex flex-wrap gap-2">
                <a href="/registrasi/template" class="text-xs bg-slate-700 hover:bg-slate-600 text-slate-200 px-3.5 py-2 rounded-lg font-semibold border border-slate-600 transition flex items-center gap-1">
                    📥 Unduh Template CSV
                </a>
                
                <button onclick="toggleImportModal()" class="text-xs bg-indigo-600 hover:bg-indigo-500 text-white px-3.5 py-2 rounded-lg font-bold transition flex items-center gap-1 shadow">
                    📤 Impor Data Siswa
                </button>

                <a href="/presensi" class="text-xs bg-emerald-600 hover:bg-emerald-500 text-white px-3.5 py-2 rounded-lg font-bold transition flex items-center gap-1">
                    ⚡ Ke Scanner Presensi
                </a>
            </div>
        </div>

        <!-- Layout 3 Kolom -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- Kolom 1 (Kiri - Lebar 5): Panel Daftar Siswa dengan Tombol Edit & Hapus -->
            <div class="lg:col-span-5 bg-slate-800 rounded-2xl border border-slate-700 p-5 flex flex-col h-[650px] shadow-lg">
                <div class="border-b border-slate-700 pb-3 mb-3">
                    <label class="text-xs text-slate-400 font-bold block mb-1">PILIH KELAS:</label>
                    <select id="class_select" onchange="loadStudentQueue()" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-sm text-emerald-300 font-bold focus:ring-emerald-500">
                        <?php if (!empty($classes)): ?>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo $c->id; ?>"><?php echo $c->class_name . ' (' . $c->academic_year . ')'; ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="flex justify-between items-center text-xs font-semibold text-slate-400 mb-2">
                    <span>DAFTAR NAMA SISWA</span>
                    <span id="queue-count" class="bg-slate-700 px-2 py-0.5 rounded text-[11px]">0 Siswa</span>
                </div>

                <!-- Daftar Siswa List Scrollable -->
                <div id="student-list" class="flex-1 overflow-y-auto space-y-2 pr-1 text-sm">
                    <p class="text-xs text-slate-500 text-center py-12">Memuat daftar siswa...</p>
                </div>
            </div>

            <!-- Kolom 2 & 3 (Kanan - Lebar 7): Kamera Perekam & Formulir Wajah -->
            <div class="lg:col-span-7 bg-slate-800 rounded-2xl border border-slate-700 p-6 shadow-lg flex flex-col justify-between">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Biodata Siswa Terpilih -->
                    <div class="space-y-3">
                        <div class="flex justify-between items-center border-b border-slate-700 pb-2">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-white">Siswa Terpilih</h2>
                            <span id="badge-status-siswa" class="text-[10px] font-bold px-2 py-0.5 rounded bg-slate-700 text-slate-400">PILIH DARI DAFTAR</span>
                        </div>

                        <input type="hidden" id="student_id">

                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">NISN</label>
                            <input type="text" id="nisn" readonly class="w-full bg-slate-700/60 border border-slate-600 rounded-lg px-3 py-2 text-sm font-mono text-slate-300">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">Nama Siswa</label>
                            <input type="text" id="full_name" readonly class="w-full bg-slate-700/60 border border-slate-600 rounded-lg px-3 py-2 text-sm font-bold text-emerald-300">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1">Jenis Kelamin</label>
                                <input type="text" id="gender_label" readonly class="w-full bg-slate-700/60 border border-slate-600 rounded-lg px-3 py-2 text-sm text-slate-300">
                                <input type="hidden" id="gender">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1">No WA Wali</label>
                                <input type="text" id="parent_phone" readonly class="w-full bg-slate-700/60 border border-slate-600 rounded-lg px-3 py-2 text-sm font-mono text-slate-300">
                            </div>
                        </div>

                        <!-- Opsi Reset Foto -->
                        <div id="btn-reset-wrap" class="hidden pt-1">
                            <button onclick="resetCurrentFace()" class="text-[11px] text-amber-400 hover:text-amber-300 underline font-semibold">
                                🔄 Foto ulang / Reset biometrik siswa ini
                            </button>
                        </div>

                        <!-- Tombol Simpan Biometrik Final -->
                        <div class="pt-4">
                            <button id="btn-save" onclick="saveStudentBiometric()" disabled class="w-full bg-slate-700 text-slate-500 font-bold py-3 px-4 rounded-xl shadow-lg transition duration-200">
                                💾 Simpan 3 Vektor Wajah
                            </button>
                            <div id="save-status" class="text-xs text-center font-medium mt-2"></div>
                        </div>
                    </div>

                    <!-- Kamera & 3 Pose Pengambilan Wajah -->
                    <div class="flex flex-col items-center">
                        <div class="border-b border-slate-700 pb-2 mb-3 w-full text-center">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-indigo-400">Kamera Perekam Wajah</h2>
                        </div>
                        
                        <div class="relative w-[280px] h-[210px] bg-black rounded-xl overflow-hidden border-2 border-slate-600 shadow-md">
                            <video id="video" width="280" height="210" autoplay muted playsinline class="transform -scale-x-100 object-cover"></video>
                        </div>

                        <div id="ai-loading" class="text-xs text-amber-400 mt-2 font-medium animate-pulse text-center">
                            Memuat model AI biometrik...
                        </div>

                        <!-- 3 Tombol Pengambilan Sudut -->
                        <div class="w-full mt-4 space-y-2">
                            <div class="text-xs font-bold text-slate-300 text-center">Ambil 3 Pose Wajah:</div>

                            <div class="grid grid-cols-3 gap-2 text-center text-xs">
                                <button id="btn-snap-0" onclick="captureAngle(0)" class="bg-indigo-600 hover:bg-indigo-500 py-2 px-1 rounded-lg font-bold border border-indigo-400/30 transition">
                                    1. Depan
                                </button>
                                <button id="btn-snap-1" onclick="captureAngle(1)" class="bg-indigo-600 hover:bg-indigo-500 py-2 px-1 rounded-lg font-bold border border-indigo-400/30 transition">
                                    2. Kiri 15°
                                </button>
                                <button id="btn-snap-2" onclick="captureAngle(2)" class="bg-indigo-600 hover:bg-indigo-500 py-2 px-1 rounded-lg font-bold border border-indigo-400/30 transition">
                                    3. Kanan 15°
                                </button>
                            </div>

                            <div class="flex justify-around text-[11px] mt-2 font-medium">
                                <span id="badge-0" class="text-slate-400">⚪ Depan: Belum</span>
                                <span id="badge-1" class="text-slate-400">⚪ Kiri: Belum</span>
                                <span id="badge-2" class="text-slate-400">⚪ Kanan: Belum</span>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- MODAL POPUP: EDIT DATA SISWA -->
    <div id="edit-modal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-slate-800 border border-slate-700 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
            <div class="flex justify-between items-center border-b border-slate-700 pb-3">
                <h3 class="text-lg font-bold text-white">✏️ Edit Biodata Siswa</h3>
                <button onclick="toggleEditModal()" class="text-slate-400 hover:text-white font-bold text-lg">&times;</button>
            </div>

            <input type="hidden" id="edit_id">

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">NISN</label>
                <input type="text" id="edit_nisn" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-sm text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Nama Lengkap Siswa</label>
                <input type="text" id="edit_name" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-sm text-white font-bold">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Jenis Kelamin</label>
                    <select id="edit_gender" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-sm text-white">
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">No WA Wali</label>
                    <input type="text" id="edit_phone" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-sm text-white">
                </div>
            </div>

            <div class="flex gap-2 pt-3">
                <button onclick="toggleEditModal()" class="flex-1 bg-slate-700 hover:bg-slate-600 py-2.5 rounded-xl text-xs font-semibold transition">Batal</button>
                <button onclick="saveEditStudent()" class="flex-1 bg-indigo-600 hover:bg-indigo-500 py-2.5 rounded-xl text-xs font-bold text-white transition">Simpan Perubahan</button>
            </div>
        </div>
    </div>

    <!-- MODAL POPUP: IMPOR CSV -->
    <div id="import-modal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-slate-800 border border-slate-700 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
            <div class="flex justify-between items-center border-b border-slate-700 pb-3">
                <h3 class="text-lg font-bold text-white">📤 Impor Data Siswa Kelas</h3>
                <button onclick="toggleImportModal()" class="text-slate-400 hover:text-white font-bold text-lg">&times;</button>
            </div>

            <p class="text-xs text-slate-400">
                Data baru akan otomatis ditambahkan ke kelas ini tanpa menghapus data siswa yang sudah ada.
            </p>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Pilih File CSV:</label>
                <input type="file" id="file_csv" accept=".csv" class="w-full text-xs text-slate-300 bg-slate-700 border border-slate-600 rounded-lg file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 cursor-pointer">
            </div>

            <div id="import-status" class="text-xs font-semibold text-center"></div>

            <div class="flex gap-2 pt-2">
                <button onclick="toggleImportModal()" class="flex-1 bg-slate-700 hover:bg-slate-600 py-2.5 rounded-xl text-xs font-semibold transition">Batal</button>
                <button onclick="uploadCsv()" class="flex-1 bg-indigo-600 hover:bg-indigo-500 py-2.5 rounded-xl text-xs font-bold text-white transition">Unggah & Simpan</button>
            </div>
        </div>
    </div>

    <script>
        const video = document.getElementById('video');
        const aiLoading = document.getElementById('ai-loading');
        const btnSave = document.getElementById('btn-save');
        const classSelect = document.getElementById('class_select');
        const studentList = document.getElementById('student-list');
        const queueCount = document.getElementById('queue-count');
        const MODEL_URL = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights';

        let studentsQueue = [];
        let currentStudentIndex = -1;
        const capturedDescriptors = [null, null, null];

        async function init() {
            try {
                await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
                await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);

                aiLoading.className = "text-xs text-emerald-400 mt-2 font-medium text-center";
                aiLoading.innerText = "✅ Kamera siap! Silakan pilih siswa di daftar.";
                
                const stream = await navigator.mediaDevices.getUserMedia({ video: { width: 280, height: 210, facingMode: 'user' } });
                video.srcObject = stream;

                loadStudentQueue();
            } catch (err) {
                console.error(err);
                aiLoading.className = "text-xs text-rose-400 mt-2 font-medium text-center";
                aiLoading.innerText = "❌ Gagal mengaktifkan kamera atau model AI.";
            }
        }

        async function loadStudentQueue() {
            const classId = classSelect.value;
            if (!classId) return;

            studentList.innerHTML = `<p class="text-xs text-slate-500 text-center py-10">Mengunduh daftar siswa...</p>`;

            try {
                const response = await fetch(`/registrasi/students/${classId}`);
                studentsQueue = await response.json();

                queueCount.innerText = `${studentsQueue.length} Siswa`;

                if (studentsQueue.length === 0) {
                    studentList.innerHTML = `
                        <div class="text-center py-10 px-2 space-y-2">
                            <p class="text-xs text-slate-400">Belum ada siswa di kelas ini.</p>
                            <p class="text-[11px] text-indigo-400 font-semibold cursor-pointer" onclick="toggleImportModal()">Klik di sini untuk Impor CSV</p>
                        </div>
                    `;
                    return;
                }

                renderStudentList();
                
                const firstPending = studentsQueue.findIndex(s => !s.has_face);
                if (firstPending !== -1) {
                    selectStudent(firstPending);
                } else {
                    selectStudent(0);
                }

            } catch (err) {
                console.error(err);
                studentList.innerHTML = `<p class="text-xs text-rose-400 text-center py-10">Gagal memuat daftar.</p>`;
            }
        }

        function renderStudentList() {
            studentList.innerHTML = '';
            studentsQueue.forEach((s, idx) => {
                const item = document.createElement('div');
                const isSelected = (idx === currentStudentIndex);
                
                item.className = `p-2.5 rounded-xl border transition flex justify-between items-center gap-2 ${
                    isSelected 
                        ? 'bg-indigo-600/30 border-indigo-500 shadow-md ring-1 ring-indigo-400' 
                        : 'bg-slate-700/50 hover:bg-slate-700 border-slate-600'
                }`;

                item.innerHTML = `
                    <div class="truncate flex-1 cursor-pointer" onclick="selectStudent(${idx})">
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-xs text-slate-100 truncate">${idx + 1}. ${s.full_name}</span>
                            ${s.has_face 
                                ? '<span class="text-[9px] font-bold bg-emerald-500/20 text-emerald-400 px-1.5 py-0.2 rounded-full">🟢</span>' 
                                : '<span class="text-[9px] font-bold bg-rose-500/20 text-rose-400 px-1.5 py-0.2 rounded-full">🔴</span>'
                            }
                        </div>
                        <span class="text-[10px] text-slate-400 font-mono">NISN: ${s.nisn}</span>
                    </div>

                    <!-- Tombol Aksi Edit & Hapus -->
                    <div class="flex items-center gap-1">
                        <button onclick="openEditModal(${idx})" title="Edit Siswa" class="p-1.5 bg-slate-600 hover:bg-amber-500/30 text-amber-300 rounded-lg text-xs transition">
                            ✏️
                        </button>
                        <button onclick="deleteStudent(${s.id}, '${s.full_name}')" title="Hapus Siswa" class="p-1.5 bg-slate-600 hover:bg-rose-500/30 text-rose-400 rounded-lg text-xs transition">
                            🗑️
                        </button>
                    </div>
                `;
                studentList.appendChild(item);
            });
        }

        function selectStudent(idx) {
            currentStudentIndex = idx;
            const s = studentsQueue[idx];
            if (!s) return;

            document.getElementById('student_id').value = s.id;
            document.getElementById('nisn').value = s.nisn;
            document.getElementById('full_name').value = s.full_name;
            document.getElementById('gender').value = s.gender;
            document.getElementById('gender_label').value = (s.gender === 'L') ? 'Laki-laki' : 'Perempuan';
            document.getElementById('parent_phone').value = s.parent_phone || '-';

            const badge = document.getElementById('badge-status-siswa');
            const resetWrap = document.getElementById('btn-reset-wrap');

            if (s.has_face) {
                badge.className = "text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-400 border border-emerald-500/30";
                badge.innerText = "🟢 SUDAH MEREKAM WAJAH";
                resetWrap.classList.remove('hidden');
            } else {
                badge.className = "text-[10px] font-bold px-2 py-0.5 rounded bg-rose-500/20 text-rose-400 border border-rose-500/30";
                badge.innerText = "🔴 BELUM MEREKAM WAJAH";
                resetWrap.classList.add('hidden');
            }

            resetCaptureButtons();
            renderStudentList();
        }

        function resetCaptureButtons() {
            capturedDescriptors[0] = null;
            capturedDescriptors[1] = null;
            capturedDescriptors[2] = null;
            document.getElementById('badge-0').className = "text-slate-400";
            document.getElementById('badge-0').innerText = '⚪ Depan: Belum';
            document.getElementById('badge-1').className = "text-slate-400";
            document.getElementById('badge-1').innerText = '⚪ Kiri: Belum';
            document.getElementById('badge-2').className = "text-slate-400";
            document.getElementById('badge-2').innerText = '⚪ Kanan: Belum';
            btnSave.disabled = true;
            btnSave.className = "w-full bg-slate-700 text-slate-500 font-bold py-3 px-4 rounded-xl shadow-lg";
            document.getElementById('save-status').innerText = '';
        }

        async function captureAngle(index) {
            aiLoading.innerText = "🔍 Mendeteksi & mengekstrak wajah...";
            
            const detection = await faceapi.detectSingleFace(
                video, 
                new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 })
            ).withFaceLandmarks().withFaceDescriptor();

            if (!detection) {
                alert("Wajah tidak terdeteksi! Pastikan wajah terlihat jelas dan menghadap kamera.");
                aiLoading.innerText = "⚠️ Wajah tidak terdeteksi. Silakan coba lagi.";
                return;
            }

            capturedDescriptors[index] = Array.from(detection.descriptor);
            
            const badge = document.getElementById(`badge-${index}`);
            const labels = ["Depan", "Kiri", "Kanan"];
            badge.className = "text-emerald-400 font-bold";
            badge.innerText = `🟢 ${labels[index]}: Terambil`;
            
            aiLoading.innerText = `✅ Sudut ${labels[index]} terekam!`;

            if (capturedDescriptors[0] && capturedDescriptors[1] && capturedDescriptors[2]) {
                btnSave.disabled = false;
                btnSave.className = "w-full bg-emerald-500 hover:bg-emerald-400 text-white font-black py-3 px-4 rounded-xl shadow-lg transition duration-200 cursor-pointer animate-pulse";
            }
        }

        async function saveStudentBiometric() {
            const class_id = classSelect.value;
            const student_id = document.getElementById('student_id').value;
            const nisn = document.getElementById('nisn').value.trim();
            const full_name = document.getElementById('full_name').value.trim();
            const gender = document.getElementById('gender').value;
            const parent_phone = document.getElementById('parent_phone').value.trim();
            const saveStatus = document.getElementById('save-status');

            btnSave.disabled = true;
            saveStatus.className = "text-xs text-center text-amber-400 mt-2 font-medium";
            saveStatus.innerText = "⏳ Menyimpan vektor ke database...";

            try {
                const response = await fetch('/registrasi/simpan', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        class_id: class_id,
                        student_id: student_id,
                        nisn: nisn,
                        full_name: full_name,
                        gender: gender,
                        parent_phone: parent_phone,
                        descriptors: capturedDescriptors
                    })
                });

                const result = await response.json();

                if (result.success) {
                    saveStatus.className = "text-xs text-center text-emerald-400 font-bold mt-2";
                    saveStatus.innerText = "🎉 " + result.message;

                    if (studentsQueue[currentStudentIndex]) {
                        studentsQueue[currentStudentIndex].has_face = true;
                    }

                    setTimeout(() => {
                        const nextPending = studentsQueue.findIndex((s, i) => i > currentStudentIndex && !s.has_face);
                        if (nextPending !== -1) {
                            selectStudent(nextPending);
                        } else {
                            const anyPending = studentsQueue.findIndex(s => !s.has_face);
                            if (anyPending !== -1) {
                                selectStudent(anyPending);
                            } else {
                                alert("🎉 Alhamdulillah, seluruh siswa di kelas ini sudah selesai direkam biometriknya!");
                                renderStudentList();
                            }
                        }
                    }, 800);

                } else {
                    saveStatus.className = "text-xs text-center text-rose-400 font-bold mt-2";
                    saveStatus.innerText = "Gagal: " + result.message;
                    btnSave.disabled = false;
                }
            } catch (err) {
                console.error(err);
                saveStatus.className = "text-xs text-center text-rose-400 font-bold mt-2";
                saveStatus.innerText = "Terjadi gangguan koneksi saat menyimpan.";
                btnSave.disabled = false;
            }
        }

        // HAPUS SISWA
        async function deleteStudent(id, name) {
            if (!confirm(`Apakah Anda yakin ingin menghapus data siswa: ${name}? Seluruh data wajah dan presensinya akan terhapus.`)) {
                return;
            }

            try {
                const response = await fetch(`/registrasi/delete/${id}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                const res = await response.json();
                if (res.success) {
                    loadStudentQueue();
                } else {
                    alert("Gagal menghapus siswa.");
                }
            } catch (err) {
                alert("Kesalahan jaringan saat menghapus.");
            }
        }

        // RESET FOTO WAJAH
        async function resetCurrentFace() {
            const studentId = document.getElementById('student_id').value;
            const name = document.getElementById('full_name').value;

            if (!confirm(`Hapus rekaman wajah ${name} dan foto ulang?`)) return;

            try {
                const res = await fetch(`/registrasi/reset-face/${studentId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                const data = await res.json();
                if (data.success) {
                    if (studentsQueue[currentStudentIndex]) {
                        studentsQueue[currentStudentIndex].has_face = false;
                    }
                    selectStudent(currentStudentIndex);
                }
            } catch (e) {
                alert("Gagal mereset foto.");
            }
        }

        // EDIT MODAL HANDLER
        function openEditModal(idx) {
            const s = studentsQueue[idx];
            if (!s) return;

            document.getElementById('edit_id').value = s.id;
            document.getElementById('edit_nisn').value = s.nisn;
            document.getElementById('edit_name').value = s.full_name;
            document.getElementById('edit_gender').value = s.gender;
            document.getElementById('edit_phone').value = s.parent_phone || '';

            document.getElementById('edit-modal').classList.remove('hidden');
        }

        function toggleEditModal() {
            document.getElementById('edit-modal').classList.toggle('hidden');
        }

        async function saveEditStudent() {
            const id = document.getElementById('edit_id').value;
            const nisn = document.getElementById('edit_nisn').value.trim();
            const full_name = document.getElementById('edit_name').value.trim();
            const gender = document.getElementById('edit_gender').value;
            const parent_phone = document.getElementById('edit_phone').value.trim();

            if (!nisn || !full_name) {
                alert("NISN dan Nama wajib diisi!");
                return;
            }

            try {
                const res = await fetch('/registrasi/update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ id, nisn, full_name, gender, parent_phone })
                });

                const data = await res.json();
                if (data.success) {
                    toggleEditModal();
                    loadStudentQueue();
                } else {
                    alert("Gagal menyimpan perubahan.");
                }
            } catch (err) {
                alert("Kesalahan jaringan.");
            }
        }

        // IMPOR MODAL HANDLER
        function toggleImportModal() {
            document.getElementById('import-modal').classList.toggle('hidden');
            document.getElementById('import-status').innerText = '';
        }

        async function uploadCsv() {
            const fileInput = document.getElementById('file_csv');
            const statusEl = document.getElementById('import-status');
            const classId = classSelect.value;

            if (!fileInput.files || fileInput.files.length === 0) {
                alert("Pilih file CSV terlebih dahulu!");
                return;
            }

            const formData = new FormData();
            formData.append('class_id', classId);
            formData.append('file_csv', fileInput.files[0]);

            statusEl.className = "text-xs font-semibold text-amber-400 text-center";
            statusEl.innerText = "⏳ Sedang mengimpor data siswa...";

            try {
                const response = await fetch('/registrasi/import', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                });

                const res = await response.json();
                if (res.success) {
                    statusEl.className = "text-xs font-semibold text-emerald-400 text-center";
                    statusEl.innerText = res.message;
                    setTimeout(() => {
                        toggleImportModal();
                        loadStudentQueue();
                    }, 1200);
                } else {
                    statusEl.className = "text-xs font-semibold text-rose-400 text-center";
                    statusEl.innerText = "Gagal mengimpor data.";
                }
            } catch (err) {
                console.error(err);
                statusEl.className = "text-xs font-semibold text-rose-400 text-center";
                statusEl.innerText = "Terjadi kesalahan jaringan saat mengunggah.";
            }
        }

        init();
    </script>
</body>
</html>