@extends('layouts.app')

@section('title', 'Daftar Alat - Panel Peminjam')
@section('header-title', 'Daftar Alat')

@section('content')

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-800">
            Daftar Alat Tersedia
        </h2>

        <p class="text-sm text-slate-500 mt-1">
            Pilih alat yang tersedia untuk diajukan dalam peminjaman.
        </p>
    </div>

    @if(session('success'))
        <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-xl text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-5 bg-red-50 border border-red-200 text-red-800 p-4 rounded-xl text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">

        <div class="px-6 py-5 border-b border-slate-200">
            <h3 class="font-semibold text-slate-800">
                Data Alat
            </h3>
        </div>

        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-left font-semibold text-slate-600">
                            No
                        </th>

                        <th class="px-6 py-4 text-left font-semibold text-slate-600">
                            Gambar
                        </th>

                        <th class="px-6 py-4 text-left font-semibold text-slate-600">
                            Nama Alat
                        </th>

                        <th class="px-6 py-4 text-left font-semibold text-slate-600">
                            Kategori
                        </th>

                        <th class="px-6 py-4 text-center font-semibold text-slate-600">
                            Stok
                        </th>

                        <th class="px-6 py-4 text-left font-semibold text-slate-600">
                            Kondisi
                        </th>

                        <th class="px-6 py-4 text-center font-semibold text-slate-600">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse($alat as $index => $alat)

                        <tr class="hover:bg-slate-50">

                            <td class="px-6 py-4 text-slate-600">
                                {{ $index + 1 }}
                            </td>

                            <td class="px-6 py-4">

                                @if($alat->gambar)
                                    <img
                                        src="{{ asset($alat->gambar) }}"
                                        alt="{{ $alat->nama_alat }}"
                                        class="w-14 h-14 object-cover rounded-lg border border-slate-200"
                                    >
                                @else
                                    <div class="w-14 h-14 rounded-lg bg-slate-100 flex items-center justify-center text-xs text-slate-400">
                                        Tidak ada
                                    </div>
                                @endif

                            </td>

                            <td class="px-6 py-4 font-medium text-slate-800">
                                {{ $alat->nama_alat }}
                            </td>

                            <td class="px-6 py-4 text-slate-600">
                                {{ $alat->kategori->nama_kategori ?? '-' }}
                            </td>

                            <td class="px-6 py-4 text-center">
                                <span class="font-semibold text-slate-700">
                                    {{ $alat->stok }}
                                </span>
                            </td>

                            <td class="px-6 py-4">

                                <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium
                                    bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    {{ $alat->status_kondisi ?? 'Baik' }}
                                </span>

                            </td>

                            <td class="px-6 py-4 text-center">

                                <a href="{{ route('peminjam.peminjaman') }}"
                                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 transition">
                                    Ajukan Peminjaman
                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-slate-500">
                                Belum ada alat yang tersedia.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

@endsection