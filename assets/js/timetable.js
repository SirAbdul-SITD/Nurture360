/* assets/js/timetable.js */
(function(){
  const $ = (s, r=document) => r.querySelector(s);
  const $$ = (s, r=document) => Array.from(r.querySelectorAll(s));

  function toggleFields() {
    const type = $('#tt_type').value;
    $$('[data-show-if]').forEach(el => {
      const cond = el.getAttribute('data-show-if');
      const [k,v] = cond.split('=');
      el.style.display = (k==='type' && v===type) ? '' : 'none';
    });
  }

  function params() {
    return {
      type: $('#tt_type').value,
      class_id: $('#tt_class_id').value,
      subject_id: $('#tt_subject_id').value,
      teacher_id: $('#tt_teacher_id').value,
      academic_year: $('#tt_year').value,
      day_of_week: $('#tt_day').value,
      date: $('#tt_date').value,
      start_time: $('#tt_start').value,
      end_time: $('#tt_end').value,
      room_number: $('#tt_room').value,
      title: $('#tt_title').value,
    };
  }

  function validate(p) {
    if (!p.class_id || !p.subject_id || !p.teacher_id) return 'Please select class, subject and teacher';
    if (p.type==='class') {
      if (!p.academic_year) return 'Academic year is required';
      if (!p.day_of_week) return 'Day of week is required';
      if (!p.start_time || !p.end_time) return 'Start and End times are required';
    } else {
      if (!p.date) return 'Exam date is required';
      if (!p.start_time || !p.end_time) return 'Start and End times are required';
    }
    return '';
  }

  async function loadItems() {
    const type = $('#tt_type').value;
    const classId = $('#tt_class_id').value;
    if (!classId) { if (window.showNotification) showNotification('Select a class','warning'); return; }
    const ay = $('#tt_year').value;
    const url = `../api/timetable_list.php?type=${encodeURIComponent(type)}&class_id=${encodeURIComponent(classId)}&academic_year=${encodeURIComponent(ay)}`;
    const res = await fetch(url);
    const data = await res.json();
    if (!data.success) { if (window.showNotification) showNotification(data.message||'Load failed','error'); return; }
    renderCards(type, data.items || []);
    renderPrint(type, data.items || []);
    updateTitles();
  }

  function renderCards(type, items) {
    const container = $('#tt_cards');
    container.innerHTML = '';
    if (!items.length) {
      container.innerHTML = `<div class="no-data"><i class="fas fa-calendar-xmark"></i><p>No timetable items yet</p></div>`;
      return;
    }
    items.forEach(it => {
      const title = it.subject_name || '';
      const el = document.createElement('div');
      el.className = 'teacher-card';
      el.innerHTML = `
        <div class="teacher-card-body">
          <div class="teacher-name">${escapeHtml(title)}</div>
        </div>
        <div class="teacher-card-actions">
          <button class="btn" title="Delete" data-id="${it.id}" data-type="${type}"><i class="fas fa-trash"></i></button>
        </div>
      `;
      el.querySelector('button[title="Delete"]').addEventListener('click', () => deleteItem(it.id, type));
      container.appendChild(el);
    });
  }

  function getSelectedClassName(){
    const sel = $('#tt_class_id');
    if (!sel) return '';
    const opt = sel.options[sel.selectedIndex];
    return opt ? opt.textContent.trim() : '';
  }

  function updateTitles(){
    const className = getSelectedClassName();
    const type = $('#tt_type').value;
    const ay = ($('#tt_year') && $('#tt_year').value) ? $('#tt_year').value : '';
    const suffix = type === 'class' ? 'Class Timetable' : 'Exam Timetable';
    const span = $('#tt_items_title');
    if (span) span.textContent = className ? `— ${className} ${suffix}${ay ? ' ('+ay+')' : ''}` : '';
  }

  function renderPrint(type, items) {
    const area = $('#printArea');
    if (!items.length) { area.innerHTML = '<div class="no-data">No items to print</div>'; return; }
    const className = getSelectedClassName();
    const ay = ($('#tt_year') && $('#tt_year').value) ? $('#tt_year').value : '';
    const appName = area.getAttribute('data-app-name') || '';
    const appAddress = area.getAttribute('data-app-address') || '';
    if (type==='class') {
      // Build unique time slots across all items
      const slotKey = it => `${it.start_time||''}-${it.end_time||''}`;
      const slotsSet = new Set();
      items.forEach(it => { if (it.start_time && it.end_time) slotsSet.add(slotKey(it)); });
      const slots = Array.from(slotsSet).sort((a,b)=>{
        const [as] = a.split('-'); const [bs] = b.split('-');
        return as < bs ? -1 : 1;
      });
      const days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
      // Map: day -> slot -> subject
      const map = {};
      items.forEach(it => {
        const d = (it.day_of_week||'').toLowerCase();
        const k = slotKey(it);
        if (!map[d]) map[d] = {};
        map[d][k] = it.subject_name || '';
      });
      const headerRow = slots.map(s => `<th>${s.replace(':00','').replace(':00','')}</th>`).join('');
      const bodyRows = days.filter(d=>Object.keys(map).length ? true : true).map(d => {
        const cells = slots.map(s => `<td>${escapeHtml((map[d] && map[d][s]) || '')}</td>`).join('');
        return `<tr><th class="tt-day">${cap(d.slice(0,3))}</th>${cells}</tr>`;
      }).join('');
      area.innerHTML = `
        <div class="print-brand">
          <div class="brand-left">
            <div class="brand-name">${escapeHtml(appName)}</div>
            ${appAddress ? `<div class="brand-address">${escapeHtml(appAddress)}</div>` : ''}
          </div>
          <div class="brand-title">Timetable</div>
          <div class="brand-right">${escapeHtml(className || '')}</div>
        </div>
        <div class="print-header">
          <h2>${escapeHtml(className)} Class Timetable${ay ? ' ('+escapeHtml(ay)+')' : ''}</h2>
        </div>
        <table class="tt-table tt-matrix">
          <thead>
            <tr>
              <th class="tt-day-head">&nbsp;</th>
              ${headerRow}
            </tr>
          </thead>
          <tbody>
            ${bodyRows}
          </tbody>
        </table>
      `;
    } else {
      // Exam timetable: Date, Subject/Title, Time, Teacher, Room
      const rows = items.slice().sort((a,b) => (a.date < b.date ? -1 : a.date > b.date ? 1 : (a.start_time < b.start_time ? -1 : 1))).map(it => `
        <tr>
          <td>${it.date||''}</td>
          <td>${escapeHtml(it.title || it.subject_name || '')}</td>
          <td>${it.start_time||''} - ${it.end_time||''}</td>
          <td>${escapeHtml(it.teacher_name||'')}</td>
          <td>${escapeHtml(it.room_number||'')}</td>
        </tr>
      `).join('');
      area.innerHTML = `
        <div class="print-brand">
          <div class="brand-left">
            <div class="brand-name">${escapeHtml(appName)}</div>
            ${appAddress ? `<div class="brand-address">${escapeHtml(appAddress)}</div>` : ''}
          </div>
          <div class="brand-title">Timetable</div>
          <div class="brand-right">${escapeHtml(className || '')}</div>
        </div>
        <div class="print-header">
          <h2>${escapeHtml(className)} Exam Timetable${ay ? ' ('+escapeHtml(ay)+')' : ''}</h2>
        </div>
        <table class="tt-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Subject</th>
              <th>Time</th>
              <th>Teacher</th>
              <th>Room</th>
            </tr>
          </thead>
          <tbody>
            ${rows}
          </tbody>
        </table>
      `;
    }
  }

  async function addItem(){
    const p = params();
    const msg = validate(p);
    if (msg) { if (window.showNotification) showNotification(msg,'warning'); return; }
    const fd = new FormData();
    Object.entries(p).forEach(([k,v]) => v!==undefined && v!==null && fd.append(k,v));
    const res = await fetch('../api/timetable_save.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (!data.success) { if (window.showNotification) showNotification(data.message||'Save failed','error'); return; }
    if (window.showNotification) showNotification('Added to timetable','success');
    await loadItems();
  }

  async function deleteItem(id, type){
    if (!confirm('Delete this item?')) return;
    const fd = new FormData(); fd.append('id', id); fd.append('type', type);
    const res = await fetch('../api/timetable_delete.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (!data.success) { if (window.showNotification) showNotification(data.message||'Delete failed','error'); return; }
    if (window.showNotification) showNotification('Deleted','success');
    await loadItems();
  }

  function cap(s){ return s ? s.charAt(0).toUpperCase()+s.slice(1) : s; }
  function escapeHtml(s){ return (s||'').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }

  function setupPrint(){
    const style = document.createElement('style');
    style.id = 'tt-print-style';
    style.textContent = `
      .print-area { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
      .print-header { text-align: center; margin: 8px 0 12px; }
      .print-brand { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; margin-bottom: 6px; }
      .print-brand .brand-left { text-align: left; }
      .print-brand .brand-title { text-align: center; font-size: 20px; font-weight: 700; }
      .print-brand .brand-right { text-align: right; font-weight: 600; }
      .brand-name { font-size: 16px; font-weight: 700; }
      .brand-address { font-size: 12px; }
      .tt-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
      .tt-table th, .tt-table td { border: 1px solid #000; padding: 6px 8px; text-align: center; vertical-align: middle; }
      .tt-table th { font-weight: 700; }
      .tt-matrix .tt-day-head { width: 64px; }
      .tt-matrix .tt-day { width: 64px; }

      @media print {
        body { background: #fff !important; }
        header, .sidebar, .page-header, .content-card > .card-header, .content-card:nth-of-type(1), .content-card:nth-of-type(2) { display: none !important; }
        .print-area { display: block !important; }
        .content-card { box-shadow: none !important; border: none !important; }
        .content-card .card-content { padding: 0 !important; }
      }
    `;
    document.head.appendChild(style);
  }

  function doPrint(){
    window.print();
  }

  document.addEventListener('DOMContentLoaded', function(){
    toggleFields();
    setupPrint();
    $('#tt_type').addEventListener('change', toggleFields);
    $('#tt_type').addEventListener('change', updateTitles);
    $('#tt_class_id').addEventListener('change', updateTitles);
    if ($('#tt_year')) $('#tt_year').addEventListener('input', updateTitles);
    $('#loadBtn').addEventListener('click', loadItems);
    $('#addItemBtn').addEventListener('click', addItem);
    $('#printBtn').addEventListener('click', doPrint);
    $('#openPrintBtn').addEventListener('click', doPrint);
    updateTitles();
  });
})();
