@extends('layouts.app')
@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="mb-4 flex gap-2 items-center">
                    <input type="date" name="start"
                        value="{{ request('start', \Carbon\Carbon::now()->subMonth()->format('Y-m-d')) }}"
                        class="border rounded px-2 py-1">
                    <span>→</span>
                    <input type="date" name="end" value="{{ request('end', \Carbon\Carbon::now()->format('Y-m-d')) }}"
                        class="border rounded px-2 py-1">
                    <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded">Filter</button>
                </form>
                <div class="bg-gray-50 p-4 rounded-lg mt-6 w-full">
                    <h3 class="font-semibold text-sm mb-3 text-center">Penawaran Per Tanggal</h3>
                    <div style="height: 250px;">
                        <canvas id="dateLineChart"></canvas>
                    </div>
                </div>
                <div class="flex gap-4 overflow-x-auto mt-6">
                    <!-- Perusahaan Terbanyak -->
                    <div class="bg-gray-50 p-4 rounded-lg flex-shrink-0" style="width: 350px;">
                        <h3 class="font-semibold text-sm mb-3 text-center">Top Perusahaan Penawaran</h3>
                        <div style="height: 250px;">
                            <canvas id="companyChart"></canvas>
                        </div>
                    </div>
                    <!-- Penawaran per PIC Admin -->
                    <div class="bg-gray-50 p-4 rounded-lg flex-shrink-0" style="width: 350px;">
                        <h3 class="font-semibold text-sm mb-3 text-center">Penawaran per PIC Admin</h3>
                        <div style="height: 250px;">
                            <canvas id="picChart"></canvas>
                        </div>
                    </div>
                    <!-- Pie Chart Status -->
                    <div class="bg-gray-50 p-4 rounded-lg flex-shrink-0" style="width: 350px;">
                        <h3 class="font-semibold text-sm mb-3 text-center">Proporsi Status Penawaran</h3>
                        <div style="height: 250px;">
                            <canvas id="statusPieChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1  gap-6 mt-6">
                    <!-- Card User Info -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-green-900 mb-2">Profil Anda</h3>
                        <div class="text-green-700 text-sm space-y-1">
                            <div><strong>Nama:</strong> {{ Auth::user()->name }}</div>
                            <div><strong>Email:</strong> {{ Auth::user()->email }}</div>
                            <div><strong>Role:</strong> {{ ucfirst(Auth::user()->role ?? 'N/A') }}</div>
                            <div><strong>Departemen:</strong> {{ Auth::user()->departemen ?? 'N/A' }}</div>
                            <div><strong>Kantor:</strong> {{ Auth::user()->kantor ?? 'N/A' }}</div>
                            <div><strong>No HP:</strong> {{ Auth::user()->nohp ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // 1. Bar Chart Perusahaan
        const companyChart = new Chart(document.getElementById('companyChart'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($topCompanies->pluck('nama_perusahaan')) !!},
                datasets: [{
                    label: 'Total Penawaran',
                    data: {!! json_encode($topCompanies->pluck('total')) !!},
                    backgroundColor: '#0A6847'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 9
                            }
                        }
                    }
                },
                onClick: (e, elements) => {
                    if (elements.length) {
                        const idx = elements[0].index;
                        alert('Perusahaan: ' + {!! json_encode($topCompanies->pluck('nama_perusahaan')) !!}[idx]);
                    }
                }
            }
        });

        // 2. Grouped Bar Chart PIC Admin (TIDAK STACKED)
        const picStats = {!! json_encode($picStats) !!};
        const picLabels = picStats.map(x => x.name);
        const draftData = picStats.map(x => x.draft);
        const successData = picStats.map(x => x.success);
        const lostData = picStats.map(x => x.lost);

        const picChart = new Chart(document.getElementById('picChart'), {
            type: 'bar',
            data: {
                labels: picLabels,
                datasets: [{
                        label: 'Draft',
                        data: draftData,
                        backgroundColor: '#234C6A'
                    },
                    {
                        label: 'Success',
                        data: successData,
                        backgroundColor: '#78C841'
                    },
                    {
                        label: 'Lost',
                        data: lostData,
                        backgroundColor: '#DC0000'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            font: {
                                size: 9
                            },
                            boxWidth: 12
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            font: {
                                size: 9
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    }
                },
                onClick: (e, elements) => {
                    if (elements.length) {
                        const idx = elements[0].index;
                        alert('PIC: ' + picLabels[idx]);
                    }
                }
            }
        });

        // 3. Pie Chart Status
        const statusColorMap = {
            draft: '#074173', // biru
            success: '#78C841', // hijau
            lost: '#DC0000' // merah
        };
        const statusLabels = {!! json_encode($statusCounts->keys()) !!};
        const statusColors = statusLabels.map(label => statusColorMap[label] || '#cccccc');

        const statusPieChart = new Chart(document.getElementById('statusPieChart'), {
            type: 'pie',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: {!! json_encode($statusCounts->values()) !!},
                    backgroundColor: statusColors
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 9
                            },
                            boxWidth: 12,
                            padding: 8
                        }
                    }
                },
                onClick: (e, elements) => {
                    if (elements.length) {
                        const idx = elements[0].index;
                        alert('Status: ' + statusLabels[idx]);
                    }
                }
            }
        });
        // 4. Line Chart Penawaran Per Tanggal
        const dateStats = {!! json_encode($dateStats) !!};
        const dateLabels = dateStats.map(x => x.tanggal);
        const dateTotals = dateStats.map(x => x.total);

        const dateLineChart = new Chart(document.getElementById('dateLineChart'), {
            type: 'line',
            data: {
                labels: dateLabels,
                datasets: [{
                    label: 'Jumlah Penawaran',
                    data: dateTotals,
                    borderColor: '#0A6847',
                    backgroundColor: 'rgba(10,104,71,0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                    pointBackgroundColor: '#0A6847'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Tanggal'
                        },
                        ticks: {
                            font: {
                                size: 9
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Penawaran'
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
    </script>
@endsection
