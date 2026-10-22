@extends('layouts.app')

@section('title', 'Pengembalian Alat - Panel Admin')
@section('header-title', 'Proses Pengembalian Alat')

@section('content')

<div class="max-w-2xl bg-white rounded-lg shadow-sm border border-gray-200 p-6">

    {{-- Pesan Error --}}
    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 p-3 rounded-lg text-sm">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="mb-6">
        <h2 class="text-lg font-bold text-gray-800">
            Konfirmasi Pengembalian
        </h2>

        <p class="text-sm text-gray-500 mt-1">
            Pastikan data alat dan peminjam sudah sesuai sebelum diproses.
        </p>
    </div>

    {{-- Data Peminjam --}}
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-semibold mb-2">
            Peminjam
        </label>

        <div class="w-full px-3 py-2 bg-gray-100 border border-gray-200 rounded-lg">
            <div class="font-semibold text-gray-800">
                {{ $peminjaman->user->name ?? 'User Dihapus' }}
            </div>

            @if($peminjaman->user)
                <div class="text-xs text-gray-500">
                    {{ $peminjaman->user->email }}
                </div>
            @endif
        </div>
    </div>

    {{-- Tanggal --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">

        <div>
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Tanggal Pinjam
            </label>

            <div class="px-3 py-2 bg-gray-100 border border-gray-200 rounded-lg text-sm">
                {{ \Carbon\Carbon::parse($peminjaman->tgl_pinjam)->format('d-m-Y') }}
            </div>
        </div>

        <div>
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                Rencana Kembali
            </label>

            <div class="px-3 py-2 bg-gray-100 border border-gray-200 rounded-lg text-sm">
                {{ \Carbon\Carbon::parse($peminjaman->tgl_kembali_plan)->format('d-m-Y') }}
            </div>
        </div>

    </div>

    {{-- Alat --}}
    <div class="mb-4">

        <label class="block text-gray-700 text-sm font-semibold mb-2">
            Alat yang Dipinjam
        </label>

        <div class="border border-gray-200 rounded-lg overflow-hidden">

            @foreach($peminjaman->detailPinjam as $detail)

                <div class="flex justify-between items-center px-4 py-3 border-b last:border-b-0">

                    <div>
                        <div class="font-semibold text-gray-800">
                            {{ $detail->alat->nama_alat ?? 'Alat Dihapus' }}
                        </div>

                        <div class="text-xs text-gray-500">
                            Jumlah dipinjam: {{ $detail->jumlah }} pcs
                        </div>
                    </div>

                    <div class="text-sm font-semibold text-gray-700">
                        {{ $detail->jumlah }} pcs
                    </div>

                </div>

            @endforeach

        </div>

    </div>

    {{-- Perhitungan Denda --}}
    @php

        $tanggalRencana = \Carbon\Carbon::parse($peminjaman->tgl_kembali_plan);
        $tanggalSekarang = \Carbon\Carbon::today();

        if ($tanggalSekarang->gt($tanggalRencana)) {
            $hariTerlambat = $tanggalRencana->diffInDays($tanggalSekarang);
        } else {
            $hariTerlambat = 0;
        }

        $dendaPerHari = 5000;

        $totalJumlahBarang = $peminjaman->detailPinjam->sum('jumlah');

        $dendaKeterlambatan =
            $hariTerlambat *
            $totalJumlahBarang *
            $dendaPerHari;

    @endphp

    <div class="mb-6">

        <label class="block text-gray-700 text-sm font-semibold mb-2">
            Informasi Pengembalian
        </label>

        <div class="border rounded-lg p-4
            {{ $hariTerlambat > 0
                ? 'bg-red-50 border-red-200'
                : 'bg-emerald-50 border-emerald-200' }}">

            <div class="flex justify-between text-sm mb-2">

                <span class="text-gray-600">
                    Tanggal Pengembalian
                </span>

                <span class="font-semibold text-gray-800">
                    {{ $tanggalSekarang->format('d-m-Y') }}
                </span>

            </div>

            <div class="flex justify-between text-sm mb-2">

                <span class="text-gray-600">
                    Keterlambatan
                </span>

                <span class="font-semibold
                    {{ $hariTerlambat > 0 ? 'text-red-600' : 'text-emerald-600' }}">

                    {{ $hariTerlambat }} hari

                </span>

            </div>

            <div class="flex justify-between text-sm mb-2">
                <span class="text-gray-600">Denda Keterlambatan</span>
                <span class="font-semibold text-gray-800">
                    Rp {{ number_format($dendaKeterlambatan, 0, ',', '.') }}
                </span>
            </div>

            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Denda Kerusakan</span>
                <span id="total_denda_kerusakan" class="font-semibold text-gray-800">
                    Rp 0
                </span>
            </div>

            @if($hariTerlambat > 0)

                <div class="mt-3 text-xs text-red-700">
                    Terlambat {{ $hariTerlambat }} hari.
                    Denda dihitung Rp 5.000 per hari.
                </div>

            @else

                <div class="mt-3 text-xs text-emerald-700">
                    Pengembalian tepat waktu. Tidak ada denda.
                </div>

            @endif

        </div>

    </div>

    {{-- Tombol --}}
        <form
            action="{{ route('admin.pengembalian.kembalikan', $peminjaman->id) }}"
            method="POST"
            onsubmit="return confirm('Yakin ingin memproses pengembalian alat ini?')">

            @csrf
            @method('PUT')

            <div class="mb-6">

                <label class="block text-gray-700 text-sm font-semibold mb-3">
                    Kondisi Alat Saat Dikembalikan
                </label>

                @foreach($peminjaman->detailPinjam as $detail)

                    <div class="border border-gray-200 rounded-lg p-4 mb-4">

                        <div class="mb-4">
                            <div class="font-semibold text-gray-800">
                                {{ $detail->alat->nama_alat ?? 'Alat Dihapus' }}
                            </div>

                            <div class="text-xs text-gray-500">
                                Jumlah dipinjam: {{ $detail->jumlah }} pcs
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">
                                    Baik
                                </label>

                                <input
                                    type="number"
                                    name="barang[{{ $detail->alat_id }}][baik]"
                                    min="0"
                                    max="{{ $detail->jumlah }}"
                                    value="{{ $detail->jumlah }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">
                                    Rusak Ringan
                                </label>

                                <input
                                    type="number"
                                    name="barang[{{ $detail->alat_id }}][rusak_ringan]"
                                    min="0"
                                    max="{{ $detail->jumlah }}"
                                    value="0"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">
                                    Rusak Berat
                                </label>

                                <input
                                    type="number"
                                    name="barang[{{ $detail->alat_id }}][rusak_berat]"
                                    min="0"
                                    max="{{ $detail->jumlah }}"
                                    value="0"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            </div>

                            <div>
                                <label class="block text-sm text-gray-600 mb-1">
                                    Tidak Lengkap
                                </label>

                                <input
                                    type="number"
                                    name="barang[{{ $detail->alat_id }}][tidak_lengkap]"
                                    min="0"
                                    max="{{ $detail->jumlah }}"
                                    value="0"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            </div>

                        </div>

                        <div class="mt-4">

                            <label class="block text-sm text-gray-600 mb-1">
                                Denda Kerusakan
                            </label>

                            <input
                                type="number"
                                name="barang[{{ $detail->alat_id }}][denda]"
                                min="0"
                                value="0"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm denda-barang">

                            <p class="text-xs text-gray-500 mt-1">
                                Isi manual jika alat mengalami kerusakan.
                            </p>

                        </div>

                    </div>

                @endforeach

            </div>

            <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">

                <div class="flex justify-between items-center mb-2">
                    <span class="text-gray-700 font-semibold">
                        Denda Keterlambatan
                    </span>

                    <span class="font-semibold text-gray-800">
                        Rp {{ number_format($dendaKeterlambatan, 0, ',', '.') }}
                    </span>
                </div>

                <div class="flex justify-between items-center mb-2">
                    <span class="text-gray-700 font-semibold">
                        Denda Kerusakan
                    </span>

                    <span id="total_denda_kerusakan" class="font-semibold text-gray-800">
                        Rp 0
                    </span>
                </div>

                <div class="border-t border-blue-200 pt-2 mt-2">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700 font-bold">
                            Total Denda
                        </span>

                        <span id="total_denda" class="text-lg font-bold text-blue-600">
                            Rp {{ number_format($dendaKeterlambatan, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

            </div>

            <div class="flex justify-end space-x-2">

                <a href="{{ route('admin.pengembalian.index') }}"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold transition">
                    Batal
                </a>

            <button
                type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                Konfirmasi Pengembalian
            </button>
        </div>
</form>
</div>

<script>
    function hitungTotalDenda() {
        const dendaKeterlambatan = {{ $dendaKeterlambatan }};

        let dendaKerusakan = 0;

        document.querySelectorAll('.denda-barang').forEach(input => {
            dendaKerusakan += parseInt(input.value) || 0;
        });

        const total = dendaKeterlambatan + dendaKerusakan;

        document.getElementById('total_denda_kerusakan').textContent =
            'Rp ' + dendaKerusakan.toLocaleString('id-ID');

        document.getElementById('total_denda').textContent =
            'Rp ' + total.toLocaleString('id-ID');
    }

    document.querySelectorAll('.denda-barang').forEach(input => {
        input.addEventListener('input', hitungTotalDenda);
    });
</script>

@endsection