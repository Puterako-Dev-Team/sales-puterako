@extends('layouts.app')
@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if ($canViewCharts)
                    <!-- Card Total Omzet Keseluruhan - PALING ATAS -->
                    @if(Auth::user()->role !== 'staff')
                    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 w-full text-white shadow-lg mb-6">
                        <h3 class="text-lg font-semibold mb-2">Total Omzet Keseluruhan</h3>
                        <p class="text-4xl font-bold">Rp {{ number_format($totalOmzetKeseluruhan ?? 0, 0, ',', '.') }}</p>
                    </div>
                    @endif
                    
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
                    
                    <!-- Chart Omzet per Sales/Staff -->
                    <div class="bg-gray-50 p-4 rounded-lg mt-6 w-full">
                        <h3 class="font-semibold text-sm mb-3 text-center">Omzet per Sales/Staff - {{ date('F Y', strtotime($month . '-01')) }}</h3>
                        <div style="height: 350px;">
                            <canvas id="omzetPerSalesChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Chart Omzet Per Bulan -->
                    <div class="bg-gray-50 p-4 rounded-lg mt-6 w-full">
                        <h3 class="font-semibold text-sm mb-3 text-center">Tren Omzet 12 Bulan Terakhir</h3>
                        <div style="height: 300px;">
                            <canvas id="omzetPerBulanChart"></canvas>
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

            @if(Auth::user()->role !== 'staff')
            // 5. Bar Chart Omzet Per Sales/Staff
            const omzetPerSales = {!! json_encode($omzetPerSales) !!};
            const omzetLabels = omzetPerSales.map(x => x.name);
            const omzetValues = omzetPerSales.map(x => x.omzet);
            const omzetJumlah = omzetPerSales.map(x => x.jumlah_penawaran);

            const omzetPerSalesChart = new Chart(document.getElementById('omzetPerSalesChart'), {
                type: 'bar',
                data: {
                    labels: omzetLabels,
                    datasets: [{
                        label: 'Omzet (Rp)',
                        data: omzetValues,
                        backgroundColor: '#22C55E',
                        borderColor: '#16A34A',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
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
                                label: function(context) {
                                    const value = context.parsed.x;
                                    const formattedValue = 'Rp ' + value.toLocaleString('id-ID');
                                    const idx = context.dataIndex;
                                    const jumlah = omzetJumlah[idx];
                                    return [
                                        formattedValue,
                                        'Jumlah: ' + jumlah
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                font: {
                                    size: 9
                                },
                                callback: function(value) {
                                    return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                                }
                            }
                        },
                        y: {
                            ticks: {
                                font: {
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });

            // 6. Line Chart Omzet Per Bulan
            const omzetPerBulan = {!! json_encode($omzetPerBulan) !!};
            const bulanLabels = omzetPerBulan.map(x => {
                const date = new Date(x.bulan);
                return new Intl.DateTimeFormat('id-ID', { month: 'short', year: 'numeric' }).format(date);
            }).reverse();
            const bulanOmzet = omzetPerBulan.map(x => x.total_omzet).reverse();
            const bulanJumlah = omzetPerBulan.map(x => x.jumlah_penawaran).reverse();

            const omzetPerBulanChart = new Chart(document.getElementById('omzetPerBulanChart'), {
                type: 'line',
                data: {
                    labels: bulanLabels,
                    datasets: [{
                        label: 'Omzet (Rp)',
                        data: bulanOmzet,
                        borderColor: '#059669',
                        backgroundColor: 'rgba(5, 150, 105, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 5,
                        pointBackgroundColor: '#059669',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
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
                                label: function(context) {
                                    const value = context.parsed.y;
                                    const formattedValue = 'Rp ' + value.toLocaleString('id-ID');
                                    const idx = context.dataIndex;
                                    const jumlah = bulanJumlah[idx];
                                    return [
                                        formattedValue,
                                        'Penawaran: ' + jumlah
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Bulan'
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
                                text: 'Omzet (Rp)'
                            },
                            ticks: {
                                font: {
                                    size: 10
                                },
                                callback: function(value) {
                                    return 'Rp ' + (value / 1000000).toFixed(0) + 'M';
                                }
                            }
                        }
                    }
                }
            });
            @endif
        </script>
    @endif
@endsection