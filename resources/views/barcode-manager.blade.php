@extends('layouts.adminapp')

@section('admincontent')

@php
    $user = auth()->user();

    // current plan features
    $features = config('plans.' . $user->plan . '.features', []);

    // check if barcode manager exists
    $isLocked = !in_array('barcode_manager', $features);
@endphp

<style>
    .barcode-wrapper{
    position:relative;
    min-height:100vh;
}

/* OVERLAY */
.feature-lock-overlay{
    position:absolute;
    inset:0;
    z-index:5;

    background:rgba(255,255,255,0.55);
    backdrop-filter:blur(4px);

    display:flex;
    align-items:flex-start;
    justify-content:center;

    padding-top:70px;
    border-radius:20px;
}

/* LOCK BOX */
.feature-lock-box{
    width:100%;
    max-width:480px;

    background:#fff;

    border-radius:24px;

    padding:30px;

    text-align:center;

    box-shadow:
        0 15px 45px rgba(0,0,0,0.10);

    border:1px solid rgba(255,152,0,0.15);

    position:relative;

    overflow:hidden;

    animation:fadeUp .4s ease;
}

@keyframes fadeUp{
    from{
        opacity:0;
        transform:translateY(20px);
    }

    to{
        opacity:1;
        transform:translateY(0);
    }
}

.feature-lock-box::before{
    content:'';

    position:absolute;
    top:0;
    left:0;

    width:100%;
    height:5px;

    background:linear-gradient(
        90deg,
        #ff9800,
        #ff5722
    );
}

/* ICON */
.lock-icon{
    width:78px;
    height:78px;

    margin:auto auto 18px;

    border-radius:50%;

    display:flex;
    align-items:center;
    justify-content:center;

    background:linear-gradient(135deg,#ff9800,#ff5722);

    color:white;

    font-size:34px;

    box-shadow:
        0 10px 25px rgba(255,87,34,0.25);
}

/* BADGE */
.feature-badge{
    display:inline-block;

    background:#fff3e0;
    color:#ff5722;

    padding:7px 16px;

    border-radius:50px;

    font-size:12px;
    font-weight:700;

    margin-bottom:18px;
}

/* TITLE */
.feature-lock-box h2{
    font-size:38px;
    font-weight:800;

    color:#111827;

    margin-bottom:12px;
}

/* TEXT */
.feature-lock-box p{
    font-size:15px;
    line-height:1.7;

    color:#6b7280;

    margin-bottom:25px;
}

/* BUTTON */
.upgrade-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;

    background:linear-gradient(135deg,#ff9800,#ff5722);

    color:white;
    text-decoration:none;

    height:50px;

    padding:0 28px;

    border-radius:14px;

    font-weight:700;

    transition:.3s ease;

    box-shadow:
        0 10px 25px rgba(255,87,34,0.25);
}

.upgrade-btn:hover{
    transform:translateY(-2px);
    color:white;
}

/* LOCKED CONTENT */
.locked-blur{
    filter:blur(1.5px);
    pointer-events:none;
    user-select:none;
}

/* MOBILE */
@media(max-width:768px){

    .feature-lock-overlay{
        padding:20px;
        padding-top:50px;
    }

    .feature-lock-box{
        padding:25px 20px;
    }

    .feature-lock-box h2{
        font-size:28px;
    }

}
</style>

<div class="barcode-wrapper">

    {{-- 🔥 LOCKED FEATURE --}}
    @if($isLocked)
        <div class="feature-lock-overlay">
            <div class="feature-lock-box compact-lock">
                <div class="feature-badge">
                    Premium Business Feature
                </div>

                <div class="lock-icon">
                    <i class='bx bx-lock-alt'></i>
                </div>

                <h2>Barcode Manager Locked</h2>

                <p>
                    Your current <strong>{{ ucfirst($user->plan) }}</strong> plan does not include advanced barcode tools.
                    Upgrade to the <strong>Business</strong> plan to unlock unlimited barcode generation,
                    smart inventory scanning, and faster checkout operations.
                </p>

                <a href="{{ url('/pricing') }}" class="upgrade-btn">
                    Upgrade Plan
                </a>

            </div>
        </div>
    @endif

    {{-- 🔥 CONTENT --}}
    <div class="{{ $isLocked ? 'locked-blur' : '' }}">

        <div class="container">
            <h2 class="mb-4">
                <i class='bx bx-package'></i>
                Barcode Manager
            </h2>

            <!-- Generate barcode from code -->
            <div class="card p-3 mb-4">
                <h5>Generate Barcode from Code</h5>

                <div class="input-group mb-3">
                    <input type="text" id="manual-barcode" class="form-control" placeholder="Enter existing barcode number">

                    <button type="button" id="generate-manual-barcode" class="btn btn-primary">
                        Generate Barcode
                    </button>
                </div>

                <div id="manual-barcode-preview" class="text-center" style="margin-bottom:20px;"></div>
            </div>

            <!-- Saved Barcodes Section -->
            <div class="card p-3">
                <h5>Saved Barcodes (Local Storage)</h5>

                <div id="saved-barcodes" class="d-flex flex-wrap gap-3 mt-3 flex-wrap">
                    <!-- Generated barcodes will appear here -->
                </div>

                <div class="mt-4">
                    <button id="download-all-barcodes" class="btn btn-success me-2" style="display:none;">
                        Download All (ZIP)
                    </button>

                    <button id="clear-all-barcodes" class="btn btn-danger">
                        Clear All
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ✅ Libraries -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

<script>
function renderSavedBarcodes() {
    const saved = JSON.parse(localStorage.getItem('barcodes') || '[]');
    const container = document.getElementById('saved-barcodes');

    container.innerHTML = '';

    saved.forEach(item => {
        container.innerHTML += `
            <div class="text-center border p-2 rounded">
                <img src="${item.image}" alt="${item.code}" height="80" style="border:1px solid #ccc; padding:5px;">
                <p class="mt-2 fw-bold">${item.name || 'Unnamed Product'}</p>
            </div>
        `;
    });

    document.getElementById('download-all-barcodes').style.display =
        saved.length ? 'inline-block' : 'none';
}

// ✅ Generate barcode manually
document.getElementById('generate-manual-barcode').addEventListener('click', async function() {

    const code = document.getElementById('manual-barcode').value.trim();

    if (!code) {
        return alert('Please enter a barcode number');
    }

    let productName = 'Unknown Product';

    try {
        const res = await fetch(`/get-product/${code}`);
        const data = await res.json();

        if (data.success && data.name) {
            productName = data.name;
        }

    } catch (error) {
        console.error('Error fetching product:', error);
    }

    const canvas = document.createElement('canvas');

    JsBarcode(canvas, code, {
        format: "CODE128",
        lineColor: "#000",
        width: 2,
        height: 80,
        displayValue: true
    });

    const imgData = canvas.toDataURL('image/png');

    const preview = document.getElementById('manual-barcode-preview');

    preview.innerHTML = `
        <div style="text-align:center">
            <img src="${imgData}" alt="${code}" height="80" style="border:1px solid #ccc; padding:5px;"><br>
            <strong>${productName}</strong>
        </div>
    `;

    let saved = JSON.parse(localStorage.getItem('barcodes') || '[]');

    if (!saved.some(b => b.code === code)) {
        saved.push({
            code: code,
            image: imgData,
            name: productName
        });

        localStorage.setItem('barcodes', JSON.stringify(saved));
    }

    renderSavedBarcodes();
});

// ✅ Download all barcodes as ZIP
document.getElementById('download-all-barcodes').addEventListener('click', async function() {

    const saved = JSON.parse(localStorage.getItem('barcodes') || '[]');

    if (saved.length === 0) {
        return alert('No barcodes to download');
    }

    const zip = new JSZip();

    saved.forEach(item => {
        const imgData = item.image.split(',')[1];

        zip.file(`${item.code}.png`, atob(imgData), {
            binary: true
        });
    });

    const blob = await zip.generateAsync({
        type: 'blob'
    });

    saveAs(blob, 'barcodes.zip');
});

// ✅ Clear all barcodes
document.getElementById('clear-all-barcodes').addEventListener('click', function() {

    if (confirm('Are you sure you want to clear all saved barcodes?')) {

        localStorage.removeItem('barcodes');

        renderSavedBarcodes();
    }
});

// ✅ On page load
renderSavedBarcodes();
</script>

@endsection