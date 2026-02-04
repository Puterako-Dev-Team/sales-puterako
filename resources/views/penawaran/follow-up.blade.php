<!-- filepath: c:\laragon\www\sales-puterako\resources\views\penawaran\follow-up.blade.php -->
@extends('layouts.app')

@section('content')
    <style>
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-draft {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-lost {
            background: #fecaca;
            color: #dc2626;
        }

        .status-success {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-po {
            background: #804cb2;
            color: #ffffff;
        }

        .timeline-item {
            position: relative;
            padding-left: 2rem;
            margin-bottom: 1.5rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            bottom: -1.5rem;
            width: 2px;
            background: #e5e7eb;
        }

        .timeline-item:last-child::before {
            display: none;
        }

        .timeline-dot {
            position: absolute;
            left: 0;
            top: 0.5rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background: #3b82f6;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #e5e7eb;
        }

        /* Mini timeline di sidebar */
        .mini-timeline {
            position: relative;
            padding-left: 1.5rem;
            margin-bottom: 0.75rem;
        }

        .mini-timeline::before {
            content: '';
            position: absolute;
            left: 0.375rem;
            top: 0;
            bottom: -0.75rem;
            width: 2px;
            background: #e5e7eb;
        }

        .mini-timeline:last-child::before {
            display: none;
        }

        .mini-dot {
            position: absolute;
            left: 0;
            top: 0.25rem;
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 0 1px #e5e7eb;
        }

        .mini-dot-blue {
            background: #3b82f6;
        }

        .mini-dot-green {
            background: #10b981;
        }

        .mini-dot-purple {
            background: #8b5cf6;
        }

        .mini-dot-orange {
            background: #f59e0b;
        }

        /* Slide form styles */
        #formSlide {
            transition: opacity 0.3s ease;
        }

        #formPanel {
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .translate-x-full {
            transform: translateX(100%);
        }

        .translate-x-0 {
            transform: translateX(0);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6b7280;
        }

        .hasil-progress-container {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .hasil-progress-text {
            flex: 1;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .hasil-progress-icon {
            flex-shrink: 0;
            margin-top: 0.125rem;
        }
    </style>

    <div class="container mx-auto p-6">
        <!-- Header dengan tombol back -->
        <div class="flex items-center p-8 text-gray-600 -mt-4 ">
            <a href="{{ route('penawaran.list') }}" class="flex items-center hover:text-green-600">
                <x-lucide-arrow-left class="w-5 h-5 mr-2" />
                List Penawaran
            </a>
            <span class="mx-2">/</span>
            <span class="font-semibold">Follow Up Penawaran </span>
        </div>

        <!-- Detail Penawaran (Header) -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <div class="flex flex-wrap gap-8">
                <!-- Kolom 1 -->
                <div class="flex-1 min-w-[250px]">
                    <h3 class="font-semibold text-gray-800 mb-4">Informasi Penawaran</h3>
                    <div class="space-y-2 text-sm">
                        <div><span class="font-medium">No. Penawaran:</span> {{ $penawaran->no_penawaran }}</div>
                        <div><span class="font-medium">Perihal:</span> {{ $penawaran->perihal }}</div>
                        <div><span class="font-medium">Status:</span>
                            @if($penawaran->status === 'po')
                                <span class="status-badge status-po">
                                    Purchase Order
                                </span>
                            @else
                                <span class="status-badge status-{{ $penawaran->status }}">
                                    {{ ucfirst($penawaran->status) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Kolom 2 -->
                <div class="flex-1 min-w-[250px]">
                    <h3 class="font-semibold text-gray-800 mb-4">Informasi Perusahaan</h3>
                    <div class="space-y-2 text-sm">
                        <div><span class="font-medium">Perusahaan:</span> {{ $penawaran->nama_perusahaan }}</div>
                        <div><span class="font-medium">Lokasi:</span> {{ $penawaran->lokasi }}</div>
                        <div><span class="font-medium">PIC Perusahaan:</span> {{ $penawaran->pic_perusahaan ?? 'N/A' }}
                        </div>
                    </div>
                </div>

                <!-- Kolom 3 -->
                <div class="flex-1 min-w-[250px]">
                    <h3 class="font-semibold text-gray-800 mb-4">PIC Admin</h3>
                    <div class="space-y-2 text-sm">
                        <div><span class="font-medium">Nama:</span> {{ $penawaran->user ? $penawaran->user->name : 'N/A' }}
                        </div>
                        <div><span class="font-medium">Email:</span>
                            {{ $penawaran->user ? $penawaran->user->email : 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Container untuk Activity & Riwayat -->
        <div class="flex gap-6">

            <!-- Aktivitas Follow Up (75%) -->
            <div class="w-3/4">
                <div class="bg-white shadow rounded-lg p-6">
                    <!-- Header dengan tombol tambah -->
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold flex items-center gap-2">
                            <x-lucide-activity class="w-5 h-5 opacity-50" />
                            Aktivitas Follow Up
                        </h3>
                        <button id="btnTambahFollowUp"
                            class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition flex items-center gap-2 text-sm">
                            <x-lucide-plus class="w-4 h-4" />
                            Tambah Aktivitas
                        </button>
                    </div>
                    <hr class="my-4">

                    <div id="timelineContainer">
                        @if ($followUps && $followUps->count() > 0)
                            <!-- Update di follow-up.blade.php bagian timeline -->
                            @foreach ($followUps as $followUp)
                                <div class="bg-white rounded-md border-l-4 {{ $followUp->is_system_generated ? 'border-yellow-500 bg-yellow-50' : 'border-green-500' }} shadow-lg p-4 mb-4 hover:shadow-xl transition-all duration-300 ease-out cursor-pointer">
                                    <!-- Header dengan icon khusus untuk system reminder -->
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex items-center gap-2">
                                            @if ($followUp->is_system_generated)
                                                <x-lucide-bell class="w-4 h-4 text-yellow-600" />
                                                <h4 class="font-semibold text-yellow-800 text-sm">{{ $followUp->nama }}
                                                </h4>
                                                <span
                                                    class="px-2 py-1 text-xs bg-yellow-200 text-yellow-800 rounded-full">SYSTEM</span>
                                            @else
                                                <h4 class="font-semibold text-gray-800 text-sm">{{ $followUp->nama }}</h4>
                                            @endif
                                        </div>

                                        <div class="text-right text-sm text-gray-500">
                                            <div>{{ \Carbon\Carbon::parse($followUp->created_at)->format('d M Y') }}</div>
                                        </div>
                                    </div>

                                    <!-- Jenis dengan styling khusus untuk reminder -->
                                    <div class="flex justify-between items-center mb-3">
                                        <div class="flex items-center gap-2">
                                            @if ($followUp->jenis == 'reminder')
                                                <x-lucide-clock class="w-4 h-4 text-yellow-600" />
                                                <span class="text-sm text-yellow-600 font-medium">Reminder Sistem</span>
                                            @elseif ($followUp->jenis == 'telepon')
                                                <x-lucide-phone class="w-4 h-4 text-blue-600" />
                                                <span class="text-sm text-blue-600">Telepon</span>
                                            @elseif($followUp->jenis == 'email')
                                                <x-lucide-mail class="w-4 h-4 text-green-600" />
                                                <span class="text-sm text-green-600">Email</span>
                                            @elseif($followUp->jenis == 'whatsapp')
                                                <x-lucide-message-circle class="w-4 h-4 text-orange-600" />
                                                <span class="text-sm text-orange-600">WhatsApp</span>
                                            @elseif($followUp->jenis == 'kunjungan')
                                                <x-lucide-map-pin class="w-4 h-4 text-red-600" />
                                                <span class="text-sm text-red-600">Kunjungan</span>
                                            @endif
                                        </div>

                                        <!-- Kanan: PIC + Status Badge -->
                                        <div class="flex items-center gap-3">
                                            <!-- PIC Perusahaan -->
                                            @if ($followUp->pic_perusahaan)
                                                <div class="flex items-center gap-2">
                                                    <x-lucide-user class="w-4 h-4 text-gray-400" />
                                                    <span
                                                        class="text-sm text-gray-600">{{ $followUp->pic_perusahaan }}</span>
                                                </div>
                                            @endif

                                            <!-- Status Badge -->
                                            <span
                                                class="px-2 py-1 text-xs rounded-full {{ $followUp->status == 'progress'
                                                    ? 'bg-yellow-100 text-yellow-800'
                                                    : ($followUp->status == 'deal'
                                                        ? 'bg-green-100 text-green-800'
                                                        : 'bg-gray-100 text-gray-800') }}">
                                                {{ ucfirst($followUp->status) }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Deskripsi dengan styling berbeda untuk system -->
                                    <div class="mb-3">
                                        <p
                                            class="text-sm {{ $followUp->is_system_generated ? 'text-yellow-700 font-medium' : 'text-gray-700' }} leading-relaxed">
                                            {{ $followUp->deskripsi }}
                                        </p>
                                    </div>

                                    @if ($followUp->hasil_progress)
                                        <div
                                            class="{{ $followUp->is_system_generated ? 'bg-yellow-50' : 'bg-gray-50' }} rounded-lg p-3 mt-3">
                                            <div class="hasil-progress-container">
                                                @if ($followUp->is_system_generated)
                                                    <x-lucide-alert-triangle
                                                        class="w-4 h-4 text-yellow-600 hasil-progress-icon" />
                                                @else
                                                    <x-lucide-clipboard-check
                                                        class="w-4 h-4 text-green-600 hasil-progress-icon" />
                                                @endif
                                                <div class="hasil-progress-text">
                                                    <div class="font-medium text-sm text-gray-800 mb-1">
                                                        {{ $followUp->is_system_generated ? 'Tindakan yang Disarankan:' : 'Hasil Progress:' }}
                                                    </div>
                                                    <p class="text-sm text-gray-700">{{ $followUp->hasil_progress }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="flex justify-end mt-3">
                                        <span class="text-xs text-gray-400">
                                            {{ \Carbon\Carbon::parse($followUp->created_at)->diffForHumans() }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <!-- KOSONG jika belum ada aktivitas -->
                            <div class="empty-state">
                                <x-lucide-calendar-x class="w-16 h-16 mx-auto mb-4 opacity-30" />
                                <h4 class="text-lg font-medium mb-2">Belum Ada Aktivitas Follow Up</h4>
                                <p class="text-sm text-gray-500">Klik "Tambah Aktivitas" untuk memulai follow up penawaran
                                    ini</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Riwayat Follow Up (25%) -->
            <div class="w-1/4">
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                        <x-lucide-clock class="w-5 h-5" />
                        Riwayat Follow Up
                    </h3>

                    <hr class="my-4">

                    <!-- Progress Summary -->
                    <div class="space-y-4 text-sm mb-6 relative">
                        <!-- Garis vertikal penghubung -->
                        <div class="absolute left-1.5 top-6 bottom-6 w-1 bg-green-100"></div>

                        <!-- Penawaran Dibuat -->
                        <div class="flex items-center gap-3 relative">
                            <div class="w-4 h-4 rounded-full bg-green-500 border-2 border-white shadow-sm z-10"></div>
                            <div>
                                <div class="font-medium">Penawaran Dibuat</div>
                                <div class="text-gray-600">{{ $penawaran->created_at->format('d M Y') }}</div>
                            </div>
                        </div>

                        <!-- Follow Ups dari Database -->
                        @if ($followUps && $followUps->count() > 0)
                            @foreach ($followUps->sortBy('created_at') as $index => $followUp)
                                <div
                                    class="flex items-center gap-3 relative {{ $followUp->is_system_generated ? 'bg-yellow-50 border border-yellow-200 rounded-lg p-2 -ml-2' : '' }}">
                                    <div
                                        class="w-4 h-4 rounded-full 
            {{ $followUp->is_system_generated ? 'bg-yellow-500 border-2 border-white shadow-lg ring-2 ring-yellow-200 animate-pulse' : 'bg-green-500 border-2 border-white' }} 
            shadow-sm z-10">
                                    </div>
                                    <div>
                                        <div
                                            class="font-medium {{ $followUp->is_system_generated ? 'text-yellow-800' : '' }}">
                                            {{ $followUp->is_system_generated ? '🔔 Reminder' : 'Follow Up ' . ($index + 1) }}
                                            @if ($followUp->is_system_generated)
                                                <span
                                                    class="inline-block w-2 h-2 bg-yellow-400 rounded-full ml-2 animate-ping"></span>
                                            @endif
                                        </div>
                                        <div
                                            class="text-gray-600 text-xs {{ $followUp->is_system_generated ? 'text-yellow-700' : '' }}">
                                            {{ \Carbon\Carbon::parse($followUp->created_at)->format('d M Y, H:i') }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <!-- Jika belum ada follow up -->
                            <div class="flex items-center gap-3 relative opacity-50">
                                <div class="w-4 h-4 rounded-full bg-gray-300 border-2 border-white shadow-sm z-10"></div>
                                <div>
                                    <div class="font-medium text-gray-400">Belum ada follow up</div>
                                    <div class="text-gray-400 text-xs">Mulai follow up pertama</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Slide-over Form Modal -->
    <div id="formSlide" class="fixed inset-0 bg-black bg-opacity-30 z-50 hidden">
        <div class="absolute right-0 top-0 h-full w-full max-w-md p-6 bg-white shadow-lg transition-transform transform translate-x-full overflow-y-auto"
            id="formPanel">
            <!-- Header -->
            <div class="p-4 border-b">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-bold">Tambah Follow Up</h2>
                    <button id="closeForm" class="text-gray-500 hover:text-gray-700">
                        <x-lucide-x class="w-5 h-5" />
                    </button>
                </div>
            </div>
            <!-- Form Content -->
            <div class="p-4">
                <form id="followUpForm" method="POST"
                    action="{{ route('penawaran.followUp.store', $penawaran->id_penawaran) }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Nama Follow Up</label>
                        <input type="text" name="nama" id="namaFollowUp"
                            class="w-full border rounded px-3 py-2 text-sm" placeholder="Contoh: Follow up proposal awal"
                            required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Jenis</label>
                        <select name="jenis" id="jenisFollowUp" class="w-full border rounded px-3 py-2 text-sm"
                            required>
                            <option value="">Pilih Jenis...</option>
                            <option value="telepon">Telepon</option>
                            <option value="email">Email</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="kunjungan">Kunjungan</option>
                            <option value="meeting">Meeting</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">PIC Perusahaan</label>
                        <input type="text" name="pic_perusahaan" id="picPerusahaanFollowUp"
                            class="w-full border rounded px-3 py-2 text-sm" placeholder="Nama PIC yang dihubungi">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Kontak</label>
                        <input type="text" name="kontak" id="kontakFollowUp"
                            class="w-full border rounded px-3 py-2 text-sm" placeholder="Nomor telepon/email">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Deskripsi</label>
                        <textarea name="deskripsi" id="deskripsiFollowUp" rows="3" class="w-full border rounded px-3 py-2 text-sm"
                            placeholder="Tujuan atau rencana follow up..." required></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Hasil Progress</label>
                        <textarea name="hasil_progress" id="hasilProgressFollowUp" rows="3"
                            class="w-full border rounded px-3 py-2 text-sm" placeholder="Hasil dari follow up (opsional)"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-1">Status</label>
                        <select name="status" id="statusFollowUp" class="w-full border rounded px-3 py-2 text-sm"
                            required>
                            <option value="progress">Progress</option>
                            <option value="deal">Deal</option>
                            <option value="pending">Pending</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <!-- Submit Button -->
                    <div class="border-t pt-4">
                        <button type="submit"
                            class="w-full bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700">
                            Simpan Follow Up
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded');

            // Modal elements
            const btnTambah = document.getElementById('btnTambahFollowUp');
            const formSlide = document.getElementById('formSlide');
            const formPanel = document.getElementById('formPanel');
            const closeFormBtn = document.getElementById('closeForm');
            const followUpForm = document.getElementById('followUpForm');

            if (!btnTambah) {
                console.error('Button tidak ditemukan!');
                return;
            }

            window.openSlide = function() {
                console.log('Opening slide...');
                formSlide.classList.remove('hidden');
                requestAnimationFrame(() => {
                    formPanel.classList.remove('translate-x-full');
                    formPanel.classList.add('translate-x-0');
                });
            }

        

            // Close modal
            function closeSlide() {
                formPanel.classList.remove('translate-x-0');
                formPanel.classList.add('translate-x-full');
                setTimeout(() => formSlide.classList.add('hidden'), 350);
            }

            // Event listeners
            btnTambah.addEventListener('click', function(e) {
                console.log('Button tambah clicked!');
                e.preventDefault();
                window.openSlide();
            });

            if (closeFormBtn) {
                closeFormBtn.addEventListener('click', closeSlide);
            }

            if (formSlide) {
                formSlide.addEventListener('click', e => {
                    if (e.target === formSlide) closeSlide();
                });
            }

            // Form submit with AJAX
            if (followUpForm) {
                followUpForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');

                    submitBtn.disabled = true;
                    submitBtn.innerHTML =
                        '<svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Loading...';

                    fetch(this.action, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                this.reset();

                                if (window.toast) {
                                    toast(data.notify);
                                } else {
                                    alert('Follow up berhasil ditambahkan');
                                }

                                closeSlide();

                                // Auto reload halaman untuk update sidebar dan timeline
                                setTimeout(() => window.location.reload(), 500);
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            if (window.toast) {
                                toast({
                                    type: 'error',
                                    title: 'Error',
                                    message: 'Gagal menyimpan follow up'
                                });
                            } else {
                                alert('Gagal menyimpan follow up');
                            }
                        })
                        .finally(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML =
                                '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"></path></svg> Simpan Follow Up';
                        });
                });
            }

            console.log('Setup complete!');
        });
    </script>
@endpush
