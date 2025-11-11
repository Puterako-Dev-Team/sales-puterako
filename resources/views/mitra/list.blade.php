@extends('layouts.app')

@section('content')
<div class="container mx-auto p-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-xl font-bold">List Mitra</h1>
        <button id="btnTambahMitra"
            class="bg-green-600 text-white px-4 py-2 rounded flex items-center gap-2 text-sm hover:bg-green-700 transition">
            + Tambah Mitra
        </button>
    </div>

    <!-- Filter -->
    <div class="bg-white shadow rounded-lg p-4 mb-4">
        <form id="filterForm" class="flex items-center gap-3">
            <input type="text" name="q" id="filterQ" class="border rounded px-3 py-2 w-full md:w-1/3"
                   placeholder="Cari nama/kota/alamat..." value="{{ request('q') }}">
            <input type="hidden" name="sort" value="{{ request('sort','id_mitra') }}">
            <input type="hidden" name="direction" value="{{ request('direction','asc') }}">
            <button type="submit" class="px-3 py-2 border rounded hover:bg-gray-50">Filter</button>
            <button type="button" id="btnReset" class="px-3 py-2 border rounded hover:bg-gray-50">Reset</button>
        </form>
    </div>

    <!-- Table -->
    <div id="tableContainer" class="bg-white shadow rounded-lg p-4">
        @include('mitra.table-content', ['mitras'=>$mitras])
    </div>

    <!-- Pagination -->
    <div id="paginationContent" class="mt-4">
        @include('penawaran.pagination', ['paginator'=>$mitras])
    </div>
</div>

<!-- Slide-over Tambah Mitra -->
<div id="formSlideMitra" class="fixed inset-0 bg-black bg-opacity-30 z-50 hidden opacity-0 transition-opacity duration-300">
    <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-lg p-8 transition-transform duration-300 ease-in-out transform translate-x-full"
         id="formPanelMitra">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Tambah Mitra</h2>
            <button id="closeFormMitra" class="text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-x" width="24" height="24"
                    fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>
        </div>
        <form id="formTambahMitra">
            @csrf
            <div class="mb-4">
                <label class="block mb-1 font-medium text-sm">Nama Perusahaan</label>
                <input type="text" name="nama_mitra" class="w-full border rounded px-3 py-2 text-sm" required>
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-medium text-sm">Provinsi</label>
                <select name="provinsi" id="provinsiSelect" class="w-full border rounded px-3 py-2 text-sm">
                    <option value="">Pilih Provinsi...</option>
                </select>
            </div>   
            <div class="mb-4">
                <label class="block mb-1 font-medium text-sm">Kota/Kabupaten</label>
                <select name="kota" id="kotaSelect" class="w-full border rounded px-3 py-2 text-sm" required disabled>
                    <option value="">Pilih Kota/Kabupaten...</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-medium text-sm">Alamat</label>
                <textarea name="alamat" rows="3" class="w-full border rounded px-3 py-2 text-sm"></textarea>
            </div>
            <div class="absolute bottom-0 left-0 w-full p-4 bg-white border-t flex gap-3">
                <button type="submit"
                        class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition text-sm">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Slide-over logic
    const formSlideMitra = document.getElementById('formSlideMitra');
    const formPanelMitra = document.getElementById('formPanelMitra');
    const btnTambahMitra = document.getElementById('btnTambahMitra');
    const closeFormMitra = document.getElementById('closeFormMitra');
    const batalFormMitra = document.getElementById('batalFormMitra');

    // Wilayah Indonesia (EMSIFA)
    const EMSIFA = 'https://www.emsifa.com/api-wilayah-indonesia/api';
    const provinsiSelect = document.getElementById('provinsiSelect');
    const kotaSelect = document.getElementById('kotaSelect');
    let provincesLoaded = false;
    const regencyCache = new Map();

    function titleCase(s) {
        return s.toLowerCase().split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
    }
    function formatRegionName(name) {
        let t = titleCase(name);
        t = t.replace(/^Kabupaten\s+/i, 'Kab. ');
        return t;
    }

    async function loadProvincesOnce() {
        if (provincesLoaded) return;
        try {
            const res = await fetch(`${EMSIFA}/provinces.json`);
            const data = await res.json();
            data.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id;            // id provinsi
                opt.textContent = formatRegionName(p.name);
                provinsiSelect.appendChild(opt);
            });
            provincesLoaded = true;
        } catch (e) {
            console.error('Load provinces failed', e);
        }
    }

    async function loadRegencies(provinceId) {
        kotaSelect.innerHTML = '<option value="">Pilih Kota/Kabupaten...</option>';
        kotaSelect.disabled = true;
        if (!provinceId) return;

        try {
            let regencies = regencyCache.get(provinceId);
            if (!regencies) {
                const res = await fetch(`${EMSIFA}/regencies/${provinceId}.json`);
                regencies = await res.json();
                regencyCache.set(provinceId, regencies);
            }
            regencies.forEach(k => {
                const opt = document.createElement('option');
                // Simpan nama kota/kabupaten sebagai value untuk dikirim ke backend
                opt.value = formatRegionName(k.name);
                opt.textContent = formatRegionName(k.name);
                kotaSelect.appendChild(opt);
            });
            kotaSelect.disabled = false;
        } catch (e) {
            console.error('Load regencies failed', e);
        }
    }

    provinsiSelect?.addEventListener('change', (e) => {
        loadRegencies(e.target.value);
    });

    function openMitraForm() {
        formSlideMitra.classList.remove('hidden');
        // Next frame: fade-in backdrop + slide-in panel
        requestAnimationFrame(() => {
            formSlideMitra.classList.remove('opacity-0');
            formSlideMitra.classList.add('opacity-100');
            formPanelMitra.classList.remove('translate-x-full');
            formPanelMitra.classList.add('translate-x-0');
        });
        loadProvincesOnce();
    }
    function closeMitraForm() {
        // Start animations: slide-out + fade-out
        formPanelMitra.classList.remove('translate-x-0');
        formPanelMitra.classList.add('translate-x-full');
        formSlideMitra.classList.remove('opacity-100');
        formSlideMitra.classList.add('opacity-0');
        // After transition ends, hide container
        setTimeout(() => {
            formSlideMitra.classList.add('hidden');
        }, 300); // match duration-300
    }

    btnTambahMitra.addEventListener('click', openMitraForm);
    closeFormMitra.addEventListener('click', closeMitraForm);
    batalFormMitra?.addEventListener('click', closeMitraForm);
    formSlideMitra.addEventListener('click', e => {
        if (e.target === formSlideMitra) closeMitraForm();
    });

    // Submit tambah mitra (AJAX)
    document.getElementById('formTambahMitra').addEventListener('submit', function(e){
        e.preventDefault();
        const fd = new FormData(e.target);

        // Provinsi: simpan nama (bukan ID)
        const provText = provinsiSelect.value
            ? provinsiSelect.options[provinsiSelect.selectedIndex].text
            : '';
        fd.set('provinsi', provText);

        // Tutup dulu biar terasa cepat
        closeMitraForm();

        fetch("{{ route('mitra.store') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: fd
        })
        .then(r => r.json())
        .then(() => {
            e.target.reset();
            kotaSelect.innerHTML = '<option value="">Pilih Kota/Kabupaten...</option>';
            kotaSelect.disabled = true;
            provinsiSelect.value = '';
            reloadKlien(); // refresh tabel tanpa alert
        })
        .catch(() => {
            // tetap refresh agar sinkron meski gagal
            reloadKlien();
        });
    });

    // Filter + sort + pagination (tetap)
    document.getElementById('filterForm').addEventListener('submit', function(e){
        e.preventDefault();
        reloadKlien();
    });
    document.getElementById('btnReset').addEventListener('click', () => {
        document.getElementById('filterQ').value = '';
        document.querySelector('input[name="sort"]').value = 'id_mitra';
        document.querySelector('input[name="direction"]').value = 'asc';
        reloadKlien();
    });

    document.addEventListener('click', function(e){
        const sortBtn = e.target.closest('.sort-button');
        if (sortBtn && sortBtn.dataset.column) {
            e.preventDefault();
            document.querySelector('input[name="sort"]').value = sortBtn.dataset.column;
            document.querySelector('input[name="direction"]').value = sortBtn.dataset.direction;
            reloadKlien();
            return;
        }
        if (e.target.closest('.pagination-link')) {
            e.preventDefault();
            const url = new URL(e.target.closest('.pagination-link').href);
            reloadKlien(url.searchParams.get('page') || 1);
        }
    });

    function reloadKlien(page = 1) {
        const params = new URLSearchParams(new FormData(document.getElementById('filterForm')));
        params.set('page', page);
        fetch(`{{ route('mitra.filter') }}?` + params.toString())
            .then(r => r.json()).then(res => {
                document.getElementById('tableContainer').innerHTML = res.table;
                document.getElementById('paginationContent').innerHTML = res.pagination;
            });
    }
</script>
@endpush