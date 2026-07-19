from ortools.sat.python import cp_model

def generate_jadwal_or_tools(data_pengampu, data_ruangan, unavailable_days=None):
    model = cp_model.CpModel()
    unavailable_days = unavailable_days or []

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

    # C7: Dosen tidak boleh dijadwalkan pada hari yang direquest tidak bisa mengajar
    hari_index = {hari: idx for idx, hari in enumerate(hari_kerja)}
    unavailable_by_dosen = {}
    for item in unavailable_days:
        dosen_id = item.get('dosen_id')
        hari = item.get('hari')
        if dosen_id is not None and hari in hari_index:
            unavailable_by_dosen.setdefault(int(dosen_id), set()).add(hari_index[hari])

    for t in tasks:
        blocked_days = unavailable_by_dosen.get(int(t['dosen_id']), set())
        if not blocked_days:
            continue

        valid_rooms = [r for r in data_ruangan if r.get('kategori', '').lower() == t['jenis']]
        for r in valid_rooms:
            for d in blocked_days:
                for s in range(num_sesi):
                    model.Add(active_vars[(t['task_id'], r['id'], d, s)] == 0)

    # C8: Teori harus dijadwalkan SEBELUM Praktikum (untuk matkul yang punya keduanya)
    # Kelompokkan task berdasarkan pengampu_id untuk menemukan pasangan Teori + Praktikum
    tasks_by_pengampu = {}
    for t in tasks:
        tasks_by_pengampu.setdefault(t['pengampu_id'], []).append(t)

    for pengampu_id, group in tasks_by_pengampu.items():
        teori_task = next((t for t in group if t['jenis'] == 'teori'), None)
        praktikum_task = next((t for t in group if t['jenis'] == 'praktikum'), None)

        # Hanya berlaku jika matkul tersebut memiliki KEDUA komponen
        if teori_task is None or praktikum_task is None:
            continue

        # Buat variabel waktu linear untuk setiap task: waktu = hari * num_sesi + sesi_mulai
        # Variabel waktu teori
        teori_time = model.NewIntVar(0, num_hari * num_sesi - 1, f"time_{teori_task['task_id']}")
        valid_rooms_teori = [r for r in data_ruangan if r.get('kategori', '').lower() == 'teori']
        teori_contributions = []
        for r in valid_rooms_teori:
            for d in range(num_hari):
                for s in range(num_sesi):
                    if (teori_task['task_id'], r['id'], d, s) in start_vars:
                        time_val = d * num_sesi + s
                        teori_contributions.append(start_vars[(teori_task['task_id'], r['id'], d, s)] * time_val)
        model.Add(teori_time == sum(teori_contributions))

        # Variabel waktu praktikum
        praktikum_time = model.NewIntVar(0, num_hari * num_sesi - 1, f"time_{praktikum_task['task_id']}")
        valid_rooms_praktikum = [r for r in data_ruangan if r.get('kategori', '').lower() == 'praktikum']
        praktikum_contributions = []
        for r in valid_rooms_praktikum:
            for d in range(num_hari):
                for s in range(num_sesi):
                    if (praktikum_task['task_id'], r['id'], d, s) in start_vars:
                        time_val = d * num_sesi + s
                        praktikum_contributions.append(start_vars[(praktikum_task['task_id'], r['id'], d, s)] * time_val)
        model.Add(praktikum_time == sum(praktikum_contributions))

        # Constraint: waktu teori harus LEBIH AWAL dari waktu praktikum
        model.Add(teori_time < praktikum_time)

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