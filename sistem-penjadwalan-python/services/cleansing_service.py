import pandas as pd
import io
import re

# HELPER FUNCTIONS
def safe_int(val):
    try:
        if pd.isna(val):
            return 0
        return int(val)
    except (TypeError, ValueError):
        return 0

def find_col(df, keys):
    for c in df.columns:
        cl = str(c).lower().strip()
        for k in keys:
            if k in cl:
                return c
    return None


# TAHAP 1: CLEANSING PLOTING (DOSEN)
def cleanse_ploting(xls):
    all_records = []
    
    for sheet_name in xls.sheet_names:
        df = pd.read_excel(xls, sheet_name=sheet_name)
        # Inisialisasi status dosen
        current_kode_dosen, current_dosen, current_mk, current_nip = None, None, None, None

        for _, row in df.iterrows():
            kode_mk_col = str(row.get("Kode MK", ""))
            kd_col = str(row.get("Kd", ""))
            
            # 1. Deteksi Baris Dosen
            if pd.notna(row.get("Kode MK")) and "," in kode_mk_col and ("S.Kom" in kode_mk_col or "S.T" in kode_mk_col or "M." in kode_mk_col):
                current_dosen = kode_mk_col.strip()
                current_kode_dosen = kd_col.strip() if kd_col.lower() != 'nan' and kd_col != '' else None
                current_mk = None
                current_nip = None 
                continue

            # 2. Deteksi NIP secara fleksibel (tidak perlu 'continue')
            if "NIP" in kode_mk_col.upper():
                numbers = re.findall(r'\d+', kode_mk_col)

                if numbers:
                    current_nip = numbers[0]

                    # update semua record dosen yang sama
                    for rec in all_records:
                        if rec.get("nama_dosen") == current_dosen:
                            rec["nip"] = current_nip

            # 3. Update Nama MK
            nama_mk_cell = str(row.get("Nama Matakuliah", ""))
            if nama_mk_cell.lower() != 'nan' and nama_mk_cell.strip() != "":
                current_mk = nama_mk_cell.strip()

            # 4. Ambil Kelas, Prodi
            kelas_raw = str(row.get("Kelas", ""))
            # Normalisasi: Jadikan huruf besar, hapus spasi berlebih (tetap pertahankan tanda strip jika ada)
            kelas_bersih = " ".join(kelas_raw.upper().split())

            prodi = str(row.get("Prodi", ""))

            # Dosen harus sudah terdeteksi dan MK harus sudah terdeteksi
            if current_dosen and current_mk and kelas_raw.lower() != 'nan' and kelas_raw.strip() != "":
                all_records.append({
                    "nip": current_nip if current_nip else "", 
                    "kode_dosen": current_kode_dosen if current_kode_dosen else "",
                    "nama_dosen": current_dosen,
                    "nama_mk": current_mk,
                    "kelas": kelas_bersih,
                    "prodi": prodi.strip() if prodi.lower() != 'nan' else ""
                })

    df_clean = pd.DataFrame(all_records).drop_duplicates()

    if not df_clean.empty:
        df_clean["nip"] = df_clean["nip"].astype(str)

    return df_clean

# TAHAP 2: CLEANSING MATKUL & SKS
def cleanse_matkul_sks(xls):
    all_records = []

    for sheet_name in xls.sheet_names:
        df = pd.read_excel(xls, sheet_name=sheet_name, header=None)

        for i in range(len(df)):
            nama_mk = df.iloc[i, 2]   
            sks_teori = df.iloc[i, 3] 
            sks_prak = df.iloc[i, 4]
            # Ambil kolom ke-6 sebagai kode_group jika ada
            kode_group = df.iloc[i, 5] if len(df.columns) > 5 else ""

            if not isinstance(nama_mk, str) or nama_mk.strip() == "":
                continue

            sks_teori = safe_int(sks_teori)
            sks_prak = safe_int(sks_prak)

            if sks_teori == 0 and sks_prak == 0:
                continue

            all_records.append({
                "nama_mk": nama_mk.strip(),
                "sks_teori": sks_teori,
                "sks_praktikum": sks_prak,
                "sks_total": sks_teori + sks_prak,
                "prodi": str(sheet_name).strip(),
                "kode_group": str(kode_group).strip() if pd.notna(kode_group) else ""
            })

    if not all_records:
        raise RuntimeError("Tidak ada data matakuliah yang berhasil diekstrak.")

    result_df = (
        pd.DataFrame(all_records)
        .drop_duplicates(subset=["nama_mk", "prodi"])
        .sort_values(by=["prodi", "nama_mk"])
        .reset_index(drop=True)
    )
    return result_df

# TAHAP 3: MERGE MATKUL & PLOTING
def merge_matkul_ploting(ploting_df, matkul_df):
    matkul_df["nama_mk"] = matkul_df["nama_mk"].astype(str).str.strip()
    matkul_df["prodi"] = matkul_df["prodi"].astype(str).str.strip()
    
    ploting_df["nama_mk"] = ploting_df["nama_mk"].astype(str).str.strip()
    ploting_df["prodi"] = ploting_df["prodi"].astype(str).str.strip()

    merged = ploting_df.merge(
        matkul_df,
        on=["nama_mk", "prodi"],
        how="left",
        suffixes=("", "_mk")
    )

    result = merged[
        [
            "nip",
            "kode_dosen",
            "nama_dosen",
            "nama_mk",
            "sks_teori",
            "sks_praktikum",
            "kelas",
            "prodi",
            "kode_group"
        ]
    ]

    result["sks_teori"] = result["sks_teori"].fillna(0).astype(int)
    result["sks_praktikum"] = result["sks_praktikum"].fillna(0).astype(int)
    result = result.fillna("") 
    
    return result

# TAHAP 4: CLEANSING RUANGAN (SUDAH DILENGKAPI FIND_COL)
def cleanse_ruangan(xls_ruang):
    df_ruang = pd.read_excel(xls_ruang, sheet_name="ruang")
    
    # Deteksi kolom secara cerdas menggunakan helper find_col
    col_ruang = find_col(df_ruang, ["nama_ruang", "nama ruang", "ruang", "kode"])
    col_kat = find_col(df_ruang, ["kategori", "jenis", "tipe"])
    col_prodi = find_col(df_ruang, ["prodi", "program studi"])

    rename_mapping = {}
    if col_ruang: rename_mapping[col_ruang] = "ruang"
    if col_kat: rename_mapping[col_kat] = "kategori"
    if col_prodi: rename_mapping[col_prodi] = "prodi"
    
    df_ruang = df_ruang.rename(columns=rename_mapping)

    if "ruang" not in df_ruang.columns:
        df_ruang["ruang"] = "Kolom Ruang Tidak Ditemukan"

    if "kategori" in df_ruang.columns:
        df_ruang["kategori"] = (
            df_ruang["kategori"]
            .astype(str)
            .str.lower()
            .replace({
                "praktek": "praktikum",
                "praktik": "praktikum",
                "lab": "praktikum"
            })
        )
    else:
        df_ruang["kategori"] = "teori" 

    if "prodi" in df_ruang.columns:
        df_ruang["prodi"] = df_ruang["prodi"].fillna("").astype(str).str.strip()
    
    df_ruang = df_ruang.fillna("")
    
    return df_ruang

# MASTER PIPELINE
def proses_cleansing_master(content_dosen, content_matkul, content_ruang):
    xls_dosen = pd.ExcelFile(io.BytesIO(content_dosen))
    xls_matkul = pd.ExcelFile(io.BytesIO(content_matkul))
    xls_ruang = pd.ExcelFile(io.BytesIO(content_ruang))

    df_ploting = cleanse_ploting(xls_dosen)
    df_matkul = cleanse_matkul_sks(xls_matkul)
    df_pengampu = merge_matkul_ploting(df_ploting, df_matkul)
    df_ruangan = cleanse_ruangan(xls_ruang)

    return {
        "pengampu": df_pengampu.to_dict(orient="records"),
        "ruangan": df_ruangan.to_dict(orient="records"),
        "mata_kuliah": df_matkul.to_dict(orient="records")
    }