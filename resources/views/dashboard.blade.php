@extends('layouts.app')
@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if ($canViewCharts)
                    <!-- Split Cards for Success and PO Omzet -->
                    @if(Auth::user()->role !== 'staff')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <!-- Card Total Omzet Success -->
                        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white shadow-lg">
                            <h3 class="text-lg font-semibold mb-2">Total Omzet Success</h3>
                            <p class="text-4xl font-bold">Rp {{ number_format($totalOmzetKeseluruhanSuccess ?? 0, 0, ',', '.') }}</p>
                        </div>
                        
                        <!-- Card Total Omzet PO -->
                        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-6 text-white shadow-lg">
                            <h3 class="text-lg font-semibold mb-2">Total Omzet Purchase Order</h3>
                            <p class="text-4xl font-bold">Rp {{ number_format($totalOmzetKeseluruhanPO ?? 0, 0, ',', '.') }}</p>
                        </div>
                    </div>
                    @else
                    @endif
                    
                    <!-- Charts untuk Supervisor, Manajer, Administrator, Direktur, dan Staff -->
                    <form method="GET" class="mb-4 flex gap-2 items-center">
                        <input type="month" name="month" value="{{ request('month', \Carbon\Carbon::now()->format('Y-m')) }}"
                            class="border rounded px-2 py-1">
                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded">Filter</button>
                    </form>
                    
                    @if(Auth::user()->role !== 'staff')
                    <!-- Split Tren Omzet 12 Bulan Terakhir - Success and PO -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                        <!-- Chart Success -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-sm mb-3 text-center">Tren Omzet Success - 12 Bulan Terakhir</h3>
                            <div style="height: 300px;">
                                <canvas id="omzetPerBulanChartSuccess"></canvas>
                            </div>
                        </div>
                        
                        <!-- Chart PO -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-sm mb-3 text-center">Tren Omzet PO - 12 Bulan Terakhir</h3>
                            <div style="height: 300px;">
                                <canvas id="omzetPerBulanChartPO"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Split Chart Omzet per Sales/Staff -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                        <!-- Chart Success -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-sm mb-3 text-center">Omzet per Sales - Success - {{ date('F Y', strtotime($month . '-01')) }}</h3>
                            <div style="height: 350px;">
                                <canvas id="omzetPerSalesChartSuccess"></canvas>
                            </div>
                        </div>
                        
                        <!-- Chart PO -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-sm mb-3 text-center">Omzet per Sales - PO - {{ date('F Y', strtotime($month . '-01')) }}</h3>
                            <div style="height: 350px;">
                                <canvas id="omzetPerSalesChartPO"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Penawaran per PIC Admin (pindah di bawah Omzet per Sales/Staff) -->
                    <div class="bg-gray-50 p-4 rounded-lg mt-6 w-full">
                        <h3 class="font-semibold text-sm mb-3 text-center">Penawaran per PIC Admin</h3>
                        <div style="height: 250px;">
                            <canvas id="picChart"></canvas>
                        </div>
                    </div>
                    @else
                    <!-- Chart Penawaran Per Tanggal untuk Staff -->
                    <div class="bg-gray-50 p-4 rounded-lg mt-6 w-full">
                        <h3 class="font-semibold text-sm mb-3 text-center">Penawaran Per Tanggal</h3>
                        <div style="height: 250px;">
                            <canvas id="dateLineChart"></canvas>
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
                    
                    <!-- Card User Info -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-6 mt-6">
                        <h3 class="text-lg font-semibold text-green-900 mb-2">Profil Anda</h3>
                        <div class="text-green-700 text-sm space-y-1">
                            <div><strong>Nama:</strong> {{ Auth::user()->name }}</div>
                            <div><strong>Email:</strong> {{ Auth::user()->email }}</div>
                            <div><strong>Role:</strong> {{ ucfirst(Auth::user()->role ?? 'N/A') }}</div>
                            <div><strong>Departemen:</strong> {{ Auth::user()->departemen ?? 'N/A' }}</div>
                            <div><strong>Kantor:</strong> {{ Auth::user()->kantor ?? 'N/A' }}</div>
                            <div><strong>No HP:</strong> {{ Auth::user()->nohp ?? 'N/vA' }}</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($canViewCharts)
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // 1. Horizontal Bar Chart Perusahaan (Top 5)
            const companyData = {!! json_encode($topCompanies) !!};
            const companyLabels = companyData.map(x => x.nama_perusahaan);
            const companyTotals = companyData.map(x => x.total);
            
            // Color palette untuk Top 5 Perusahaan - warna berbeda setiap bar
            const companyColorPalette = [
                '#059669', // emerald-600
                '#10b981', // emerald-500
                '#34d399', // emerald-400
                '#6ee7b7', // emerald-300
                '#a7f3d0'  // emerald-200
            ];
            
            const companyColors = companyTotals.map((_, index) => companyColorPalette[index]);

            const companyChart = new Chart(document.getElementById('companyChart'), {
                type: 'bar',
                data: {
                    labels: companyLabels,
                    datasets: [{
                        label: 'Total Penawaran',
                        data: companyTotals,
                        backgroundColor: companyColors,
                        borderColor: companyColors.map(color => color),
                        borderWidth: 1
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
            
            // Color palette - warna berbeda untuk setiap bar
            const picColorPalette = [
                '#059669', // emerald-600
                '#10b981', // emerald-500
                '#34d399', // emerald-400
                '#6ee7b7', // emerald-300
                '#a7f3d0', // emerald-200
                '#16a34a', // green-600
                '#22c55e', // green-500
                '#4ade80', // green-400
                '#86efac', // green-300
                '#dcfce7'  // green-100
            ];
            
            const picColors = picTotals.map((_, index) => picColorPalette[index % picColorPalette.length]);

            const picChart = new Chart(document.getElementById('picChart'), {
                type: 'bar',
                data: {
                    labels: picLabels,
                    datasets: [
                        {
                            label: 'Total Penawaran',
                            data: picTotals,
                            backgroundColor: picColors,
                            borderColor: picColors.map(color => color),
                            borderWidth: 1
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
                po: '#992cff', // ungu
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
            
            // 4. Line Chart Penawaran Per Tanggal (untuk staff)
            const dateStats = {!! json_encode($dateStats) !!};
            const dateLabels = dateStats.map(x => x.tanggal);
            const dateTotals = dateStats.map(x => x.total);

            if (document.getElementById('dateLineChart')) {
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
            }
            @if(Auth::user()->role !== 'staff')
            // 5. Bar Chart Omzet Per Sales/Staff - SUCCESS
            const omzetPerSalesSuccess = {!! json_encode($omzetPerSalesSuccess) !!};
            const omzetLabelsSuccess = omzetPerSalesSuccess.map(x => x.name);
            const omzetValuesSuccess = omzetPerSalesSuccess.map(x => x.omzet);
            const omzetJumlahSuccess = omzetPerSalesSuccess.map(x => x.jumlah_penawaran);

            const omzetPerSalesChartSuccess = new Chart(document.getElementById('omzetPerSalesChartSuccess'), {
                type: 'bar',
                data: {
                    labels: omzetLabelsSuccess,
                    datasets: [{
                        label: 'Omzet Success (Rp)',
                        data: omzetValuesSuccess,
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
                                    const jumlah = omzetJumlahSuccess[idx];
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

            // 5b. Bar Chart Omzet Per Sales/Staff - PO
            const omzetPerSalesPO = {!! json_encode($omzetPerSalesPO) !!};
            const omzetLabelsPO = omzetPerSalesPO.map(x => x.name);
            const omzetValuesPO = omzetPerSalesPO.map(x => x.omzet);
            const omzetJumlahPO = omzetPerSalesPO.map(x => x.jumlah_penawaran);

            const omzetPerSalesChartPO = new Chart(document.getElementById('omzetPerSalesChartPO'), {
                type: 'bar',
                data: {
                    labels: omzetLabelsPO,
                    datasets: [{
                        label: 'Omzet PO (Rp)',
                        data: omzetValuesPO,
                        backgroundColor: '#a855f7',
                        borderColor: '#9333ea',
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
                                    const jumlah = omzetJumlahPO[idx];
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

            // 6. Line Chart Omzet Per Bulan - SUCCESS
            const omzetPerBulanSuccess = {!! json_encode($omzetPerBulanSuccess) !!};
            const bulanLabelsSuccess = omzetPerBulanSuccess.map(x => {
                const date = new Date(x.bulan);
                return new Intl.DateTimeFormat('id-ID', { month: 'short', year: 'numeric' }).format(date);
            }).reverse();
            const bulanOmzetSuccess = omzetPerBulanSuccess.map(x => x.total_omzet).reverse();
            const bulanJumlahSuccess = omzetPerBulanSuccess.map(x => x.jumlah_penawaran).reverse();

            const omzetPerBulanChartSuccess = new Chart(document.getElementById('omzetPerBulanChartSuccess'), {
                type: 'line',
                data: {
                    labels: bulanLabelsSuccess,
                    datasets: [{
                        label: 'Omzet Success (Rp)',
                        data: bulanOmzetSuccess,
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
                                    const jumlah = bulanJumlahSuccess[idx];
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

            // 6b. Line Chart Omzet Per Bulan - PO
            const omzetPerBulanPO = {!! json_encode($omzetPerBulanPO) !!};
            const bulanLabelsPO = omzetPerBulanPO.map(x => {
                const date = new Date(x.bulan);
                return new Intl.DateTimeFormat('id-ID', { month: 'short', year: 'numeric' }).format(date);
            }).reverse();
            const bulanOmzetPO = omzetPerBulanPO.map(x => x.total_omzet).reverse();
            const bulanJumlahPO = omzetPerBulanPO.map(x => x.jumlah_penawaran).reverse();

            const omzetPerBulanChartPO = new Chart(document.getElementById('omzetPerBulanChartPO'), {
                type: 'line',
                data: {
                    labels: bulanLabelsPO,
                    datasets: [{
                        label: 'Omzet PO (Rp)',
                        data: bulanOmzetPO,
                        borderColor: '#a855f7',
                        backgroundColor: 'rgba(168, 85, 247, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 5,
                        pointBackgroundColor: '#a855f7',
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
                                    const jumlah = bulanJumlahPO[idx];
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