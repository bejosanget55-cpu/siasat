<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <title>Pemindai Presensi Wajah Otomatis</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <style>
        #scanner-wrap {
            position: relative;
            display: inline-block;
        }
        canvas {
            position: absolute;
            top: 0;
            left: 0;
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen py-6 px-4">

    <div class="max-w-6xl mx-auto space-y-6">
        
        <!-- Header & Navigasi Terpadu -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-slate-800 p-5 rounded-2xl border border-slate-700 shadow-lg">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-3 h-3 bg-emerald-500 rounded-full animate-ping"></span>
                    <h1 class="text-2xl font-black tracking-wide text-emerald-400">GATE SCANNER PRESENSI WAJAH</h1>
                </div>
                <p class="text-xs text-slate-400 mt-1">Mode Otomatis Gerbang Masuk Madrasah (Tanpa Sentuh / Hands-Free)</p>
            </div>
            <div class="mt-4 md:mt-0 flex gap-2">
                <a href="/rekap" class="text-xs bg-indigo-600 hover:bg-indigo-500 px-3.5 py-2 rounded-lg font-bold transition flex items-center gap-1 shadow">
                    📊 Lihat Rekap Kehadiran
                </a>
                <a href="/registrasi" class="text-xs bg-slate-700 hover:bg-slate-600 px-3.5 py-2 rounded-lg font-medium transition flex items-center gap-1">
                    + Daftarkan Siswa Baru
                </a>
            </div>
        </div>

        <!-- Pengaturan Mode (Default: Semua Kelas & Presensi Pagi) -->
        <div class="bg-slate-800 p-4 rounded-xl border border-slate-700 flex flex-wrap gap-4 items-center">
            <div>
                <label class="text-xs text-slate-400 font-semibold block mb-1">TARGET KELAS:</label>
                <select id="select-class" onchange="loadBiometrics()" class="bg-slate-700 border border-slate-600 text-sm rounded-lg px-3 py-2 focus:ring-emerald-500 font-semibold text-emerald-300">
                    <option value="all" selected>🌟 SEMUA KELAS (Mode Gerbang Pagi)</option>
                    <?php if (!empty($classes)): ?>
                        <?php foreach ($classes as$c): ?>
                            <option value="<?php echo $c->id; ?>">Khusus Kelas <?php echo $c->class_name; ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label class="text-xs text-slate-400 font-semibold block mb-1">SESI PRESENSI:</label>
                <input type="text" id="activity_type" value="Presensi Pagi" class="bg-slate-700 border border-slate-600 text-sm rounded-lg px-3 py-2 font-semibold text-white">
            </div>

            <div class="flex-1 text-right">
                <span id="load-info" class="text-xs font-semibold text-amber-400">
                    ⏳ Mempersiapkan data biometrik...
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Area Kamera Video -->
            <div class="lg:col-span-2 bg-slate-800 rounded-2xl border border-slate-700 p-4 flex flex-col items-center">
                <div id="scanner-wrap" class="rounded-xl overflow-hidden border-2 border-slate-600 bg-black shadow-inner">
                    <video id="video" width="600" height="450" autoplay muted playsinline class="transform -scale-x-100 object-cover"></video>
                </div>
                
                <div id="scanner-status" class="w-full mt-3 p-3 text-center rounded-xl font-semibold text-sm bg-slate-700 text-slate-300">
                    Kamera sedang dimuat...
                </div>
            </div>

            <!-- Area Riwayat Log Kehadiran Real-Time -->
            <div class="bg-slate-800 rounded-2xl border border-slate-700 p-5 flex flex-col h-[520px]">
                <h3 class="text-sm font-bold uppercase tracking-wider text-emerald-400 border-b border-slate-700 pb-3 mb-3 flex justify-between items-center">
                    <span>Siswa Masuk Hari Ini</span>
                    <span id="attendance-counter" class="text-xs bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded-full border border-emerald-500/30">0 Siswa</span>
                </h3>

                <div id="log-list" class="flex-1 overflow-y-auto space-y-2 pr-1 text-sm">
                    <p class="text-xs text-slate-500 text-center py-10" id="empty-state">Belum ada siswa yang melintas.</p>
                </div>
            </div>

        </div>

    </div>

    <script>
        const video = document.getElementById('video');
        const selectClass = document.getElementById('select-class');
        const loadInfo = document.getElementById('load-info');
        const scannerStatus = document.getElementById('scanner-status');
        const logList = document.getElementById('log-list');
        const emptyState = document.getElementById('empty-state');
        const counterEl = document.getElementById('attendance-counter');

        const MODEL_URL = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights';

        let faceMatcher = null;
        let totalPresent = 0;
        const recentScanned = new Map(); 

        function playBeep(success = true) {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                
                osc.type = "sine";
                osc.frequency.value = success ? 880 : 330;
                gain.gain.setValueAtTime(0.1, ctx.currentTime);
                osc.start();
                osc.stop(ctx.currentTime + 0.15);
            } catch(e) {}
        }

        async function init() {
            try {
                scannerStatus.innerText = "⏳ Memuat model AI biometrik...";
                await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
                await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
                
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { width: 600, height: 450, facingMode: 'user' }
                });
                video.srcObject = stream;

                await loadBiometrics();

            } catch (err) {
                console.error(err);
                scannerStatus.innerText = "❌ Gagal membuka kamera atau AI.";
            }
        }

        async function loadBiometrics() {
            const classId = selectClass.value;
            loadInfo.innerText = "⏳ Sinkronisasi data biometrik...";
            faceMatcher = null;

            try {
                const response = await fetch(`/presensi/descriptors/${classId}`);
                const students = await response.json();

                if (students.length === 0) {
                    loadInfo.className = "text-xs font-semibold text-rose-400";
                    loadInfo.innerText = "⚠️ Belum ada biometrik siswa yang terdaftar.";
                    return;
                }

                const labeledDescriptors = students.map(s => {
                    const descFloatArrays = s.descriptors.map(d => new Float32Array(d));
                    return new faceapi.LabeledFaceDescriptors(`${s.student_id}__${s.full_name}__${s.class_name}`, descFloatArrays);
                });

                faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.6);
                
                loadInfo.className = "text-xs font-semibold text-emerald-400";
                loadInfo.innerText = `✅ Siap! ${students.length} biometrik siswa aktif.`;
                scannerStatus.innerText = "📸 Mode Otomatis Aktif — Siswa silakan melintas di depan kamera";
            } catch (err) {
                console.error(err);
                loadInfo.innerText = "❌ Gagal memuat database biometrik.";
            }
        }

        video.addEventListener('play', () => {
            const canvas = faceapi.createCanvasFromMedia(video);
            document.getElementById('scanner-wrap').append(canvas);

            const displaySize = { width: video.width, height: video.height };
            faceapi.matchDimensions(canvas, displaySize);

            setInterval(async () => {
                if (!faceMatcher) return;

                const detections = await faceapi.detectAllFaces(
                    video, 
                    new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 })
                ).withFaceLandmarks().withFaceDescriptors();

                const resized = faceapi.resizeResults(detections, displaySize);
                canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);

                resized.forEach(d => {
                    const match = faceMatcher.findBestMatch(d.descriptor);
                    const box = d.detection.box;

                    if (match.label !== 'unknown') {
                        const [studentId, studentName, className] = match.label.split('__');

                        const drawBox = new faceapi.draw.DrawBox(box, { 
                            label: `${studentName} [${className}] (${Math.round((1 - match.distance) * 100)}%)`,
                            boxColor: '#10b981' 
                        });
                        drawBox.draw(canvas);

                        triggerAttendance(studentId, studentName, className);
                    } else {
                        const drawBox = new faceapi.draw.DrawBox(box, { label: "Tidak Dikenali", boxColor: '#ef4444' });
                        drawBox.draw(canvas);
                    }
                });
            }, 150);
        });

        async function triggerAttendance(studentId, studentName, className) {
            const now = Date.now();
            if (recentScanned.has(studentId) && (now - recentScanned.get(studentId)) < 8000) {
                return;
            }
            recentScanned.set(studentId, now);

            const activityType = document.getElementById('activity_type').value;

            try {
                const res = await fetch('/presensi/scan', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        student_id: studentId,
                        activity_type: activityType
                    })
                });

                const result = await res.json();

                if (result.status === 'success') {
                    playBeep(true);
                    addLog(result.data.name, result.data.class_name, result.data.time);
                    scannerStatus.className = "w-full mt-3 p-3 text-center rounded-xl font-bold text-sm bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 animate-pulse";
                    scannerStatus.innerText = `✅ HADIR: ${result.data.name} (${result.data.class_name}) - ${result.data.time}`;
                } else if (result.status === 'already') {
                    playBeep(false);
                    scannerStatus.className = "w-full mt-3 p-3 text-center rounded-xl font-semibold text-sm bg-amber-500/20 text-amber-300 border border-amber-500/40";
                    scannerStatus.innerText = `ℹ️ ${result.message}`;
                }
            } catch (err) {
                console.error(err);
            }
        }

        function addLog(name, className, time) {
            if (emptyState) emptyState.remove();
            totalPresent++;
            counterEl.innerText = `${totalPresent} Siswa`;

            const item = document.createElement('div');
            item.className = "p-3 bg-slate-700/60 rounded-xl border border-slate-600 flex justify-between items-center";
            item.innerHTML = `
                <div>
                    <p class="font-bold text-slate-100">${name}</p>
                    <span class="text-[10px] text-indigo-300 bg-indigo-500/20 px-2 py-0.5 rounded font-medium mr-1">${className}</span>
                    <span class="text-[10px] text-emerald-400 font-semibold bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">HADIR</span>
                </div>
                <span class="text-xs text-slate-400 font-mono">${time}</span>
            `;
            logList.prepend(item);
        }

        init();
    </script>
</body>
</html>