import io
import pytest
from unittest.mock import patch
from fastapi.testclient import TestClient
from main import app

client = TestClient(app)

# Helper untuk membuat dummy file upload dengan format tuple httpx yang valid
def build_dummy_file():
    return ("test.xlsx", io.BytesIO(b"dummy content"), "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")

# ==========================================
# 🧪 TESTING ENDPOINT 1: /api/cleansing/master
# ==========================================

def test_cleansing_master_success():
    """Menguji eksekusi cleansing master saat 3 file Excel diunggah dengan benar"""
    with patch("main.proses_cleansing_master") as mock_cleansing:
        mock_cleansing.return_value = {
            "dosen": [{"id": 1, "nama": "Dosen Test"}],
            "matkul": [{"id": 101, "nama": "Matkul Test"}],
            "ruang": [{"id": 10, "nama": "Ruang Test"}]
        }

        files = {
            "file_dosen": build_dummy_file(),
            "file_matkul": build_dummy_file(),
            "file_ruang": build_dummy_file()
        }

        response = client.post("/api/cleansing/master", files=files)

        assert response.status_code == 200
        data = response.json()
        assert data["status"] == "success"
        assert "data" in data
        assert mock_cleansing.called


def test_cleansing_master_missing_files():
    """Menguji validasi error 422 jika ada file yang tidak diunggah"""
    response = client.post("/api/cleansing/master")
    assert response.status_code == 422


def test_cleansing_master_internal_error():
    """Menguji penanganan error 500 jika proses cleansing melempar exception"""
    with patch("main.proses_cleansing_master", side_effect=Exception("File Corrupted")):
        files = {
            "file_dosen": build_dummy_file(),
            "file_matkul": build_dummy_file(),
            "file_ruang": build_dummy_file()
        }
        
        response = client.post("/api/cleansing/master", files=files)
        
        assert response.status_code == 500
        data = response.json()
        assert data["status"] == "error"
        assert "File Corrupted" in data["message"]

# ==========================================
# 🧪 TESTING ENDPOINT 2: /api/generate-jadwal
# ==========================================

def test_generate_jadwal_success():
    """Menguji eksekusi generate jadwal saat data JSON valid"""
    with patch("main.generate_jadwal_or_tools") as mock_solver:
        mock_solver.return_value = {
            "status_solver": "SUKSES",
            "data": [
                {
                    "dosen_id": 1,
                    "mata_kuliah_id": 101,
                    "kelas_id": 1,
                    "ruang_id": 10,
                    "hari": "Senin",
                    "sesi_mulai": 1,
                    "sesi_selesai": 2
                }
            ]
        }

        payload = {
            "pengampu": [{"id": 1, "dosen_id": 1, "mata_kuliah_id": 101}],
            "ruangan": [{"id": 10, "nama": "Lab 1"}],
            "unavailable_days": []
        }

        response = client.post("/api/generate-jadwal", json=payload)

        assert response.status_code == 200
        data = response.json()
        assert data["status_solver"] == "SUKSES"
        assert len(data["data"]) == 1


def test_generate_jadwal_internal_error():
    """Menguji penanganan error 500 jika solver OR-Tools mengalami exception"""
    with patch("main.generate_jadwal_or_tools", side_effect=Exception("Solver Timeout")):
        payload = {
            "pengampu": [],
            "ruangan": [],
            "unavailable_days": []
        }

        response = client.post("/api/generate-jadwal", json=payload)

        assert response.status_code == 500
        data = response.json()
        assert data["status"] == "error"
        assert data["status_solver"] == "GAGAL"
        assert "recommendation" in data