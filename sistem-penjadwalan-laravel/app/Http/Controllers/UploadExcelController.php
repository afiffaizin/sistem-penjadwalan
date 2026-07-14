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

class UploadExcelController extends Controller
{
    public function uploadForm()
    {
        return view('upload-data');
    }

    public function cleansingView()
    {
        $isCleansed = Storage::exists('temp_data.json');
        return view('cleansing', compact('isCleansed'));
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

        session([
            'upload_tahun' => $request->tahun_ajar,
            'upload_semester' => $request->semester
        ]);

        try {
            $response = Http::timeout(120)
                ->attach('file_dosen', file_get_contents($request->file('file_dosen')), 'dosen.xlsx')
                ->attach('file_matkul', file_get_contents($request->file('file_matkul')), 'matkul.xlsx')
                ->attach('file_ruang', file_get_contents($request->file('file_ruang')), 'ruang.xlsx')
                ->post(config('services.python.url') . '/api/cleansing/master');

            if ($response->failed()) {
                $errorData = $response->json();
                return back()->with('error', 'Gagal: ' . ($errorData['message'] ?? 'Error Python'));
            }

            $data = $response->json();

            Storage::put('temp_data.json', json_encode($data['data']));

            return redirect()->route('cleansing.view')->with('success', ' Data berhasil dianalisis dan dicleansing oleh Sistem!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal terhubung ke Python. Detail: ' . $e->getMessage());
        }
    }

    public function storeDatabase(Request $request)
    {
        if (!Storage::exists('temp_data.json')) {
            return redirect()->route('upload.form')->with('error', 'File data sementara hilang, silakan upload ulang Excel Anda.');
        }

        $fileContent = Storage::get('temp_data.json');
        $data = json_decode($fileContent, true);

        $tahunUpload = session('upload_tahun', '2025/2026');
        $semesterUpload = session('upload_semester', 'Gasal');

        DB::beginTransaction();

        try {
            $tahunAjar = TahunAjar::firstOrCreate(
                ['tahun' => $tahunUpload, 'semester' => $semesterUpload],
                ['is_active' => true]
            );

            // SIMPAN DATA RUANGAN
            foreach ($data['ruangan'] as $r) {
                $namaProdi = trim($r['prodi']) ?: 'Umum';
                $prodi = ProgramStudi::firstOrCreate(['nama' => $namaProdi]);

                Ruang::updateOrCreate(
                    ['nama' => $r['ruang'], 'prodi_id' => $prodi->id],
                    ['kategori' => $r['kategori']]
                );
            }

            // SIMPAN DATA MATA KULIAH
            foreach ($data['mata_kuliah'] as $mk) {
                $prodi = ProgramStudi::firstOrCreate(['nama' => $mk['prodi']]);

                MataKuliah::updateOrCreate(
                    ['nama' => $mk['nama_mk'], 'prodi_id' => $prodi->id],
                    [
                        'sks_teori' => $mk['sks_teori'],
                        'sks_praktikum' => $mk['sks_praktikum'],
                        'sks_total' => $mk['sks_total'],
                    ]
                );
            }

            // SIMPAN DATA PENGAMPU
            foreach ($data['pengampu'] as $p) {
                $prodi = ProgramStudi::firstOrCreate(['nama' => $p['prodi']]);

                $dosen = Dosen::firstOrCreate(
                    ['nama' => $p['nama_dosen']],
                    [
                        'kode_dosen' => $p['kode_dosen'],
                        'nip'       => $p['nip'] ?? null
                    ]
                );

                $dosen->prodis()->syncWithoutDetaching([$prodi->id]);

                $mk = MataKuliah::firstOrCreate(
                    ['nama' => $p['nama_mk'], 'prodi_id' => $prodi->id],
                    [
                        'sks_teori' => $p['sks_teori'],
                        'sks_praktikum' => $p['sks_praktikum'],
                        'sks_total' => ($p['sks_teori'] + $p['sks_praktikum'])
                    ]
                );

                $kelas = Kelas::firstOrCreate(
                    ['nama' => $p['kelas'], 'prodi_id' => $prodi->id, 'tahun_ajar_id' => $tahunAjar->id]
                );

                DosenMatkul::firstOrCreate([
                    'dosen_id' => $dosen->id,
                    'mata_kuliah_id' => $mk->id,
                    'kelas_id' => $kelas->id,
                    'tahun_ajar_id' => $tahunAjar->id,
                ]);
            }

            DB::commit();

            Storage::delete('temp_data.json');

            session()->forget(['upload_tahun', 'upload_semester']);

            return redirect()->route('jadwal.index')->with('success ', 'Seluruh data berhasil disimpan ke Database.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan ke Database: ' . $e->getMessage());
        }
    }
}
