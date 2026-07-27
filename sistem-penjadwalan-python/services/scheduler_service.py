from ortools.sat.python import cp_model
import math
from collections import defaultdict


def _precheck_feasibility(tasks, rooms_by_cat, unavail_by_dosen, num_hari, num_sesi):
    """
    Run quick capacity checks BEFORE building the CP model.
    Returns a list of human-readable violation strings (empty = OK).
    """
    violations = []

    # ── Check 1: task duration fits in a single day ──
    for t in tasks:
        if t['durasi'] > num_sesi:
            violations.append(
                f"Task '{t['task_id']}' (dosen_id={t['dosen_id']}, kelas_id={t['kelas_id']}) "
                f"berdurasi {t['durasi']} jam, melebihi kapasitas sesi per hari ({num_sesi})."
            )

    # ── Check 2: missing room category ──
    needed_cats = {t['jenis'] for t in tasks}
    for cat in needed_cats:
        if cat not in rooms_by_cat or len(rooms_by_cat[cat]) == 0:
            violations.append(
                f"Tidak ada ruangan berkategori '{cat}' tetapi ada {sum(1 for t in tasks if t['jenis'] == cat)} task yang membutuhkannya."
            )

    # ── Check 3: dosen overload ──
    # Friday (day index 4) loses 1 usable slot due to break at session 5 (C6).
    # Effective capacity per day = num_sesi, except Friday = num_sesi - 1.
    friday_idx = 4
    dosen_tasks = defaultdict(list)
    for t in tasks:
        dosen_tasks[t['dosen_id']].append(t)

    for dosen_id, dtasks in dosen_tasks.items():
        blocked_days = unavail_by_dosen.get(int(dosen_id), set())
        avail_days = [d for d in range(num_hari) if d not in blocked_days]
        # capacity = sum of usable slots across available days
        capacity = sum(num_sesi if d != friday_idx else (num_sesi - 1) for d in avail_days)
        total_hours = sum(t['durasi'] for t in dtasks)
        if total_hours > capacity:
            violations.append(
                f"Dosen (id={dosen_id}) memiliki total {total_hours} jam mengajar, "
                f"tetapi hanya tersedia {capacity} slot "
                f"({len(avail_days)} hari tersedia, {len(blocked_days)} hari diblokir). "
                f"Kelebihan {total_hours - capacity} jam. "
                f"Mata kuliah: {', '.join(t['task_id'] for t in dtasks)}."
            )

    # ── Check 4: kelas overload ──
    kelas_tasks = defaultdict(list)
    for t in tasks:
        kelas_tasks[t['kelas_id']].append(t)

    # kelas has no unavailable days, so full capacity minus friday break
    kelas_capacity = (num_hari - 1) * num_sesi + (num_sesi - 1)  # 4*8 + 7 = 39
    for kelas_id, ktasks in kelas_tasks.items():
        total_hours = sum(t['durasi'] for t in ktasks)
        if total_hours > kelas_capacity:
            violations.append(
                f"Kelas (id={kelas_id}) memiliki total {total_hours} jam perkuliahan, "
                f"tetapi kapasitas maksimum adalah {kelas_capacity} slot per minggu. "
                f"Kelebihan {total_hours - kelas_capacity} jam."
            )

    # ── Check 5: room category capacity ──
    for cat in needed_cats:
        if cat not in rooms_by_cat:
            continue
        num_rooms = len(rooms_by_cat[cat])
        cat_capacity = num_rooms * ((num_hari - 1) * num_sesi + (num_sesi - 1))
        cat_hours = sum(t['durasi'] for t in tasks if t['jenis'] == cat)
        if cat_hours > cat_capacity:
            violations.append(
                f"Total jam untuk kategori '{cat}' adalah {cat_hours}, "
                f"tetapi hanya ada {num_rooms} ruangan dengan kapasitas total {cat_capacity} slot. "
                f"Kelebihan {cat_hours - cat_capacity} jam."
            )

    return violations


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
    for p in data_pengampu:
        p_id = p['id']
        jt = p.get('jam_teori', 0)
        jp = p.get('jam_praktikum', 0)

        if jt > 0:
            tasks.append({
                'task_id': f"{p_id}_T",
                'pengampu_id': p_id,
                'dosen_id': p['dosen_id'],
                'mata_kuliah_id': p['mata_kuliah_id'],
                'kelas_id': p['kelas_id'],
                'tahun_ajar_id': p['tahun_ajar_id'],
                'durasi': jt,
                'jenis': 'teori',
            })
        if jp > 0:
            tasks.append({
                'task_id': f"{p_id}_P",
                'pengampu_id': p_id,
                'dosen_id': p['dosen_id'],
                'mata_kuliah_id': p['mata_kuliah_id'],
                'kelas_id': p['kelas_id'],
                'tahun_ajar_id': p['tahun_ajar_id'],
                'durasi': jp,
                'jenis': 'praktikum',
            })

    if not tasks:
        return {"status_solver": "GAGAL", "pesan": "Tidak ada task untuk dijadwalkan.", "data": []}

    # ── Pre-index rooms by category ──
    rooms_by_cat = {}
    for r in data_ruangan:
        cat = r.get('kategori', '').lower()
        rooms_by_cat.setdefault(cat, []).append(r)

    # Build room-index mapping per category: room_id → local index
    room_idx_map = {}   # cat → {room_id: index}
    room_list_map = {}  # cat → [room_id, ...]
    for cat, rooms in rooms_by_cat.items():
        room_list_map[cat] = [r['id'] for r in rooms]
        room_idx_map[cat] = {r['id']: i for i, r in enumerate(rooms)}

    # ── Pre-index unavailable days by dosen ──
    hari_index = {h: i for i, h in enumerate(hari_kerja)}
    unavail_by_dosen = {}
    for item in unavailable_days:
        did = item.get('dosen_id')
        h = item.get('hari')
        if did is not None and h in hari_index:
            unavail_by_dosen.setdefault(int(did), set()).add(hari_index[h])

    # ── Pre-solve feasibility checks ──
    violations = _precheck_feasibility(tasks, rooms_by_cat, unavail_by_dosen, num_hari, num_sesi)
    if violations:
        detail = " | ".join(violations)
        return {
            "status_solver": "GAGAL",
            "pesan": f"Data tidak layak dijadwalkan ({len(violations)} masalah): {detail}",
            "data": [],
            "violations": violations,
        }

    # ── 2. Create compact variables: 3 IntVars per task ──
    # For each task i:
    #   day[i]   ∈ [0, num_hari-1]
    #   start[i] ∈ [0, num_sesi - durasi]   (0-indexed start session)
    #   room[i]  ∈ [0, len(valid_rooms)-1]   (index into category room list)
    day_vars = []
    start_vars = []
    room_vars = []

    for i, t in enumerate(tasks):
        cat = t['jenis']
        num_rooms = len(rooms_by_cat.get(cat, []))
        if num_rooms == 0:
            return {
                "status_solver": "GAGAL",
                "pesan": f"Tidak ada ruangan berkategori '{cat}' untuk task {t['task_id']}.",
                "data": [],
            }

        durasi = t['durasi']
        max_start = num_sesi - durasi  # 0-indexed

        day_vars.append(model.NewIntVar(0, num_hari - 1, f"day_{i}"))
        start_vars.append(model.NewIntVar(0, max_start, f"start_{i}"))
        room_vars.append(model.NewIntVar(0, num_rooms - 1, f"room_{i}"))

    n = len(tasks)

    # Helper: linearised timeslot = day * num_sesi + start_session
    # Used for ordering and overlap detection
    def _time_var(i):
        """Return an IntVar = day[i] * num_sesi + start[i]."""
        tv = model.NewIntVar(0, num_hari * num_sesi - 1, f"time_{i}")
        model.Add(tv == day_vars[i] * num_sesi + start_vars[i])
        return tv

    # ── 3. Constraints ──

    # C6: Wajib Istirahat Jumat Sesi 5  (day=4, session-index=4)
    # A task occupies sessions [start, start+dur-1].  Block if it covers index 4 on Friday.
    # Equivalent: if day==4 then NOT (start <= 4 AND start+dur-1 >= 4)
    #           → if day==4 then (start > 4 OR start+dur-1 < 4)
    #           → if day==4 then (start >= 5 OR start <= 3 - 0) → start >= 5 OR start+dur <= 4
    for i, t in enumerate(tasks):
        durasi = t['durasi']
        is_friday = model.NewBoolVar(f"fri_{i}")
        model.Add(day_vars[i] == 4).OnlyEnforceIf(is_friday)
        model.Add(day_vars[i] != 4).OnlyEnforceIf(is_friday.Not())

        # If friday: task must not cover session-index 4
        # Task covers [start, start+dur-1].  Covers index 4 iff start <= 4 AND start+dur-1 >= 4
        # → start <= 4 AND start >= 5-dur → start ∈ [max(0,5-dur), 4]
        # Forbid that range on friday.
        low = max(0, 5 - durasi)
        high = 4
        if low <= high:
            # if is_friday → start < low OR start > high
            ok_before = model.NewBoolVar(f"fri_ok_b_{i}")
            ok_after = model.NewBoolVar(f"fri_ok_a_{i}")
            model.Add(start_vars[i] <= low - 1).OnlyEnforceIf(ok_before)
            model.Add(start_vars[i] >= low).OnlyEnforceIf(ok_before.Not())
            model.Add(start_vars[i] >= high + 1).OnlyEnforceIf(ok_after)
            model.Add(start_vars[i] <= high).OnlyEnforceIf(ok_after.Not())
            # friday → at least one of ok_before / ok_after
            model.AddBoolOr([ok_before, ok_after]).OnlyEnforceIf(is_friday)

    # C7: Dosen unavailable days
    for i, t in enumerate(tasks):
        blocked = unavail_by_dosen.get(int(t['dosen_id']), set())
        for bd in blocked:
            model.Add(day_vars[i] != bd)

    # C3: Anti Bentrok Ruangan  — no two tasks in the same physical room at overlapping times
    # C4: Anti Bentrok Dosen    — no dosen teaches two tasks at overlapping times
    # C5: Anti Bentrok Kelas    — no kelas has two tasks at overlapping times
    #
    # For every pair (i,j) sharing a resource, we need a no-overlap constraint:
    #   They overlap iff same_day AND start[i] < start[j]+dur[j] AND start[j] < start[i]+dur[i]
    #   → forbid that conjunction.
    #
    # We use reified bools + interval approach:
    #   same_day → (end[i] <= start[j]) OR (end[j] <= start[i])
    #   For room: also require same physical room  (room_id equality via category index)

    # Group tasks by shared resources for pairwise constraints
    # Build index structures
    tasks_by_dosen = {}
    tasks_by_kelas = {}
    tasks_by_cat = {}
    for i, t in enumerate(tasks):
        tasks_by_dosen.setdefault(t['dosen_id'], []).append(i)
        tasks_by_kelas.setdefault(t['kelas_id'], []).append(i)
        tasks_by_cat.setdefault(t['jenis'], []).append(i)

    def add_no_overlap_pair(i, j):
        """If same day → one must finish before other starts."""
        same_day = model.NewBoolVar(f"sd_{i}_{j}")
        model.Add(day_vars[i] == day_vars[j]).OnlyEnforceIf(same_day)
        model.Add(day_vars[i] != day_vars[j]).OnlyEnforceIf(same_day.Not())

        # i before j OR j before i  (when same day)
        i_before_j = model.NewBoolVar(f"ib_{i}_{j}")
        model.Add(start_vars[i] + tasks[i]['durasi'] <= start_vars[j]).OnlyEnforceIf(i_before_j)
        model.Add(start_vars[i] + tasks[i]['durasi'] > start_vars[j]).OnlyEnforceIf(i_before_j.Not())

        j_before_i = model.NewBoolVar(f"jb_{i}_{j}")
        model.Add(start_vars[j] + tasks[j]['durasi'] <= start_vars[i]).OnlyEnforceIf(j_before_i)
        model.Add(start_vars[j] + tasks[j]['durasi'] > start_vars[i]).OnlyEnforceIf(j_before_i.Not())

        model.AddBoolOr([same_day.Not(), i_before_j, j_before_i])

    # C4: Dosen — pairwise no-overlap among tasks of same dosen
    for dosen_id, idxs in tasks_by_dosen.items():
        for a in range(len(idxs)):
            for b in range(a + 1, len(idxs)):
                add_no_overlap_pair(idxs[a], idxs[b])

    # C5: Kelas — pairwise no-overlap among tasks of same kelas
    for kelas_id, idxs in tasks_by_kelas.items():
        for a in range(len(idxs)):
            for b in range(a + 1, len(idxs)):
                add_no_overlap_pair(idxs[a], idxs[b])

    # C3: Ruangan — pairwise no-overlap among tasks that *could* share a room
    # Two tasks share a room iff same category AND same room index → same physical room
    for cat, idxs in tasks_by_cat.items():
        for a in range(len(idxs)):
            for b in range(a + 1, len(idxs)):
                i, j = idxs[a], idxs[b]
                # same room?
                same_room = model.NewBoolVar(f"sr_{i}_{j}")
                model.Add(room_vars[i] == room_vars[j]).OnlyEnforceIf(same_room)
                model.Add(room_vars[i] != room_vars[j]).OnlyEnforceIf(same_room.Not())

                same_day = model.NewBoolVar(f"sdr_{i}_{j}")
                model.Add(day_vars[i] == day_vars[j]).OnlyEnforceIf(same_day)
                model.Add(day_vars[i] != day_vars[j]).OnlyEnforceIf(same_day.Not())

                i_before_j = model.NewBoolVar(f"ibr_{i}_{j}")
                model.Add(start_vars[i] + tasks[i]['durasi'] <= start_vars[j]).OnlyEnforceIf(i_before_j)
                model.Add(start_vars[i] + tasks[i]['durasi'] > start_vars[j]).OnlyEnforceIf(i_before_j.Not())

                j_before_i = model.NewBoolVar(f"jbr_{i}_{j}")
                model.Add(start_vars[j] + tasks[j]['durasi'] <= start_vars[i]).OnlyEnforceIf(j_before_i)
                model.Add(start_vars[j] + tasks[j]['durasi'] > start_vars[i]).OnlyEnforceIf(j_before_i.Not())

                # same_room AND same_day → one before other
                model.AddBoolOr([same_room.Not(), same_day.Not(), i_before_j, j_before_i])

    # C8: Teori before Praktikum (for pengampu that have both)
    tasks_by_pengampu = {}
    for i, t in enumerate(tasks):
        tasks_by_pengampu.setdefault(t['pengampu_id'], []).append(i)

    time_vars_cache = {}
    for pengampu_id, idxs in tasks_by_pengampu.items():
        teori_idx = next((i for i in idxs if tasks[i]['jenis'] == 'teori'), None)
        prak_idx = next((i for i in idxs if tasks[i]['jenis'] == 'praktikum'), None)
        if teori_idx is None or prak_idx is None:
            continue

        if teori_idx not in time_vars_cache:
            time_vars_cache[teori_idx] = _time_var(teori_idx)
        if prak_idx not in time_vars_cache:
            time_vars_cache[prak_idx] = _time_var(prak_idx)

        model.Add(time_vars_cache[teori_idx] < time_vars_cache[prak_idx])

    # ── 4. Solve ──
    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 300.0
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
            pesan = (
                f"Solver membuktikan bahwa jadwal TIDAK MUNGKIN dibuat (status: {status_name}). "
                "Kemungkinan penyebab: kombinasi constraint dosen, kelas, ruangan, "
                "dan istirahat Jumat sesi 5 membuat tidak ada solusi yang valid. "
                "Periksa apakah ada dosen dengan beban mengajar terlalu tinggi "
                "atau kelas dengan terlalu banyak mata kuliah."
            )
        elif status == cp_model.UNKNOWN:
            pesan = (
                f"Solver kehabisan waktu sebelum menemukan solusi (status: {status_name}, "
                f"waktu: {solver.WallTime():.1f}s). Coba kurangi jumlah task atau tambah waktu solver."
            )
        elif status == cp_model.MODEL_INVALID:
            pesan = f"Model tidak valid (status: {status_name}). Ini adalah bug internal."
        else:
            pesan = f"Solver gagal dengan status tidak terduga: {status_name}."
        return {"status_solver": "GAGAL", "pesan": pesan, "data": []}
