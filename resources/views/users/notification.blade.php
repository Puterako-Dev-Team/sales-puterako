@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto py-8">
    <div class="rounded-lg">
        <div class="flex justify-between mb-4">
            <h2 class="text-xl font-semibold">Notifikasi</h2>
            <form method="POST" action="{{ route('notifications.readAll') }}">
                @csrf
                <button type="submit" class="text-sm px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700 border">Tandai sebagai sudah dibaca</button>
            </form>
        </div>
        @forelse (auth()->user()->notifications as $notif)
            <div class="flex items-start gap-4 p-4 mb-3 border border-gray-200  rounded {{ $notif->read_at ? 'bg-white' : 'bg-orange-50' }}">
                <div class="flex-1">
                    <div class="font-semibold text-base mb-1">{!! $notif->data['title'] ?? 'Notifikasi Baru' !!}</div>
                    <div class="text-sm text-gray-700 mb-1">{!! $notif->data['body'] ?? '' !!}</div>
                    <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($notif->created_at)->format('d-m-Y H:i') }}</div>
                </div>
                <a href="{{ $notif->data['url'] ?? route('dashboard') }}" class="px-3 py-2 bg-white border rounded text-sm hover:bg-gray-100 inline-block">
                    Lihat Detail
                </a>
            </div>
        @empty
            <div class="text-center text-gray-500 py-8">Tidak ada notifikasi.</div>
        @endforelse
    </div>
</div>
@endsection