import io
import pandas as pd
import pytest
from unittest.mock import patch, MagicMock
from ortools.sat.python import cp_model

from services.cleansing_service import (
    safe_int,
    find_col,
    cleanse_ploting,
    cleanse_matkul_sks,
    cleanse_ruangan,
    proses_cleansing_master,
)
from services.scheduler_service import (
    _precheck_feasibility,
    generate_jadwal_or_tools,
)

# ==========================================
# 🧪 1. TEST HELPER & CLEANSING SERVICE
# ==========================================

def test_safe_int_utility():
    assert safe_int(10) == 10
    assert safe_int("15") == 15
    assert safe_int(None) == 0
    assert safe_int("invalid") == 0

def test_find_col_utility():
    df = pd.DataFrame(columns=["Nama Ruang", "Kategori_Ruangan", "Prodi"])
    assert find_col(df, ["ruang"]) == "Nama Ruang"
    assert find_col(df, ["kategori"]) == "Kategori_Ruangan"
    assert find_col(df, ["tidak_ada"]) is None

def test_cleanse_matkul_sks_exception_on_empty():
    empty_df = pd.DataFrame([["", "", ""]])
    buf = io.BytesIO()
    with pd.ExcelWriter(buf, engine="openpyxl") as writer:
        empty_df.to_excel(writer, sheet_name="Prodi Test", header=False, index=False)
    
    xls_matkul = pd.ExcelFile(io.BytesIO(buf.getvalue()))
    with pytest.raises(RuntimeError):
        cleanse_matkul_sks(xls_matkul)

def test_cleanse_ruangan_exception_missing_sheet():
    dummy_df = pd.DataFrame([{"col": 1}])
    buf = io.BytesIO()
    with pd.ExcelWriter(buf, engine="openpyxl") as writer:
        dummy_df.to_excel(writer, sheet_name="SheetLain", index=False)
        
    xls_ruang = pd.ExcelFile(io.BytesIO(buf.getvalue()))
    with pytest.raises(ValueError):
        cleanse_ruangan(xls_ruang)

def test_proses_cleansing_master_full_pipeline():
    ploting_data = [
        {"Kode MK": "Dr. Aris, S.Kom., M.T.", "Kd": "D01", "Homebase": "TI", "Nama Matakuliah": "", "Kelas": "", "Prodi": ""},
        {"Kode MK": "NIP 198501012010121001", "Kd": "", "Homebase": "", "Nama Matakuliah": "", "Kelas": "", "Prodi": ""},
        {"Kode MK": "MK001", "Kd": "", "Homebase": "", "Nama Matakuliah": "Pemrograman Web", "Kelas": "TI-2A", "Prodi": "Teknik Informatika"},
        {"Kode MK": "MK002", "Kd": "", "Homebase": "", "Nama Matakuliah": "Basis Data", "Kelas": "TI-2B", "Prodi": "Teknik Informatika"}
    ]
    df_plot = pd.DataFrame(ploting_data)

    matkul_data = [
        ["1", "MK001", "Pemrograman Web", 2, 1, "PW_GRP"],
        ["2", "MK002", "Basis Data", 3, 0, "BD_GRP"]
    ]
    df_mk = pd.DataFrame(matkul_data)

    ruang_data = [
        {"nama_ruang": "Lab Komputer 1", "kategori": "praktek", "prodi": "Teknik Informatika", "spesifik_mk": "Pemrograman Web"},
        {"nama_ruang": "Ruang Teori 1", "kategori": "teori", "prodi": "Teknik Informatika", "spesifik_mk": ""}
    ]
    df_rg = pd.DataFrame(ruang_data)

    buf_dosen, buf_mk, buf_rg = io.BytesIO(), io.BytesIO(), io.BytesIO()
    
    with pd.ExcelWriter(buf_dosen, engine="openpyxl") as w:
        df_plot.to_excel(w, sheet_name="Ploting", index=False)
        
    with pd.ExcelWriter(buf_mk, engine="openpyxl") as w:
        df_mk.to_excel(w, sheet_name="Teknik Informatika", header=False, index=False)
        
    with pd.ExcelWriter(buf_rg, engine="openpyxl") as w:
        df_rg.to_excel(w, sheet_name="ruang", index=False)

    hasil = proses_cleansing_master(buf_dosen.getvalue(), buf_mk.getvalue(), buf_rg.getvalue())

    assert "pengampu" in hasil
    assert "ruangan" in hasil
    assert "mata_kuliah" in hasil
    assert len(hasil["pengampu"]) > 0

# ==========================================
# 🧪 2. TEST PRECHECK FEASIBILITY SCHEDULER
# ==========================================

def test_precheck_missing_room_cat():
    tasks = [{'jenis': 'praktikum', 'durasi': 2, 'dosen_id': 1, 'kelas_id': 1}]
    cause, reasons, rec = _precheck_feasibility(tasks, {}, {}, 5, 8)
    assert cause == "Kategori ruangan tidak ditemukan."

def test_precheck_room_capacity_overload():
    tasks = [{'jenis': 'teori', 'durasi': 100, 'dosen_id': 1, 'kelas_id': 1}]
    rooms_by_cat = {'teori': [{'id': 1}]}
    cause, _, _ = _precheck_feasibility(tasks, rooms_by_cat, {}, 5, 8)
    assert cause == "Kapasitas ruangan tidak mencukupi."

def test_precheck_lecturer_overload():
    tasks = [{'jenis': 'teori', 'durasi': 50, 'dosen_id': 1, 'dosen_nama': 'Dosen A', 'kelas_id': 1}]
    rooms_by_cat = {'teori': [{'id': 1}, {'id': 2}]}
    unavail = {1: {0, 1, 2, 3}}
    cause, _, _ = _precheck_feasibility(tasks, rooms_by_cat, unavail, 5, 8)
    assert cause == "Beban mengajar dosen melebihi batas maksimal."

def test_precheck_class_overload():
    # Bagi ke 2 dosen agar lolos dari Check #3 (Lecturer Overload) dan memicu Check #4 (Class Overload)
    tasks = [
        {'jenis': 'teori', 'durasi': 25, 'dosen_id': 101, 'kelas_id': 1, 'kelas_nama': 'K1'},
        {'jenis': 'teori', 'durasi': 25, 'dosen_id': 102, 'kelas_id': 1, 'kelas_nama': 'K1'}
    ]
    rooms_by_cat = {'teori': [{'id': 1}, {'id': 2}]}
    cause, _, _ = _precheck_feasibility(tasks, rooms_by_cat, {}, 5, 8)
    assert cause == "Total sesi perkuliahan kelas melebihi batas maksimal per minggu."

def test_precheck_task_duration_exceed():
    # Durasi 10 melebihi sesi harian 8, disiapkan ruangan mencukupi agar lolos Check #2
    tasks = [{'task_id': 'T1', 'jenis': 'teori', 'durasi': 10, 'dosen_id': 1, 'kelas_id': 1, 'mata_kuliah_nama': 'Matkul X'}]
    rooms_by_cat = {'teori': [{'id': 1}, {'id': 2}]}
    cause, _, _ = _precheck_feasibility(tasks, rooms_by_cat, {}, 5, 8)
    assert cause == "Durasi mata kuliah melebihi batas sesi harian."

# ==========================================
# 🧪 3. TEST GENERATE JADWAL OR-TOOLS (EXHAUSTIVE)
# ==========================================

def test_generate_jadwal_empty_pengampu():
    res = generate_jadwal_or_tools([], [])
    assert res["status_solver"] == "GAGAL"

def test_generate_jadwal_success_full_constraints():
    pengampu = [
        {
            "id": 1, "dosen_id": 101, "dosen_nama": "Dr. Aris",
            "mata_kuliah_id": 1, "mata_kuliah_nama": "Pemrograman Web",
            "group_matkul": "PW1", "kelas_id": 10, "kelas_nama": "TI-2A",
            "tahun_ajar_id": 1, "prodi_id": 1, "jam_teori": 2, "jam_praktikum": 2
        }
    ]
    ruangan = [
        {"id": 1, "nama": "Ruang Teori 1", "kategori": "teori", "prodi_id": 1, "spesifik_mk": "Pemrograman Web"},
        {"id": 2, "nama": "Lab Komputer 1", "kategori": "praktikum", "prodi_id": 1, "spesifik_mk": ""}
    ]
    unavail = [{"dosen_id": 101, "hari": "Jumat"}]

    res = generate_jadwal_or_tools(pengampu, ruangan, unavail)
    assert res["status_solver"] == "SUKSES"
    assert len(res["data"]) == 2

@patch("ortools.sat.python.cp_model.CpSolver.WallTime", return_value=0.5)
@patch("ortools.sat.python.cp_model.CpSolver.Solve")
def test_generate_jadwal_infeasible_status(mock_solve, mock_wall_time):
    mock_solve.return_value = cp_model.INFEASIBLE
    pengampu = [{"id": 1, "jam_teori": 2, "dosen_id": 1, "kelas_id": 1}]
    ruangan = [{"id": 1, "kategori": "teori"}]
    
    res = generate_jadwal_or_tools(pengampu, ruangan)
    assert res["status_solver"] == "GAGAL"
    assert "Terjadi bentrok" in res["pesan"]

@patch("ortools.sat.python.cp_model.CpSolver.WallTime", return_value=0.5)
@patch("ortools.sat.python.cp_model.CpSolver.Solve")
def test_generate_jadwal_unknown_status(mock_solve, mock_wall_time):
    mock_solve.return_value = cp_model.UNKNOWN
    pengampu = [{"id": 1, "jam_teori": 2, "dosen_id": 1, "kelas_id": 1}]
    ruangan = [{"id": 1, "kategori": "teori"}]
    
    res = generate_jadwal_or_tools(pengampu, ruangan)
    assert res["status_solver"] == "GAGAL"
    assert "kehabisan waktu" in res["pesan"]

@patch("ortools.sat.python.cp_model.CpSolver.WallTime", return_value=0.5)
@patch("ortools.sat.python.cp_model.CpSolver.Solve")
def test_generate_jadwal_model_invalid_status(mock_solve, mock_wall_time):
    mock_solve.return_value = cp_model.MODEL_INVALID
    pengampu = [{"id": 1, "jam_teori": 2, "dosen_id": 1, "kelas_id": 1}]
    ruangan = [{"id": 1, "kategori": "teori"}]
    
    res = generate_jadwal_or_tools(pengampu, ruangan)
    assert res["status_solver"] == "GAGAL"
    assert "internal" in res["pesan"]