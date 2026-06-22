<?php
$csrf = (string)($csrf ?? csrf_token());
$filters = $filters ?? [];
$students = $students ?? [];

$yearId = (int)($yearId ?? 0);
$term = (int)($term ?? 1);
$term = ($term === 2) ? 2 : 1;
$subjectId = (int)($subjectId ?? 0);

$basePath = '/tracks/class_attendance_create';
$baseQuery = http_build_query(array_filter([
  'year_id' => $yearId,
  'term' => $term,
  'subject_id' => $subjectId,
  'class_level' => (string)($filters['class_level'] ?? ''),
  'room' => (string)($filters['room'] ?? ''),
  'q' => (string)($filters['q'] ?? ''),
], static fn($v) => $v !== '' && $v !== 0));
?>

<div class="grid gap-6">
  <section class="rounded-3xl border border-base-300 bg-base-100/80 p-5 shadow-sm backdrop-blur">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight">➕ สร้างรอบเรียน</h1>
        <p class="mt-1 text-sm text-base-content/70">เลือกวิชา/วันที่/รายชื่อนักเรียน แล้วสร้างรอบเรียนเพื่อไปเช็คชื่อ</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <a class="rounded-2xl border border-base-300 bg-base-100 px-4 py-2.5 text-sm hover:bg-base-200" href="/tracks/class_attendance?year_id=<?= (int)$yearId ?>&term=<?= (int)$term ?>">← ตารางเรียนตามวัน</a>
      </div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <?= e((string)$error) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="mt-4 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
        <?= e((string)$success) ?>
      </div>
    <?php endif; ?>

    <form class="mt-4 grid gap-4" method="post">
      <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>" />
      <input type="hidden" name="action" value="create_session" />
      <input type="hidden" id="selectedGroupId" name="selected_group_id" value="" />

      <div class="grid gap-3 md:grid-cols-3">
        <div>
          <label class="text-xs font-medium">ปีการศึกษา</label>
          <select id="attCreateYear" name="year_id" class="mt-1 w-full rounded-2xl border border-base-300 bg-base-100 px-3 py-2.5 text-sm outline-none focus:border-calm-500" onchange="(function(){var y=document.getElementById('attCreateYear'); var t=document.getElementById('attCreateTerm'); if(!y||!t) return; location.href='/tracks/class_attendance_create?year_id='+encodeURIComponent(y.value)+'&term='+encodeURIComponent(t.value)+'&subject_id='+encodeURIComponent(y.form.subject_id.value);})()">
            <?php foreach (($years ?? []) as $y): ?>
              <?php
                $id = (int)($y['id'] ?? 0);
                $label = (string)($y['title'] ?? '') . ' (' . (string)($y['year_be'] ?? '') . ')';
                $active = ((int)($y['is_active'] ?? 0) === 1) ? ' • active' : '';
              ?>
              <option value="<?= $id ?>" <?= $id === $yearId ? 'selected' : '' ?>><?= e($label . $active) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="text-xs font-medium">เทอม</label>
          <select id="attCreateTerm" name="term" class="mt-1 w-full rounded-2xl border border-base-300 bg-base-100 px-3 py-2.5 text-sm outline-none focus:border-calm-500" onchange="(function(){var y=document.getElementById('attCreateYear'); var t=document.getElementById('attCreateTerm'); if(!y||!t) return; location.href='/tracks/class_attendance_create?year_id='+encodeURIComponent(y.value)+'&term='+encodeURIComponent(t.value)+'&subject_id='+encodeURIComponent(y.form.subject_id.value);})()">
            <option value="1" <?= $term === 1 ? 'selected' : '' ?>>เทอม 1</option>
            <option value="2" <?= $term === 2 ? 'selected' : '' ?>>เทอม 2</option>
          </select>
        </div>

        <div>
          <label class="text-xs font-medium">วิชา</label>
          <select name="subject_id" class="mt-1 w-full rounded-2xl border border-base-300 bg-base-100 px-3 py-2.5 text-sm outline-none focus:border-calm-500" onchange="location.href='/tracks/class_attendance_create?year_id=<?= (int)$yearId ?>&term=<?= (int)$term ?>&subject_id='+encodeURIComponent(this.value)">
            <?php
              $grouped = [];
              foreach (($subjects ?? []) as $s) {
                  $grp = (string)($s['group_title'] ?? 'ไม่มีกลุ่ม');
                  $grouped[$grp][] = $s;
              }
              foreach ($grouped as $grpLabel => $items):
            ?>
              <optgroup label="<?= e($grpLabel) ?>">
                <?php foreach ($items as $s): ?>
                  <?php
                    $id = (int)($s['id'] ?? 0);
                    $code = (string)($s['subject_code'] ?? '');
                    $title = (string)($s['title'] ?? '');
                    $label = $code !== '' ? "[{$code}] {$title}" : $title;
                    $suffix = ((int)($s['is_active'] ?? 1) === 1) ? '' : ' (ปิด)';
                  ?>
                  <option value="<?= $id ?>" <?= $id === $subjectId ? 'selected' : '' ?>><?= e($label . $suffix) ?></option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="grid gap-3 md:grid-cols-2">
        <div>
          <label class="text-xs font-medium">วันที่เรียน <span class="text-base-content/50">(เพิ่มได้หลายวัน)</span></label>
          <div id="sessionDatesContainer" class="mt-1 grid gap-2">
            <div class="date-row flex items-center gap-2">
              <input type="text" class="thai-dp-text flex-1 rounded-2xl border border-base-300 bg-base-100 px-3 py-2.5 text-sm outline-none focus:border-calm-500" placeholder="เลือกวันที่..." data-hidden-id="dp_1" />
              <input type="hidden" id="dp_1" name="session_dates[]" value="" />
              <button type="button" class="remove-date rounded-xl border border-base-300 bg-base-100 px-3 py-2 text-sm text-red-600 hover:bg-red-50 hidden">✕</button>
            </div>
          </div>
          <button type="button" id="addDateBtn" class="mt-2 rounded-2xl border border-dashed border-calm-400 bg-calm-50 px-3 py-2 text-xs text-calm-700 hover:bg-calm-100">+ เพิ่มวันที่เรียน</button>
          <div class="mt-1 text-[11px] text-base-content/60">ถ้าวิชานี้เรียนหลายวัน เช่น วันที่ 10 กับ 17 ให้กด "+ เพิ่มวันที่" แล้วใส่ให้ครบ</div>
        </div>

        <div>
          <label class="text-xs font-medium">หมายเหตุ (ไม่บังคับ)</label>
          <input name="note" value="<?= e((string)($note ?? '')) ?>" placeholder="เช่น กลุ่มเช้า / กลุ่มบ่าย" class="mt-1 w-full rounded-2xl border border-base-300 bg-base-100 px-3 py-2.5 text-sm outline-none focus:border-calm-500" />
        </div>
      </div>

      <script>
      (function () {
        /* ─── Thai Date Picker ─── */
        var MONTHS_TH = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
                         'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
        var DAYS_TH = ['อา','จ','อ','พ','พฤ','ศ','ส'];
        var dpCounter = 1;
        var activePopup = null;

        function pad(n) { return n < 10 ? '0' + n : '' + n; }
        function fmtThai(d) { return d.getDate() + ' ' + MONTHS_TH[d.getMonth()] + ' ' + (d.getFullYear() + 543); }
        function toIso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
        function fromIso(s) {
          if (!s || !/^\d{4}-\d{2}-\d{2}$/.test(s)) return null;
          var p = s.split('-'), d = new Date(+p[0], +p[1] - 1, +p[2]);
          return isNaN(d.getTime()) ? null : d;
        }
        function daysInMonth(y, m) { return new Date(y, m + 1, 0).getDate(); }

        function buildCalendar(popup, state, hiddenEl, textEl) {
          var y = state.viewYear, m = state.viewMonth;
          var firstDay = new Date(y, m, 1).getDay();
          var days = daysInMonth(y, m);
          var selIso = hiddenEl.value;
          var todayIso = toIso(new Date());

          popup.innerHTML = '';

          /* header */
          var hdr = document.createElement('div');
          hdr.style.cssText = 'display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;';

          function navBtn(txt, cb) {
            var b = document.createElement('button');
            b.type = 'button'; b.textContent = txt;
            b.style.cssText = 'background:none;border:none;cursor:pointer;padding:4px 10px;border-radius:8px;font-size:13px;color:#291334;line-height:1;';
            b.onmouseover = function () { this.style.background = '#efeae6'; };
            b.onmouseout  = function () { this.style.background = 'none'; };
            b.addEventListener('click', function (e) { e.stopPropagation(); cb(); buildCalendar(popup, state, hiddenEl, textEl); });
            return b;
          }

          hdr.appendChild(navBtn('◀', function () { state.viewMonth--; if (state.viewMonth < 0) { state.viewMonth = 11; state.viewYear--; } }));
          var lbl = document.createElement('span');
          lbl.style.cssText = 'font-weight:700;font-size:13px;color:#291334;';
          lbl.textContent = MONTHS_TH[m] + ' ' + (y + 543);
          hdr.appendChild(lbl);
          hdr.appendChild(navBtn('▶', function () { state.viewMonth++; if (state.viewMonth > 11) { state.viewMonth = 0; state.viewYear++; } }));
          popup.appendChild(hdr);

          /* grid */
          var grid = document.createElement('div');
          grid.style.cssText = 'display:grid;grid-template-columns:repeat(7,1fr);gap:2px;';

          DAYS_TH.forEach(function (d) {
            var c = document.createElement('div');
            c.style.cssText = 'text-align:center;padding:4px 0;font-size:11px;font-weight:600;color:#291334;opacity:0.5;';
            c.textContent = d; grid.appendChild(c);
          });

          for (var i = 0; i < firstDay; i++) grid.appendChild(document.createElement('div'));

          for (var dd = 1; dd <= days; dd++) {
            (function (day) {
              var iso = y + '-' + pad(m + 1) + '-' + pad(day);
              var btn = document.createElement('button');
              btn.type = 'button'; btn.textContent = day;
              var base = 'text-align:center;padding:5px 2px;border:none;border-radius:8px;cursor:pointer;width:100%;font-size:13px;font-family:inherit;';
              if (iso === selIso)    btn.style.cssText = base + 'background:#2b7f79;color:white;font-weight:700;';
              else if (iso === todayIso) btn.style.cssText = base + 'background:#e6f3f2;color:#2b7f79;font-weight:700;';
              else {
                btn.style.cssText = base + 'background:none;color:#291334;';
                btn.onmouseover = function () { this.style.background = '#efeae6'; };
                btn.onmouseout  = function () { this.style.background = 'none'; };
              }
              btn.addEventListener('click', function (e) {
                e.stopPropagation();
                hiddenEl.value = iso;
                textEl.value = fmtThai(new Date(y, m, day));
                closePopup();
              });
              grid.appendChild(btn);
            })(dd);
          }
          popup.appendChild(grid);
        }

        function onDocClick(e) { if (activePopup && !activePopup.contains(e.target)) closePopup(); }

        function closePopup() {
          if (activePopup) { activePopup.remove(); activePopup = null; }
          document.removeEventListener('click', onDocClick);
        }

        function openPicker(textEl, hiddenEl) {
          closePopup();
          var ref = fromIso(hiddenEl.value) || new Date();
          var state = { viewYear: ref.getFullYear(), viewMonth: ref.getMonth() };
          var popup = document.createElement('div');
          popup.style.cssText = 'position:absolute;z-index:9999;background:white;border:1px solid rgba(0,0,0,0.1);border-radius:16px;box-shadow:0 8px 30px rgba(0,0,0,0.15);padding:12px;min-width:270px;';
          buildCalendar(popup, state, hiddenEl, textEl);
          document.body.appendChild(popup);
          var rect = textEl.getBoundingClientRect();
          popup.style.top  = (rect.bottom + (window.scrollY || window.pageYOffset) + 4) + 'px';
          popup.style.left = (rect.left  + (window.scrollX || window.pageXOffset)) + 'px';
          activePopup = popup;
          setTimeout(function () { document.addEventListener('click', onDocClick); }, 0);
        }

        function initPicker(textEl, hiddenEl) {
          if (textEl.dataset.dpInit) return;
          textEl.dataset.dpInit = '1';
          textEl.readOnly = true;
          textEl.style.cursor = 'pointer';
          if (hiddenEl.value) { var d = fromIso(hiddenEl.value); if (d) textEl.value = fmtThai(d); }
          textEl.addEventListener('click', function (e) { e.stopPropagation(); openPicker(textEl, hiddenEl); });
        }

        function initAll() {
          document.querySelectorAll('.thai-dp-text').forEach(function (el) {
            if (el.dataset.dpInit) return;
            var hid = document.getElementById(el.dataset.hiddenId || '');
            if (hid) initPicker(el, hid);
          });
        }

        /* ─── Date row manager ─── */
        var container = document.getElementById('sessionDatesContainer');
        var addBtn    = document.getElementById('addDateBtn');

        function makeRow() {
          dpCounter++;
          var id = 'dp_' + dpCounter;
          var row = document.createElement('div');
          row.className = 'date-row flex items-center gap-2';
          row.innerHTML =
            '<input type="text" class="thai-dp-text flex-1 rounded-2xl border border-base-300 bg-base-100 px-3 py-2.5 text-sm outline-none focus:border-calm-500" placeholder="เลือกวันที่..." data-hidden-id="' + id + '" />'
            + '<input type="hidden" id="' + id + '" name="session_dates[]" value="" />'
            + '<button type="button" class="remove-date rounded-xl border border-base-300 bg-base-100 px-3 py-2 text-sm text-red-600 hover:bg-red-50">✕</button>';
          return row;
        }

        function refreshRemove() {
          var rows = container.querySelectorAll('.date-row');
          rows.forEach(function (r) {
            var b = r.querySelector('.remove-date');
            if (b) b.classList.toggle('hidden', rows.length <= 1);
          });
        }

        if (addBtn) {
          addBtn.addEventListener('click', function () {
            var row = makeRow();
            container.appendChild(row);
            initAll();
            refreshRemove();
          });
        }

        container.addEventListener('click', function (e) {
          var btn = e.target.closest('.remove-date');
          if (!btn) return;
          btn.closest('.date-row').remove();
          refreshRemove();
        });

        initAll();
        refreshRemove();
      })();
      </script>

      <div class="grid gap-3 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-3xl border border-base-300 bg-base-100/70 p-3">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="text-xs font-semibold">👥 เลือกนักเรียน</div>
            <div class="flex flex-wrap items-center gap-2">
              <button type="button" class="rounded-2xl border border-base-300 bg-base-100 px-3 py-2 text-xs hover:bg-base-200" onclick="(function(){document.querySelectorAll('input[name=\'student_codes[]\']').forEach(function(el){el.checked=true;});})();">เลือกทั้งหมด</button>
              <button type="button" class="rounded-2xl border border-base-300 bg-base-100 px-3 py-2 text-xs hover:bg-base-200" onclick="(function(){document.querySelectorAll('input[name=\'student_codes[]\']').forEach(function(el){el.checked=false;});})();">เอาออกทั้งหมด</button>
              <div class="text-[11px] text-base-content/60">ติ๊กได้หลายคน • แสดงสูงสุด 800</div>
            </div>
          </div>

          <div class="mt-3 grid gap-3 md:grid-cols-6">
            <div class="md:col-span-2">
              <label class="text-xs font-medium">ชั้น</label>
              <select class="mt-1 w-full rounded-2xl border border-base-300 bg-base-100 px-3 py-2.5 text-sm outline-none focus:border-calm-500" onchange="location.href='<?= e((string)$basePath) ?>?<?= e((string)$baseQuery) ?>&class_level='+encodeURIComponent(this.value)">
                <?php foreach (($classLevelOptions ?? []) as $val => $label): ?>
                  <option value="<?= e((string)$val) ?>" <?= ((string)($filters['class_level'] ?? '') === (string)$val) ? 'selected' : '' ?>><?= e((string)$label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="md:col-span-2">
              <label class="text-xs font-medium">ห้อง</label>
              <input value="<?= e((string)($filters['room'] ?? '')) ?>" placeholder="เช่น 1" class="mt-1 w-full rounded-2xl border border-base-300 bg-base-100 px-3 py-2.5 text-sm outline-none focus:border-calm-500" onkeydown="if(event.key==='Enter'){event.preventDefault();location.href='<?= e((string)$basePath) ?>?<?= e((string)$baseQuery) ?>&room='+encodeURIComponent(this.value)}" />
            </div>

            <div class="md:col-span-2">
              <label class="text-xs font-medium">ค้นหา</label>
              <input value="<?= e((string)($filters['q'] ?? '')) ?>" placeholder="เลขประจำตัว / ชื่อ / นามสกุล" class="mt-1 w-full rounded-2xl border border-base-300 bg-base-100 px-3 py-2.5 text-sm outline-none focus:border-calm-500" onkeydown="if(event.key==='Enter'){event.preventDefault();location.href='<?= e((string)$basePath) ?>?<?= e((string)$baseQuery) ?>&q='+encodeURIComponent(this.value)}" />
            </div>
          </div>

          <div class="mt-3 max-h-[420px] overflow-auto rounded-2xl border border-base-300 bg-base-100">
            <ul class="divide-y divide-base-300">
              <?php foreach ($students as $st): ?>
                <?php
                  $code = (string)($st['student_code'] ?? '');
                  $name = (string)($st['first_name'] ?? '') . ' ' . (string)($st['last_name'] ?? '');
                  $meta = (string)($st['class_level'] ?? '') . '/' . (string)($st['class_room'] ?? '') . ' เลขที่ ' . (string)($st['number_in_room'] ?? '');
                ?>
                <li class="px-3 py-2 hover:bg-base-200">
                  <label class="flex items-start gap-3">
                    <input class="mt-1 h-4 w-4 rounded border-base-300" type="checkbox" name="student_codes[]" value="<?= e($code) ?>" />
                    <span class="block flex-1">
                      <span class="flex flex-wrap items-center gap-2">
                        <span class="font-medium"><?= e($name) ?></span>
                        <span class="rounded-full bg-pastel-sky/60 px-2.5 py-1 text-xs text-base-content/70 ring-1 ring-base-300"><?= e($code) ?></span>
                      </span>
                      <span class="mt-1 block text-xs text-base-content/60"><?= e($meta) ?></span>
                    </span>
                  </label>
                </li>
              <?php endforeach; ?>

              <?php if (empty($students)): ?>
                <li class="px-4 py-10 text-center text-sm text-base-content/70">ไม่พบรายชื่อนักเรียนตามตัวกรอง</li>
              <?php endif; ?>
            </ul>
          </div>
        </div>

        <div class="rounded-3xl border border-base-300 bg-base-200 p-4">
          <div class="text-sm font-semibold">📥 Import รหัสนักเรียน</div>
          <p class="mt-1 text-xs text-base-content/60">ใส่ 1 บรรทัดต่อ 1 คน (ใส่แค่รหัส หรือ ใส่ชื่อแล้วตามด้วยรหัสก็ได้)</p>

          <?php if (!empty($classGroups)): ?>
          <div class="mt-3">
            <label class="text-xs font-medium">📂 เลือกจากกลุ่มเรียนที่บันทึกไว้</label>
            <div class="mt-1 flex flex-wrap items-center gap-2">
              <select id="classGroupPicker" class="flex-1 min-w-0 rounded-2xl border border-base-300 bg-base-100 px-3 py-2.5 text-sm outline-none focus:border-calm-500">
                <option value="">— เลือกกลุ่ม —</option>
                <?php foreach ($classGroups as $cg): ?>
                  <?php
                    $cgcodes = class_group_parse_codes((string)($cg['student_codes'] ?? ''));
                    $cgcount = count($cgcodes);
                  ?>
                  <option value="<?= e((string)($cg['student_codes'] ?? '')) ?>" data-gid="<?= (int)$cg['id'] ?>">
                    <?= e((string)$cg['title']) ?> (<?= $cgcount ?> คน)
                  </option>
                <?php endforeach; ?>
              </select>
              <button type="button" id="appendGroupBtn" class="shrink-0 rounded-2xl border border-base-300 bg-base-100 px-4 py-2.5 text-sm hover:bg-base-200">
                ➕ เพิ่มต่อท้าย
              </button>
            </div>
            <p class="mt-1 text-[11px] text-base-content/60">เลือกกลุ่ม = แทนที่รหัสในกล่องอัตโนมัติ — «เพิ่มต่อท้าย» รวมเข้ากัน | <a class="underline text-calm-700" href="/tracks/class-groups">จัดการกลุ่มเรียน</a></p>
          </div>
          <?php else: ?>
          <p class="mt-2 text-xs text-base-content/50">ยังไม่มีกลุ่มเรียน — <a class="underline text-calm-700" href="/tracks/class-groups">สร้างกลุ่มเรียน</a> เพื่อเลือกได้ที่นี่</p>
          <?php endif; ?>

          <textarea
            id="studentCodesTextarea"
            name="student_codes_text"
            rows="10"
            placeholder="ตัวอย่าง:&#10;65001&#10;สมชาย ใจดี 65002&#10;65003"
            class="mt-3 w-full rounded-2xl border border-base-300 bg-base-100 px-3 py-2.5 text-sm outline-none focus:border-calm-500"
          ><?= e((string)($studentCodesText ?? '')) ?></textarea>

          <script>
          (function () {
            var picker    = document.getElementById('classGroupPicker');
            var appendBtn = document.getElementById('appendGroupBtn');
            var textarea  = document.getElementById('studentCodesTextarea');
            var groupIdInput = document.getElementById('selectedGroupId');
            if (!picker || !textarea) return;
            picker.addEventListener('change', function () {
              var raw = picker.value; if (!raw) return;
              textarea.value = raw; textarea.focus();
              if (groupIdInput) groupIdInput.value = picker.options[picker.selectedIndex].dataset.gid || '';
            });
            if (appendBtn) {
              appendBtn.addEventListener('click', function () {
                var raw = picker.value; if (!raw) return;
                var existing = textarea.value;
                textarea.value = existing === '' ? raw : (existing.trimEnd() + '\n' + raw);
                textarea.focus();
                // clear group association when appending multiple groups
                if (groupIdInput) groupIdInput.value = '';
              });
            }
          })();
          </script>

          <div class="mt-4">
            <button class="w-full rounded-2xl bg-neutral px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:opacity-90">✅ สร้างรอบเรียน</button>
          </div>

          <div class="mt-3 text-[11px] text-base-content/60">หมายเหตุ: ถ้าเลือก “ติ๊กชื่อ” และ “วางรหัส” พร้อมกัน ระบบจะรวมให้ แล้วตัดซ้ำอัตโนมัติ</div>
        </div>
      </div>
    </form>
  </section>
</div>
