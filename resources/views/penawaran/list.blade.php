@extends('layouts.app')

@section('content')
    <style>
        #formPanel {
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .translate-x-full {
            transform: translateX(100%);
        }

        .translate-x-0 {
            transform: translateX(0);
        }
    </style>
    <div class="container mx-auto p-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-xl font-bold">List Penawaran</h1>
            <button id="btnTambah"
                class="bg-green-500  text-white px-4 py-2 rounded flex items-center gap-2 text-sm hover:bg-green-700 transition">
                <x-lucide-plus class="w-5 h-5 inline" />
                Tambah
            </button>
        </div>
        <div class="bg-white shadow rounded-lg">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-green-500 text-white">
                        <th class="px-2 py-2 font-semibold text-center">ID</th>
                        <th class="px-2 py-2 font-semibold text-center">No Penawaran</th>
                        <th class="px-2 py-2 font-semibold text-center">Perihal</th>
                        <th class="px-2 py-2 font-semibold text-center">Nama Perusahaan</th>
                        <th class="px-2 py-2 font-semibold text-center">PIC Perusahaan</th>
                        <th class="px-2 py-2 font-semibold text-center">PIC Admin</th>
                        <th class="px-2 py-2 font-semibold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($penawarans as $p)
                        <tr class="border-b transition">
                            <td class="px-2 py-2 text-center">{{ $p->id_penawaran }}</td>
                            <td class="px-2 py-2 text-center">{{ $p->no_penawaran }}</td>
                            <td class="px-2 py-2">{{ $p->perihal }}</td>
                            <td class="px-2 py-2">{{ $p->nama_perusahaan }}</td>
                            <td class="px-2 py-2">{{ $p->pic_perusahaan }}</td>
                            <td class="px-2 py-2">{{ $p->pic_admin }}</td>
                            <td class="px-2 py-2 text-center">
                                <div class="flex gap-1 justify-center">
                                    @if ($p->tiket)
                                        <button
                                            class="bg-gray-300 text-gray-700 px-2 py-1 rounded flex items-center gap-1 text-xs"
                                            disabled>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-ticket"
                                                width="14" height="14" fill="none" stroke="currentColor"
                                                stroke-width="2">
                                                <path d="M3 7V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v2" />
                                                <path d="M21 7v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7" />
                                                <path d="M7 7v10" />
                                                <path d="M17 7v10" />
                                                <path d="M9 11h6" />
                                            </svg>
                                            Tiket
                                        </button>
                                    @else
                                        <a href="{{ route('penawaran.show', ['id' => $p->id_penawaran]) }}"
                                            class="bg-green-500 text-white px-2 py-2 rounded hover:bg-green-600 transition"
                                            title="Lihat Detail">
                                            <x-lucide-file-text class="w-5 h-5 inline" />
                                        </a>
                                        <button
                                            class="bg-yellow-500 text-white px-2 py-2 rounded flex items-center gap-1 text-xs hover:bg-yellow-700 transition"
                                            title="Edit">
                                            <x-lucide-square-pen class="w-5 h-5 inline" />
                                        </button>
                                    @endif
                                    <button class="bg-red-500 text-white px-2 py-2 rounded hover:bg-red-700 transition"
                                        title="Hapus Data">
                                        <x-lucide-trash-2 class="w-5 h-5 inline" />
                                    </button>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8">
                                <div class="flex flex-col items-center justify-center text-gray-500 gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-ticket" width="32"
                                        height="32" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 7V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v2" />
                                        <path d="M21 7v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7" />
                                        <path d="M7 7v10" />
                                        <path d="M17 7v10" />
                                        <path d="M9 11h6" />
                                    </svg>
                                    Belum ada tiket penawaran
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Slide-over Form -->
    <div id="formSlide" class="fixed inset-0 bg-black bg-opacity-30 z-50 hidden">
        <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-lg p-8 transition-transform transform translate-x-full"
            id="formPanel">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Tambah Penawaran</h2>
                <button id="closeForm" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-x" width="24" height="24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>
            <form id="formPanel" method="POST" action="{{ route('penawaran.store') }}">
                @csrf
                <div class="mb-4">
                    <label class="block mb-1 font-medium text-sm">Perihal</label>
                    <input type="text" name="perihal" class="w-full border rounded px-3 py-2 text-sm" required>
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-medium text-sm">Nama Perusahaan</label>
                    <input type="text" name="nama_perusahaan" class="w-full border rounded px-3 py-2 text-sm" required>
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-medium text-sm">Lokasi</label>
                    <input type="text" name="lokasi" class="w-full border rounded px-3 py-2 text-sm" required>
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-medium text-sm">PIC Perusahaan</label>
                    <input type="text" name="pic_perusahaan" class="w-full border rounded px-3 py-2 text-sm" required>
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-medium text-sm">PIC Admin</label>
                    <input type="text" name="pic_admin" class="w-full border rounded px-3 py-2 text-sm"
                        value="Admin Dummy" required>
                </div>
                <div class="mb-4">
                    <label class="block mb-1 font-medium text-sm">No Penawaran</label>
                    <input type="text" name="no_penawaran" class="w-full border rounded px-3 py-2 text-sm" required>
                </div>
                <div class="absolute bottom-0 left-0 w-full p-4 bg-white border-t">
                    <button type="submit"
                        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition w-full text-sm">
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
        const btnTambah = document.getElementById('btnTambah');
        const formSlide = document.getElementById('formSlide');
        const formPanel = document.getElementById('formPanel');
        const closeForm = document.getElementById('closeForm');

        btnTambah.addEventListener('click', function() {
            formSlide.classList.remove('hidden');
            setTimeout(() => {
                formPanel.classList.remove('translate-x-full');
                formPanel.classList.add('translate-x-0');
            }, 10);
        });

        function closeModal() {
            formPanel.classList.remove('translate-x-0');
            formPanel.classList.add('translate-x-full');
            setTimeout(() => {
                formSlide.classList.add('hidden');
            }, 400); // Sesuaikan dengan durasi transition
        }

        closeForm.addEventListener('click', closeModal);

        formSlide.addEventListener('click', function(e) {
            if (e.target === formSlide) {
                closeModal();
            }
        });
    </script>
@endpush
