<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meal plan</title>
    <link rel="icon" type="image/png" href="{{ asset('image/logo.png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root { font-family: 'Trebuchet MS', 'Segoe UI', sans-serif; color: #24332d; background: #eef2ee; }
        * { box-sizing: border-box; }
        body { margin: 0; background: linear-gradient(135deg, #eef2ee 0%, #f7f4ed 100%); }
        main { max-width: 1440px; margin: 0 auto; padding: 42px 28px 64px; }
        header { display: flex; justify-content: space-between; align-items: end; gap: 28px; margin-bottom: 28px; }
        h1 { color: #1c4738; font-family: Georgia, 'Times New Roman', serif; font-size: clamp(2.4rem, 5vw, 4.6rem); line-height: .95; margin: 0; font-weight: 400; letter-spacing: -.03em; }
        p { color: #68756d; margin: 10px 0 0; } form, .toolbar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .upload-panel { min-width: min(100%, 520px); }
        .panel { background: rgba(255, 253, 248, .9); border: 1px solid #d5ddd5; padding: 20px; border-radius: 10px; box-shadow: 0 12px 30px rgba(43, 65, 52, .07); }
        input, button { font: inherit; padding: 10px 12px; border: 1px solid #b9c5bc; border-radius: 5px; background: #fffdf8; }
        input:focus-visible, button:focus-visible { outline: 3px solid rgba(52, 117, 88, .25); outline-offset: 2px; }
        button { background: #246044; color: white; border-color: #246044; cursor: pointer; font-weight: 600; transition: background .2s, transform .2s; }
        button:hover:not(:disabled) { background: #174a34; transform: translateY(-1px); }
        button:disabled { cursor: not-allowed; opacity: .45; }
        #file-name { color: #68756d; font-size: .9rem; overflow-wrap: anywhere; }
        .toolbar { margin-bottom: 18px; } .toolbar label { color: #4e5e55; font-weight: 600; }
        .manual-entry-trigger { margin: 0 0 18px; }
        .manual-dialog { width: min(520px, calc(100% - 28px)); max-width: none; padding: 0; border: 1px solid #cbd6cc; border-radius: 10px; color: #24332d; background: #fffdf8; box-shadow: 0 24px 80px rgba(20, 42, 30, .28); }
        .manual-dialog::backdrop { background: rgba(22, 37, 29, .55); backdrop-filter: blur(2px); }
        .manual-dialog-content { padding: 24px; }
        .manual-dialog-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 20px; }
        .manual-dialog h2 { color: #1c4738; font-size: 1.3rem; margin: 0; }
        .manual-dialog-header p { margin: 5px 0 0; font-size: .9rem; }
        .manual-dialog-close { flex: 0 0 40px; width: 40px; height: 40px; padding: 0; color: #45564b; background: #edf3ee; border-color: #d5ddd5; font-size: 1.35rem; line-height: 1; }
        .manual-dialog form { display: grid; gap: 14px; }
        .manual-dialog label { display: grid; gap: 6px; color: #4e5e55; font-size: .88rem; font-weight: 600; }
        .manual-dialog input, .manual-dialog select { width: 100%; min-height: 44px; }
        .manual-dialog select { padding: 10px 12px; border: 1px solid #b9c5bc; border-radius: 5px; color: inherit; background: #fffdf8; font: inherit; }
        .manual-dialog-actions { display: flex; justify-content: flex-end; gap: 9px; margin-top: 6px; }
        .manual-dialog-cancel { color: #405148; background: #fffdf8; border-color: #b9c5bc; }
        .checkin-heading { display: flex; justify-content: space-between; align-items: end; gap: 20px; margin-bottom: 18px; }
        .checkin-heading h2 { color: #1c4738; font-size: 1.35rem; margin: 0; } .checkin-heading p { margin: 5px 0 0; }
        .checkin-closed-notice { padding: 10px 13px; margin: 0 0 14px; border: 1px solid #e5c47e; border-radius: 6px; color: #68430e; background: #fff5d9; font-size: .88rem; }
        .floor-filters { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 18px; }
        .floor-filter { min-width: 48px; padding: 10px 13px; background: #fffdf8; color: #246044; border-color: #b9cdbd; }
        .floor-filter.active, .floor-filter:hover { background: #246044; color: #fff; border-color: #246044; }
        .stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; margin-bottom: 18px; }
        .stat { background: #f2f6f2; border: 1px solid #dce6dd; border-radius: 7px; padding: 13px 15px; }
        .stat strong { display: block; color: #1c4738; font-size: 1.55rem; line-height: 1; } .stat span { display: block; color: #68756d; font-size: .82rem; margin-top: 5px; }
        .stat.no-breakfast-stat { background: #fbf1e6; border-color: #ead4ba; } .stat.no-breakfast-stat strong { color: #8a501e; }
        .stat.included-stat { background: #f2f6f2; }
        .included-stat > span { margin: 0 0 10px; font-weight: 600; }
        .included-breakdown { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
        .included-breakdown strong { font-size: 1.2rem; }
        .included-breakdown span { font-size: .7rem; line-height: 1.2; }
        .room-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 14px; }
        .room-card { border: 1px solid #d5ddd5; border-radius: 8px; overflow: hidden; background: #fffdf8; }
        .room-card.checked-room { border-color: #9ec4aa; } .room-header { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; background: #edf3ee; border-bottom: 1px solid #d5ddd5; }
        .room-number { color: #1d533b; font-size: 1.2rem; font-weight: 700; } .room-type { color: #68756d; font-size: .82rem; }
        .room-count { color: #52645a; font-size: .9rem; font-weight: 700; } .guest-list { padding: 4px 14px 8px; }
        .guest-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 13px 2px; border-bottom: 1px solid #e3e8e2; }
        .guest-row:last-child { border-bottom: 0; } .guest-row.checked { opacity: .7; } .guest-row.no-breakfast-row { background: #fffaf4; }
        .guest-row.birthday-row { padding-inline: 10px; border-radius: 6px; background: #fff5d9; box-shadow: inset 4px 0 #d78a2f; animation: birthday-pulse 2.8s ease-in-out infinite; }
        @keyframes birthday-pulse { 50% { box-shadow: inset 4px 0 #d78a2f, 0 0 0 2px rgba(215, 138, 47, .16); } }
        .birthday-notice { padding: 13px 16px; margin: 0 0 16px; border: 1px solid #e5c47e; border-radius: 6px; color: #68430e; background: #fff5d9; }
        .birthday-notice strong { color: #51330a; }
        .birthday-link { color: #68430e; font-weight: 700; text-decoration-thickness: 1px; text-underline-offset: 3px; }
        .birthday-link:focus-visible { outline: 3px solid #d78a2f; outline-offset: 3px; }
        .guest-row[id] { scroll-margin-top: 18px; }
        .guest-row:target { outline: 3px solid #d78a2f; outline-offset: 2px; }
        .birthday-badge { display: inline-block; margin-left: 7px; padding: 3px 7px; border-radius: 4px; color: #68430e; background: #f2d18a; font-size: .72rem; font-weight: 700; vertical-align: middle; }
        @media (prefers-reduced-motion: reduce) { .guest-row.birthday-row { animation: none; } }
        .guest-name { color: #273a30; font-size: 1rem; font-weight: 600; } .guest-meta { color: #758179; font-size: .78rem; line-height: 1.45; margin-top: 4px; }
        .guest-nationality { color: #246044; font-weight: 600; }
        .checkin-button { min-width: 112px; min-height: 44px; padding: 9px 12px; font-size: .85rem; }
        .checkin-button.checked { background: #dceee2; color: #246044; border-color: #9ec4aa; cursor: default; }
        .checkin-button.no-breakfast { background: #a8662b; border-color: #a8662b; }
        .checkin-button.no-breakfast.checked { background: #f6e5d3; color: #8a501e; border-color: #d9b58e; }
        .no-breakfast-badge { display: inline-block; color: #8a501e; background: #f6e5d3; border: 1px solid #e3c49f; border-radius: 999px; padding: 9px 12px; font-size: .78rem; font-weight: 700; white-space: nowrap; }
        .empty-state { padding: 28px 0 10px; text-align: center; } .filter-empty { display: none; }
        .notice { padding: 13px 16px; background: #dceee2; margin-bottom: 18px; border: 1px solid #b9d7c3; border-radius: 6px; } .error { color: #9d322d; background: #fbe8e5; border-color: #edc0b9; }
        @media (max-width: 900px) {
            main { padding: 22px 16px 38px; }
            header { flex-direction: column; align-items: stretch; gap: 16px; }
            .upload-panel { display: none !important; }
            .toolbar form { width: 100%; flex-direction: row; align-items: center; }
            .toolbar label { white-space: nowrap; }
            .toolbar input { flex: 1 1 auto; min-width: 0; }
            .toolbar button { width: auto; flex: 0 0 auto; }
            .checkin-heading { flex-direction: column; align-items: flex-start; }
            .stats { grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 8px; }
            .room-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        }
        @media (max-width: 480px) {
            body { min-width: 320px; }
            main { padding: 18px 12px 32px; }
            h1 { font-size: clamp(2rem, 10vw, 2.8rem); }
            .panel { padding: 14px; border-radius: 8px; }
            .upload-panel { display: none; }
            .toolbar { gap: 8px; }
            .checkin-heading { gap: 8px; }
            .checkin-heading h2 { font-size: 1.2rem; }
            .floor-filters { gap: 6px; }
            .floor-filter { flex: 1 1 calc(33.333% - 6px); min-width: 0; }
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .included-stat { grid-column: span 2; }
            .stat { padding: 11px 9px; }
            .stat strong { font-size: 1.2rem; }
            .stat span { font-size: .72rem; }
            .room-grid { grid-template-columns: 1fr; }
            .room-header { padding: 12px 12px; }
            .room-number { font-size: 1.05rem; }
            .room-count { font-size: .8rem; }
            .guest-row { display: flex; flex-direction: column; align-items: stretch; gap: 10px; padding: 12px 2px; }
            .guest-name { font-size: .95rem; }
            .group-meta { font-size: .74rem; }
            .guest-meta { font-size: .74rem; }
            .checkin-button, .no-breakfast-badge { width: 100%; min-width: 0; }
            .no-breakfast-badge { text-align: center; }
        }
    </style>
</head>
<body>
<main>
    <header>
        <div><h1>Meal plan</h1>@if(auth()->user()->isFo())<p>Nhập danh sách khách từ file XML xuất từ PMS.</p>@endif</div>
        @if(auth()->user()->isFo())
        <div class="panel upload-panel"><form method="POST" action="{{ route('meal-plans.store') }}" enctype="multipart/form-data">
            @csrf <input id="report" type="file" name="report" accept=".xml,.pdf,application/xml,application/pdf" required><span id="file-name">Chưa chọn file</span><button id="upload-button" type="submit" disabled>Nhập XML</button>
        </form></div>
        @endif
        <div class="panel" style="padding: 12px 16px; min-width: 220px;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                <div>
                    <div style="font-size: .82rem; color: #68756d;">Nhân viên</div>
                    <strong style="display:block; color:#1c4738; margin-top: 2px;">{{ auth()->user()->name }}</strong>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" style="padding: 8px 12px; min-height: auto;">Đăng xuất</button>
                </form>
            </div>
        </div>
    </header>
    @if(session('success')) <div class="notice">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="notice error">{{ $errors->first() }}</div> @endif
    <div class="toolbar"><form method="GET"><label for="date">Ngày báo cáo</label><input id="date" type="date" name="date" value="{{ $date }}"><button type="submit">Xem</button></form></div>
    @if(auth()->user()->canManuallyAddMealPlanGuest())
    <button type="button" class="manual-entry-trigger" id="open-manual-entry">+ Thêm khách buffet</button>
    <dialog class="manual-dialog" id="manual-entry-dialog" aria-labelledby="manual-entry-title">
        <div class="manual-dialog-content">
            <div class="manual-dialog-header">
                <div><h2 id="manual-entry-title">Thêm khách buffet</h2><p>Khách sẽ được thêm vào danh sách ngày đã chọn. FB thực hiện check-in khi khách đến.</p></div>
                <button type="button" class="manual-dialog-close" id="close-manual-entry" aria-label="Đóng">&times;</button>
            </div>
            <form method="POST" action="{{ route('meal-plans.manual-store') }}">
                @csrf
                <label for="manual-report-date">Ngày<input id="manual-report-date" type="date" name="report_date" value="{{ old('report_date', $date) }}" required></label>
                <label for="manual-room">Số phòng<input id="manual-room" type="text" name="room" value="{{ old('room') }}" maxlength="20" required></label>
                <label for="manual-guest-name">Họ tên<input id="manual-guest-name" type="text" name="guest_name" value="{{ old('guest_name') }}" maxlength="255" required></label>
                <label for="manual-nationality">Quốc tịch<select id="manual-nationality" name="nationality" required><option value="" disabled @selected(old('nationality') === null)>Chọn quốc tịch</option>@foreach($manualNationalities as $nationality)<option value="{{ $nationality }}" @selected(old('nationality') === $nationality)>{{ $nationality }}</option>@endforeach</select></label>
                <label for="manual-is-child">Đối tượng<select id="manual-is-child" name="is_child"><option value="0" @selected(old('is_child', '0') === '0')>Người lớn</option><option value="1" @selected(old('is_child') === '1')>Trẻ em</option></select></label>
                <div class="manual-dialog-actions"><button type="button" class="manual-dialog-cancel" id="cancel-manual-entry">Hủy</button><button type="submit">Thêm khách</button></div>
            </form>
        </div>
    </dialog>
    @endif
    <div class="panel">
        @php($noBreakfastGuests = $guests->filter(fn ($guest) => strtolower(trim((string) $guest->meal_plan)) === 'no breakfast'))
        @php($buffetGuests = $guests->reject(fn ($guest) => strtolower(trim((string) $guest->meal_plan)) === 'no breakfast'))
        @php($adultPax = $buffetGuests->reject(fn ($guest) => $guest->is_child)->count())
        @php($childCount = $buffetGuests->filter(fn ($guest) => $guest->is_child)->count())
        @php($birthdayKey = substr($date, 5))
        @php($birthdayGuests = $guests->filter(fn ($guest) => $guest->birthday && $guest->birthday->format('m-d') === $birthdayKey))
        @php($checkedInCount = $buffetGuests->whereNotNull('buffet_checked_in_at')->count())
        @php($availableFloors = collect(['1', '2', '3', '4', '5', '6', '8', '9'])->filter(fn ($floor) => $guests->contains(fn ($guest) => str_starts_with((string) $guest->room, $floor))) )
        @unless($canCheckIn)<div class="checkin-closed-notice" role="status">Check-in buffet chỉ mở từ 05:00 đến 12:00 (giờ Việt Nam).</div>@endunless
        <div class="floor-filters" aria-label="Lọc theo tầng"><button type="button" class="floor-filter active" data-floor-filter="all">Tất cả</button>@foreach($availableFloors as $floor)<button type="button" class="floor-filter" data-floor-filter="{{ $floor }}">{{ $floor }}</button>@endforeach</div>
        <div class="stats">
            <div class="stat"><strong data-stat="total-guests">{{ $guests->count() }}</strong><span>Tổng khách</span></div>
            <div class="stat included-stat"><span>Bao gồm</span><div class="included-breakdown">
                <div><strong data-stat="no-breakfast">{{ $noBreakfastGuests->count() }}</strong><span>No breakfast</span></div>
                <div><strong data-stat="pax">{{ $adultPax }}</strong><span>Pax</span></div>
                <div><strong data-stat="child">{{ $childCount }}</strong><span>Child</span></div>
            </div></div>
            <div class="stat"><strong id="checked-in-count">{{ $checkedInCount }}</strong><span>Đã check-in buffet</span></div>
            <div class="stat"><strong id="remaining-count">{{ $buffetGuests->count() - $checkedInCount }}</strong><span>Còn lại buffet</span></div>
            <div class="stat birthday-stat"><strong>{{ $birthdayGuests->count() }}</strong><span>Sinh nhật hôm nay</span></div>
        </div>
        @if($birthdayGuests->isNotEmpty())
        <div class="birthday-notice" role="status"><strong>Sinh nhật hôm nay:</strong> @foreach($birthdayGuests as $birthdayGuest)<a class="birthday-link" href="#guest-{{ $birthdayGuest->id }}">{{ $birthdayGuest->guest_name }} · Phòng {{ $birthdayGuest->room }} · {{ $birthdayGuest->birthday->format('d/m/Y') }}</a>@if(!$loop->last), @endif @endforeach</div>
        @endif
        @if($guests->isNotEmpty())
        <div class="room-grid">
            @foreach($guests->groupBy('room') as $room => $roomGuests)
            @php($roomBuffetGuests = $roomGuests->reject(fn ($guest) => strtolower(trim((string) $guest->meal_plan)) === 'no breakfast'))
            @php($roomNoBreakfastGuests = $roomGuests->filter(fn ($guest) => strtolower(trim((string) $guest->meal_plan)) === 'no breakfast'))
            <section class="room-card {{ $roomBuffetGuests->every(fn ($guest) => $guest->buffet_checked_in_at) ? 'checked-room' : '' }}" data-room-card data-floor="{{ substr((string) $room, 0, 1) }}">
                <div class="room-header"><div><div class="room-number">Phòng {{ $room }}</div><div class="room-type">{{ $roomGuests->first()->room_type }}</div></div><span class="room-count" data-room-count>{{ $roomBuffetGuests->whereNotNull('buffet_checked_in_at')->count() }}/{{ $roomBuffetGuests->count() }} @if($roomNoBreakfastGuests->isNotEmpty()) · {{ $roomNoBreakfastGuests->count() }} no breakfast @endif</span></div>
                <div class="guest-list">
                    @foreach($roomGuests as $guest)
                    @php($isNoBreakfast = strtolower(trim((string) $guest->meal_plan)) === 'no breakfast')
                    @php($isCheckedIn = (bool) $guest->buffet_checked_in_at)
                    @php($isBirthday = $guest->birthday && $guest->birthday->format('m-d') === $birthdayKey)
                    <div id="guest-{{ $guest->id }}" tabindex="-1" class="guest-row {{ $isCheckedIn ? 'checked' : '' }} {{ $isNoBreakfast ? 'no-breakfast-row' : '' }} {{ $isBirthday ? 'birthday-row' : '' }}" data-birthday="{{ $isBirthday ? 'true' : 'false' }}" data-guest-row="{{ $guest->id }}">
                        <div><div class="guest-name">{{ $guest->guest_name }}@if($isBirthday)<span class="birthday-badge">Sinh nhật</span>@endif</div><div class="guest-meta"><span class="guest-nationality">{{ $guest->nationality ?: 'Chưa rõ quốc tịch' }}</span> · {{ $guest->is_child ? 'Trẻ em' : 'Người lớn' }} · {{ $guest->meal_plan ?: 'Không có meal plan' }}@if($isBirthday) · Ngày sinh: {{ $guest->birthday->format('d/m/Y') }}@endif</div></div>
                        @if($isNoBreakfast)
                        <span class="no-breakfast-badge">No breakfast</span>
                        @else
                        <button type="button" class="checkin-button {{ $isCheckedIn ? 'checked' : '' }}" data-check-in-url="{{ route('meal-plans.check-in', $guest) }}" data-check-in-status="checked_in" {{ $isCheckedIn || !$canCheckIn ? 'disabled' : '' }}>{{ $isCheckedIn ? 'Đã check-in' : ($canCheckIn ? 'Check-in' : 'Ngoài giờ') }}</button>
                        @endif
                    </div>
                    @endforeach
                </div>
            </section>
            @endforeach
        </div>
        <p class="empty-state filter-empty" data-filter-empty>Không có phòng ở tầng này.</p>
        @else <p class="empty-state">Chưa có dữ liệu khách cho ngày này.</p> @endif
    </div>
</main>
<script>
    history.scrollRestoration = 'manual';

    const resetScrollToTop = () => {
        window.scrollTo(0, 0);
    };

    window.addEventListener('load', resetScrollToTop);
    window.addEventListener('pageshow', resetScrollToTop);

    const manualEntryDialog = document.getElementById('manual-entry-dialog');
    if (manualEntryDialog) {
        document.getElementById('open-manual-entry').addEventListener('click', () => manualEntryDialog.showModal());
        document.getElementById('close-manual-entry').addEventListener('click', () => manualEntryDialog.close());
        document.getElementById('cancel-manual-entry').addEventListener('click', () => manualEntryDialog.close());
        manualEntryDialog.addEventListener('click', (event) => {
            if (event.target === manualEntryDialog) manualEntryDialog.close();
        });
        @if($errors->hasAny(['report_date', 'room', 'guest_name', 'nationality']))
        manualEntryDialog.showModal();
        @endif
    }

    const pdfInput = document.getElementById('report');
    const fileName = document.getElementById('file-name');
    const uploadButton = document.getElementById('upload-button');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    if (pdfInput) {
        pdfInput.addEventListener('change', () => {
            const file = pdfInput.files[0];
            fileName.textContent = file ? file.name : 'Chưa chọn file';
            uploadButton.disabled = !file;
        });
    }

    const floorFilters = document.querySelectorAll('[data-floor-filter]');
    const roomCards = document.querySelectorAll('[data-room-card]');
    const filterEmpty = document.querySelector('[data-filter-empty]');
    floorFilters.forEach((filter) => {
        filter.addEventListener('click', () => {
            const selectedFloor = filter.dataset.floorFilter;
            let visibleRooms = 0;
            floorFilters.forEach((item) => item.classList.toggle('active', item === filter));
            roomCards.forEach((roomCard) => {
                const isVisible = selectedFloor === 'all' || roomCard.dataset.floor === selectedFloor;
                roomCard.style.display = isVisible ? '' : 'none';
                if (isVisible) visibleRooms += 1;
            });
            if (filterEmpty) filterEmpty.style.display = visibleRooms === 0 ? 'block' : 'none';
        });
    });

    document.querySelectorAll('[data-check-in-url]').forEach((button) => {
        button.addEventListener('click', async () => {
            button.disabled = true;
            try {
                const response = await fetch(button.dataset.checkInUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                    body: new URLSearchParams({ status: button.dataset.checkInStatus }),
                });
                if (!response.ok) throw new Error('Check-in failed');
                const result = await response.json();
                button.textContent = result.status === 'no_breakfast' ? 'No breakfast' : 'Đã check-in';
                button.classList.add('checked');
                button.closest('.guest-row').classList.add('checked');
                document.getElementById('checked-in-count').textContent = Number(document.getElementById('checked-in-count').textContent) + 1;
                document.getElementById('remaining-count').textContent = Number(document.getElementById('remaining-count').textContent) - 1;
                const roomCard = button.closest('[data-room-card]');
                const roomCount = roomCard.querySelector('[data-room-count]');
                const countMatch = roomCount.textContent.match(/^(\d+)\/(\d+)/);
                const checked = Number(countMatch[1]);
                const total = Number(countMatch[2]);
                roomCount.textContent = roomCount.textContent.replace(/^\d+\/\d+/, `${checked + 1}/${total}`);
                if (checked + 1 === total) roomCard.classList.add('checked-room');
            } catch (error) {
                button.disabled = false;
                alert('Không thể ghi nhận check-in. Vui lòng thử lại.');
            }
        });
    });
</script>
</body>
</html>
