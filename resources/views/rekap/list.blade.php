{{-- filepath: resources/views/rekap/list.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="container mx-auto p-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-xl font-bold">Daftar Rekap</h1>
            <button id="btnTambahRekap"
                class="bg-green-600 text-white px-4 py-2 rounded flex items-center gap-2 text-sm hover:bg-green-700 transition">
                <x-lucide-plus class="w-5 h-5 inline" />
                Tambah Rekap
            </button>
        </div>

        <div class="bg-white shadow rounded-lg" id="tableContainer">
            @include('rekap.table-content', ['rekaps' => $rekaps])
        </div>

        <div class="mt-6">
            {{ $rekaps->links() }}
        </div>
    </div>

    <!-- Slide-over Form Rekap -->
    <div id="formSlideRekap" class="fixed inset-0 bg-black bg-opacity-30 z-50 hidden">
        <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-lg transition-transform transform translate-x-full"
            id="formPanelRekap">
            <div class="sticky top-0 bg-white border-b border-gray-100 p-6 z-10">
                <div class="flex justify-between items-center">
                    <h2 id="formTitleRekap" class="text-xl font-bold">Tambah Rekap</h2>
                    <button id="closeFormRekap" class="text-gray-500 hover:text-gray-700 p-1 hover:bg-gray-100 rounded">
                        <svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-x" width="24" height="24"
                            fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <form id="rekapForm" method="POST" action="{{ route('rekap.store') }}">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block mb-2 font-medium text-sm text-gray-700">Nama Rekap</label>
                            <input type="text" name="nama" id="f_nama_rekap"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                        </div>
                        <div>
                            <label class="block mb-2 font-medium text-sm text-gray-700">No Penawaran</label>
                            <select name="penawaran_id" id="f_penawaran_id"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                                <option value="">Pilih Penawaran...</option>
                                @foreach ($penawarans as $p)
                                    <option value="{{ $p->id_penawaran }}" data-perusahaan="{{ $p->nama_perusahaan }}">
                                        {{ $p->no_penawaran }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 font-medium text-sm text-gray-700">Nama Perusahaan</label>
                            <input type="text" name="nama_perusahaan" id="f_nama_perusahaan"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100" readonly
                                required>
                        </div>
                    </div>
                    <div class="mt-8 pt-6 border-t border-gray-100">
                        <button type="submit"
                            class="w-full bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center gap-2 font-medium">
                            Simpan Rekap
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const btnTambahRekap = document.getElementById('btnTambahRekap');
        const formSlideRekap = document.getElementById('formSlideRekap');
        const formPanelRekap = document.getElementById('formPanelRekap');
        const closeFormRekap = document.getElementById('closeFormRekap');
        const rekapForm = document.getElementById('rekapForm');
        const f_penawaran_id = document.getElementById('f_penawaran_id');
        const f_nama_perusahaan = document.getElementById('f_nama_perusahaan');

        btnTambahRekap.addEventListener('click', function() {
            rekapForm.reset();
            f_nama_perusahaan.value = '';
            formSlideRekap.classList.remove('hidden');
            requestAnimationFrame(() => {
                formPanelRekap.classList.remove('translate-x-full');
                formPanelRekap.classList.add('translate-x-0');
            });
        });

        closeFormRekap.addEventListener('click', function() {
            formPanelRekap.classList.remove('translate-x-0');
            formPanelRekap.classList.add('translate-x-full');
            setTimeout(() => formSlideRekap.classList.add('hidden'), 350);
        });

        formSlideRekap.addEventListener('click', function(e) {
            if (e.target === formSlideRekap) {
                formPanelRekap.classList.remove('translate-x-0');
                formPanelRekap.classList.add('translate-x-full');
                setTimeout(() => formSlideRekap.classList.add('hidden'), 350);
            }
        });

        // Auto-fill nama perusahaan dari penawaran
        f_penawaran_id.addEventListener('change', function() {
            var perusahaan = this.options[this.selectedIndex].getAttribute('data-perusahaan');
            f_nama_perusahaan.value = perusahaan || '';
        });

        document.addEventListener('DOMContentLoaded', updatePreview);
    </script>
@endsection
