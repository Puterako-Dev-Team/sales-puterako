@extends('layouts.app')
@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if ($canViewCharts)
                    <!-- Charts untuk Supervisor, Manajer, Administrator, Direktur, dan Staff -->
                    <form method="GET" class="mb-4 flex gap-2 items-center">
                        <input type="month" name="month" value="{{ request('month', \Carbon\Carbon::now()->format('Y-m')) }}"
                            class="border rounded px-2 py-1">
                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded">Filter</button>
                    </form>
                    <div class="bg-gray-50 p-4 rounded-lg mt-6 w-full">
                        <h3 class="font-semibold text-sm mb-3 text-center">Penawaran Per Tanggal</h3>
                        <div style="height: 250px;">
                            <canvas id="dateLineChart"></canvas>
                        </div>
                    </div>
                    <!-- Penawaran per PIC Admin (hanya untuk non-staff) -->
                    @if(Auth::user()->role !== 'staff')
                    <div class="bg-gray-50 p-4 rounded-lg mt-6 w-full">
                        <h3 class="font-semibold text-sm mb-3 text-center">Penawaran per PIC Admin</h3>
                        <div style="height: 250px;">
                            <canvas id="picChart"></canvas>
                        </div>
                    </div>
                    @endif
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                        <!-- Perusahaan Terbanyak -->
                        <div class="bg-gray-50 p-4 rounded-lg w-full">
                            <h3 class="font-semibold text-sm mb-3 text-center">Top Perusahaan Penawaran</h3>
                            <div style="height: 250px;">
                                <canvas id="companyChart"></canvas>
                            </div>
                        </div>

                        <!-- Pie Chart Status -->
                        <div class="bg-gray-50 p-4 rounded-lg w-full">
                            <h3 class="font-semibold text-sm mb-3 text-center">Proporsi Status Penawaran</h3>
                            <div style="height: 250px;">
                                <canvas id="statusPieChart"></canvas>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Welcome Message untuk Staff -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-8 text-center">
                        <h2 class="text-3xl font-bold text-green-900 mb-4">Selamat Datang!</h2>
                        <p class="text-green-700 text-lg mb-6">{{ Auth::user()->name }}, Anda berhasil masuk ke sistem Sales
                            Puterako</p>
                    </div>
                @endif

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

    @if ($canViewCharts)
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // 1. Horizontal Bar Chart Perusahaan
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
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: true,
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleFont: {
                                size: 12
                            },
                            bodyFont: {
                                size: 11
                            },
                            padding: 10,
                            cornerRadius: 4,
                            displayColors: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                font: {
                                    size: 10
                                }
                            }
                        },
                        y: {
                            ticks: {
                                font: {
                                    size: 9
                                }
                            }
                        }
                    }
                }
            });

            @if(Auth::user()->role !== 'staff')
            // 2. Bar Chart PIC Admin - Total Penawaran dengan tooltip detail status
            const picStats = {!! json_encode($picStats) !!};
            const picLabels = picStats.map(x => x.name);
            const picTotals = picStats.map(x => x.total);

            const picChart = new Chart(document.getElementById('picChart'), {
                type: 'bar',
                data: {
                    labels: picLabels,
                    datasets: [
                        {
                            label: 'Total Penawaran',
                            data: picTotals,
                            backgroundColor: '#0A6847'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            enabled: true,
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleFont: {
                                size: 12
                            },
                            bodyFont: {
                                size: 11
                            },
                            padding: 10,
                            cornerRadius: 4,
                            displayColors: true,
                            callbacks: {
                                afterLabel: function(context) {
                                    const pic = picStats[context.dataIndex];
                                    return [
                                        'Draft: ' + pic.draft,
                                        'Success: ' + pic.success,
                                        'Lost: ' + pic.lost
                                    ];
                                }
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
                    }
                }
            });
            @endif

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
                        },
                        tooltip: {
                            enabled: true,
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleFont: {
                                size: 12
                            },
                            bodyFont: {
                                size: 11
                            },
                            padding: 10,
                            cornerRadius: 4,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + context.parsed;
                                }
                            }
                        }
                    }
                }
            });
            // 4. Line Chart Penawaran Per Tanggal (hanya tanggal dengan aktivitas)
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
    @endif
@endsection