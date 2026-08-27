from fastapi import FastAPI, UploadFile, File, Request, Body
from fastapi.responses import JSONResponse
import asyncio
import traceback

from services.cleansing_service import proses_cleansing_master
from services.scheduler_service import generate_jadwal_or_tools

app = FastAPI(
    title="API Penjadwalan Perkuliahan JKB",
    description="Backend Service untuk Data Cleansing dan Constraint Programming"
)

# ENDPOINT 1: CLEANSING DATA MASTER
@app.post("/api/cleansing/master")
async def api_cleansing(
    file_dosen: UploadFile = File(...),
    file_matkul: UploadFile = File(...),
    file_ruang: UploadFile = File(...)
):
    try:
        content_dosen = await file_dosen.read()
        content_matkul = await file_matkul.read()
        content_ruang = await file_ruang.read()

        hasil_bersih = proses_cleansing_master(content_dosen, content_matkul, content_ruang)

        return {
            "status": "success", 
            "pesan": "3 File Master berhasil dibersihkan",
            "data": hasil_bersih
        }
        
    except Exception as e:
        return JSONResponse(
            status_code=500, 
            content={"status": "error", "message": f"Gagal memproses data: {str(e)}"}
        )

# ENDPOINT 2: GENERATE JADWAL (OR-TOOLS)
@app.post("/api/generate-jadwal")
async def api_generate_jadwal(request_data: dict = Body(...)):
    try:
        print("\n1. REQUEST GENERATE JADWAL MASUK!")
        
        print(" 2. DATA JSON BERHASIL DITANGKAP ")

        data_pengampu = request_data.get("pengampu", [])
        data_ruangan = request_data.get("ruangan", [])
        unavailable_days = request_data.get("unavailable_days", [])

        print(f"Total Pengampu diterima: {len(data_pengampu)}")
        print(f"Total Ruangan diterima: {len(data_ruangan)}")
        print(f"Total Request Hari Tidak Bisa Mengajar diterima: {len(unavailable_days)}")
        print(" 3. MEMULAI PROSES OR-TOOLS ")

        # ponytail: run CPU-bound solver in thread so event loop stays alive
        # (blocks event loop otherwise → Docker health check fails → container restart → cURL 52)
        jadwal_final = await asyncio.to_thread(
            generate_jadwal_or_tools, data_pengampu, data_ruangan, unavailable_days
        )

        print("4. OR-TOOLS BERHASIL MENYELESAIKAN JADWAL! \n")
        return jadwal_final
        
    except Exception as e:
        print(f"ERROR PYTHON: {str(e)} ")
        traceback.print_exc()
        return JSONResponse(
            status_code=500, 
            content={
                "status": "error",
                "status_solver": "GAGAL",
                "pesan": "Terjadi kesalahan server yang tidak terduga.",
                "violations": [
                    "Kesalahan internal server mencegah proses penjadwalan selesai."
                ],
                "recommendation": "Silakan coba lagi. Jika masalah berlanjut, hubungi administrator sistem.",
            }
        )

if __name__ == "__main__":
    import uvicorn
    uvicorn.run("main:app", host="0.0.0.0", port=8000, reload=True)