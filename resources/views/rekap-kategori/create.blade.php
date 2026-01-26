{{-- filepath: resources/views/rekap-kategori/create.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="container mx-auto p-8">
        <div class="flex items-center p-8 text-gray-600 mb-2">
            <a href="{{ route('rekap-kategori.index') }}" class="flex items-center hover:text-green-600">
                <x-lucide-arrow-left class="w-5 h-5 mr-2" />
                Master Kategori Rekap
            </a>
            <span class="mx-2">/</span>
            <span class="font-semibold">
                {{ isset($rekapKategori) ? 'Edit Kategori' : 'Tambah Kategori' }}
            </span>
        </div>

        <div class="bg-white rounded-lg shadow p-8 max-w-2xl mx-auto">
            <h1 class="text-2xl font-bold text-green-700 mb-6">
                {{ isset($rekapKategori) ? 'Edit Kategori' : 'Tambah Kategori Baru' }}
            </h1>

            <form action="{{ isset($rekapKategori) ? route('rekap-kategori.update', $rekapKategori->id) : route('rekap-kategori.store') }}"
                method="POST">
                @csrf
                @if(isset($rekapKategori))
                    @method('PUT')
                @endif

                <div class="mb-6">
                    <label for="nama" class="block text-sm font-semibold text-gray-700 mb-2">
                        Nama Kategori <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="nama" name="nama"
                        class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent @error('nama') border-red-500 @enderror"
                        value="{{ old('nama', $rekapKategori->nama ?? '') }}"
                        placeholder="Contoh: Perangkat Keras, Perangkat Lunak, dll"
                        required>
                    @error('nama')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-3">
                    <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition flex items-center gap-2">
                        <x-lucide-save class="w-5 h-5" />
                        {{ isset($rekapKategori) ? 'Perbarui Kategori' : 'Simpan Kategori' }}
                    </button>
                    <a href="{{ route('rekap-kategori.index') }}"
                        class="bg-gray-400 hover:bg-gray-500 text-white px-6 py-2 rounded-lg transition flex items-center gap-2">
                        <x-lucide-x class="w-5 h-5" />
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
