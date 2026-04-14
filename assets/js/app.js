let membersData = [];

document.getElementById('file-input').addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('excel_file', file);

    const statusDiv = document.getElementById('upload-status');
    statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses Excel...';

    try {
        const response = await fetch('process.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.status === 'success') {
            membersData = result.members;
            renderMembers(membersData);
            document.getElementById('results-section').style.display = 'block';
            document.getElementById('total-count').innerText = `${result.count} Data Berhasil Diimport`;
            statusDiv.innerHTML = '<span style="color: #10b981;">Berhasil diimport!</span>';
            
            // Generate batch buttons based on data length
            renderBatchButtons(membersData);
        } else {
            statusDiv.innerHTML = `<span style="color: #ef4444;">Error: ${result.message}</span>`;
        }
    } catch (error) {
        statusDiv.innerHTML = `<span style="color: #ef4444;">Error: ${error.message}</span>`;
    }
});

function renderMembers(members) {
    const grid = document.getElementById('member-grid');
    grid.innerHTML = '';

    members.forEach(member => {
        const item = document.createElement('div');
        item.className = 'kta-item';
        item.innerHTML = `
            <img src="generate_kta.php?id=${member.id}" class="kta-preview-img" alt="${member.nama}">
            <div class="kta-info">
                <h3>${member.nama}</h3>
                <p>${member.nik} | ${member.bagian}</p>
                <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                    <button class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;" onclick="previewKTA(${member.id})">
                        <i class="fas fa-eye"></i> Preview
                    </button>
                    <a href="generate_kta.php?id=${member.id}&download=1" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: #475569;" download="${member.nama}.png">
                        <i class="fas fa-download"></i> Simpan
                    </a>
                </div>
            </div>
        `;
        grid.appendChild(item);
    });
}

function previewKTA(id) {
    const modal = document.getElementById('preview-modal');
    const canvas = document.getElementById('preview-canvas');
    const ctx = canvas.getContext('2d');
    
    modal.style.display = 'flex';
    
    const img = new Image();
    img.onload = function() {
        canvas.width = img.width;
        canvas.height = img.height;
        ctx.drawImage(img, 0, 0);
    };
    img.src = `generate_kta.php?id=${id}`;
}

function renderBatchButtons(members) {
    const container = document.getElementById('batch-buttons-container');
    container.innerHTML = '';
    
    // Pecah data per 100 untuk menghindari error Invalid string length
    const BATCH_SIZE = 100;
    const totalBatches = Math.ceil(members.length / BATCH_SIZE);

    if (members.length === 0) return;

    for (let i = 0; i < totalBatches; i++) {
        const start = i * BATCH_SIZE + 1;
        const end = Math.min((i + 1) * BATCH_SIZE, members.length);
        
        const btn = document.createElement('button');
        btn.className = 'btn';
        btn.innerHTML = `<i class="fas fa-bolt"></i> Generate PDF Data ${start}-${end}`;
        btn.onclick = () => generateBatchPDF(i, start, end, members.slice(start - 1, end));
        container.appendChild(btn);
    }
}

async function generateBatchPDF(batchIndex, start, end, batchData) {
    const container = document.getElementById('batch-buttons-container');
    const btn = container.children[batchIndex];
    const originalText = btn.innerHTML;
    
    // Disable all buttons during generation to prevent overlapping
    Array.from(container.children).forEach(b => b.disabled = true);
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mendevelop PDF...';

    // Create a new jsPDF instance matching CR80 standard ID Card
    const { jsPDF } = window.jspdf;
    
    // ISO CR80 Dimensions
    const cardWidth = 85.60;
    const cardHeight = 53.98;

    const pdf = new jsPDF({
        orientation: 'landscape',
        unit: 'mm',
        format: [cardWidth, cardHeight]
    });

    // Helper to get image as data URL
    const getBase64Image = (id) => {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'Anonymous';
            img.onload = () => {
                const canvas = document.createElement('canvas');
                canvas.width = img.width;
                canvas.height = img.height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0);
                resolve(canvas.toDataURL('image/jpeg', 0.8));
            };
            img.onerror = () => reject(new Error('Gagal memuat KTA'));
            img.src = `generate_kta.php?id=${id}&t=${new Date().getTime()}`;
        });
    };

    try {
        // Process images in parallel chunks within this batch
        const chunkSize = 15;
        let processedCount = 0;

        for (let i = 0; i < batchData.length; i += chunkSize) {
            const chunk = batchData.slice(i, i + chunkSize);
            
            const promises = chunk.map(async (member, idx) => {
                try {
                    const imgData = await getBase64Image(member.id);
                    return { globalIndex: i + idx, imgData };
                } catch (e) {
                    console.error(`Gagal generate ${member.nama}`, e);
                    return { globalIndex: i + idx, imgData: null };
                }
            });

            const chunkResults = await Promise.all(promises);

            chunkResults.forEach(result => {
                if (result.imgData) {
                    if (result.globalIndex > 0) {
                        pdf.addPage([cardWidth, cardHeight], 'landscape');
                    }
                    pdf.addImage(result.imgData, 'JPEG', 0, 0, cardWidth, cardHeight);
                }
                processedCount++;
            });

            btn.innerHTML = `<i class="fas fa-bolt"></i> Memproses ${processedCount} dari ${batchData.length}...`;
        }

        btn.innerHTML = '<i class="fas fa-check"></i> Selesai!';
        
        const blob = pdf.output('blob');
        const url = window.URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = `Data_KTA_${start}-${end}.pdf`;
        a.click();
        
        // Bersihkan memori object URL
        setTimeout(() => URL.revokeObjectURL(url), 100);
        
        // Restore button state dan beri waktu untuk Garbage Collection
        setTimeout(() => {
            btn.innerHTML = originalText;
            Array.from(container.children).forEach(b => b.disabled = false);
        }, 1000);
        
    } catch (e) {
         btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Terjadi Kesalahan';
         console.error(e);
         setTimeout(() => {
            btn.innerHTML = originalText;
            Array.from(container.children).forEach(b => b.disabled = false);
        }, 3000);
    }
}
