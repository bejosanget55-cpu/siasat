<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uji Coba Deteksi Wajah AI</title>
    <!-- Tailwind CSS CDN untuk tampilan modern -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Face-API.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <style>
        /* Agar kanvas kotak deteksi menempel pas di atas video kamera */
        #video-container {
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
<body class="bg-slate-900 text-white min-h-screen flex flex-col items-center justify-center p-4">

    <div class="max-w-2xl w-full bg-slate-800 rounded-2xl shadow-2xl p-6 border border-slate-700 text-center">
        <h1 class="text-2xl font-bold text-emerald-400 mb-1">Presensi Wajah Cerdas</h1>
        <p class="text-slate-400 text-sm mb-4">Uji Coba Deteksi Wajah AI (Client-Side Browser)</p>

        <!-- Status Indikator -->
        <div id="status-box" class="bg-amber-500/20 text-amber-300 border border-amber-500/40 rounded-lg py-2 px-4 mb-4 text-sm font-medium animate-pulse">
            ⏳ Memuat model AI... Harap tunggu sebentar.
        </div>

        <!-- Wadah Kamera & Deteksi -->
        <div class="flex justify-center">
            <div id="video-container" class="rounded-xl overflow-hidden shadow-lg border-2 border-slate-600 bg-black">
                <video id="video" width="480" height="360" autoplay muted playsinline class="transform -scale-x-100"></video>
            </div>
        </div>

        <!-- Info Hasil Deteksi -->
        <div class="mt-4 p-3 bg-slate-700/50 rounded-xl text-left text-sm space-y-1">
            <p><strong>Status Deteksi:</strong> <span id="face-count" class="text-emerald-400 font-semibold">Mencari wajah...</span></p>
            <p><strong>Akurasi AI:</strong> <span id="face-score" class="text-slate-300">-</span></p>
        </div>
    </div>

    <script>
        const video = document.getElementById('video');
        const statusBox = document.getElementById('status-box');
        const faceCount = document.getElementById('face-count');
        const faceScore = document.getElementById('face-score');

        // Menggunakan model bobot dari CDN
        const MODEL_URL = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights';

        // 1. Memuat Model AI
        async function loadModels() {
            try {
                await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
                await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);

                statusBox.className = "bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 rounded-lg py-2 px-4 mb-4 text-sm font-medium";
                statusBox.innerHTML = "✅ Model AI Siap! Membuka kamera...";
                startCamera();
            } catch (err) {
                console.error(err);
                statusBox.className = "bg-rose-500/20 text-rose-300 border border-rose-500/40 rounded-lg py-2 px-4 mb-4 text-sm font-medium";
                statusBox.innerHTML = "❌ Gagal memuat model AI. Pastikan terhubung dengan internet.";
            }
        }

        // 2. Membuka Akses Kamera Laptop/HP
        function startCamera() {
            navigator.mediaDevices.getUserMedia({
                video: { width: 480, height: 360, facingMode: 'user' },
                audio: false
            })
            .then(stream => {
                video.srcObject = stream;
            })
            .catch(err => {
                console.error(err);
                statusBox.innerHTML = "❌ Akses kamera ditolak atau tidak ditemukan!";
            });
        }

        // 3. Proses Deteksi Real-Time Saat Kamera Aktif
        video.addEventListener('play', () => {
            const canvas = faceapi.createCanvasFromMedia(video);
            document.getElementById('video-container').append(canvas);

            const displaySize = { width: video.width, height: video.height };
            faceapi.matchDimensions(canvas, displaySize);

            // Perulangan deteksi setiap 100ms (10 FPS hemat daya)
            setInterval(async () => {
                const detections = await faceapi.detectAllFaces(
                    video, 
                    new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 })
                ).withFaceLandmarks();

                const resizedDetections = faceapi.resizeResults(detections, displaySize);
                
                // Bersihkan kanvas sebelumnya
                canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);

                // Gambar kotak wajah & landmark jika terdeteksi
                if (resizedDetections.length > 0) {
                    faceapi.draw.drawDetections(canvas, resizedDetections);
                    faceapi.draw.drawFaceLandmarks(canvas, resizedDetections);

                    faceCount.innerHTML = `Terdeteksi ${resizedDetections.length} Wajah`;
                    faceScore.innerHTML = `${Math.round(resizedDetections[0].detection.score * 100)}% kecocokan wajah manusia`;
                } else {
                    faceCount.innerHTML = "Tidak ada wajah terdeteksi";
                    faceScore.innerHTML = "-";
                }
            }, 100);
        });

        // Jalankan pemuatan model saat halaman terbuka
        loadModels();
    </script>
</body>
</html>