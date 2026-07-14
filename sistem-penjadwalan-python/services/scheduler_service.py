from ortools.sat.python import cp_model

def generate_jadwal_or_tools(data_pengampu, data_ruangan):
    model = cp_model.CpModel()

    hari_kerja = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']
    num_hari = len(hari_kerja)
    num_sesi = 8

    # 1. Teori & Praktikum dipisah jadi tugas mandiri
    tasks = []
    for p in data_pengampu:
        p_id = p['id']
        jt = p.get('jam_teori', 0)
        jp = p.get('jam_praktikum', 0)

        # Jam teori
        if jt > 0:
            tasks.append({
                'task_id': f"{p_id}_T",
                'pengampu_id': p_id,
                'dosen_id': p['dosen_id'],
                'mata_kuliah_id': p['mata_kuliah_id'],
                'kelas_id': p['kelas_id'],
                'tahun_ajar_id': p['tahun_ajar_id'], 
                'durasi': jt,
                'jenis': 'teori'
            })
            
        # Jam praktikum
        if jp > 0:
            tasks.append({
                'task_id': f"{p_id}_P",
                'pengampu_id': p_id,
                'dosen_id': p['dosen_id'],
                'mata_kuliah_id': p['mata_kuliah_id'],
                'kelas_id': p['kelas_id'],
                'tahun_ajar_id': p['tahun_ajar_id'], 
                'durasi': jp,
                'jenis': 'praktikum'
            })

    # 2. PEMBUATAN VARIABEL MATRIX
    start_vars = {}   
    active_vars = {}  

    for t in tasks:
        t_id = t['task_id']
        durasi = t['durasi']
        
        valid_rooms = [r for r in data_ruangan if r.get('kategori', '').lower() == t['jenis']]

        for r in valid_rooms:
            r_id = r['id']
            for d in range(num_hari):
                for s in range(num_sesi):
                    active_vars[(t_id, r_id, d, s)] = model.NewBoolVar(f"ACT_{t_id}_R{r_id}_D{d}_S{s}")
                    
                    if s <= num_sesi - durasi:
                        start_vars[(t_id, r_id, d, s)] = model.NewBoolVar(f"STR_{t_id}_R{r_id}_D{d}_S{s}")
                    else:
                        start_vars[(t_id, r_id, d, s)] = model.NewConstant(0)

    # 3. PENAMBAHAN CONSTRAINTS
    # C1: Setiap tugas harus jalan 1 kali
    for t in tasks:
        valid_rooms = [r for r in data_ruangan if r.get('kategori', '').lower() == t['jenis']]
        model.AddExactlyOne(
            start_vars[(t['task_id'], r['id'], d, s)]
            for r in valid_rooms for d in range(num_hari) for s in range(num_sesi)
        )

    # C2: SKS saling berurutan dalam 1 tugas
    for t in tasks:
        t_id = t['task_id']
        durasi = t['durasi']
        valid_rooms = [r for r in data_ruangan if r.get('kategori', '').lower() == t['jenis']]
        for r in valid_rooms:
            r_id = r['id']
            for d in range(num_hari):
                for s in range(num_sesi):
                    start_window = [start_vars[(t_id, r_id, d, sp)] for sp in range(max(0, s - durasi + 1), s + 1)]
                    model.Add(active_vars[(t_id, r_id, d, s)] == sum(start_window))

    # C3: Anti Bentrok Ruangan
    for r in data_ruangan:
        r_id = r['id']
        r_kat = r.get('kategori', '').lower()
        compatible_tasks = [t for t in tasks if t['jenis'] == r_kat]
        for d in range(num_hari):
            for s in range(num_sesi):
                model.AddAtMostOne(active_vars[(t['task_id'], r_id, d, s)] for t in compatible_tasks)

    # C4: Anti Bentrok Dosen
    dosen_ids = set(t['dosen_id'] for t in tasks)
    for dosen_id in dosen_ids:
        tasks_dosen = [t for t in tasks if t['dosen_id'] == dosen_id]
        for d in range(num_hari):
            for s in range(num_sesi):
                active_for_dosen = []
                for t in tasks_dosen:
                    valid_rooms = [r for r in data_ruangan if r.get('kategori', '').lower() == t['jenis']]
                    for r in valid_rooms:
                        active_for_dosen.append(active_vars[(t['task_id'], r['id'], d, s)])
                model.AddAtMostOne(active_for_dosen)

    # C5: Anti Bentrok Kelas Mahasiswa
    kelas_ids = set(t['kelas_id'] for t in tasks)
    for kelas_id in kelas_ids:
        tasks_kelas = [t for t in tasks if t['kelas_id'] == kelas_id]
        for d in range(num_hari):
            for s in range(num_sesi):
                active_for_kelas = []
                for t in tasks_kelas:
                    valid_rooms = [r for r in data_ruangan if r.get('kategori', '').lower() == t['jenis']]
                    for r in valid_rooms:
                        active_for_kelas.append(active_vars[(t['task_id'], r['id'], d, s)])
                model.AddAtMostOne(active_for_kelas)

    # C6: Wajib Istirahat Jumat Sesi 5
    for t in tasks:
        valid_rooms = [r for r in data_ruangan if r.get('kategori', '').lower() == t['jenis']]
        for r in valid_rooms:
            model.Add(active_vars[(t['task_id'], r['id'], 4, 4)] == 0)

    # 4. SOLVER
    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 240.0 
    status = solver.Solve(model)

    # 5. KEMBALIKAN DATA LENGKAP KE LARAVEL
    hasil_jadwal = []
    if status == cp_model.OPTIMAL or status == cp_model.FEASIBLE:
        for t in tasks:
            valid_rooms = [r for r in data_ruangan if r.get('kategori', '').lower() == t['jenis']]
            for r in valid_rooms:
                for d in range(num_hari):
                    for s in range(num_sesi):
                        if solver.Value(start_vars[(t['task_id'], r['id'], d, s)]) == 1:
                            hasil_jadwal.append({
                                "pengampu_id": t['pengampu_id'],
                                "dosen_id": t['dosen_id'],
                                "mata_kuliah_id": t['mata_kuliah_id'],
                                "kelas_id": t['kelas_id'],
                                "tahun_ajar_id": t['tahun_ajar_id'],
                                "ruang_id": r['id'],
                                "hari": hari_kerja[d],
                                "sesi_mulai": s + 1,
                                "sesi_selesai": s + t['durasi']
                            })
        return {"status_solver": "SUKSES", "pesan": "Berhasil", "data": hasil_jadwal}
    else:
        return {"status_solver": "GAGAL", "pesan": "Gagal.", "data": []}