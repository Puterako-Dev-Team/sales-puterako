<!-- filepath: c:\laragon\www\sales-puterako\resources\views\dashboard.blade.php -->

@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                        <p class="text-gray-600 mt-1">Selamat datang di Puterako Super App - Sales</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
</div>
@endsection