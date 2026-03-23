@props(['cutiPerTanggal' => []])

<!-- Cuti Modal (Paginated) -->
<div id="cutiModal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/50 backdrop-blur-sm">
    <div class="bg-white p-6 rounded-2xl w-[95%] max-w-2xl shadow-lg transform transition-all scale-95 opacity-0"
        id="cutiModalContent">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-800">List of Employee on Leave</h2>
            <button onclick="window.closeCutiModal && window.closeCutiModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Navigation and Info -->
        <div class="flex items-center justify-between mb-4">
            <span id="currentPageInfo" class="text-sm text-gray-600">Page 1 of 1</span>
            <div class="flex gap-2">
                <button id="prevPage"
                    class="px-3 py-1 text-sm bg-gray-100 rounded-lg hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button id="nextPage"
                    class="px-3 py-1 text-sm bg-gray-100 rounded-lg hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- Container for employees grid pages -->
        <div id="cutiContainer" class="overflow-hidden">
            <div id="cutiPages" class="flex transition-transform duration-300 ease-in-out">
                <!-- Filled by JS -->
            </div>
        </div>
    </div>
    
</div>

@push('scripts')
<script>
    ;(function() {
        // Provide dataset globally for calendar markers and modal
        window.cutiPerTanggal = window.cutiPerTanggal || @json($cutiPerTanggal);

        // Pagination state
        let currentPage = 1;
        let totalPages = 1;
        const itemsPerPage = 6;

        function updatePaginationInfo() {
            const currentPageInfo = document.getElementById('currentPageInfo');
            const prevPageBtn = document.getElementById('prevPage');
            const nextPageBtn = document.getElementById('nextPage');
            const cutiPages = document.getElementById('cutiPages');

            if (!currentPageInfo || !prevPageBtn || !nextPageBtn || !cutiPages) return;

            currentPageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
            prevPageBtn.disabled = currentPage === 1 || totalPages <= 1;
            nextPageBtn.disabled = currentPage === totalPages || totalPages <= 1;
            cutiPages.style.transform = `translateX(-${(currentPage - 1) * 100}%)`;
        }

        function showCutiForDate(dateStr) {
            const modal = document.getElementById('cutiModal');
            const modalContent = document.getElementById('cutiModalContent');
            const cutiPages = document.getElementById('cutiPages');
            const currentPageInfo = document.getElementById('currentPageInfo');
            const prevPageBtn = document.getElementById('prevPage');
            const nextPageBtn = document.getElementById('nextPage');

            if (!modal || !modalContent || !cutiPages || !currentPageInfo || !prevPageBtn || !nextPageBtn) return;

            cutiPages.innerHTML = "";
            currentPage = 1;

            const data = (window.cutiPerTanggal || {});
            if (data[dateStr] && Array.isArray(data[dateStr]) && data[dateStr].length) {
                const employees = data[dateStr];
                totalPages = Math.ceil(employees.length / itemsPerPage);

                for (let page = 0; page < totalPages; page++) {
                    const pageContainer = document.createElement('div');
                    pageContainer.className = 'w-full flex-shrink-0 grid grid-cols-2 gap-3';

                    const startIndex = page * itemsPerPage;
                    const endIndex = Math.min(startIndex + itemsPerPage, employees.length);

                    for (let i = startIndex; i < endIndex; i++) {
                        const cuti = employees[i];
                        const firstLetter = cuti && cuti.employee ? String(cuti.employee).substring(0, 1).toUpperCase() : "?";
                        pageContainer.innerHTML += `
                            <div class="flex items-center gap-2 p-3 rounded-lg bg-gray-50">
                                ${cuti && cuti.url_profile ? `
                                    <img class="flex items-center justify-center object-cover w-10 h-10 rounded-full"
                                         src="${cuti.url_profile}" alt="${cuti.employee}">
                                ` : `
                                    <span class="flex items-center justify-center w-10 h-10 text-xs text-blue-600 bg-blue-100 rounded-full">
                                        ${firstLetter}
                                    </span>
                                `}
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 truncate">${cuti && cuti.employee ? cuti.employee : '-'}</p>
                                    <p class="text-xs text-gray-500 truncate">${cuti && cuti.email ? cuti.email : '-'}</p>
                                </div>
                            </div>
                        `;
                    }

                    cutiPages.appendChild(pageContainer);
                }

                updatePaginationInfo();
            } else {
                totalPages = 1;
                cutiPages.innerHTML = `
                    <div class="w-full py-8 text-center text-gray-600">
                        Tidak ada karyawan yang cuti pada tanggal ini
                    </div>
                `;
                currentPageInfo.textContent = "Page 1 of 1";
                prevPageBtn.disabled = true;
                nextPageBtn.disabled = true;
            }

            modal.classList.remove("hidden");
            setTimeout(() => {
                modalContent.classList.remove("scale-95", "opacity-0");
                modalContent.classList.add("scale-100", "opacity-100");
            }, 10);
        }

        function closeModal() {
            const modal = document.getElementById('cutiModal');
            const modalContent = document.getElementById('cutiModalContent');
            if (!modal || !modalContent) return;
            modalContent.classList.add("scale-95", "opacity-0");
            modalContent.classList.remove("scale-100", "opacity-100");
            setTimeout(() => modal.classList.add("hidden"), 150);
        }

        // Attach pagination controls
        document.addEventListener('DOMContentLoaded', function () {
            const prevPageBtn = document.getElementById('prevPage');
            const nextPageBtn = document.getElementById('nextPage');
            if (prevPageBtn) {
                prevPageBtn.addEventListener('click', function() {
                    if (currentPage > 1) {
                        currentPage--;
                        updatePaginationInfo();
                    }
                });
            }
            if (nextPageBtn) {
                nextPageBtn.addEventListener('click', function() {
                    if (currentPage < totalPages) {
                        currentPage++;
                        updatePaginationInfo();
                    }
                });
            }
        });

        // Expose as globals for existing calendars
        window.showCutiModal = showCutiForDate;
        window.closeCutiModal = closeModal;
        window.showEvent = window.showCutiModal; // backward-compat for onclick="showEvent(...)"
    })();
</script>
@endpush

