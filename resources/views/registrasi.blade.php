<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <title>Pendaftaran Siswa & Perekaman Wajah</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen py-8 px-4">

    <div class="max-w-4xl mx-auto bg-slate-800 rounded-2xl shadow-xl border border-slate-700 p-6 md:p-8">
        <div class="flex justify-between items-center border-b border-slate-700 pb-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-emerald-400">Pendaftaran Biometrik Siswa</h1>
                <p class="text-sm text-slate-400">Perekaman Data Siswa dan 3 Vektor Wajah AI (Multi-Angle Enrolment)</p>
            </div>
            <a href="/presensi" class="text-xs bg-slate-700 hover:bg-slate-600 px-3 py-2 rounded-lg font-medium transition">
                ⚡ Ke Menu Presensi
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Kolom Kiri: Form Biodata -->
            <div class="space-y-4">
                <h2 class="text-lg font-semibold text-white border-l-4 border-emerald-500 pl-2">Biodata Siswa</h2>
                
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Kelas</label>
                    <select id="class_id" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500">
                        <?php if (!empty($classes) && count($classes) > 0): ?>
                            <?php foreach ($classes as$c): ?>
                                <option value="<?php echo $c->id; ?>"><?php echo $c->class_name . ' (' .$c->academic_year . ')'; ?></option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">Belum ada kelas (tambahkan di phpMyAdmin)</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">NISN</label>
                    <input type="text" id="nisn" placeholder="Contoh: 0012345678" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Nama Lengkap</label>
                    <input type="text" id="full_name" placeholder="Nama siswa" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Jenis Kelamin</label>
                        <select id="gender" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500">
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">No WA Wali</label>
                        <input type="text" id="parent_phone" placeholder="08xxxxxxxxxx" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <!-- Tombol Simpan Final -->
                <button id="btn-save" onclick="saveStudent()" disabled class="w-full mt-6 bg-slate-700 text-slate-500 font-semibold py-3 px-4 rounded-xl shadow-lg transition duration-200">
                    💾 Simpan Data & Vektor Wajah
                </button>
                <div id="save-status" class="text-xs text-center font-medium mt-2"></div>
            </div>

            <!-- Kolom Kanan: Kamera & Perekaman 3 Sudut -->
            <div class="flex flex-col items-center">
                <h2 class="text-lg font-semibold text-white border-l-4 border-indigo-500 pl-2 self-start mb-3">Kamera Perekam Wajah</h2>
                
                <div class="relative w-[320px] h-[240px] bg-black rounded-xl overflow-hidden border-2 border-slate-600 shadow-md">
                    <video id="video" width="320" height="240" autoplay muted playsinline class="transform -scale-x-100 object-cover"></video>
                </div>

                <div id="ai-loading" class="text-xs text-amber-400 mt-2 font-medium animate-pulse">
                    Memuat model AI...
                </div>

                <!-- Kontrol Pengambilan 3 Sudut -->
                <div class="w-full mt-5 space-y-2">
                    <div class="text-xs font-semibold text-slate-300">Ambil 3 Pose Wajah:</div>

                    <div class="grid grid-cols-3 gap-2 text-center text-xs">
                        <button id="btn-snap-0" onclick="captureAngle(0)" class="bg-indigo-600 hover:bg-indigo-500 py-2 px-1 rounded-lg font-medium border border-indigo-400/30">
                            1. Depan
                        </button>
                        <button id="btn-snap-1" onclick="captureAngle(1)" class="bg-indigo-600 hover:bg-indigo-500 py-2 px-1 rounded-lg font-medium border border-indigo-400/30">
                            2. Kiri 15°
                        </button>
                        <button id="btn-snap-2" onclick="captureAngle(2)" class="bg-indigo-600 hover:bg-indigo-500 py-2 px-1 rounded-lg font-medium border border-indigo-400/30">
                            3. Kanan 15°
                        </button>
                    </div>

                    <!-- Indikator Status -->
                    <div class="flex justify-around text-xs mt-2 text-slate-400">
                        <span id="badge-0">⚪ Depan: Belum</span>
                        <span id="badge-1">⚪ Kiri: Belum</span>
                        <span id="badge-2">⚪ Kanan: Belum</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const video = document.getElementById('video');
        const aiLoading = document.getElementById('ai-loading');
        const btnSave = document.getElementById('btn-save');
        const MODEL_URL = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights';

        const capturedDescriptors = [null, null, null];

        // 1. Inisialisasi Model AI & Kamera
        async function init() {
            try {
                await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
                await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);

                aiLoading.className = "text-xs text-emerald-400 mt-2 font-medium";
                aiLoading.innerText = "✅ Kamera siap! Silakan ambil 3 sudut wajah.";
                
                const stream = await navigator.mediaDevices.getUserMedia({ video: { width: 320, height: 240, facingMode: 'user' } });
                video.srcObject = stream;
            } catch (err) {
                console.error(err);
                aiLoading.className = "text-xs text-rose-400 mt-2 font-medium";
                aiLoading.innerText = "❌ Gagal memuat kamera atau model AI.";
            }
        }

        // 2. Ekstraksi Vektor Wajah Sesuai Sudut
        async function captureAngle(index) {
            aiLoading.innerText = "🔍 Mendeteksi & mengekstrak fitur wajah...";
            
            const detection = await faceapi.detectSingleFace(
                video, 
                new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 })
            ).withFaceLandmarks().withFaceDescriptor();

            if (!detection) {
                alert("Wajah tidak terdeteksi! Pastikan wajah terlihat jelas dan pencahayaan cukup.");
                aiLoading.innerText = "⚠️ Wajah tidak terdeteksi. Silakan coba lagi.";
                return;
            }

            capturedDescriptors[index] = Array.from(detection.descriptor);
            
            const badge = document.getElementById(`badge-${index}`);
            const labels = ["Depan", "Kiri", "Kanan"];
            badge.className = "text-emerald-400 font-semibold";
            badge.innerText = `🟢 ${labels[index]}: Terambil`;
            
            aiLoading.innerText = `✅ Sudut ${labels[index]} berhasil direkam!`;

            if (capturedDescriptors[0] && capturedDescriptors[1] && capturedDescriptors[2]) {
                btnSave.disabled = false;
                btnSave.className = "w-full mt-6 bg-emerald-500 hover:bg-emerald-400 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition duration-200 cursor-pointer animate-pulse";
            }
        }

        // 3. Simpan ke Database MySQL via AJAX
        async function saveStudent() {
            const class_id = document.getElementById('class_id').value;
            const nisn = document.getElementById('nisn').value.trim();
            const full_name = document.getElementById('full_name').value.trim();
            const gender = document.getElementById('gender').value;
            const parent_phone = document.getElementById('parent_phone').value.trim();
            const saveStatus = document.getElementById('save-status');

            if (!class_id) {
                alert("Pilih kelas terlebih dahulu!");
                return;
            }
            if (!nisn || !full_name) {
                alert("Harap isi NISN dan Nama Lengkap siswa!");
                return;
            }

            btnSave.disabled = true;
            saveStatus.className = "text-xs text-center text-amber-400 mt-2 font-medium";
            saveStatus.innerText = "⏳ Menyimpan ke database MySQL...";

            try {
                const response = await fetch('/registrasi/simpan', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        class_id: class_id,
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
                    alert("Berhasil! Data siswa & 3 vektor biometrik tersimpan di database.");

                    // Reset form untuk input siswa berikutnya
                    document.getElementById('nisn').value = '';
                    document.getElementById('full_name').value = '';
                    document.getElementById('parent_phone').value = '';
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
                    btnSave.className = "w-full mt-6 bg-slate-700 text-slate-500 font-semibold py-3 px-4 rounded-xl shadow-lg";
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

        init();
    </script>
</body>
</html>