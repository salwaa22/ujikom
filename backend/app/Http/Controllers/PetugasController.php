<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\DetailPengembalian;
use App\Models\Alat;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PetugasController extends Controller
{
    // Menampilkan daftar pengajuan peminjaman dari siswa/peminjam
    public function indexPeminjaman(Request $request)
    {
        $search = $request->input('search');

        $peminjaman = Peminjaman::with(['user', 'detailPinjam.alat'])
            ->where('status', 'diajukan')
            ->when($search, function ($query, $search) {
                return $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();

        return view('petugas.peminjaman.index', compact('peminjaman', 'search'));
    }

    public function setujuiPeminjaman($id)
    {
        DB::beginTransaction();

        try {
            $peminjaman = Peminjaman::with('detailPinjam')->findOrFail($id);
            $peminjaman->update(['status' => 'dipinjam']);

            LogAktivitas::create([
                'user_id' => auth()->id(),
                'aktivitas' => 'Menyetujui peminjaman ID #' . $peminjaman->id . '.',
            ]);

            // Kurangi stok alat secara otomatis
            foreach ($peminjaman->detailPinjam as $detail) {
                $alat = Alat::findOrFail($detail->alat_id);
                $alat->stok -= $detail->jumlah;
                $alat->save();
            }

            DB::commit();
            return redirect()->back()->with('success', 'Peminjaman disetujui dan stok alat dikurangi.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function indexPengembalian()
    {
        $pengembalian = Peminjaman::with([
            'user',
            'detailPinjam.alat',
            'pengembalian'
        ])
        ->latest()
        ->get();

        return view('petugas.pengembalian.index', compact('pengembalian'));
    }

    public function tolakPeminjaman($id)
    {
        $peminjaman = Peminjaman::findOrFail($id);

        if ($peminjaman->status !== 'diajukan') {
            return redirect()->back()->with('error', 'Peminjaman ini tidak dapat ditolak.');
        }
        
        $peminjaman->delete();

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Menolak peminjaman ID #' . $peminjaman->id . '.',
        ]);

        return redirect()->back()->with('success', 'Peminjaman berhasil ditolak.');
    }

    public function createPengembalian($id)
    {
        $peminjaman = Peminjaman::with([
            'user',
            'detailPinjam.alat'
        ])->findOrFail($id);

        if ($peminjaman->status !== 'menunggu_pengembalian') {
            return redirect()
                ->back()
                ->with('error', 'Peminjam belum mengajukan pengembalian.');
        }

        return view('petugas.pengembalian.create', compact('peminjaman'));
    }

    public function prosesPengembalian(Request $request, $peminjamanId)
    {
        $request->validate([
            'barang' => 'required|array',
            'barang.*.baik' => 'required|integer|min:0',
            'barang.*.rusak_ringan' => 'required|integer|min:0',
            'barang.*.rusak_berat' => 'required|integer|min:0',
            'barang.*.tidak_lengkap' => 'required|integer|min:0',
            'barang.*.denda' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            $peminjaman = Peminjaman::with('detailPinjam')->findOrFail($peminjamanId);

            if ($peminjaman->status !== 'menunggu_pengembalian') {
                DB::rollback();

                return redirect()
                    ->back()
                    ->with('error', 'Peminjam belum mengajukan pengembalian.');
            }

            // Hitung keterlambatan
            $tanggalRencana = \Carbon\Carbon::parse($peminjaman->tgl_kembali_plan);
            $tanggalSekarang = \Carbon\Carbon::today();

            if ($tanggalSekarang->gt($tanggalRencana)) {
                $hariTerlambat = $tanggalRencana->diffInDays($tanggalSekarang);
            } else {
                $hariTerlambat = 0;
            }

           $dendaPerHari = 5000;

            // Hitung total jumlah barang yang dipinjam
            $totalJumlahBarang = $peminjaman->detailPinjam->sum('jumlah');

            // Denda keterlambatan = hari terlambat × jumlah barang × Rp5.000
            $dendaKeterlambatan = $hariTerlambat * $totalJumlahBarang * $dendaPerHari;

            $dendaKerusakan = 0;

            // Simpan data pengembalian terlebih dahulu
            $pengembalian = Pengembalian::create([
                'peminjaman_id' => $peminjaman->id,
                'tgl_kembali' => now(),
                'kondisi_kembali' => 'Diperiksa',
                'denda' => 0,
                'petugas_id' => auth()->id(),
            ]);

            // Simpan kondisi setiap alat
            foreach ($peminjaman->detailPinjam as $detail) {

                $dataBarang = $request->barang[$detail->alat_id] ?? [];

                $jumlahBaik = (int) ($dataBarang['baik'] ?? 0);
                $jumlahRusakRingan = (int) ($dataBarang['rusak_ringan'] ?? 0);
                $jumlahRusakBerat = (int) ($dataBarang['rusak_berat'] ?? 0);
                $jumlahTidakLengkap = (int) ($dataBarang['tidak_lengkap'] ?? 0);
                $dendaBarang = (int) ($dataBarang['denda'] ?? 0);

                $totalKondisi =
                    $jumlahBaik +
                    $jumlahRusakRingan +
                    $jumlahRusakBerat +
                    $jumlahTidakLengkap;

                // Jumlah kondisi harus sama dengan jumlah yang dipinjam
                if ($totalKondisi !== (int) $detail->jumlah) {
                    throw new \Exception(
                        "Jumlah kondisi {$detail->alat->nama_alat} tidak sesuai dengan jumlah yang dipinjam."
                    );
                }

                DetailPengembalian::create([
                    'pengembalian_id' => $pengembalian->id,
                    'alat_id' => $detail->alat_id,
                    'jumlah_baik' => $jumlahBaik,
                    'jumlah_rusak_ringan' => $jumlahRusakRingan,
                    'jumlah_rusak_berat' => $jumlahRusakBerat,
                    'jumlah_tidak_lengkap' => $jumlahTidakLengkap,
                    'denda_kerusakan' => $dendaBarang,
                ]);

                $dendaKerusakan += $dendaBarang;
            }

            // Total seluruh denda
            $totalDenda = $dendaKeterlambatan + $dendaKerusakan;

            // Update total denda pada pengembalian
            $pengembalian->update([
                'denda' => $totalDenda,
            ]);

            LogAktivitas::create([
                'user_id' => auth()->id(),
                'aktivitas' => 'Memproses pengembalian peminjaman ID #' . $peminjaman->id . '.',
            ]);

            // Update status peminjaman jadi selesai
            $peminjaman->update(['status' => 'dikembalikan']);

            // Kembalikan stok alat ke inventaris
            foreach ($peminjaman->detailPinjam as $detail) {
                $alat = Alat::findOrFail($detail->alat_id);
                $alat->stok += $detail->jumlah;
                $alat->save();
            }

            DB::commit();
            return redirect()->route('petugas.pengembalian.index')->with('success', 'Pengembalian berhasil dicatat dan stok dipulihkan.');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }


   
    public function indexLaporan(Request $request)
    {
        $request->validate([
            'dari' => 'nullable|date',
            'sampai' => 'nullable|date|after_or_equal:dari',
            'status' => 'nullable|string',
            'nama' => 'nullable|string|max:100',
        ]);

        $query = Peminjaman::with([
            'user',
            'detailPinjam.alat'
        ]);

        // Filter tanggal mulai
        if ($request->filled('dari')) {
            $query->whereDate('tgl_pinjam', '>=', $request->dari);
        }

        // Filter tanggal akhir
        if ($request->filled('sampai')) {
            $query->whereDate('tgl_pinjam', '<=', $request->sampai);
        }

        // Filter status peminjaman
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter berdasarkan nama peminjam
        if ($request->filled('nama')) {
            $nama = $request->nama;

            $query->whereHas('user', function ($q) use ($nama) {
                $q->where('name', 'like', '%' . $nama . '%');
            });
        }

        // Urutkan data terbaru
        $peminjaman = $query
            ->latest()
            ->get();

        // Daftar status untuk pilihan filter
        $statusList = Peminjaman::query()
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        return view('petugas.laporan.index', compact(
            'peminjaman',
            'statusList'
        ));
    }

}