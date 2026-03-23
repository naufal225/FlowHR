// // Import Excel Modal Functionality
// const importExcelBtn = document.getElementById("importExcelBtn");
// const importExcelModal = document.getElementById("importExcelModal");
// const closeImportModal = document.getElementById("closeImportModal");
// const cancelImportBtn = document.getElementById("cancelImportBtn");

// function openImportModal() {
//     importExcelModal.classList.remove("hidden");
//     document.body.classList.add("overflow-hidden");
// }

// function closeImportModalFunc() {
//     importExcelModal.classList.add("hidden");
//     document.body.classList.remove("overflow-hidden");
//     resetFileUpload();
// }

// importExcelBtn.addEventListener("click", openImportModal);
// closeImportModal.addEventListener("click", closeImportModalFunc);
// cancelImportBtn.addEventListener("click", closeImportModalFunc);

// // Close modal when clicking outside
// importExcelModal.addEventListener("click", function (e) {
//     if (e.target === importExcelModal) {
//         closeImportModalFunc();
//     }
// });

// // Enhanced Drag & Drop Functionality
// const dropZone = document.getElementById("dropZone");
// const fileInput = document.getElementById("excel-file");
// const defaultState = document.getElementById("defaultState");
// const dragOverState = document.getElementById("dragOverState");
// const selectedFileDiv = document.getElementById("selected-file");
// const fileNameSpan = document.getElementById("file-name");
// const fileSizeSpan = document.getElementById("file-size");
// const removeFileBtn = document.getElementById("remove-file");
// const errorMessage = document.getElementById("error-message");
// const errorText = document.getElementById("error-text");
// const importBtn = document.getElementById("importBtn");
// const importBtnText = document.getElementById("importBtnText");
// const importBtnSpinner = document.getElementById("importBtnSpinner");

// let dragCounter = 0;

// // Prevent default drag behaviors
// ["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
//     dropZone.addEventListener(eventName, preventDefaults, false);
//     document.body.addEventListener(eventName, preventDefaults, false);
// });

// function preventDefaults(e) {
//     e.preventDefault();
//     e.stopPropagation();
// }

// // Highlight drop zone when item is dragged over it
// ["dragenter", "dragover"].forEach((eventName) => {
//     dropZone.addEventListener(eventName, highlight, false);
// });

// ["dragleave", "drop"].forEach((eventName) => {
//     dropZone.addEventListener(eventName, unhighlight, false);
// });

// function highlight(e) {
//     dragCounter++;
//     dropZone.classList.add("border-amber-400", "bg-amber-50");
//     defaultState.classList.add("hidden");
//     dragOverState.classList.remove("hidden");
// }

// function unhighlight(e) {
//     dragCounter--;
//     if (dragCounter === 0) {
//         dropZone.classList.remove("border-amber-400", "bg-amber-50");
//         defaultState.classList.remove("hidden");
//         dragOverState.classList.add("hidden");
//     }
// }

// // Handle dropped files
// dropZone.addEventListener("drop", handleDrop, false);

// function handleDrop(e) {
//     const dt = e.dataTransfer;
//     const files = dt.files;

//     if (files.length > 0) {
//         handleFile(files[0]);
//     }
// }

// // Handle file input change
// fileInput.addEventListener("change", function (e) {
//     if (e.target.files.length > 0) {
//         handleFile(e.target.files[0]);
//     }
// });

// function handleFile(file) {
//     hideError();

//     // Validate file type
//     const allowedTypes = [
//         "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", // .xlsx
//         "application/vnd.ms-excel", // .xls
//     ];

//     if (
//         !allowedTypes.includes(file.type) &&
//         !file.name.match(/\.(xlsx|xls)$/i)
//     ) {
//         showError("Please select a valid Excel file (.xlsx or .xls)");
//         return;
//     }

//     // Validate file size (10MB limit)
//     const maxSize = 10 * 1024 * 1024; // 10MB in bytes
//     if (file.size > maxSize) {
//         showError("File size must be less than 10MB");
//         return;
//     }

//     // Display selected file
//     fileNameSpan.textContent = file.name;
//     fileSizeSpan.textContent = formatFileSize(file.size);
//     selectedFileDiv.classList.remove("hidden");
//     importBtn.disabled = false;

//     // Update drop zone appearance
//     dropZone.classList.add("border-green-400", "bg-green-50");
//     defaultState.classList.add("hidden");

//     // Create a simple success state
//     defaultState.innerHTML = `
//                 <div class="space-y-4">
//                     <div class="flex justify-center">
//                         <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
//                             <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
//                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
//                             </svg>
//                         </div>
//                     </div>
//                     <div>
//                         <p class="text-lg font-medium text-green-700">File ready to upload</p>
//                         <p class="text-sm text-green-600">Click Import Data to proceed</p>
//                     </div>
//                 </div>
//             `;
//     defaultState.classList.remove("hidden");
// }

// function formatFileSize(bytes) {
//     if (bytes === 0) return "0 Bytes";
//     const k = 1024;
//     const sizes = ["Bytes", "KB", "MB", "GB"];
//     const i = Math.floor(Math.log(bytes) / Math.log(k));
//     return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
// }

// function showError(message) {
//     errorText.textContent = message;
//     errorMessage.classList.remove("hidden");
//     selectedFileDiv.classList.add("hidden");
//     importBtn.disabled = true;
// }

// function hideError() {
//     errorMessage.classList.add("hidden");
// }

// function resetFileUpload() {
//     fileInput.value = "";
//     selectedFileDiv.classList.add("hidden");
//     errorMessage.classList.add("hidden");
//     importBtn.disabled = true;
//     dragCounter = 0;

//     // Reset drop zone appearance
//     dropZone.classList.remove(
//         "border-amber-400",
//         "bg-amber-50",
//         "border-green-400",
//         "bg-green-50"
//     );
//     defaultState.classList.remove("hidden");
//     dragOverState.classList.add("hidden");

//     // Reset default state content
//     defaultState.innerHTML = `
//                 <div class="space-y-4">
//                     <div class="flex justify-center">
//                         <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center group-hover:bg-amber-200 transition-colors">
//                             <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
//                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
//                             </svg>
//                         </div>
//                     </div>
//                     <div>
//                         <p class="text-lg font-medium text-gray-900">Drop your Excel file here</p>
//                         <p class="text-sm text-gray-500 mt-1">or click to browse files</p>
//                     </div>
//                     <div class="flex items-center justify-center space-x-2 text-xs text-gray-400">
//                         <span>Supported formats:</span>
//                         <span class="px-2 py-1 bg-gray-100 rounded text-gray-600 font-medium">.xlsx</span>
//                         <span class="px-2 py-1 bg-gray-100 rounded text-gray-600 font-medium">.xls</span>
//                     </div>
//                 </div>
//             `;
// }

// // Remove file functionality
// removeFileBtn.addEventListener("click", function () {
//     resetFileUpload();
// });

// // Form submission with loading state
// document.querySelector("form").addEventListener("submit", function () {
//     importBtn.disabled = true;
//     importBtnText.textContent = "Importing...";
//     importBtnSpinner.classList.remove("hidden");
// });

// Handle window resize
window.addEventListener("resize", function () {
    if (window.innerWidth >= 1024) {
        sidebarOverlay.classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
    }
});
// Delete confirmation functionality
let userIdToDelete = null;

// Initialize delete functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeDeleteFunctionality();
});

function initializeDeleteFunctionality() {
    // Add event listeners to all delete buttons
    const deleteButtons = document.querySelectorAll('.delete-user-btn');
    const cancelButtons = document.querySelectorAll('#cancelDeleteButton');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-user-name');
            confirmDelete(userId, userName);
        });
    });

    // Add event listener for modal close button
    const closeModalBtn = document.querySelector('#deleteConfirmModal .bg-gray-500');
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeDeleteModal);
    }

    // Add event listener for confirm delete button
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', executeDelete);
    }

    // Add event listener for cancel buttons
    cancelButtons.forEach(button => {
        button.addEventListener('click', closeDeleteModal);
    });
}

function confirmDelete(userId, userName) {
    userIdToDelete = userId;
    document.getElementById('userName').textContent = userName;
    document.getElementById('deleteConfirmModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closeDeleteModal() {
    userIdToDelete = null;
    document.getElementById('deleteConfirmModal').classList.add('hidden');
    document.body.style.overflow = 'auto'; // Restore scrolling
}

function executeDelete() {
    if (!userIdToDelete) return;

    // Show loading state
    const deleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteText = document.getElementById('deleteButtonText');
    const deleteSpinner = document.getElementById('deleteSpinner');

    const cancelButtons = document.querySelectorAll('#cancelDeleteButton');
    cancelButtons.forEach(btn => {
        btn.disabled = true;
    });

    deleteBtn.disabled = true;
    deleteText.textContent = 'Deleting...';
    deleteSpinner.classList.remove('hidden');

    // Submit the form
    document.getElementById(`delete-form-${userIdToDelete}`).submit();
}

// Close modal when pressing Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeDeleteModal();
    }
});
