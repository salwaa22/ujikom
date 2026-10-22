
@extends('layouts.app')

@section('title', 'Cetak Laporan - Dashboard Petugas')
@section('header-title', 'Laporan Peminjaman Alat')

@section('content')
    {{-- Filter dan tombol, tidak ikut tercetak --}}
    <div class="no-print bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-5">
            <div>
                <h3 class="text-lg font-bold text-slate-800">
                    Rekap Data Peminjaman
                </h3>
                <p class="text-slate-500 text-sm">
                    Filter data sebelum mencetak laporan.
                </p>
            </div>

            <button type="button" onclick="window.print()"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-medium">
                <i class="fa-solid fa-print mr-2"></i>
                Cetak Laporan
            </button>
        </div>

        <form method="GET" action="{{ url()->current() }}"
            class="grid grid-cols-1 md:grid-cols-4 gap-4">

            <div>
                <label class="block text-sm font-medium mb-2">Dari Tanggal</label>
                <input type="date" name="dari"
                    value="{{ request('dari') }}"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Sampai Tanggal</label>
                <input type="date" name="sampai"
                    value="{{ request('sampai') }}"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Status</label>
                <select name="status"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2">
                    <option value="">Semua Status</option>
                    @foreach ($statusList as $status)
                        <option value="{{ $status }}"
                            @selected(request('status') == $status)>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Nama Peminjam</label>
                <input type="text" name="nama"
                    value="{{ request('nama') }}"
                    placeholder="Cari nama peminjam..."
                    class="w-full border border-slate-300 rounded-lg px-3 py-2">
            </div>

            <div class="md:col-span-4 flex flex-wrap gap-2">
                <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg">
                    Filter Data
                </button>

                <a href="{{ url()->current() }}"
                    class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg">
                    Reset Filter
                </a>
            </div>
        </form>
    </div>

    {{-- Bagian ini saja yang dicetak --}}
    <div id="area-laporan" class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6">
            <h2 class="text-xl font-bold text-slate-800 text-center">
                LAPORAN PEMINJAMAN ALAT
            </h2>

            <p class="text-sm text-slate-500 text-center mt-1">
                @if (request('dari') || request('sampai'))
                    Periode:
                    {{ request('dari') ?: 'Awal' }}
                    s/d
                    {{ request('sampai') ?: 'Sekarang' }}
                @else
                    Semua Periode
                @endif
            </p>

            <p class="text-sm text-slate-500 text-center mt-1">
                Status: {{ request('status') ? ucfirst(request('status')) : 'Semua Status' }}
                | Peminjam: {{ request('nama') ?: 'Semua Peminjam' }}
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 text-xs uppercase border-b">
                        <th class="py-3 px-4">No</th>
                        <th class="py-3 px-4">Peminjam</th>
                        <th class="py-3 px-4">Tgl Pinjam</th>
                        <th class="py-3 px-4">Rencana Kembali</th>
                        <th class="py-3 px-4">Alat</th>
                        <th class="py-3 px-4">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                    @forelse ($peminjaman as $item)
                        <tr>
                            <td class="py-3 px-4">{{ $loop->iteration }}</td>
                            <td class="py-3 px-4">
                                {{ $item->user->name ?? '-' }}
                            </td>
                            <td class="py-3 px-4">
                                {{ $item->tgl_pinjam }}
                            </td>
                            <td class="py-3 px-4">
                                {{ $item->tgl_kembali_plan }}
                            </td>
                            <td class="py-3 px-4">
                                <ul class="list-disc list-inside">
                                    @forelse ($item->detailPinjam as $detail)
                                        <li>
                                            {{ $detail->alat->nama_alat ?? 'Alat' }}
                                            ({{ $detail->jumlah }} pcs)
                                        </li>
                                    @empty
                                        <li>-</li>
                                    @endforelse
                                </ul>
                            </td>
                            <td class="py-3 px-4">
                                {{ ucfirst($item->status) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                Tidak ada data sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 text-sm text-slate-500">
            Total data: {{ $peminjaman->count() }} peminjaman
        </div>
    </div>

    {{-- CSS khusus cetak --}}
    <style>
        @media print {
            @page {
                size: landscape;
                margin: 12mm;
            }

            body {
                background: white !important;
            }

            body * {
                visibility: hidden;
            }

            #area-laporan,
            #area-laporan * {
                visibility: visible;
            }

            #area-laporan {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                border: none !important;
                box-shadow: none !important;
                overflow: visible !important;
                border-radius: 0 !important;
            }

            #area-laporan table {
                width: 100%;
                border-collapse: collapse;
            }

            #area-laporan th,
            #area-laporan td {
                border: 1px solid #d1d5db;
                padding: 8px;
                color: #000 !important;
                font-size: 10pt;
            }

            #area-laporan thead {
                display: table-header-group;
            }

            #area-laporan tr {
                break-inside: avoid;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
@endsection
