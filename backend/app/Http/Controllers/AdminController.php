<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Alat;
use App\Models\Kategori;
use App\Models\User;
use App\Models\Peminjaman;
use App\Models\DetailPinjam;
use App\Models\Pengembalian;
use App\Models\DetailPengembalian;
use Illuminate\Support\Facades\DB;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // Menampilkan dashboard admin dan log aktivitas
    public function index()
    {
        $logs = LogAktivitas::with('user')->latest()->take(10)->get();
        return view('admin.dashboard', compact('logs'));
    }

    // CRUD Alat: Menampilkan daftar alat
    public function indexAlat(Request $request)
    {
        $search = $request->input('search');

        $alat = Alat::with('kategori')
            ->when($search, function ($query, $search) {
                return $query->where('nama_alat', 'like', "%{$search}%")
                    ->orWhere('status_kondisi', 'like', "%{$search}%")
                    ->orWhereHas('kategori', function ($q) use ($search) {
                        $q->where('nama_kategori', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('admin.alat.index', compact('alat', 'search'));
    }

    // Menampilkan form tambah alat
    public function createAlat()
    {
        $kategori = Kategori::all();
        return view('admin.alat.create', compact('kategori'));
    }

    // Menyimpan alat baru
    public function storeAlat(Request $request)
    {
        $request->validate([
            'nama_alat' => 'required|string|max:255',
            'kategori_id' => 'required|exists:kategori,id',
            'stok' => 'required|integer|min:0',
            'status_kondisi' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->except('gambar');

        // Handle Upload Gambar jika ada
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('storage/alat'), $filename);
            $data['gambar'] = 'storage/alat/' . $filename;
        }

        $alat = Alat::create($data);

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Menambahkan alat baru: ' . $alat->nama_alat,
        ]);

        return redirect()->route('admin.alat.index')->with('success', 'Data alat berhasil ditambahkan.');
    }

    // Menampilkan form edit alat
    public function editAlat($id)
    {
        $alat = Alat::findOrFail($id);
        $kategori = Kategori::all();
        return view('admin.alat.edit', compact('alat', 'kategori'));
    }

    // Memperbarui data alat
    public function updateAlat(Request $request, $id)
    {
        $alat = Alat::findOrFail($id);

        $request->validate([
            'nama_alat' => 'required|string|max:255',
            'kategori_id' => 'required|exists:kategori,id',
            'stok' => 'required|integer|min:0',
            'status_kondisi' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->all();

        // Handle Update Gambar jika ada file baru
        if ($request->hasFile('gambar')) {
            // Hapus gambar lama jika ada
            if ($alat->gambar && file_exists(public_path($alat->gambar))) {
                unlink(public_path($alat->gambar));
            }

            $file = $request->file('gambar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('storage/alat'), $filename);
            $data['gambar'] = 'storage/alat/' . $filename;
        }

        $alat->update($data);

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Mengubah data alat: ' . $alat->nama_alat,
        ]);

        return redirect()->route('admin.alat.index')->with('success', 'Data alat berhasil diperbarui.');
    }

    // Menghapus data alat
    public function destroyAlat($id)
    {
        $alat = Alat::findOrFail($id);
        $namaAlat = $alat->nama_alat;

        // Hapus file gambar fisik jika ada
        if ($alat->gambar && file_exists(public_path($alat->gambar))) {
            unlink(public_path($alat->gambar));
        }

        $alat->delete();

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Menghapus alat: ' . $namaAlat,
        ]);

        return redirect()->route('admin.alat.index')->with('success', 'Data alat berhasil dihapus.');
    }

    // CRUD User: Manajemen user (admin, petugas, peminjam)
    public function indexUser(Request $request)
    {
        $search = $request->input('search');

        $users = User::when($search, function ($query, $search) {
            return $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('role', 'like', "%{$search}%");
        })
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('admin.user.index', compact('users', 'search'));
    }

    public function createUser()
    {
        return view('admin.user.create');
    }

    // Menyimpan user baru ke database
    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,petugas,peminjam',
            'no_hp' => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'no_hp' => $request->no_hp,
        ]);

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Menambahkan user baru: ' . $user->name,
        ]);

        return redirect()->route('admin.user.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function editUser($id)
    {
        $user = User::findOrFail($id);
        return view('admin.user.edit', compact('user'));
    }

    // Memperbarui data user
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'role' => 'required|in:admin,petugas,peminjam',
            'no_hp' => 'nullable|string|max:20',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'no_hp' => $request->no_hp,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Mengubah data user: ' . $user->name,
        ]);

        return redirect()->route('admin.user.index')->with('success', 'Data user berhasil diperbarui.');
    }

    // Menghapus user
    public function destroyUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        $namaUser = $user->name;

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Menghapus user: ' . $namaUser,
        ]);

        return redirect()->route('admin.user.index')->with('success', 'User berhasil dihapus.');
    }

    // CRUD Kategori
    public function indexKategori(Request $request)
    {
        $search = $request->input('search');

        $kategori = Kategori::when($search, function ($query, $search) {
            return $query->where('nama_kategori', 'like', "%{$search}%");
        })
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('admin.kategori.index', compact('kategori', 'search'));
    }

    // Menampilkan form tambah kategori
    public function createKategori()
    {
        return view('admin.kategori.create');
    }

    // Menyimpan kategori baru
    public function storeKategori(Request $request)
    {
        $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori',
        ]);

        $kategori = Kategori::create([
            'nama_kategori' => $request->nama_kategori,
        ]);

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Menambahkan kategori baru: ' . $kategori->nama_kategori,
        ]);

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    // Menampilkan form edit kategori
    public function editKategori($id)
    {
        $kategori = Kategori::findOrFail($id);
        return view('admin.kategori.edit', compact('kategori'));
    }

    // Memperbarui kategori
    public function updateKategori(Request $request, $id)
    {
        $kategori = Kategori::findOrFail($id);

        $request->validate([
            'nama_kategori' => 'required|string|max:255|unique:kategori,nama_kategori,' . $id,
        ]);

        $kategori->update([
            'nama_kategori' => $request->nama_kategori,
        ]);

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Mengubah kategori: ' . $kategori->nama_kategori,
        ]);

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    // Menghapus kategori
    public function destroyKategori($id)
    {
        $kategori = Kategori::findOrFail($id);

        if ($kategori->alat()->count() > 0) {
            return redirect()->route('admin.kategori.index')
                ->with('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh data alat.');
        }

        $namaKategori = $kategori->nama_kategori;

        $kategori->delete();

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Menghapus kategori: ' . $namaKategori,
        ]);

        return redirect()->route('admin.kategori.index')->with('success', 'Kategori berhasil dihapus.');
    }
    // 1. Menampilkan daftar peminjaman ($peminjaman)
    public function indexPeminjaman(Request $request)
    {
        $search = $request->input('search');

        $peminjaman = Peminjaman::with(['user', 'detailPinjam.alat'])
            ->when($search, function ($query, $search) {
                return $query->where('status', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(5)
            ->withQueryString();

        return view('admin.peminjaman.index', compact('peminjaman', 'search'));
    }

    // 2. Menampilkan form tambah peminjaman ($user dan $alat)
    public function createPeminjaman()
    {
        $user = User::where('role', 'peminjam')->get(); 
        $alat = Alat::where('stok', '>', 0)->get();

        return view('admin.peminjaman.create', compact('user', 'alat'));
    }

    // 3. Menyimpan data peminjaman baru
    public function storePeminjaman(Request $request)
    {
        $request->validate([
            'user_id'          => 'required|exists:users,id',
            'tgl_pinjam'       => 'required|date',
            'tgl_kembali_plan' => 'required|date|after_or_equal:tgl_pinjam',
            'alat_id'          => 'required|array',
            'alat_id.*'        => 'exists:alat,id', // Tabel di DB bernama 'alat'
            'jumlah'           => 'required|array',
            'jumlah.*'         => 'integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $peminjaman = Peminjaman::create([
                'user_id'          => $request->user_id,
                'tgl_pinjam'       => $request->tgl_pinjam,
                'tgl_kembali_plan' => $request->tgl_kembali_plan,
                'status'           => 'diajukan',
            ]);

            foreach ($request->alat_id as $index => $alatId) {
                $jumlahPinjam = $request->jumlah[$index];
                $alatData = Alat::findOrFail($alatId);

                if ($alatData->stok < $jumlahPinjam) {
                    throw new \Exception("Stok alat '{$alatData->nama_alat}' tidak mencukupi.");
                }

                DetailPinjam::create([
                    'peminjaman_id' => $peminjaman->id,
                    'alat_id'       => $alatId,
                    'jumlah'        => $jumlahPinjam,
                ]);
            }

            LogAktivitas::create([
                'user_id' => auth()->id(),
                'aktivitas' => 'Menambahkan peminjaman baru ID #' . $peminjaman->id . '.',
            ]);

            DB::commit();

            return redirect()->route('admin.peminjaman.index')->with('success', 'Data peminjaman berhasil diajukan.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    // 4. Memperbarui status peminjaman
    public function updateStatusPeminjaman(Request $request, $id)
    {
        $peminjaman = Peminjaman::with('detailPinjam.alat')->findOrFail($id);

        $request->validate([
            'status' => 'required|in:diajukan,dipinjam,dikembalikan,telat',
        ]);

        DB::beginTransaction();
        try {
            $statusLama = $peminjaman->status;
            $statusBaru = $request->status;

            if ($statusLama != 'dipinjam' && $statusBaru == 'dipinjam') {
                foreach ($peminjaman->detailPinjam as $detail) {
                    $alatData = $detail->alat;
                    if ($alatData->stok < $detail->jumlah) {
                        throw new \Exception("Stok alat {$alatData->nama_alat} tidak mencukupi untuk dipinjam.");
                    }
                    $alatData->decrement('stok', $detail->jumlah);
                }
            } elseif ($statusLama == 'dipinjam' && in_array($statusBaru, ['dikembalikan', 'selesai'])) {
                foreach ($peminjaman->detailPinjam as $detail) {
                    $detail->alat->increment('stok', $detail->jumlah);
                }
            }

            $peminjaman->update(['status' => $statusBaru]);

            LogAktivitas::create([
                'user_id' => auth()->id(),
                'aktivitas' => 'Mengubah status peminjaman ID #' . $peminjaman->id .
                    ' dari ' . $statusLama . ' menjadi ' . $statusBaru . '.',
            ]);

            DB::commit();

            return redirect()->route('admin.peminjaman.index')->with('success', 'Status peminjaman berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', $e->getMessage());
        }
    }

    // 5. Menghapus data peminjaman
    public function destroyPeminjaman($id)
    {
        $peminjaman = Peminjaman::with('detailPinjam')->findOrFail($id);

        if ($peminjaman->status == 'dipinjam') {
            foreach ($peminjaman->detailPinjam as $detail) {
                if ($detail->alat) {
                    $detail->alat->increment('stok', $detail->jumlah);
                }
            }
        }

        $peminjaman->delete();

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aktivitas' => 'Menghapus peminjaman ID #' . $peminjaman->id . '.',
            ]);

        return redirect()->route('admin.peminjaman.index')->with('success', 'Data peminjaman berhasil dihapus.');
    }

    // 6. Menampilkan daftar pengembalian
    public function indexPengembalian()
    {
        $peminjaman = Peminjaman::with([
            'user',
            'detailPinjam.alat',
            'pengembalian'
        ])
        ->whereIn('status', [
            'dipinjam',
            'telat',
            'menunggu_pengembalian'
        ])
        ->latest()
        ->paginate(5);

        foreach ($peminjaman as $pinjam) {
            if (
                $pinjam->status === 'dipinjam' &&
                Carbon::today()->gt(Carbon::parse($pinjam->tgl_kembali_plan))
            ) {
                $pinjam->update(['status' => 'telat']);
            }
        }

        return view('admin.pengembalian.index', compact('peminjaman'));
    }

    // 7. Proses Pengembalian
    public function kembalikan(Request $request, $id)
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
            $peminjaman = Peminjaman::with('detailPinjam.alat')->findOrFail($id);

            if ($peminjaman->status !== 'menunggu_pengembalian') {
                DB::rollback();

                return back()->with(
                    'error',
                    'Peminjam belum mengajukan pengembalian.'
                );
            }

            // Hitung keterlambatan
            $tanggalRencana = Carbon::parse($peminjaman->tgl_kembali_plan);
            $tanggalKembali = Carbon::today();

            if ($tanggalKembali->gt($tanggalRencana)) {
                $hariTerlambat = $tanggalRencana->diffInDays($tanggalKembali);
            } else {
                $hariTerlambat = 0;
            }

            // Denda Rp5.000 per barang per hari
            $dendaPerHari = 5000;

            $totalJumlahBarang = $peminjaman->detailPinjam->sum('jumlah');

            $dendaKeterlambatan =
                $hariTerlambat *
                $totalJumlahBarang *
                $dendaPerHari;

            $dendaKerusakan = 0;

            // Simpan pengembalian
            $pengembalian = Pengembalian::create([
                'peminjaman_id' => $peminjaman->id,
                'tgl_kembali' => $tanggalKembali,
                'kondisi_kembali' => 'Diperiksa',
                'denda' => 0,
                'petugas_id' => auth()->id(),
            ]);

            // Simpan kondisi masing-masing alat
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

            $pengembalian->update([
                'denda' => $totalDenda,
            ]);

            // Status selesai
            $peminjaman->update([
                'status' => 'dikembalikan',
            ]);

            // Kembalikan stok
            foreach ($peminjaman->detailPinjam as $detail) {
                if ($detail->alat) {
                    $detail->alat->increment('stok', $detail->jumlah);
                }
            }

            LogAktivitas::create([
                'user_id' => auth()->id(),
                'aktivitas' =>
                    'Memproses pengembalian peminjaman ID #' .
                    $peminjaman->id . '.',
            ]);

            DB::commit();

            return redirect()
                ->route('admin.pengembalian.index')
                ->with('success', 'Pengembalian berhasil. Stok alat dikembalikan.');

        } catch (\Exception $e) {

            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Pengembalian gagal: ' . $e->getMessage());
        }
    }

    // 8. Form Pengembalian
    public function createPengembalian($id)
    {
        $peminjaman = Peminjaman::with([
            'user',
            'detailPinjam.alat'
        ])->findOrFail($id);

        if ($peminjaman->status !== 'menunggu_pengembalian') {
            return back()->with(
                'error',
                'Peminjam belum mengajukan pengembalian.'
            );
        }

        return view('admin.pengembalian.create', compact('peminjaman'));
    }
}