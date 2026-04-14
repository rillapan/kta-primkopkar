<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTA Generator | Premium Card System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>KTA Generator</h1>
            <p>Sistem Pembuatan Kartu Tanda Anggota PT PRIMATEXCO INDONESIA</p>
        </header>

        <section class="glass-card">
            <div class="upload-zone" onclick="document.getElementById('file-input').click()">
                <i class="fas fa-file-excel"></i>
                <h2>Import Data Excel</h2>
                <p>Klik di sini atau drag & drop file .xlsx Anda</p>
                <form id="upload-form" enctype="multipart/form-data">
                    <input type="file" name="excel_file" id="file-input" accept=".xlsx">
                </form>
            </div>
            <div id="upload-status" style="margin-top: 1rem; text-align: center; color: var(--text-muted);"></div>
        </section>

        <section id="results-section" style="display: none;">
            <div class="actions-bar glass-card" style="padding: 1.5rem; margin-bottom: 2rem;">
                <div>
                    <h2 id="total-count">0 Data Berhasil Diimport</h2>
                </div>
                <div style="display: flex; flex-direction: column; gap: 1rem; width: 100%;">
                    <div id="batch-buttons-container" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <!-- Batch buttons will be dynamically inserted here -->
                    </div>
                </div>
            </div>

            <div class="preview-grid" id="member-grid">
                <!-- Items will be populated here -->
            </div>
        </section>
    </div>

    <!-- Modal for Preview Card (Hidden by default) -->
    <div id="preview-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); z-index:100; align-items:center; justify-content:center;">
        <div class="glass-card" style="max-width: 600px; width: 90%;">
             <canvas id="preview-canvas" style="width:100%; height:auto; border-radius:1rem;"></canvas>
             <div style="margin-top:2rem; display:flex; justify-content:flex-end;">
                 <button class="btn" onclick="document.getElementById('preview-modal').style.display='none'">Tutup</button>
             </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="assets/js/app.js?v=2"></script>
</body>
</html>
