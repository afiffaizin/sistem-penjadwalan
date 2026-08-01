<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\ProgramStudi;
use App\Models\Dosen;
use App\Models\MataKuliah;
use App\Models\Ruang;
use App\Models\TahunAjar;
use App\Models\Kelas;
use App\Models\DosenMatkul;
use App\Models\Jadwal;
use App\Models\DosenUnavailableDay;

class UploadExcelController extends Controller
{
    /**
     * Nama file temp unik per user untuk mencegah race condition antar sekretaris.
     */
    private function tempFileName(): string
    {
        return 'temp_data_' . auth()->id() . '.json';
    }

    public function uploadForm()
    {
        return view('upload-data');
    }

    public function cleansingView()
    {
        $isCleansed = Storage::exists($this->tempFileName());
        $importHistories = TahunAjar::latest('id')->get();
        return view('cleansing', compact('isCleansed', 'importHistories'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'tahun_ajar'  => 'required|string',
            'semester'    => 'required|in:Gasal,Genap',
            'file_dosen'  => 'required|mimes:xlsx,xls,csv',
            'file_matkul' => 'required|mimes:xlsx,xls,csv',
            'file_ruang'  => 'required|mimes:xlsx,xls,csv',
        ]);

        $exists = TahunAjar::where('tahun', $request->tahun_ajar)
            ->where('semester', $request->semester)
            ->exists();

        if ($exists) {
            return back()->with(
                'error',
                "<strong>❌ Upload dataset gagal.</strong><br><br>" .
                    "Dataset untuk Tahun Ajaran {$request->tahun_ajar} Semester {$request->semester} sudah tersedia.<br>" .
                    "Silakan gunakan data yang ada atau hapus data lama sebelum mengunggah yang baru."
            );
        }

        try {
            $response = Http::timeout(120)
                ->attach('file_dosen', file_get_contents($request->file('file_dosen')), 'dosen.xlsx')
                ->attach('file_matkul', file_get_contents($request->file('file_matkul')), 'matkul.xlsx')
                ->attach('file_ruang', file_get_contents($request->file('file_ruang')), 'ruang.xlsx')
                ->post(config('services.python.url') . '/api/cleansing/master');

            if ($response->failed()) {
                $errorData = $response->json();
                return back()->with('error', 'Gagal: ' . e($errorData['message'] ?? 'Error Python'));
            }

            $data = $response->json();

            // Simpan data cleansing + metadata tahun/semester dalam temp file per-user
            $tempPayload = [
                'tahun_ajar' => $request->tahun_ajar,
                'semester'   => $request->semester,
                'data'       => $data['data'],
            ];
            Storage::put($this->tempFileName(), json_encode($tempPayload));

            return redirect()->route('cleansing.view')->with('success', ' Data berhasil dianalisis dan dicleansing oleh Sistem!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal terhubung ke Python. Detail: ' . $e->getMessage());
        }
    }

    public function storeDatabase(Request $request)
    {
        $tempFile = $this->tempFileName();

        if (!Storage::exists($tempFile)) {
            return redirect()->route('upload.form')->with('error', 'File data sementara hilang, silakan upload ulang Excel Anda.');
        }

        $fileContent = Storage::get($tempFile);
        $payload = json_decode($fileContent, true);

        // Ambil tahun/semester dari temp file (bukan session) agar tidak hilang saat session expired
        $tahunUpload = $payload['tahun_ajar'] ?? null;
        $semesterUpload = $payload['semester'] ?? null;
        $data = $payload['data'] ?? null;

        if (!$tahunUpload || !$semesterUpload || !$data) {
            Storage::delete($tempFile);
            return redirect()->route('upload.form')->with('error', 'Data sementara tidak valid, silakan upload ulang Excel Anda.');
        }

        DB::beginTransaction();

        try {
            $tahunAjar = TahunAjar::firstOrCreate(
                ['tahun' => $tahunUpload, 'semester' => $semesterUpload],
                ['is_active' => true]
            );

            $taId = $tahunAjar->id;

            // SIMPAN DATA RUANGAN
            foreach ($data['ruangan'] as $r) {
                $namaProdi = trim($r['prodi']) ?: 'Umum';
                $prodi = ProgramStudi::firstOrCreate(['nama' => $namaProdi]);

                Ruang::updateOrCreate(
                    ['nama' => $r['ruang'], 'prodi_id' => $prodi->id, 'tahun_ajar_id' => $taId],
                    ['kategori' => $r['kategori']]
                );
            }

            // SIMPAN DATA MATA KULIAH
            foreach ($data['mata_kuliah'] as $mk) {
                $prodi = ProgramStudi::firstOrCreate(['nama' => $mk['prodi']]);

                MataKuliah::updateOrCreate(
                    ['nama' => $mk['nama_mk'], 'prodi_id' => $prodi->id, 'tahun_ajar_id' => $taId],
                    [
                        'sks_teori' => $mk['sks_teori'],
                        'sks_praktikum' => $mk['sks_praktikum'],
                        'sks_total' => $mk['sks_total'],
                        'kode_group' => $mk['kode_group'] ?? null,
                    ]
                );
            }

            // SIMPAN DATA PENGAMPU
            foreach ($data['pengampu'] as $p) {
                $prodi = ProgramStudi::firstOrCreate(['nama' => $p['prodi']]);

                $dosen = Dosen::firstOrCreate(
                    ['nama' => $p['nama_dosen'], 'tahun_ajar_id' => $taId],
                    [
                        'kode_dosen' => $p['kode_dosen'],
                        'nip'       => $p['nip'] ?? null
                    ]
                );

                $dosen->prodis()->syncWithoutDetaching([$prodi->id]);

                $mk = MataKuliah::firstOrCreate(
                    ['nama' => $p['nama_mk'], 'prodi_id' => $prodi->id, 'tahun_ajar_id' => $taId],
                    [
                        'sks_teori' => $p['sks_teori'],
                        'sks_praktikum' => $p['sks_praktikum'],
                        'sks_total' => ($p['sks_teori'] + $p['sks_praktikum']),
                        'kode_group' => $p['kode_group'] ?? null,
                    ]
                );

                $kelas = Kelas::firstOrCreate(
                    ['nama' => $p['kelas'], 'prodi_id' => $prodi->id, 'tahun_ajar_id' => $taId]
                );

                DosenMatkul::firstOrCreate([
                    'dosen_id' => $dosen->id,
                    'mata_kuliah_id' => $mk->id,
                    'kelas_id' => $kelas->id,
                    'tahun_ajar_id' => $taId,
                ]);
            }

            DB::commit();

            Storage::delete($tempFile);

            return redirect()->route('jadwal.index')->with('success', 'Seluruh data berhasil disimpan ke Database.');
        } catch (\Exception $e) {
            DB::rollBack();
            Storage::delete($tempFile);
            return back()->with('error', 'Gagal menyimpan ke Database: ' . $e->getMessage());
        }
    }

    public function resetData(Request $request)
    {
        $request->validate([
            'tahun_ajar_id' => 'required|exists:tahun_ajars,id',
        ]);

        DB::beginTransaction();

        try {
            $taId = $request->tahun_ajar_id;

            // Delete transactional data
            Jadwal::where('tahun_ajar_id', $taId)->delete();
            DosenMatkul::where('tahun_ajar_id', $taId)->delete();
            DosenUnavailableDay::where('tahun_ajar_id', $taId)->delete();
            Kelas::where('tahun_ajar_id', $taId)->delete();

            // Delete master data owned by this import
            $dosenIds = Dosen::where('tahun_ajar_id', $taId)->pluck('id');
            DB::table('dosen_prodi')->whereIn('dosen_id', $dosenIds)->delete();
            Dosen::where('tahun_ajar_id', $taId)->delete();
            MataKuliah::where('tahun_ajar_id', $taId)->delete();
            Ruang::where('tahun_ajar_id', $taId)->delete();

            // Delete tahun_ajar itself
            TahunAjar::where('id', $taId)->delete();

            // Remove temp cleansing file if exists
            $tempFile = $this->tempFileName();
            if (Storage::exists($tempFile)) {
                Storage::delete($tempFile);
            }

            DB::commit();

            return redirect()->route('cleansing.view')
                ->with('success', 'Seluruh data hasil import berhasil direset. Silakan upload ulang file Excel.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mereset data: ' . $e->getMessage());
        }
    }
}
