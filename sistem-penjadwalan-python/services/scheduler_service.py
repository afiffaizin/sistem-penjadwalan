from ortools.sat.python import cp_model
import math
from collections import defaultdict


def _precheck_feasibility(tasks, rooms_by_cat, unavail_by_dosen, num_hari, num_sesi):
    """
    Run quick capacity checks BEFORE building the CP model.

    Returns (primary_cause, reasons_list, recommendation) or (None, [], None) if OK.
    Priority of checks: Missing Rooms > Room Capacity > Lecturer Overload > Class Overload > Task Duration.
    """
    # Build name lookup maps from task data
    dosen_names = {}
    kelas_names = {}
    for t in tasks:
        if t.get('dosen_nama'):
            dosen_names[t['dosen_id']] = t['dosen_nama']
        if t.get('kelas_nama'):
            kelas_names[t['kelas_id']] = t['kelas_nama']

    def _dosen_label(did):
        return dosen_names.get(did, f"Lecturer ID {did}")

    def _kelas_label(kid):
        return kelas_names.get(kid, f"Class ID {kid}")

    needed_cats = {t['jenis'] for t in tasks}
    cat_labels = {'teori': 'Teori', 'praktikum': 'Praktikum'}

    # 1. Missing Room Category
    missing_cats = []
    for cat in needed_cats:
        if cat not in rooms_by_cat or len(rooms_by_cat[cat]) == 0:
            missing_cats.append(cat)
            
    if missing_cats:
        reasons = []
        for cat in missing_cats:
            count = sum(1 for t in tasks if t['jenis'] == cat)
            label = cat_labels.get(cat, cat.title())
            reasons.append(f"**{count} matkul** membutuhkan ruangan **{label}**, namun ruangan tersebut tidak tersedia.")
        return (
            "Kategori ruangan tidak ditemukan.",
            reasons,
            "Silakan tambahkan jenis ruangan yang dibutuhkan pada menu Master Data sebelum melakukan generate jadwal."
        )

    # 2. Room Category Capacity Overload
    for cat in needed_cats:
        num_rooms = len(rooms_by_cat[cat])
        cat_capacity = num_rooms * ((num_hari - 1) * num_sesi + (num_sesi - 1))
        cat_hours = sum(t['durasi'] for t in tasks if t['jenis'] == cat)
        if cat_hours > cat_capacity:
            label = cat_labels.get(cat, cat.title())
            return (
                "Kapasitas ruangan tidak mencukupi.",
                [
                    f"Total kebutuhan ruangan **{label}** adalah **{cat_hours} sesi**.",
                    f"Hanya tersedia **{num_rooms} ruangan** dengan total kapasitas **{cat_capacity} sesi**.",
                    f"Kelebihan beban: **{cat_hours - cat_capacity} sesi**."
                ],
                f"Silakan tambahkan ruangan {label} baru atau kurangi jumlah matkul yang dijadwalkan."
            )

    # 3. Lecturer Overload
    friday_idx = 4
    dosen_tasks = defaultdict(list)
    for t in tasks:
        dosen_tasks[t['dosen_id']].append(t)

    for dosen_id, dtasks in dosen_tasks.items():
        try:
            d_id = int(dosen_id)
        except (ValueError, TypeError):
            d_id = dosen_id
            
        blocked_days = unavail_by_dosen.get(d_id, set())
        avail_days = [d for d in range(num_hari) if d not in blocked_days]
        capacity = sum(num_sesi if d != friday_idx else (num_sesi - 1) for d in avail_days)
        total_hours = sum(t['durasi'] for t in dtasks)
        if total_hours > capacity:
            name = _dosen_label(dosen_id)
            return (
                "Beban mengajar dosen melebihi batas maksimal.",
                [
                    f"Dosen: **{name}**.",
                    f"Total beban mengajar: **{total_hours} sesi**.",
                    f"Kapasitas maksimal yang tersedia: **{capacity} sesi** ({len(avail_days)} hari tersedia, {len(blocked_days)} hari diblokir).",
                    f"Kelebihan beban: **{total_hours - capacity} sesi**."
                ],
                "Silakan kurangi beban mengajar dosen ini, buka jadwal hari yang diblokir, atau pindahkan sebagian matkul ke dosen lain."
            )

    # 4. Class Overload
    kelas_tasks = defaultdict(list)
    for t in tasks:
        kelas_tasks[t['kelas_id']].append(t)

    kelas_capacity = (num_hari - 1) * num_sesi + (num_sesi - 1)
    for kelas_id, ktasks in kelas_tasks.items():
        total_hours = sum(t['durasi'] for t in ktasks)
        if total_hours > kelas_capacity:
            name = _kelas_label(kelas_id)
            return (
                "Total sesi perkuliahan kelas melebihi batas maksimal per minggu.",
                [
                    f"Kelas: **{name}**.",
                    f"Total sesi ditugaskan: **{total_hours} sesi**.",
                    f"Kapasitas maksimal mingguan: **{kelas_capacity} sesi**.",
                    f"Kelebihan beban: **{total_hours - kelas_capacity} sesi**."
                ],
                "Silakan pindahkan sebagian mata kuliah ke kelas lain."
            )

    # 5. Task Duration Exceeds Daily Limit
    for t in tasks:
        if t['durasi'] > num_sesi:
            mk_name = t.get('mata_kuliah_nama', t['task_id'])
            return (
                "Durasi mata kuliah melebihi batas sesi harian.",
                [
                    f"Mata Kuliah: **{mk_name}**.",
                    f"Membutuhkan sesi berturut-turut sebanyak: **{t['durasi']} sesi**.",
                    f"Batas maksimal sesi per hari: **{num_sesi} sesi**."
                ],
                "Silakan kurangi durasi blok SKS pada mata kuliah ini."
            )

    return None, [], None


def generate_jadwal_or_tools(data_pengampu, data_ruangan, unavailable_days=None):
    """
    Compact CP-SAT model: 3 IntVars per task (day, start_session, room_index)
    instead of O(T×R×D×S) BoolVars.  Keeps all 8 original constraints (C1–C8).
    """
    model = cp_model.CpModel()
    unavailable_days = unavailable_days or []

    hari_kerja = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']
    num_hari = len(hari_kerja)
    num_sesi = 8

    # ── 1. Build tasks from pengampu (same logic as before) ──
    tasks = []
    # If the payload format doesn't have an 'id' but rather 'nip', it might be using the output of cleansing_service directly
    for i, p in enumerate(data_pengampu):
        p_id = p.get('id', i + 1)
        jt = p.get('jam_teori', p.get('sks_teori', 0))
        jp = p.get('jam_praktikum', p.get('sks_praktikum', 0))

        if jt > 0:
            tasks.append({
                'task_id': f"{p_id}_T",
                'pengampu_id': p_id,
                'dosen_id': p.get('dosen_id', p.get('kode_dosen', '')),
                'dosen_nama': p.get('dosen_nama', p.get('nama_dosen', '')),
                'mata_kuliah_id': p.get('mata_kuliah_id', p.get('nama_mk', '')),
                'mata_kuliah_nama': p.get('mata_kuliah_nama', p.get('nama_mk', '')),
                'kelas_id': p.get('kelas_id', p.get('kelas', '')),
                'kelas_nama': p.get('kelas_nama', p.get('kelas', '')),
                'tahun_ajar_id': p.get('tahun_ajar_id', 1),
                'group_matkul': p.get('group_matkul', '-'),
                'prodi_id': p.get('prodi_id'),
                'durasi': jt,
                'jenis': 'teori',
            })
        if jp > 0:
            tasks.append({
                'task_id': f"{p_id}_P",
                'pengampu_id': p_id,
                'dosen_id': p.get('dosen_id', p.get('kode_dosen', '')),
                'dosen_nama': p.get('dosen_nama', p.get('nama_dosen', '')),
                'mata_kuliah_id': p.get('mata_kuliah_id', p.get('nama_mk', '')),
                'mata_kuliah_nama': p.get('mata_kuliah_nama', p.get('nama_mk', '')),
                'kelas_id': p.get('kelas_id', p.get('kelas', '')),
                'kelas_nama': p.get('kelas_nama', p.get('kelas', '')),
                'tahun_ajar_id': p.get('tahun_ajar_id', 1),
                'group_matkul': p.get('group_matkul', '-'),
                'prodi_id': p.get('prodi_id'),
                'durasi': jp,
                'jenis': 'praktikum',
            })

    if not tasks:
        return {
            "status_solver": "GAGAL",
            "pesan": "Tidak ada data beban mengajar untuk dijadwalkan.",
            "data": [],
            "violations": [
                "Belum ada ploting dosen pengampu mata kuliah pada semester ini."
            ],
            "recommendation": "Silakan atur ploting dosen pengampu mata kuliah untuk semester yang dipilih terlebih dahulu."
        }

    # ── Pre-index rooms by category ──
    rooms_by_cat = {}
    for r in data_ruangan:
        cat = r.get('kategori', '').lower()
        rooms_by_cat.setdefault(cat, []).append(r)

    # Build room-index mapping per category: room_id → local index
    room_idx_map = {}   # cat → {room_id: index}
    room_list_map = {}  # cat → [room_id, ...]
    for cat, rooms in rooms_by_cat.items():
        room_list_map[cat] = [r.get('id', r.get('ruang', '')) for r in rooms]
        room_idx_map[cat] = {r.get('id', r.get('ruang', '')): i for i, r in enumerate(rooms)}

    # ── Pre-index unavailable days by dosen ──
    hari_index = {h: i for i, h in enumerate(hari_kerja)}
    unavail_by_dosen = {}
    for item in unavailable_days:
        did = item.get('dosen_id')
        h = item.get('hari')
        if did is not None and h in hari_index:
            try:
                unavail_by_dosen.setdefault(int(did), set()).add(hari_index[h])
            except (ValueError, TypeError):
                unavail_by_dosen.setdefault(did, set()).add(hari_index[h])

    # ── Pre-solve feasibility checks ──
    primary_cause, reasons, recommendation = _precheck_feasibility(tasks, rooms_by_cat, unavail_by_dosen, num_hari, num_sesi)
    if primary_cause:
        return {
            "status_solver": "GAGAL",
            "pesan": primary_cause,
            "data": [],
            "violations": reasons,
            "recommendation": recommendation,
        }


    # -- 2. Create variables (Interval-based for AddNoOverlap) --
    day_vars = []
    start_vars = []
    room_vars = []
    task_intervals = {} # i -> d -> interval_var
    task_presences = {} # i -> d -> bool_var
    
    for i, t in enumerate(tasks):
        cat = t["jenis"]
        num_rooms = len(rooms_by_cat.get(cat, []))
        if num_rooms == 0:
            cat_labels = {"teori": "Teori", "praktikum": "Praktikum"}
            label = cat_labels.get(cat, cat.title())
            mk_name = t.get("mata_kuliah_nama", t["task_id"])
            return {
                "status_solver": "GAGAL",
                "pesan": "Kategori ruangan tidak ditemukan.",
                "data": [],
                "violations": [
                    f"Mata Kuliah: **{mk_name}** membutuhkan ruangan **{label}**, namun ruangan tersebut tidak tersedia."
                ],
                "recommendation": f"Silakan tambahkan minimal satu ruangan {label} pada master data, lalu coba generate jadwal kembali.",
            }

        durasi = t["durasi"]
        max_start = num_sesi - durasi  # 0-indexed

        dv = model.NewIntVar(0, num_hari - 1, f"day_{i}")
        sv = model.NewIntVar(0, max_start, f"start_{i}")
        rv = model.NewIntVar(0, num_rooms - 1, f"room_{i}")
        
        day_vars.append(dv)
        start_vars.append(sv)
        room_vars.append(rv)
        
        task_intervals[i] = {}
        task_presences[i] = {}
        for d in range(num_hari):
            p = model.NewBoolVar(f"p_{i}_{d}")
            model.Add(dv == d).OnlyEnforceIf(p)
            model.Add(dv != d).OnlyEnforceIf(p.Not())
            task_presences[i][d] = p
            
            ev = model.NewIntVar(durasi, num_sesi, f"end_{i}_{d}")
            model.Add(ev == sv + durasi)
            iv = model.NewOptionalIntervalVar(sv, durasi, ev, p, f"int_{i}_{d}")
            task_intervals[i][d] = iv
            
    n = len(tasks)

    # ── C10: Praktikum room-prodi soft preference (penalty-based) ──
    # Same prodi → 0, shared (NULL) → low penalty, different prodi → high penalty.
    # All rooms remain valid candidates; solver minimizes total penalty.
    PENALTY_SHARED = 5
    PENALTY_CROSS_PRODI = 15
    prodi_penalties = []  # list of IntVar penalties to minimize

    if 'praktikum' in rooms_by_cat:
        prak_rooms = rooms_by_cat['praktikum']
        num_prak_rooms = len(prak_rooms)

        for i, t in enumerate(tasks):
            if t['jenis'] != 'praktikum':
                continue
            task_prodi = t.get('prodi_id')
            if not task_prodi:
                continue

            # Build penalty array: one value per room index
            pen_values = []
            for idx, r in enumerate(prak_rooms):
                rp = r.get('prodi_id')
                if rp and rp == task_prodi:
                    pen_values.append(0)
                elif not rp:
                    pen_values.append(PENALTY_SHARED)
                else:
                    pen_values.append(PENALTY_CROSS_PRODI)

            pen_var = model.NewIntVar(0, PENALTY_CROSS_PRODI, f"prodi_pen_{i}")
            model.AddElement(room_vars[i], pen_values, pen_var)
            prodi_penalties.append(pen_var)

    # Helper for C8 (Teori before Praktikum)
    def _time_var(i):
        tv = model.NewIntVar(0, num_hari * num_sesi - 1, f"time_{i}")
        model.Add(tv == day_vars[i] * num_sesi + start_vars[i])
        return tv

    # -- 3. Constraints --

    # C6: Wajib Istirahat Jumat Sesi 5 (index 4)
    for i, t in enumerate(tasks):
        durasi = t["durasi"]
        is_friday = task_presences[i][4]

        low = max(0, 5 - durasi)
        high = 4
        if low <= high:
            ok_before = model.NewBoolVar(f"fri_ok_b_{i}")
            ok_after = model.NewBoolVar(f"fri_ok_a_{i}")
            model.Add(start_vars[i] <= low - 1).OnlyEnforceIf(ok_before)
            model.Add(start_vars[i] >= low).OnlyEnforceIf(ok_before.Not())
            model.Add(start_vars[i] >= high + 1).OnlyEnforceIf(ok_after)
            model.Add(start_vars[i] <= high).OnlyEnforceIf(ok_after.Not())
            model.AddBoolOr([ok_before, ok_after]).OnlyEnforceIf(is_friday)

    # C7: Dosen unavailable days
    for i, t in enumerate(tasks):
        did = t["dosen_id"]
        try:
            d_id = int(did)
        except (ValueError, TypeError):
            d_id = did
        blocked = unavail_by_dosen.get(d_id, set())
        for bd in blocked:
            model.Add(day_vars[i] != bd)

    # C4 & C5: No Overlap for Dosen and Kelas
    tasks_by_dosen = {}
    tasks_by_kelas = {}
    tasks_by_cat = {}
    for i, t in enumerate(tasks):
        tasks_by_dosen.setdefault(t["dosen_id"], []).append(i)
        tasks_by_kelas.setdefault(t["kelas_id"], []).append(i)
        tasks_by_cat.setdefault(t["jenis"], []).append(i)
        
    for d in range(num_hari):
        for dosen_id, idxs in tasks_by_dosen.items():
            if len(idxs) > 1:
                model.AddNoOverlap([task_intervals[i][d] for i in idxs])
                
        for kelas_id, idxs in tasks_by_kelas.items():
            if len(idxs) > 1:
                model.AddNoOverlap([task_intervals[i][d] for i in idxs])

    # C3: No Overlap for Rooms
    for cat, idxs in tasks_by_cat.items():
        num_rooms = len(rooms_by_cat[cat])
        if len(idxs) > 1:
            for d in range(num_hari):
                for r in range(num_rooms):
                    room_day_intervals = []
                    for i in idxs:
                        p_dr = model.NewBoolVar(f"p_{i}_{d}_{r}")
                        in_r = model.NewBoolVar(f"in_r_{i}_{r}")
                        model.Add(room_vars[i] == r).OnlyEnforceIf(in_r)
                        model.Add(room_vars[i] != r).OnlyEnforceIf(in_r.Not())
                        
                        # p_dr == task_presences[i][d] AND in_r
                        model.AddImplication(p_dr, task_presences[i][d])
                        model.AddImplication(p_dr, in_r)
                        model.AddBoolOr([task_presences[i][d].Not(), in_r.Not(), p_dr])
                        
                        ev = model.NewIntVar(tasks[i]["durasi"], num_sesi, f"end_r_{i}_{d}_{r}")
                        model.Add(ev == start_vars[i] + tasks[i]["durasi"])
                        iv = model.NewOptionalIntervalVar(start_vars[i], tasks[i]["durasi"], ev, p_dr, f"int_r_{i}_{d}_{r}")
                        room_day_intervals.append(iv)
                        
                    if len(room_day_intervals) > 1:
                        model.AddNoOverlap(room_day_intervals)

    # C8: Teori before Praktikum
    tasks_by_pengampu = {}
    for i, t in enumerate(tasks):
        tasks_by_pengampu.setdefault(t["pengampu_id"], []).append(i)

    time_vars_cache = {}
    for pengampu_id, idxs in tasks_by_pengampu.items():
        teori_idx = next((i for i in idxs if tasks[i]["jenis"] == "teori"), None)
        prak_idx = next((i for i in idxs if tasks[i]["jenis"] == "praktikum"), None)
        if teori_idx is None or prak_idx is None:
            continue

        if teori_idx not in time_vars_cache:
            time_vars_cache[teori_idx] = _time_var(teori_idx)
        if prak_idx not in time_vars_cache:
            time_vars_cache[prak_idx] = _time_var(prak_idx)

        model.Add(time_vars_cache[teori_idx] < time_vars_cache[prak_idx])

    # C9: Teori before Praktikum (Berdasarkan Grouping Matkul & Kelas)
    tasks_by_kelas_group = {}
    for i, t in enumerate(tasks):
        group_id = t.get("group_matkul", "-")
        kelas_id = t["kelas_id"]
        
        if group_id and str(group_id).strip() != "" and str(group_id).strip() != "-":
            group_key = f"{kelas_id}_{group_id}"
            tasks_by_kelas_group.setdefault(group_key, []).append(i)

    for group_key, idxs in tasks_by_kelas_group.items():
        teori_idx = next((i for i in idxs if tasks[i]["jenis"] == "teori"), None)
        prak_idx = next((i for i in idxs if tasks[i]["jenis"] == "praktikum"), None)
        
        if teori_idx is not None and prak_idx is not None:
            if teori_idx not in time_vars_cache:
                time_vars_cache[teori_idx] = _time_var(teori_idx)
            if prak_idx not in time_vars_cache:
                time_vars_cache[prak_idx] = _time_var(prak_idx)
                
            model.Add(time_vars_cache[teori_idx] < time_vars_cache[prak_idx])

    # ── 4. Solve ──
    # Minimize prodi room mismatch penalties (0 if no praktikum tasks with prodi)
    if prodi_penalties:
        model.Minimize(sum(prodi_penalties))

    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 700.0
    solver.parameters.max_memory_in_mb = 2048
    solver.parameters.num_workers = 4

    print(f"   Model: {n} tasks, {model.Proto().variables.__len__()} vars")
    status = solver.Solve(model)
    print(f"   Solver status: {solver.StatusName(status)}, time: {solver.WallTime():.1f}s")

    # ── 5. Extract results ──
    if status in (cp_model.OPTIMAL, cp_model.FEASIBLE):
        hasil = []
        for i, t in enumerate(tasks):
            cat = t['jenis']
            d = solver.Value(day_vars[i])
            s = solver.Value(start_vars[i])
            ri = solver.Value(room_vars[i])
            room_id = room_list_map[cat][ri]

            hasil.append({
                "pengampu_id": t['pengampu_id'],
                "dosen_id": t['dosen_id'],
                "mata_kuliah_id": t['mata_kuliah_id'],
                "kelas_id": t['kelas_id'],
                "tahun_ajar_id": t['tahun_ajar_id'],
                "ruang_id": room_id,
                "hari": hari_kerja[d],
                "sesi_mulai": s + 1,           # 1-indexed for Laravel
                "sesi_selesai": s + t['durasi'],  # inclusive
            })
        return {"status_solver": "SUKSES", "pesan": "Berhasil", "data": hasil}
    else:
        status_name = solver.StatusName(status)
        if status == cp_model.INFEASIBLE:
            pesan = "Terjadi bentrok pada batasan penjadwalan."
            violations = [
                "Sistem tidak dapat menemukan kombinasi jadwal yang valid tanpa bentrok untuk dosen, kelas, dan ruangan yang ada.",
                "Hal ini biasanya terjadi karena keterbatasan waktu (contoh: dosen mengajar terlalu banyak kelas atau terlalu banyak hari yang diblokir)."
            ]
            recommendation = "Silakan tinjau kembali beban mengajar dosen, kurangi hari tidak mengajar yang diblokir, atau sesuaikan jumlah mata kuliah."
        elif status == cp_model.UNKNOWN:
            pesan = "Sistem kehabisan waktu saat mencoba menyusun jadwal."
            violations = [
                f"Sistem mencapai batas waktu {solver.WallTime():.0f} detik sebelum berhasil menemukan solusi jadwal.",
                "Masalah penjadwalan saat ini terlalu kompleks untuk diselesaikan dalam batas waktu tersebut."
            ]
            recommendation = "Cobalah untuk mengurangi beban SKS atau hubungi administrator sistem untuk menambah batas waktu pencarian jadwal."
        elif status == cp_model.MODEL_INVALID:
            pesan = "Terjadi kesalahan sistem internal."
            violations = [
                "Model matematis untuk proses penjadwalan tidak valid."
            ]
            recommendation = "Silakan hubungi administrator sistem."
        else:
            pesan = "Terjadi kesalahan yang tidak terduga."
            violations = [
                f"Proses penjadwalan gagal dengan status: {status_name}."
            ]
            recommendation = "Silakan coba lagi. Jika masalah berlanjut, hubungi administrator sistem."

        return {
            "status_solver": "GAGAL",
            "pesan": pesan,
            "data": [],
            "violations": violations,
            "recommendation": recommendation,
        }
