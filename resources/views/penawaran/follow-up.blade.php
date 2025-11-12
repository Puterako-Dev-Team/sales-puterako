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
    </style>

    <div class="container mx-auto p-6">
        <!-- Header dengan tombol back -->
        <div class="flex items-center p-8 text-gray-600 ">
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
                        <div><span class="font-medium">Tanggal:</span> {{ $penawaran->created_at->format('d F Y') }}</div>
                        <div><span class="font-medium">Perihal:</span> {{ $penawaran->perihal }}</div>
                        <div><span class="font-medium">Status:</span>
                            <span class="status-badge status-{{ $penawaran->status }}">
                                {{ ucfirst($penawaran->status) }}
                            </span>
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
                            <x-lucide-activity class="w-5 h-5" />
                            Aktivitas Follow Up
                        </h3>
                        <button id="btnTambahFollowUp"
                            class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition flex items-center gap-2 text-sm">
                            <x-lucide-plus class="w-4 h-4" />
                            Tambah Aktivitas
                        </button>
                    </div>
                    <hr class="my-4">

                    <div class="space-y-0" id="timelineContainer">
                        <!-- KOSONG jika belum ada aktivitas -->
                        <div class="empty-state">
                            <x-lucide-calendar-x class="w-16 h-16 mx-auto mb-4 opacity-30" />
                            <h4 class="text-lg font-medium mb-2">Belum Ada Aktivitas Follow Up</h4>
                            <p class="text-sm text-gray-500">Klik "Tambah Aktivitas" untuk memulai follow up penawaran ini
                            </p>
                        </div>
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
                        <div class="absolute left-1.5 top-6 bottom-6 w-1 bg-gray-200"></div>

                        <div class="flex items-center gap-3 relative ">
                            <div class="w-4 h-4 rounded-full bg-gray-500 border-2 border-white shadow-sm z-10"></div>
                            <div>
                                <div class="font-medium">Penawaran Dibuat</div>
                                <div class="text-gray-600">12 Juni 2025</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 relative">
                            <div class="w-4 h-4 rounded-full bg-gray-500 border-2 border-white shadow-sm z-10"></div>
                            <div>
                                <div class="font-medium">Follow Up 1</div>
                                <div class="text-gray-600">24 Juni 2025</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 relative">
                            <div class="w-4 h-4 rounded-full bg-gray-500 border-2 border-white shadow-sm z-10"></div>
                            <div>
                                <div class="font-medium">(System) Follow Up 2</div>
                                <div class="text-gray-600">24 Juni 2025</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 relative">
                            <div class="w-4 h-4 rounded-full bg-gray-500 border-2 border-white shadow-sm z-10"></div>
                            <div>
                                <div class="font-medium">Follow Up 2</div>
                                <div class="text-gray-600">30 Juni 2025</div>
                            </div>
                        </div>
                    </div>

                    
                </div>
            </div>
        </div>
    </div>

    <!-- Slide-over Form Modal -->
    <div id="formSlide" class="fixed inset-0 bg-black bg-opacity-30 z-50 hidden">
        <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-lg p-8 transition-transform transform translate-x-full"
            id="formPanel">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Tambah Follow Up</h2>
                <button id="closeForm" class="text-gray-500 hover:text-gray-700">
                    <x-lucide-x class="w-6 h-6" />
                </button>
            </div>

            <form id="followUpForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Follow Up</label>
                    <select name="jenis_followup" id="jenisFollowUp"
                        class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                        <option value="">Pilih Jenis...</option>
                        <option value="telepon">Telepon</option>
                        <option value="email">Email</option>
                        <option value="meeting">Meeting</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="kunjungan">Kunjungan</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal</label>
                    <input type="date" name="tanggal" id="tanggalFollowUp" value="{{ date('Y-m-d') }}"
                        class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jam</label>
                    <input type="time" name="jam" id="jamFollowUp" value="{{ date('H:i') }}"
                        class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
                    <textarea name="catatan" id="catatanFollowUp" rows="4"
                        class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Tulis hasil/rencana follow up..." required></textarea>
                </div>

                <div class="absolute bottom-0 left-0 w-full p-4 bg-white border-t">
                    <button type="submit"
                        class="w-full bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700 transition flex items-center justify-center gap-2 text-sm">
                        <x-lucide-save class="w-4 h-4" />
                        Simpan Follow Up
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Modal elements
        const btnTambah = document.getElementById('btnTambahFollowUp');
        const formSlide = document.getElementById('formSlide');
        const formPanel = document.getElementById('formPanel');
        const closeFormBtn = document.getElementById('closeForm');
        const followUpForm = document.getElementById('followUpForm');

        // Data untuk menyimpan aktivitas (demo)
        let activities = [];

        // Color mapping untuk jenis aktivitas
        const activityColors = {
            telepon: 'blue',
            email: 'green',
            meeting: 'purple',
            whatsapp: 'orange',
            kunjungan: 'red'
        };

        // Open modal
        function openSlide() {
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

        // Update timeline display
        function updateTimeline() {
            const timelineContainer = document.getElementById('timelineContainer');
            const miniTimeline = document.getElementById('miniTimeline');
            const totalCount = document.getElementById('totalCount');
            const lastContact = document.getElementById('lastContact');

            if (activities.length === 0) {
                // Tampilkan empty state
                timelineContainer.innerHTML = `
            <div class="empty-state">
                <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 13l2 2 4-4"></path>
                </svg>
                <h4 class="text-lg font-medium mb-2">Belum Ada Aktivitas Follow Up</h4>
                <p class="text-sm text-gray-500">Klik "Tambah Aktivitas" untuk memulai follow up penawaran ini</p>
            </div>
        `;

                miniTimeline.innerHTML = `
            <div class="text-center py-4 text-gray-400 text-sm">
                <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M8 12h8"></path>
                </svg>
                Belum ada aktivitas
            </div>
        `;

                totalCount.textContent = '0';
                lastContact.textContent = '-';
                return;
            }

            // Sort activities by date desc
            const sortedActivities = [...activities].sort((a, b) => new Date(b.datetime) - new Date(a.datetime));

            // Update main timeline
            timelineContainer.innerHTML = sortedActivities.map((activity, index) => {
                const color = activityColors[activity.jenis] || 'blue';
                const isLast = index === sortedActivities.length - 1;

                return `
            <div class="timeline-item">
                <div class="timeline-dot" style="background: var(--color-${color}-600, #3b82f6);"></div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <span class="font-semibold text-${color}-600" style="color: var(--color-${color}-600, #3b82f6);">${activity.jenis.charAt(0).toUpperCase() + activity.jenis.slice(1)}</span>
                            <span class="text-sm text-gray-500 ml-2">
                                ${activity.tanggal} ${activity.jam}
                            </span>
                        </div>
                        <span class="text-xs text-gray-400">
                            ${activity.timeAgo}
                        </span>
                    </div>
                    <p class="text-sm text-gray-700">${activity.catatan}</p>
                </div>
            </div>
        `;
            }).join('');

            // Update mini timeline
            miniTimeline.innerHTML = sortedActivities.map((activity, index) => {
                const color = activityColors[activity.jenis] || 'blue';
                const isLast = index === sortedActivities.length - 1;

                return `
            <div class="mini-timeline ${isLast ? '' : ''}">
                <div class="mini-dot mini-dot-${color}" style="background: var(--color-${color}-600, #3b82f6);"></div>
                <div class="text-xs">
                    <div class="font-medium">${activity.jenis.charAt(0).toUpperCase() + activity.jenis.slice(1)}</div>
                    <div class="text-gray-500">${activity.tanggal.split('-').reverse().join('/')}, ${activity.jam}</div>
                </div>
            </div>
        `;
            }).join('');

            // Update summary
            totalCount.textContent = activities.length;
            lastContact.textContent = sortedActivities[0] ? sortedActivities[0].tanggal.split('-').reverse().join('/') :
                '-';
        }

        // Event listeners
        btnTambah.addEventListener('click', openSlide);
        closeFormBtn.addEventListener('click', closeSlide);
        formSlide.addEventListener('click', e => {
            if (e.target === formSlide) closeSlide();
        });

        // Form submit
        followUpForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const newActivity = {
                jenis: formData.get('jenis_followup'),
                tanggal: formData.get('tanggal'),
                jam: formData.get('jam'),
                catatan: formData.get('catatan'),
                datetime: formData.get('tanggal') + ' ' + formData.get('jam'),
                timeAgo: 'Baru saja'
            };

            // Add to activities array
            activities.push(newActivity);

            // Update display
            updateTimeline();

            // Show notification
            if (window.toast) {
                toast({
                    type: 'success',
                    title: 'Berhasil',
                    message: 'Follow up berhasil ditambahkan'
                });
            } else {
                alert('Follow up berhasil ditambahkan');
            }

            // Reset form and close modal
            this.reset();
            closeSlide();
        });

        // Initial load
        document.addEventListener('DOMContentLoaded', function() {
            updateTimeline();
        });
    </script>
@endpush
