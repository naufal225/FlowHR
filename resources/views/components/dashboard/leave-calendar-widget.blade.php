@props([
    'approvedByDate' => [],
    'holidayDates' => [],
    'holidaysByDate' => [],
    'title' => 'Employee Leave Calendar',
    'helperText' => 'Klik tanggal untuk melihat daftar karyawan yang cuti.',
    'emptyMessage' => 'Tidak ada leave dan tidak ada holiday pada tanggal ini.',
])

<article data-leave-calendar-widget data-empty-message="{{ $emptyMessage }}" class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-2xl">
    <script type="application/json" data-approved-by-date>@json($approvedByDate)</script>
    <script type="application/json" data-holiday-dates>@json(array_values($holidayDates))</script>
    <script type="application/json" data-holidays-by-date>@json($holidaysByDate)</script>

    <header class="px-5 py-4 border-b border-gray-100 bg-gray-50">
        <h3 class="text-sm font-semibold text-gray-800">{{ $title }}</h3>
    </header>

    <div class="p-5">
        <div class="flex items-center justify-between mb-4">
            <button data-prev-month type="button" aria-label="Previous month"
                class="flex items-center justify-center w-8 h-8 text-gray-500 transition rounded-lg hover:bg-gray-100">
                <i class="text-xs fas fa-chevron-left"></i>
            </button>
            <h2 data-month-year class="text-sm font-semibold text-gray-800"></h2>
            <button data-next-month type="button" aria-label="Next month"
                class="flex items-center justify-center w-8 h-8 text-gray-500 transition rounded-lg hover:bg-gray-100">
                <i class="text-xs fas fa-chevron-right"></i>
            </button>
        </div>

        <div class="grid grid-cols-7 mb-2">
            @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                <div class="py-2 text-xs font-medium text-center text-gray-400">{{ $day }}</div>
            @endforeach
        </div>

        <div data-dates-grid class="grid grid-cols-7 gap-1"></div>

        <p class="mt-3 text-xs text-center text-gray-500">{{ $helperText }}</p>

        <div class="flex items-center justify-center gap-4 pt-3 mt-4 border-t border-gray-100">
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-2 h-2 bg-blue-600 rounded-full"></span>
                <span class="text-xs text-gray-500">Approved Leave</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-2 h-2 bg-red-500 rounded-full"></span>
                <span class="text-xs text-gray-500">Holiday</span>
            </div>
        </div>
    </div>

    <div data-modal
        class="fixed inset-0 z-50 items-center justify-center hidden bg-black/40 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-hidden="true">
        <div data-modal-content
            class="bg-white rounded-2xl mx-4 w-full max-w-lg max-h-[85vh] overflow-hidden flex flex-col shadow-2xl transform transition-all duration-200 scale-95 opacity-0"
            role="document">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0 bg-gray-50">
                <div>
                    <h2 class="text-sm font-semibold text-gray-800">Detail Tanggal</h2>
                    <span data-selected-date-label class="block mt-0.5 text-xs text-gray-500"></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <button data-prev-page type="button" aria-label="Previous page"
                        class="flex items-center justify-center text-gray-500 transition rounded-lg w-7 h-7 hover:bg-gray-200 disabled:opacity-30 disabled:cursor-not-allowed">
                        <i class="text-xs fas fa-chevron-left"></i>
                    </button>
                    <button data-next-page type="button" aria-label="Next page"
                        class="flex items-center justify-center text-gray-500 transition rounded-lg w-7 h-7 hover:bg-gray-200 disabled:opacity-30 disabled:cursor-not-allowed">
                        <i class="text-xs fas fa-chevron-right"></i>
                    </button>
                    <button data-close-modal type="button" aria-label="Close modal"
                        class="flex items-center justify-center ml-1 text-gray-400 transition rounded-lg w-7 h-7 hover:bg-gray-200 hover:text-gray-600">
                        <i class="text-xs fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div data-pagination-meta class="hidden px-6 py-2 bg-white border-b border-gray-100">
                <span data-current-page-info class="text-xs text-gray-400">Page 1 of 1</span>
            </div>

            <div class="flex-1 p-5 overflow-y-auto space-y-4 bg-gray-50/50">
                <section data-holiday-section class="hidden p-4 bg-white border border-red-100 rounded-xl">
                    <h3 class="mb-3 text-xs font-semibold tracking-wide text-red-600 uppercase">Holiday</h3>
                    <div data-holiday-list class="space-y-2"></div>
                </section>

                <section data-leave-section class="hidden p-4 bg-white border border-blue-100 rounded-xl">
                    <h3 class="mb-3 text-xs font-semibold tracking-wide text-blue-600 uppercase">Approved Leave</h3>
                    <div class="overflow-hidden">
                        <div data-modal-pages class="flex transition-transform duration-300 ease-in-out"></div>
                    </div>
                </section>

                <div data-empty-state class="hidden py-10 text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full">
                        <i class="text-xl text-gray-300 fas fa-calendar-times"></i>
                    </div>
                    <p data-empty-text class="text-sm text-gray-500">{{ $emptyMessage }}</p>
                </div>
            </div>
        </div>
    </div>
</article>

@once
    @push('scripts')
        <script>
            (() => {
                const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'
                ];

                const parseJson = (content, fallback) => {
                    try {
                        return JSON.parse(content || '');
                    } catch (error) {
                        return fallback;
                    }
                };

                const escapeHtml = (value) => String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');

                const formatDisplayDate = (dateStr) => {
                    const date = new Date(dateStr + 'T00:00:00');
                    return date.toLocaleDateString('en-US', {
                        weekday: 'short',
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric',
                    });
                };

                const initLeaveCalendarWidget = (root) => {
                    if (!root || root.dataset.initialized === 'true') {
                        return;
                    }

                    root.dataset.initialized = 'true';

                    const approvedByDate = parseJson(
                        root.querySelector('script[data-approved-by-date]')?.textContent,
                        {}
                    );
                    const holidaysByDate = parseJson(
                        root.querySelector('script[data-holidays-by-date]')?.textContent,
                        {}
                    );
                    const holidayDateItems = parseJson(
                        root.querySelector('script[data-holiday-dates]')?.textContent,
                        []
                    );
                    const holidayDates = new Set([
                        ...holidayDateItems,
                        ...Object.keys(holidaysByDate || {}),
                    ]);
                    const emptyMessage = root.dataset.emptyMessage || 'Tidak ada leave dan tidak ada holiday pada tanggal ini.';

                    const monthYear = root.querySelector('[data-month-year]');
                    const datesGrid = root.querySelector('[data-dates-grid]');
                    const prevMonthBtn = root.querySelector('[data-prev-month]');
                    const nextMonthBtn = root.querySelector('[data-next-month]');

                    const modal = root.querySelector('[data-modal]');
                    const modalContent = root.querySelector('[data-modal-content]');
                    const selectedDateLabel = root.querySelector('[data-selected-date-label]');
                    const prevPageBtn = root.querySelector('[data-prev-page]');
                    const nextPageBtn = root.querySelector('[data-next-page]');
                    const closeModalBtn = root.querySelector('[data-close-modal]');
                    const paginationMeta = root.querySelector('[data-pagination-meta]');
                    const currentPageInfo = root.querySelector('[data-current-page-info]');
                    const holidaySection = root.querySelector('[data-holiday-section]');
                    const holidayList = root.querySelector('[data-holiday-list]');
                    const leaveSection = root.querySelector('[data-leave-section]');
                    const modalPages = root.querySelector('[data-modal-pages]');
                    const emptyState = root.querySelector('[data-empty-state]');
                    const emptyText = root.querySelector('[data-empty-text]');

                    if (!monthYear || !datesGrid || !prevMonthBtn || !nextMonthBtn || !modal || !modalContent ||
                        !selectedDateLabel || !prevPageBtn || !nextPageBtn || !closeModalBtn || !paginationMeta ||
                        !currentPageInfo || !holidaySection || !holidayList || !leaveSection || !modalPages || !emptyState ||
                        !emptyText) {
                        return;
                    }

                    const itemsPerPage = 6;
                    let currentPage = 1;
                    let totalPages = 1;

                    const today = new Date();
                    let currentMonth = today.getMonth();
                    let currentYear = today.getFullYear();

                    const setPaginationVisibility = (visible) => {
                        paginationMeta.classList.toggle('hidden', !visible);
                        prevPageBtn.classList.toggle('hidden', !visible);
                        nextPageBtn.classList.toggle('hidden', !visible);
                    };

                    const updatePagination = () => {
                        currentPageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
                        prevPageBtn.disabled = currentPage <= 1;
                        nextPageBtn.disabled = currentPage >= totalPages;
                        modalPages.style.transform = `translateX(-${(currentPage - 1) * 100}%)`;
                    };

                    const renderHolidayContent = (holidayItems) => {
                        holidayList.innerHTML = '';

                        if (!Array.isArray(holidayItems) || holidayItems.length === 0) {
                            holidaySection.classList.add('hidden');
                            return false;
                        }

                        holidaySection.classList.remove('hidden');

                        holidayItems.forEach((holiday) => {
                            const name = escapeHtml((holiday?.name || '').trim() || 'Holiday');
                            const startFrom = escapeHtml(holiday?.start_from || '-');
                            const endAt = escapeHtml(holiday?.end_at || '-');
                            const rangeLabel = startFrom === endAt ? startFrom : `${startFrom} s/d ${endAt}`;

                            const item = document.createElement('div');
                            item.className = 'flex items-start justify-between gap-3 p-3 border border-red-100 rounded-lg bg-red-50/50';
                            item.innerHTML = `
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-red-700 truncate">${name}</p>
                                    <p class="mt-1 text-xs text-red-600">Range: ${rangeLabel}</p>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-semibold text-red-700 bg-red-100 rounded-full">
                                    Holiday
                                </span>
                            `;
                            holidayList.appendChild(item);
                        });

                        return true;
                    };

                    const renderLeaveContent = (employees) => {
                        modalPages.innerHTML = '';
                        currentPage = 1;

                        if (!Array.isArray(employees) || employees.length === 0) {
                            totalPages = 1;
                            leaveSection.classList.add('hidden');
                            setPaginationVisibility(false);
                            return false;
                        }

                        leaveSection.classList.remove('hidden');
                        totalPages = Math.ceil(employees.length / itemsPerPage);
                        setPaginationVisibility(true);

                        for (let pageIndex = 0; pageIndex < totalPages; pageIndex++) {
                            const page = document.createElement('div');
                            page.className = 'w-full flex-shrink-0 grid grid-cols-1 gap-3 sm:grid-cols-2';

                            const start = pageIndex * itemsPerPage;
                            const end = Math.min(start + itemsPerPage, employees.length);

                            for (let i = start; i < end; i++) {
                                const employee = employees[i] || {};
                                const nameRaw = employee.employee || '-';
                                const emailRaw = employee.email || '-';
                                const name = escapeHtml(nameRaw);
                                const email = escapeHtml(emailRaw);
                                const profile = employee.url_profile;
                                const firstLetter = escapeHtml(String(nameRaw).trim().charAt(0).toUpperCase() || '?');

                                const card = document.createElement('div');
                                card.className = 'flex items-center gap-3 p-3 bg-white border border-blue-100 rounded-xl';
                                card.innerHTML = `
                                    ${profile
                                        ? `<img src="${escapeHtml(profile)}" alt="${name}" class="object-cover border border-gray-200 rounded-full w-9 h-9 shrink-0">`
                                        : `<div class="flex items-center justify-center text-xs font-semibold text-blue-600 border border-blue-100 rounded-full bg-blue-50 w-9 h-9 shrink-0">${firstLetter}</div>`
                                    }
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate">${name}</p>
                                        <p class="text-xs text-gray-400 truncate">${email}</p>
                                    </div>
                                `;
                                page.appendChild(card);
                            }

                            modalPages.appendChild(page);
                        }

                        updatePagination();
                        return true;
                    };

                    const openModal = (dateStr) => {
                        selectedDateLabel.textContent = formatDisplayDate(dateStr);

                        const holidays = Array.isArray(holidaysByDate?.[dateStr]) ? holidaysByDate[dateStr] : [];
                        const employees = Array.isArray(approvedByDate?.[dateStr]) ? approvedByDate[dateStr] : [];

                        const hasHoliday = renderHolidayContent(holidays);
                        const hasLeave = renderLeaveContent(employees);
                        const shouldShowEmptyState = !hasHoliday && !hasLeave;

                        emptyState.classList.toggle('hidden', !shouldShowEmptyState);
                        emptyText.textContent = emptyMessage;

                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                        modal.setAttribute('aria-hidden', 'false');

                        void modalContent.offsetWidth;
                        modalContent.classList.remove('scale-95', 'opacity-0');
                        modalContent.classList.add('scale-100', 'opacity-100');
                        document.body.style.overflow = 'hidden';
                    };

                    const closeModal = () => {
                        modalContent.classList.add('scale-95', 'opacity-0');
                        modalContent.classList.remove('scale-100', 'opacity-100');

                        setTimeout(() => {
                            modal.classList.add('hidden');
                            modal.classList.remove('flex');
                            modal.setAttribute('aria-hidden', 'true');
                            document.body.style.overflow = 'auto';
                        }, 180);
                    };

                    const createDot = (className) => {
                        const dot = document.createElement('span');
                        dot.className = `w-1.5 h-1.5 rounded-full ${className}`;
                        return dot;
                    };

                    const renderCalendar = (month, year) => {
                        datesGrid.innerHTML = '';
                        monthYear.textContent = `${monthNames[month]} ${year}`;

                        const firstDay = new Date(year, month, 1).getDay();
                        const daysInMonth = new Date(year, month + 1, 0).getDate();

                        for (let i = 0; i < firstDay; i++) {
                            const emptyCell = document.createElement('div');
                            datesGrid.appendChild(emptyCell);
                        }

                        for (let day = 1; day <= daysInMonth; day++) {
                            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                            const isToday = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();
                            const hasApproved = Array.isArray(approvedByDate[dateStr]) && approvedByDate[dateStr].length > 0;
                            const isHoliday = holidayDates.has(dateStr);

                            const dayButton = document.createElement('button');
                            dayButton.type = 'button';
                            dayButton.className = `relative flex items-center justify-center mx-auto my-0.5 w-8 h-8 rounded-lg text-xs cursor-pointer transition-all ${
                                isToday
                                    ? 'bg-blue-600 text-white font-bold shadow-sm'
                                    : 'text-gray-700 hover:bg-gray-100'
                            }`;
                            dayButton.setAttribute('aria-label', `Open calendar detail for ${dateStr}`);
                            dayButton.addEventListener('click', () => openModal(dateStr));

                            const dayText = document.createElement('span');
                            dayText.textContent = String(day);
                            dayButton.appendChild(dayText);

                            if (hasApproved || isHoliday) {
                                const dotContainer = document.createElement('span');
                                dotContainer.className = 'absolute top-0.5 right-0.5 flex items-center gap-0.5';

                                if (hasApproved) {
                                    dotContainer.appendChild(createDot('bg-blue-600'));
                                }
                                if (isHoliday) {
                                    dotContainer.appendChild(createDot('bg-red-500'));
                                }

                                dayButton.appendChild(dotContainer);
                            }

                            datesGrid.appendChild(dayButton);
                        }
                    };

                    prevMonthBtn.addEventListener('click', () => {
                        currentMonth--;
                        if (currentMonth < 0) {
                            currentMonth = 11;
                            currentYear--;
                        }
                        renderCalendar(currentMonth, currentYear);
                    });

                    nextMonthBtn.addEventListener('click', () => {
                        currentMonth++;
                        if (currentMonth > 11) {
                            currentMonth = 0;
                            currentYear++;
                        }
                        renderCalendar(currentMonth, currentYear);
                    });

                    prevPageBtn.addEventListener('click', () => {
                        if (currentPage > 1) {
                            currentPage--;
                            updatePagination();
                        }
                    });

                    nextPageBtn.addEventListener('click', () => {
                        if (currentPage < totalPages) {
                            currentPage++;
                            updatePagination();
                        }
                    });

                    closeModalBtn.addEventListener('click', closeModal);
                    modal.addEventListener('click', (event) => {
                        if (event.target === modal) {
                            closeModal();
                        }
                    });

                    document.addEventListener('keydown', (event) => {
                        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                            closeModal();
                        }
                    });

                    renderCalendar(currentMonth, currentYear);
                };

                document.addEventListener('DOMContentLoaded', () => {
                    document.querySelectorAll('[data-leave-calendar-widget]').forEach(initLeaveCalendarWidget);
                });
            })();
        </script>
    @endpush
@endonce
